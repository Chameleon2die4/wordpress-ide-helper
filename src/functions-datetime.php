<?php /** @noinspection GrazieInspection */
/** @noinspection RegExpSimplifiable */
/** @noinspection PhpUnusedLocalVariableInspection */
/** @noinspection SpellCheckingInspection */
/** @noinspection PhpUnused */
/** @noinspection PhpUndefinedConstantInspection */
/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocMissingThrowsInspection */

/**
 * Convert given MySQL date string into a different format.
 *
 *  - `$format` should be a PHP date format string.
 *  - 'U' and 'G' formats will return an integer sum of timestamp with timezone offset.
 *  - `$date` is expected to be local time in MySQL format (`Y-m-d H:i:s`).
 *
 * Historically UTC time could be passed to the function to produce Unix timestamp.
 *
 * If `$translate` is true then the given date and format string will
 * be passed to `wp_date()` for translation.
 *
 * @since 0.71
 *
 * @param string $format    Format of the date to return.
 * @param string $date      Date string to convert.
 * @param bool   $translate Whether the return date should be translated. Default true.
 * @return string|int|false Integer if `$format` is 'U' or 'G', string otherwise.
 *                          False on failure.
 */
function mysql2date( $format, $date, $translate = true ) {
    if ( empty( $date ) ) {
        return false;
    }

    $datetime = date_create( $date, wp_timezone() );

    if ( false === $datetime ) {
        return false;
    }

    // Returns a sum of timestamp with timezone offset. Ideally should never be used.
    if ( 'G' === $format || 'U' === $format ) {
        return $datetime->getTimestamp() + $datetime->getOffset();
    }

    if ( $translate ) {
        return wp_date( $format, $datetime->getTimestamp() );
    }

    return $datetime->format( $format );
}

/**
 * Retrieves the current time based on specified type.
 *
 *  - The 'mysql' type will return the time in the format for MySQL DATETIME field.
 *  - The 'timestamp' or 'U' types will return the current timestamp or a sum of timestamp
 *    and timezone offset, depending on `$gmt`.
 *  - Other strings will be interpreted as PHP date formats (e.g. 'Y-m-d').
 *
 * If `$gmt` is a truthy value then both types will use GMT time, otherwise the
 * output is adjusted with the GMT offset for the site.
 *
 * @since 1.0.0
 * @since 5.3.0 Now returns an integer if `$type` is 'U'. Previously a string was returned.
 *
 * @param string   $type Type of time to retrieve. Accepts 'mysql', 'timestamp', 'U',
 *                       or PHP date format string (e.g. 'Y-m-d').
 * @param int|bool $gmt  Optional. Whether to use GMT timezone. Default false.
 * @return int|string Integer if `$type` is 'timestamp' or 'U', string otherwise.
 */
function current_time( $type, $gmt = 0 ) {
    // Don't use non-GMT timestamp, unless you know the difference and really need to.
    if ( 'timestamp' === $type || 'U' === $type ) {
        return $gmt ? time() : time() + (int) ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS );
    }

    if ( 'mysql' === $type ) {
        $type = 'Y-m-d H:i:s';
    }

    $timezone = $gmt ? new DateTimeZone( 'UTC' ) : wp_timezone();
    $datetime = new DateTime( 'now', $timezone );

    return $datetime->format( $type );
}

/**
 * Retrieves the current time as an object using the site's timezone.
 *
 * @since 5.3.0
 *
 * @return DateTimeImmutable Date and time object.
 */
function current_datetime() {
    return new DateTimeImmutable( 'now', wp_timezone() );
}

/**
 * Retrieves the timezone of the site as a string.
 *
 * Uses the `timezone_string` option to get a proper timezone name if available,
 * otherwise falls back to a manual UTC ± offset.
 *
 * Example return values:
 *
 *  - 'Europe/Rome'
 *  - 'America/North_Dakota/New_Salem'
 *  - 'UTC'
 *  - '-06:30'
 *  - '+00:00'
 *  - '+08:45'
 *
 * @since 5.3.0
 *
 * @return string PHP timezone name or a ±HH:MM offset.
 */
function wp_timezone_string() {
    $timezone_string = get_option( 'timezone_string' );

    if ( $timezone_string ) {
        return $timezone_string;
    }

    $offset  = (float) get_option( 'gmt_offset' );
    $hours   = (int) $offset;
    $minutes = ( $offset - $hours );

    $sign      = ( $offset < 0 ) ? '-' : '+';
    $abs_hour  = abs( $hours );
    $abs_mins  = abs( $minutes * 60 );
    return sprintf( '%s%02d:%02d', $sign, $abs_hour, $abs_mins );
}

/**
 * Retrieves the timezone of the site as a `DateTimeZone` object.
 *
 * Timezone can be based on a PHP timezone string or a ±HH:MM offset.
 *
 * @since 5.3.0
 *
 * @return DateTimeZone Timezone object.
 */
function wp_timezone() {
    return new DateTimeZone( wp_timezone_string() );
}

/**
 * Retrieves the date in localized format, based on a sum of Unix timestamp and
 * timezone offset in seconds.
 *
 * If the locale specifies the locale month and weekday, then the locale will
 * take over the format for the date. If it isn't, then the date format string
 * will be used instead.
 *
 * Note that due to the way WP typically generates a sum of timestamp and offset
 * with `strtotime()`, it implies offset added at a _current_ time, not at the time
 * the timestamp represents. Storing such timestamps or calculating them differently
 * will lead to invalid output.
 *
 * @since 0.71
 * @since 5.3.0 Converted into a wrapper for wp_date().
 *
 * @global WP_Locale $wp_locale WordPress date and time locale object.
 *
 * @param string   $format                Format to display the date.
 * @param int|bool $timestamp_with_offset Optional. A sum of Unix timestamp and timezone offset
 *                                        in seconds. Default false.
 * @param bool     $gmt                   Optional. Whether to use GMT timezone. Only applies
 *                                        if timestamp is not provided. Default false.
 * @return string The date, translated if locale specifies it.
 */
function date_i18n( $format, $timestamp_with_offset = false, $gmt = false ) {
    $timestamp = $timestamp_with_offset;

    // If timestamp is omitted it should be current time (summed with offset, unless `$gmt` is true).
    if ( ! is_numeric( $timestamp ) ) {
        // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested
        $timestamp = current_time( 'timestamp', $gmt );
    }

    /*
	 * This is a legacy implementation quirk that the returned timestamp is also with offset.
	 * Ideally this function should never be used to produce a timestamp.
	 */
    if ( 'U' === $format ) {
        $date = $timestamp;
    } elseif ( $gmt && false === $timestamp_with_offset ) { // Current time in UTC.
        $date = wp_date( $format, null, new DateTimeZone( 'UTC' ) );
    } elseif ( false === $timestamp_with_offset ) { // Current time in site's timezone.
        $date = wp_date( $format );
    } else {
        /*
		 * Timestamp with offset is typically produced by a UTC `strtotime()` call on an input without timezone.
		 * This is the best attempt to reverse that operation into a local time to use.
		 */
        $local_time = gmdate( 'Y-m-d H:i:s', $timestamp );
        $timezone   = wp_timezone();
        $datetime   = date_create( $local_time, $timezone );
        $date       = wp_date( $format, $datetime->getTimestamp(), $timezone );
    }

    /**
     * Filters the date formatted based on the locale.
     *
     * @since 2.8.0
     *
     * @param string $date      Formatted date string.
     * @param string $format    Format to display the date.
     * @param int    $timestamp A sum of Unix timestamp and timezone offset in seconds.
     *                          Might be without offset if input omitted timestamp but requested GMT.
     * @param bool   $gmt       Whether to use GMT timezone. Only applies if timestamp was not provided.
     *                          Default false.
     */
    return apply_filters( 'date_i18n', $date, $format, $timestamp, $gmt );
}

/**
 * Retrieves the date, in localized format.
 *
 * This is a newer function, intended to replace `date_i18n()` without legacy quirks in it.
 *
 * Note that, unlike `date_i18n()`, this function accepts a true Unix timestamp, not summed
 * with timezone offset.
 *
 * @since 5.3.0
 *
 * @global WP_Locale $wp_locale WordPress date and time locale object.
 *
 * @param string       $format    PHP date format.
 * @param int          $timestamp Optional. Unix timestamp. Defaults to current time.
 * @param DateTimeZone $timezone  Optional. Timezone to output result in. Defaults to timezone
 *                                from site settings.
 * @return string|false The date, translated if locale specifies it. False on invalid timestamp input.
 */
function wp_date( $format, $timestamp = null, $timezone = null ) {
    global $wp_locale;

    if ( null === $timestamp ) {
        $timestamp = time();
    } elseif ( ! is_numeric( $timestamp ) ) {
        return false;
    }

    if ( ! $timezone ) {
        $timezone = wp_timezone();
    }

    $datetime = date_create( '@' . $timestamp );
    $datetime->setTimezone( $timezone );

    if ( empty( $wp_locale->month ) || empty( $wp_locale->weekday ) ) {
        $date = $datetime->format( $format );
    } else {
        // We need to unpack shorthand `r` format because it has parts that might be localized.
        $format = preg_replace( '/(?<!\\\\)r/', DATE_RFC2822, $format );

        $new_format    = '';
        $format_length = strlen( $format );
        $month         = $wp_locale->get_month( $datetime->format( 'm' ) );
        $weekday       = $wp_locale->get_weekday( $datetime->format( 'w' ) );

        for ( $i = 0; $i < $format_length; $i ++ ) {
            switch ( $format[ $i ] ) {
                case 'D':
                    $new_format .= addcslashes( $wp_locale->get_weekday_abbrev( $weekday ), '\\A..Za..z' );
                    break;
                case 'F':
                    $new_format .= addcslashes( $month, '\\A..Za..z' );
                    break;
                case 'l':
                    $new_format .= addcslashes( $weekday, '\\A..Za..z' );
                    break;
                case 'M':
                    $new_format .= addcslashes( $wp_locale->get_month_abbrev( $month ), '\\A..Za..z' );
                    break;
                case 'a':
                    $new_format .= addcslashes( $wp_locale->get_meridiem( $datetime->format( 'a' ) ), '\\A..Za..z' );
                    break;
                case 'A':
                    $new_format .= addcslashes( $wp_locale->get_meridiem( $datetime->format( 'A' ) ), '\\A..Za..z' );
                    break;
                case '\\':
                    $new_format .= $format[ $i ];

                    // If character follows a slash, we add it without translating.
                    /** @noinspection PhpConditionAlreadyCheckedInspection */
                    if ( $i < $format_length ) {
                        $new_format .= $format[ ++$i ];
                    }
                    break;
                default:
                    $new_format .= $format[ $i ];
                    break;
            }
        }

        $date = $datetime->format( $new_format );
        $date = wp_maybe_decline_date( $date, $format );
    }

    /**
     * Filters the date formatted based on the locale.
     *
     * @since 5.3.0
     *
     * @param string       $date      Formatted date string.
     * @param string       $format    Format to display the date.
     * @param int          $timestamp Unix timestamp.
     * @param DateTimeZone $timezone  Timezone.
     */
    return apply_filters( 'wp_date', $date, $format, $timestamp, $timezone );
}

/**
 * Determines if the date should be declined.
 *
 * If the locale specifies that month names require a genitive case in certain
 * formats (like 'j F Y'), the month name will be replaced with a correct form.
 *
 * @since 4.4.0
 * @since 5.4.0 The `$format` parameter was added.
 *
 * @global WP_Locale $wp_locale WordPress date and time locale object.
 *
 * @param string $date   Formatted date string.
 * @param string $format Optional. Date format to check. Default empty string.
 * @return string The date, declined if locale specifies it.
 */
function wp_maybe_decline_date( $date, $format = '' ) {
    global $wp_locale;

    // i18n functions are not available in SHORTINIT mode.
    if ( ! function_exists( '_x' ) ) {
        return $date;
    }

    /*
	 * translators: If months in your language require a genitive case,
	 * translate this to 'on'. Do not translate into your own language.
	 */
    if ( 'on' === _x( 'off', 'decline months names: on or off' ) ) {

        $months          = $wp_locale->month;
        $months_genitive = $wp_locale->month_genitive;

        /*
		 * Match a format like 'j F Y' or 'j. F' (day of the month, followed by month name)
		 * and decline the month.
		 */
        if ( $format ) {
            $decline = preg_match( '#[dj]\.? F#', $format );
        } else {
            // If the format is not passed, try to guess it from the date string.
            $decline = preg_match( '#\b\d{1,2}\.? [^\d ]+\b#u', $date );
        }

        if ( $decline ) {
            foreach ( $months as $key => $month ) {
                $months[ $key ] = '# ' . preg_quote( $month, '#' ) . '\b#u';
            }

            foreach ( $months_genitive as $key => $month ) {
                $months_genitive[ $key ] = ' ' . $month;
            }

            $date = preg_replace( $months, $months_genitive, $date );
        }

        /*
		 * Match a format like 'F jS' or 'F j' (month name, followed by day with an optional ordinal suffix)
		 * and change it to declined 'j F'.
		 */
        if ( $format ) {
            $decline = preg_match( '#F [dj]#', $format );
        } else {
            // If the format is not passed, try to guess it from the date string.
            $decline = preg_match( '#\b[^\d ]+ \d{1,2}(st|nd|rd|th)?\b#u', trim( $date ) );
        }

        if ( $decline ) {
            foreach ( $months as $key => $month ) {
                $months[ $key ] = '#\b' . preg_quote( $month, '#' ) . ' (\d{1,2})(st|nd|rd|th)?([-–]\d{1,2})?(st|nd|rd|th)?\b#u';
            }

            foreach ( $months_genitive as $key => $month ) {
                $months_genitive[ $key ] = '$1$3 ' . $month;
            }

            $date = preg_replace( $months, $months_genitive, $date );
        }
    }

    // Used for locale-specific rules.
    $locale = get_locale();

    if ( 'ca' === $locale ) {
        // " de abril| de agost| de octubre..." -> " d'abril| d'agost| d'octubre..."
        $date = preg_replace( '# de ([ao])#i', " d'\\1", $date );
    }

    return $date;
}

/**
 * Convert float number to format based on the locale.
 *
 * @since 2.3.0
 *
 * @global WP_Locale $wp_locale WordPress date and time locale object.
 *
 * @param float $number   The number to convert based on locale.
 * @param int   $decimals Optional. Precision of the number of decimal places. Default 0.
 * @return string Converted number in string format.
 */
function number_format_i18n( $number, $decimals = 0 ) {
    global $wp_locale;

    if ( isset( $wp_locale ) ) {
        $formatted = number_format( $number, absint( $decimals ), $wp_locale->number_format['decimal_point'], $wp_locale->number_format['thousands_sep'] );
    } else {
        $formatted = number_format( $number, absint( $decimals ) );
    }

    /**
     * Filters the number formatted based on the locale.
     *
     * @since 2.8.0
     * @since 4.9.0 The `$number` and `$decimals` parameters were added.
     *
     * @param string $formatted Converted number in string format.
     * @param float  $number    The number to convert based on locale.
     * @param int    $decimals  Precision of the number of decimal places.
     */
    return apply_filters( 'number_format_i18n', $formatted, $number, $decimals );
}

/**
 * Convert number of bytes largest unit bytes will fit into.
 *
 * It is easier to read 1 KB than 1024 bytes and 1 MB than 1048576 bytes. Converts
 * number of bytes to human readable number by taking the number of that unit
 * that the bytes will go into it. Supports TB value.
 *
 * Please note that integers in PHP are limited to 32 bits, unless they are on
 * 64 bit architecture, then they have 64 bit size. If you need to place the
 * larger size then what PHP integer type will hold, then use a string. It will
 * be converted to a double, which should always have 64 bit length.
 *
 * Technically the correct unit names for powers of 1024 are KiB, MiB etc.
 *
 * @since 2.3.0
 *
 * @param int|string $bytes    Number of bytes. Note max integer size for integers.
 * @param int        $decimals Optional. Precision of number of decimal places. Default 0.
 * @return string|false Number string on success, false on failure.
 */
function size_format( $bytes, $decimals = 0 ) {
    $quant = array(
        /* translators: Unit symbol for terabyte. */
        _x( 'TB', 'unit symbol' ) => TB_IN_BYTES,
        /* translators: Unit symbol for gigabyte. */
        _x( 'GB', 'unit symbol' ) => GB_IN_BYTES,
        /* translators: Unit symbol for megabyte. */
        _x( 'MB', 'unit symbol' ) => MB_IN_BYTES,
        /* translators: Unit symbol for kilobyte. */
        _x( 'KB', 'unit symbol' ) => KB_IN_BYTES,
        /* translators: Unit symbol for byte. */
        _x( 'B', 'unit symbol' )  => 1,
    );

    if ( 0 === $bytes ) {
        /* translators: Unit symbol for byte. */
        return number_format_i18n( 0, $decimals ) . ' ' . _x( 'B', 'unit symbol' );
    }

    foreach ( $quant as $unit => $mag ) {
        if ( (float) $bytes >= $mag ) {
            return number_format_i18n( $bytes / $mag, $decimals ) . ' ' . $unit;
        }
    }

    return false;
}

/**
 * Convert a duration to human readable format.
 *
 * @param string $duration Duration will be in string format (HH:ii:ss) OR (ii:ss),
 *                         with a possible prepended negative sign (-).
 * @return string|false A human readable duration string, false on failure.
 * @noinspection PhpUnusedLocalVariableInspection*@since 5.1.0
 *
 */
function human_readable_duration( $duration = '' ) {
    if ( ( empty( $duration ) || ! is_string( $duration ) ) ) {
        return false;
    }

    $duration = trim( $duration );

    // Remove prepended negative sign.
    if ( '-' === substr( $duration, 0, 1 ) ) {
        $duration = substr( $duration, 1 );
    }

    // Extract duration parts.
    $duration_parts = array_reverse( explode( ':', $duration ) );
    $duration_count = count( $duration_parts );

    $hour   = null;
    $minute = null;
    $second = null;

    if ( 3 === $duration_count ) {
        // Validate HH:ii:ss duration format.
        /** @noinspection PhpUnnecessaryBoolCastInspection */
        if ( ! ( (bool) preg_match( '/^([0-9]+):([0-5]?[0-9]):([0-5]?[0-9])$/', $duration ) ) ) {
            return false;
        }
        // Three parts: hours, minutes & seconds.
        list( $second, $minute, $hour ) = $duration_parts;
    } elseif ( 2 === $duration_count ) {
        // Validate ii:ss duration format.
        /** @noinspection PhpUnnecessaryBoolCastInspection */
        if ( ! ( (bool) preg_match( '/^([0-5]?[0-9]):([0-5]?[0-9])$/', $duration ) ) ) {
            return false;
        }
        // Two parts: minutes & seconds.
        list( $second, $minute ) = $duration_parts;
    } else {
        return false;
    }

    $human_readable_duration = array();

    // Add the hour part to the string.
    if ( is_numeric( $hour ) ) {
        /* translators: %s: Time duration in hour or hours. */
        $human_readable_duration[] = sprintf( _n( '%s hour', '%s hours', $hour ), (int) $hour );
    }

    // Add the minute part to the string.
    if ( is_numeric( $minute ) ) {
        /* translators: %s: Time duration in minute or minutes. */
        $human_readable_duration[] = sprintf( _n( '%s minute', '%s minutes', $minute ), (int) $minute );
    }

    // Add the second part to the string.
    if ( is_numeric( $second ) ) {
        /* translators: %s: Time duration in second or seconds. */
        $human_readable_duration[] = sprintf( _n( '%s second', '%s seconds', $second ), (int) $second );
    }

    return implode( ', ', $human_readable_duration );
}

/**
 * Get the week start and end from the datetime or date string from MySQL.
 *
 * @since 0.71
 *
 * @param string     $mysqlstring   Date or datetime field type from MySQL.
 * @param int|string $start_of_week Optional. Start of the week as an integer. Default empty string.
 * @return int[] {
 *     Week start and end dates as Unix timestamps.
 *
 *     @type int $start The week start date as a Unix timestamp.
 *     @type int $end   The week end date as a Unix timestamp.
 * }
 */
function get_weekstartend( $mysqlstring, $start_of_week = '' ) {
    // MySQL string year.
    $my = substr( $mysqlstring, 0, 4 );

    // MySQL string month.
    $mm = substr( $mysqlstring, 8, 2 );

    // MySQL string day.
    $md = substr( $mysqlstring, 5, 2 );

    // The timestamp for MySQL string day.
    $day = mktime( 0, 0, 0, $md, $mm, $my );

    // The day of the week from the timestamp.
    $weekday = gmdate( 'w', $day );

    if ( ! is_numeric( $start_of_week ) ) {
        $start_of_week = get_option( 'start_of_week' );
    }

    if ( $weekday < $start_of_week ) {
        $weekday += 7;
    }

    // The most recent week start day on or before $day.
    $start = $day - DAY_IN_SECONDS * ( $weekday - $start_of_week );

    // $start + 1 week - 1 second.
    $end = $start + WEEK_IN_SECONDS - 1;
    return compact( 'start', 'end' );
}

/**
 * Determines whether the publish date of the current post in the loop is different
 * from the publish date of the previous post in the loop.
 *
 * For more information on this and similar theme functions, check out
 * the {@link https://developer.wordpress.org/themes/basics/conditional-tags/
 * Conditional Tags} article in the Theme Developer Handbook.
 *
 * @since 0.71
 *
 * @global string $currentday  The day of the current post in the loop.
 * @global string $previousday The day of the previous post in the loop.
 *
 * @return int 1 when new day, 0 if not a new day.
 */
function is_new_day() {
    global $currentday, $previousday;

    if ( $currentday !== $previousday ) {
        return 1;
    } else {
        return 0;
    }
}

/**
 * gmt_offset modification for smart timezone handling.
 *
 * Overrides the gmt_offset option if we have a timezone_string available.
 *
 * @since 2.8.0
 *
 * @return float|false Timezone GMT offset, false otherwise.
 */
function wp_timezone_override_offset() {
    $timezone_string = get_option( 'timezone_string' );
    if ( ! $timezone_string ) {
        return false;
    }

    $timezone_object = timezone_open( $timezone_string );
    $datetime_object = date_create();
    if ( false === $timezone_object || false === $datetime_object ) {
        return false;
    }
    return round( timezone_offset_get( $timezone_object, $datetime_object ) / HOUR_IN_SECONDS, 2 );
}

/**
 * Sort-helper for timezones.
 *
 * @since 2.9.0
 * @access private
 *
 * @param array $a
 * @param array $b
 * @return int
 */
function _wp_timezone_choice_usort_callback( $a, $b ) {
    // Don't use translated versions of Etc.
    if ( 'Etc' === $a['continent'] && 'Etc' === $b['continent'] ) {
        // Make the order of these more like the old dropdown.
        if ( 'GMT+' === substr( $a['city'], 0, 4 ) && 'GMT+' === substr( $b['city'], 0, 4 ) ) {
            return -1 * ( strnatcasecmp( $a['city'], $b['city'] ) );
        }
        if ( 'UTC' === $a['city'] ) {
            if ( 'GMT+' === substr( $b['city'], 0, 4 ) ) {
                return 1;
            }
            return -1;
        }
        if ( 'UTC' === $b['city'] ) {
            if ( 'GMT+' === substr( $a['city'], 0, 4 ) ) {
                return -1;
            }
            return 1;
        }
        return strnatcasecmp( $a['city'], $b['city'] );
    }
    if ( $a['t_continent'] == $b['t_continent'] ) {
        if ( $a['t_city'] == $b['t_city'] ) {
            return strnatcasecmp( $a['t_subcity'], $b['t_subcity'] );
        }
        return strnatcasecmp( $a['t_city'], $b['t_city'] );
    } else {
        // Force Etc to the bottom of the list.
        if ( 'Etc' === $a['continent'] ) {
            return 1;
        }
        if ( 'Etc' === $b['continent'] ) {
            return -1;
        }
        return strnatcasecmp( $a['t_continent'], $b['t_continent'] );
    }
}

/**
 * Gives a nicely-formatted list of timezone strings.
 *
 * @since 2.9.0
 * @since 4.7.0 Added the `$locale` parameter.
 *
 * @param string $selected_zone Selected timezone.
 * @param string $locale        Optional. Locale to load the timezones in. Default current site locale.
 * @return string
 */
function wp_timezone_choice( $selected_zone, $locale = null ) {
    static $mo_loaded = false, $locale_loaded = null;

    $continents = array( 'Africa', 'America', 'Antarctica', 'Arctic', 'Asia', 'Atlantic', 'Australia', 'Europe', 'Indian', 'Pacific' );

    // Load translations for continents and cities.
    if ( ! $mo_loaded || $locale !== $locale_loaded ) {
        $locale_loaded = $locale ? $locale : get_locale();
        $mofile        = WP_LANG_DIR . '/continents-cities-' . $locale_loaded . '.mo';
        unload_textdomain( 'continents-cities' );
        load_textdomain( 'continents-cities', $mofile );
        $mo_loaded = true;
    }

    $zonen = array();
    foreach ( timezone_identifiers_list() as $zone ) {
        $zone = explode( '/', $zone );
        if ( ! in_array( $zone[0], $continents, true ) ) {
            continue;
        }

        // This determines what gets set and translated - we don't translate Etc/* strings here, they are done later.
        $exists    = array(
          0 => ( isset( $zone[0] ) && $zone[0] ),
          1 => ( isset( $zone[1] ) && $zone[1] ),
          2 => ( isset( $zone[2] ) && $zone[2] ),
        );
        $exists[3] = ( $exists[0] && 'Etc' !== $zone[0] );
        $exists[4] = ( $exists[1] && $exists[3] );
        $exists[5] = ( $exists[2] && $exists[3] );

        // phpcs:disable WordPress.WP.I18n.LowLevelTranslationFunction,WordPress.WP.I18n.NonSingularStringLiteralText
        $zonen[] = array(
          'continent'   => ( $exists[0] ? $zone[0] : '' ),
          'city'        => ( $exists[1] ? $zone[1] : '' ),
          'subcity'     => ( $exists[2] ? $zone[2] : '' ),
          't_continent' => ( $exists[3] ? translate( str_replace( '_', ' ', $zone[0] ), 'continents-cities' ) : '' ),
          't_city'      => ( $exists[4] ? translate( str_replace( '_', ' ', $zone[1] ), 'continents-cities' ) : '' ),
          't_subcity'   => ( $exists[5] ? translate( str_replace( '_', ' ', $zone[2] ), 'continents-cities' ) : '' ),
        );
        // phpcs:enable
    }
    usort( $zonen, '_wp_timezone_choice_usort_callback' );

    $structure = array();

    if ( empty( $selected_zone ) ) {
        $structure[] = '<option selected="selected" value="">' . __( 'Select a city' ) . '</option>';
    }

    foreach ( $zonen as $key => $zone ) {
        // Build value in an array to join later.
        $value = array( $zone['continent'] );

        if ( empty( $zone['city'] ) ) {
            // It's at the continent level (generally won't happen).
            $display = $zone['t_continent'];
        } else {
            // It's inside a continent group.

            // Continent optgroup.
            if ( ! isset( $zonen[ $key - 1 ] ) || $zonen[ $key - 1 ]['continent'] !== $zone['continent'] ) {
                $label       = $zone['t_continent'];
                $structure[] = '<optgroup label="' . esc_attr( $label ) . '">';
            }

            // Add the city to the value.
            $value[] = $zone['city'];

            $display = $zone['t_city'];
            if ( ! empty( $zone['subcity'] ) ) {
                // Add the subcity to the value.
                $value[]  = $zone['subcity'];
                $display .= ' - ' . $zone['t_subcity'];
            }
        }

        // Build the value.
        $value    = implode( '/', $value );
        $selected = '';
        if ( $value === $selected_zone ) {
            $selected = 'selected="selected" ';
        }
        $structure[] = '<option ' . $selected . 'value="' . esc_attr( $value ) . '">' . esc_html( $display ) . '</option>';

        // Close continent optgroup.
        if ( ! empty( $zone['city'] ) && ( ! isset( $zonen[ $key + 1 ] ) || ( isset( $zonen[ $key + 1 ] ) && $zonen[ $key + 1 ]['continent'] !== $zone['continent'] ) ) ) {
            $structure[] = '</optgroup>';
        }
    }

    // Do UTC.
    $structure[] = '<optgroup label="' . esc_attr__( 'UTC' ) . '">';
    $selected    = '';
    if ( 'UTC' === $selected_zone ) {
        $selected = 'selected="selected" ';
    }
    $structure[] = '<option ' . $selected . 'value="' . esc_attr( 'UTC' ) . '">' . __( 'UTC' ) . '</option>';
    $structure[] = '</optgroup>';

    // Do manual UTC offsets.
    $structure[]  = '<optgroup label="' . esc_attr__( 'Manual Offsets' ) . '">';
    $offset_range = array(
      -12,
      -11.5,
      -11,
      -10.5,
      -10,
      -9.5,
      -9,
      -8.5,
      -8,
      -7.5,
      -7,
      -6.5,
      -6,
      -5.5,
      -5,
      -4.5,
      -4,
      -3.5,
      -3,
      -2.5,
      -2,
      -1.5,
      -1,
      -0.5,
      0,
      0.5,
      1,
      1.5,
      2,
      2.5,
      3,
      3.5,
      4,
      4.5,
      5,
      5.5,
      5.75,
      6,
      6.5,
      7,
      7.5,
      8,
      8.5,
      8.75,
      9,
      9.5,
      10,
      10.5,
      11,
      11.5,
      12,
      12.75,
      13,
      13.75,
      14,
    );
    foreach ( $offset_range as $offset ) {
        if ( 0 <= $offset ) {
            $offset_name = '+' . $offset;
        } else {
            $offset_name = (string) $offset;
        }

        $offset_value = $offset_name;
        $offset_name  = str_replace( array( '.25', '.5', '.75' ), array( ':15', ':30', ':45' ), $offset_name );
        $offset_name  = 'UTC' . $offset_name;
        $offset_value = 'UTC' . $offset_value;
        $selected     = '';
        if ( $offset_value === $selected_zone ) {
            $selected = 'selected="selected" ';
        }
        $structure[] = '<option ' . $selected . 'value="' . esc_attr( $offset_value ) . '">' . esc_html( $offset_name ) . '</option>';

    }
    $structure[] = '</optgroup>';

    return implode( "\n", $structure );
}

/**
 * Test if the supplied date is valid for the Gregorian calendar.
 *
 * @since 3.5.0
 *
 * @link https://www.php.net/manual/en/function.checkdate.php
 *
 * @param int    $month       Month number.
 * @param int    $day         Day number.
 * @param int    $year        Year number.
 * @param string $source_date The date to filter.
 * @return bool True if valid date, false if not valid date.
 */
function wp_checkdate( $month, $day, $year, $source_date ) {
    /**
     * Filters whether the given date is valid for the Gregorian calendar.
     *
     * @since 3.5.0
     *
     * @param bool   $checkdate   Whether the given date is valid.
     * @param string $source_date Date to check.
     */
    return apply_filters( 'wp_checkdate', checkdate( $month, $day, $year ), $source_date );
}
