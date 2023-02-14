<?php /** @noinspection PhpUnnecessaryCurlyVarSyntaxInspection */
/** @noinspection RegExpSimplifiable */
/** @noinspection PhpMissingParamTypeInspection */
/** @noinspection SpellCheckingInspection */
/** @noinspection PhpUnused */

/**
 * Main WordPress Formatting API.
 *
 * Handles many functions for formatting output.
 *
 * @package WordPress
 */


/**
 * Checks and cleans a URL.
 *
 * A number of characters are removed from the URL. If the URL is for displaying
 * (the default behaviour) ampersands are also replaced. The {@see 'clean_url'} filter
 * is applied to the returned cleaned URL.
 *
 * @since 2.8.0
 *
 * @param string   $url       The URL to be cleaned.
 * @param string[] $protocols Optional. An array of acceptable protocols.
 *                            Defaults to return value of wp_allowed_protocols().
 * @param string   $_context  Private. Use esc_url_raw() for database usage.
 * @return string The cleaned URL after the {@see 'clean_url'} filter is applied.
 *                An empty string is returned if `$url` specifies a protocol other than
 *                those in `$protocols`, or if `$url` contains an empty string.
 */
function esc_url( $url, $protocols = null, $_context = 'display' ) {
    $original_url = $url;

    if ( '' === $url ) {
        return $url;
    }

    $url = str_replace( ' ', '%20', ltrim( $url ) );
    $url = preg_replace( '|[^a-z0-9-~+_.?#=!&;,/:%@$\|*\'()\[\]\\x80-\\xff]|i', '', $url );

    if ( '' === $url ) {
        return $url;
    }

    if ( 0 !== stripos( $url, 'mailto:' ) ) {
        $strip = array( '%0d', '%0a', '%0D', '%0A' );
        /** @noinspection PhpUndefinedFunctionInspection */
        $url   = _deep_replace( $strip, $url );
    }

    $url = str_replace( ';//', '://', $url );
    /*
     * If the URL doesn't appear to contain a scheme, we presume
     * it needs http:// prepended (unless it's a relative link
     * starting with /, # or ?, or a PHP file).
     */
    if ( strpos( $url, ':' ) === false && ! in_array( $url[0], array( '/', '#', '?' ), true ) &&
      ! preg_match( '/^[a-z0-9-]+?\.php/i', $url ) ) {
        /** @noinspection HttpUrlsUsage */
        $url = 'http://' . $url;
    }

    // Replace ampersands and single quotes only when displaying.
    if ( 'display' === $_context ) {
        /** @noinspection PhpUndefinedFunctionInspection */
        $url = wp_kses_normalize_entities( $url );
        $url = str_replace( '&amp;', '&#038;', $url );
        $url = str_replace( "'", '&#039;', $url );
    }

    if ( ( false !== strpos( $url, '[' ) ) || ( false !== strpos( $url, ']' ) ) ) {

        /** @noinspection PhpUndefinedFunctionInspection */
        $parsed = wp_parse_url( $url );
        $front  = '';

        if ( isset( $parsed['scheme'] ) ) {
            $front .= $parsed['scheme'] . '://';
        } elseif ( '/' === $url[0] ) {
            $front .= '//';
        }

        if ( isset( $parsed['user'] ) ) {
            $front .= $parsed['user'];
        }

        if ( isset( $parsed['pass'] ) ) {
            $front .= ':' . $parsed['pass'];
        }

        if ( isset( $parsed['user'] ) || isset( $parsed['pass'] ) ) {
            $front .= '@';
        }

        if ( isset( $parsed['host'] ) ) {
            $front .= $parsed['host'];
        }

        if ( isset( $parsed['port'] ) ) {
            $front .= ':' . $parsed['port'];
        }

        $end_dirty = str_replace( $front, '', $url );
        $end_clean = str_replace( array( '[', ']' ), array( '%5B', '%5D' ), $end_dirty );
        $url       = str_replace( $end_dirty, $end_clean, $url );

    }

    if ( '/' === $url[0] ) {
        $good_protocol_url = $url;
    } else {
        if ( ! is_array( $protocols ) ) {
            /** @noinspection PhpUndefinedFunctionInspection */
            $protocols = wp_allowed_protocols();
        }
        /** @noinspection PhpUndefinedFunctionInspection */
        $good_protocol_url = wp_kses_bad_protocol( $url, $protocols );
        if ( strtolower( $good_protocol_url ) != strtolower( $url ) ) {
            return '';
        }
    }

    /**
     * Filters a string cleaned and escaped for output as a URL.
     *
     * @since 2.3.0
     *
     * @param string $good_protocol_url The cleaned URL to be returned.
     * @param string $original_url      The URL prior to cleaning.
     * @param string $_context          If 'display', replace ampersands and single quotes only.
     */
    return apply_filters( 'clean_url', $good_protocol_url, $original_url, $_context );
}

/**
 * Escaping for HTML blocks.
 *
 * @since 2.8.0
 *
 * @param string $text
 * @return string
 */
function esc_html( $text ) {
    /** @noinspection PhpUndefinedFunctionInspection */
    $safe_text = wp_check_invalid_utf8( $text );
    /** @noinspection PhpUndefinedFunctionInspection */
    $safe_text = _wp_specialchars( $safe_text, ENT_QUOTES );
    /**
     * Filters a string cleaned and escaped for output in HTML.
     *
     * Text passed to esc_html() is stripped of invalid or special characters
     * before output.
     *
     * @since 2.8.0
     *
     * @param string $safe_text The text after it has been escaped.
     * @param string $text      The text prior to being escaped.
     */
    return apply_filters( 'esc_html', $safe_text, $text );
}

/**
 * Escaping for HTML attributes.
 *
 * @since 2.8.0
 *
 * @param string $text
 * @return string
 */
function esc_attr( $text ) {
    /** @noinspection PhpUndefinedFunctionInspection */
    $safe_text = wp_check_invalid_utf8( $text );
    /** @noinspection PhpUndefinedFunctionInspection */
    $safe_text = _wp_specialchars( $safe_text, ENT_QUOTES );
    /**
     * Filters a string cleaned and escaped for output in an HTML attribute.
     *
     * Text passed to esc_attr() is stripped of invalid or special characters
     * before output.
     *
     * @since 2.0.6
     *
     * @param string $safe_text The text after it has been escaped.
     * @param string $text      The text prior to being escaped.
     */
    return apply_filters( 'attribute_escape', $safe_text, $text );
}

/**
 * Adds backslashes before letters and before a number at the start of a string.
 *
 * @since 0.71
 *
 * @param string $string Value to which backslashes will be added.
 * @return string String with backslashes inserted.
 */
function backslashit( $string ) {
    if ( isset( $string[0] ) && $string[0] >= '0' && $string[0] <= '9' ) {
        $string = '\\\\' . $string;
    }
    return addcslashes( $string, 'A..Za..z' );
}

/**
 * Appends a trailing slash.
 *
 * Will remove trailing forward and backslashes if it exists already before adding
 * a trailing forward slash. This prevents double slashing a string or path.
 *
 * The primary use of this is for paths and thus should be used for paths. It is
 * not restricted to paths and offers no specific path support.
 *
 * @since 1.2.0
 *
 * @param string $string What to add the trailing slash to.
 * @return string String with trailing slash added.
 */
function trailingslashit( $string ) {
    return untrailingslashit( $string ) . '/';
}

/**
 * Removes trailing forward slashes and backslashes if they exist.
 *
 * The primary use of this is for paths and thus should be used for paths. It is
 * not restricted to paths and offers no specific path support.
 *
 * @since 2.2.0
 *
 * @param string $string What to remove the trailing slashes from.
 * @return string String without the trailing slashes.
 */
function untrailingslashit( $string ) {
    return rtrim( $string, '/\\' );
}

/**
 * Sanitises various option values based on the nature of the option.
 *
 * This is basically a switch statement which will pass $value through a number
 * of functions depending on the $option.
 *
 * @since 2.0.5
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param string $option The name of the option.
 * @param string $value  The unsanitised value.
 * @return string Sanitized value.
 */
function sanitize_option( $option, $value ) {
    global $wpdb;

    $original_value = $value;
    $error          = null;

    switch ( $option ) {
        case 'admin_email':
        case 'new_admin_email':
            $value = $wpdb->strip_invalid_text_for_column( $wpdb->options, 'option_value', $value );
            if ( is_wp_error( $value ) ) {
                $error = $value->get_error_message();
            } else {
                $value = sanitize_email( $value );
                if ( ! is_email( $value ) ) {
                    $error = __( 'The email address entered did not appear to be a valid email address. Please enter a valid email address.' );
                }
            }
            break;

        case 'thumbnail_size_w':
        case 'thumbnail_size_h':
        case 'medium_size_w':
        case 'medium_size_h':
        case 'medium_large_size_w':
        case 'medium_large_size_h':
        case 'large_size_w':
        case 'large_size_h':
        case 'mailserver_port':
        case 'comment_max_links':
        case 'page_on_front':
        case 'page_for_posts':
        case 'rss_excerpt_length':
        case 'default_category':
        case 'default_email_category':
        case 'default_link_category':
        case 'close_comments_days_old':
        case 'comments_per_page':
        case 'thread_comments_depth':
        case 'users_can_register':
        case 'start_of_week':
        case 'site_icon':
            $value = absint( $value );
            break;

        case 'posts_per_page':
        case 'posts_per_rss':
            $value = (int) $value;
            if ( empty( $value ) ) {
                $value = 1;
            }
            if ( $value < -1 ) {
                $value = abs( $value );
            }
            break;

        case 'default_ping_status':
        case 'default_comment_status':
            // Options that if not there have 0 value but need to be something like "closed".
            if ( '0' == $value || '' === $value ) {
                $value = 'closed';
            }
            break;

        case 'blogdescription':
        case 'blogname':
            $value = $wpdb->strip_invalid_text_for_column( $wpdb->options, 'option_value', $value );
            if ( $value !== $original_value ) {
                /** @noinspection PhpUndefinedFunctionInspection */
                $value = $wpdb->strip_invalid_text_for_column( $wpdb->options, 'option_value', wp_encode_emoji( $original_value ) );
            }

            if ( is_wp_error( $value ) ) {
                $error = $value->get_error_message();
            } else {
                $value = esc_html( $value );
            }
            break;

        case 'blog_charset':
            $value = preg_replace( '/[^a-zA-Z0-9_-]/', '', $value ); // Strip slashes.
            break;

        case 'blog_public':
            // This is the value if the settings checkbox is not checked on POST. Don't rely on this.
            if ( null === $value ) {
                $value = 1;
            } else {
                $value = (int) $value;
            }
            break;

        case 'date_format':
        case 'time_format':
        case 'mailserver_url':
        case 'mailserver_login':
        case 'mailserver_pass':
        case 'upload_path':
            $value = $wpdb->strip_invalid_text_for_column( $wpdb->options, 'option_value', $value );
            if ( is_wp_error( $value ) ) {
                $error = $value->get_error_message();
            } else {
                $value = strip_tags( $value );
                /** @noinspection PhpUndefinedFunctionInspection */
                $value = wp_kses_data( $value );
            }
            break;

        case 'ping_sites':
            $value = explode( "\n", $value );
            $value = array_filter( array_map( 'trim', $value ) );
            $value = array_filter( array_map( 'esc_url_raw', $value ) );
            $value = implode( "\n", $value );
            break;

        case 'gmt_offset':
            $value = preg_replace( '/[^0-9:.-]/', '', $value ); // Strip slashes.
            break;

        case 'siteurl':
            $value = $wpdb->strip_invalid_text_for_column( $wpdb->options, 'option_value', $value );
            if ( is_wp_error( $value ) ) {
                $error = $value->get_error_message();
            } else {
                if ( preg_match( '#http(s?)://(.+)#i', $value ) ) {
                    $value = esc_url_raw( $value );
                } else {
                    $error = __( 'The WordPress address you entered did not appear to be a valid URL. Please enter a valid URL.' );
                }
            }
            break;

        case 'home':
            $value = $wpdb->strip_invalid_text_for_column( $wpdb->options, 'option_value', $value );
            if ( is_wp_error( $value ) ) {
                $error = $value->get_error_message();
            } else {
                if ( preg_match( '#http(s?)://(.+)#i', $value ) ) {
                    $value = esc_url_raw( $value );
                } else {
                    $error = __( 'The Site address you entered did not appear to be a valid URL. Please enter a valid URL.' );
                }
            }
            break;

        case 'WPLANG':
            /** @noinspection PhpUndefinedFunctionInspection */
            $allowed = get_available_languages();
            if ( ! is_multisite() && defined( 'WPLANG' ) && '' !== WPLANG && 'en_US' !== WPLANG ) {
                $allowed[] = WPLANG;
            }
            if ( ! in_array( $value, $allowed, true ) && ! empty( $value ) ) {
                $value = get_option( $option );
            }
            break;

        case 'illegal_names':
            $value = $wpdb->strip_invalid_text_for_column( $wpdb->options, 'option_value', $value );
            if ( is_wp_error( $value ) ) {
                $error = $value->get_error_message();
            } else {
                if ( ! is_array( $value ) ) {
                    $value = explode( ' ', $value );
                }

                $value = array_values( array_filter( array_map( 'trim', $value ) ) );

                if ( ! $value ) {
                    $value = '';
                }
            }
            break;

        case 'limited_email_domains':
        case 'banned_email_domains':
            $value = $wpdb->strip_invalid_text_for_column( $wpdb->options, 'option_value', $value );
            if ( is_wp_error( $value ) ) {
                $error = $value->get_error_message();
            } else {
                if ( ! is_array( $value ) ) {
                    $value = explode( "\n", $value );
                }

                $domains = array_values( array_filter( array_map( 'trim', $value ) ) );
                $value   = array();

                foreach ( $domains as $domain ) {
                    if ( ! preg_match( '/(--|\.\.)/', $domain ) && preg_match( '|^([a-zA-Z0-9-.])+$|', $domain ) ) {
                        $value[] = $domain;
                    }
                }
                if ( ! $value ) {
                    $value = '';
                }
            }
            break;

        case 'timezone_string':
            $allowed_zones = timezone_identifiers_list();
            if ( ! in_array( $value, $allowed_zones, true ) && ! empty( $value ) ) {
                $error = __( 'The timezone you have entered is not valid. Please select a valid timezone.' );
            }
            break;

        case 'permalink_structure':
        case 'category_base':
        case 'tag_base':
            $value = $wpdb->strip_invalid_text_for_column( $wpdb->options, 'option_value', $value );
            if ( is_wp_error( $value ) ) {
                $error = $value->get_error_message();
            } else {
                $value = esc_url_raw( $value );
                /** @noinspection HttpUrlsUsage */
                $value = str_replace( 'http://', '', $value );
            }

            if ( 'permalink_structure' === $option && null === $error
              && '' !== $value && ! preg_match( '/%[^\/%]+%/', $value )
            ) {
                /** @noinspection HtmlUnknownTarget */
                $error = sprintf(
                /* translators: %s: Documentation URL. */
                  __( 'A structure tag is required when using custom permalinks. <a href="%s">Learn more</a>' ),
                  __( 'https://wordpress.org/support/article/using-permalinks/#choosing-your-permalink-structure' )
                );
            }
            break;

        case 'default_role':
            /** @noinspection PhpUndefinedFunctionInspection */
            if ( ! get_role( $value ) && get_role( 'subscriber' ) ) {
                $value = 'subscriber';
            }
            break;

        case 'moderation_keys':
        case 'disallowed_keys':
            $value = $wpdb->strip_invalid_text_for_column( $wpdb->options, 'option_value', $value );
            if ( is_wp_error( $value ) ) {
                $error = $value->get_error_message();
            } else {
                $value = explode( "\n", $value );
                $value = array_filter( array_map( 'trim', $value ) );
                $value = array_unique( $value );
                $value = implode( "\n", $value );
            }
            break;
    }

    if ( null !== $error ) {
        if ( '' === $error && is_wp_error( $value ) ) {
            /* translators: 1: Option name, 2: Error code. */
            $error = sprintf( __( 'Could not sanitize the %1$s option. Error code: %2$s' ), $option, $value->get_error_code() );
        }

        $value = get_option( $option );
        if ( function_exists( 'add_settings_error' ) ) {
            add_settings_error( $option, "invalid_{$option}", $error );
        }
    }

    /**
     * Filters an option value following sanitization.
     *
     * @since 2.3.0
     * @since 4.3.0 Added the `$original_value` parameter.
     *
     * @param string $value          The sanitized option value.
     * @param string $option         The option name.
     * @param string $original_value The original value passed to the function.
     */
    return apply_filters( "sanitize_option_{$option}", $value, $option, $original_value );
}

/**
 * Strips out all characters that are not allowable in an email.
 *
 * @since 1.5.0
 *
 * @param string $email Email address to filter.
 * @return string Filtered email address.
 */
function sanitize_email( $email ) {
    // Test for the minimum length the email can be.
    if ( strlen( $email ) < 6 ) {
        /**
         * Filters a sanitized email address.
         *
         * This filter is evaluated under several contexts, including 'email_too_short',
         * 'email_no_at', 'local_invalid_chars', 'domain_period_sequence', 'domain_period_limits',
         * 'domain_no_periods', 'domain_no_valid_subs', or no context.
         *
         * @since 2.8.0
         *
         * @param string $sanitized_email The sanitized email address.
         * @param string $email           The email address, as provided to sanitize_email().
         * @param string|null $message    A message to pass to the user. null if email is sanitized.
         */
        return apply_filters( 'sanitize_email', '', $email, 'email_too_short' );
    }

    // Test for an @ character after the first position.
    if ( strpos( $email, '@', 1 ) === false ) {
        /** This filter is documented in wp-includes/formatting.php */
        return apply_filters( 'sanitize_email', '', $email, 'email_no_at' );
    }

    // Split out the local and domain parts.
    list( $local, $domain ) = explode( '@', $email, 2 );

    // LOCAL PART
    // Test for invalid characters.
    $local = preg_replace( '/[^a-zA-Z0-9!#$%&\'*+\/=?^_`{|}~.-]/', '', $local );
    if ( '' === $local ) {
        /** This filter is documented in wp-includes/formatting.php */
        return apply_filters( 'sanitize_email', '', $email, 'local_invalid_chars' );
    }

    // DOMAIN PART
    // Test for sequences of periods.
    $domain = preg_replace( '/\.{2,}/', '', $domain );
    if ( '' === $domain ) {
        /** This filter is documented in wp-includes/formatting.php */
        return apply_filters( 'sanitize_email', '', $email, 'domain_period_sequence' );
    }

    // Test for leading and trailing periods and whitespace.
    $domain = trim( $domain, " \t\n\r\0\x0B." );
    if ( '' === $domain ) {
        /** This filter is documented in wp-includes/formatting.php */
        return apply_filters( 'sanitize_email', '', $email, 'domain_period_limits' );
    }

    // Split the domain into subs.
    $subs = explode( '.', $domain );

    // Assume the domain will have at least two subs.
    if ( 2 > count( $subs ) ) {
        /** This filter is documented in wp-includes/formatting.php */
        return apply_filters( 'sanitize_email', '', $email, 'domain_no_periods' );
    }

    // Create an array that will contain valid subs.
    $new_subs = array();

    // Loop through each sub.
    foreach ( $subs as $sub ) {
        // Test for leading and trailing hyphens.
        $sub = trim( $sub, " \t\n\r\0\x0B-" );

        // Test for invalid characters.
        $sub = preg_replace( '/[^a-z0-9-]+/i', '', $sub );

        // If there's anything left, add it to the valid subs.
        if ( '' !== $sub ) {
            $new_subs[] = $sub;
        }
    }

    // If there aren't 2 or more valid subs.
    if ( 2 > count( $new_subs ) ) {
        /** This filter is documented in wp-includes/formatting.php */
        return apply_filters( 'sanitize_email', '', $email, 'domain_no_valid_subs' );
    }

    // Join valid subs into the new domain.
    $domain = implode( '.', $new_subs );

    // Put the email back together.
    $sanitized_email = $local . '@' . $domain;

    // Congratulations, your email made it!
    /** This filter is documented in wp-includes/formatting.php */
    return apply_filters( 'sanitize_email', $sanitized_email, $email, null );
}

/**
 * Verifies that an email is valid.
 *
 * Does not grok i18n domains. Not RFC compliant.
 *
 * @param string $email      Email address to verify.
 * @param bool   $deprecated Deprecated.
 * @return string|false Valid email address on success, false on failure.
 * @noinspection GrazieInspection*@since 0.71
 *
 */
function is_email( $email, $deprecated = false ) {
    if ( ! empty( $deprecated ) ) {
        _deprecated_argument( __FUNCTION__, '3.0.0' );
    }

    // Test for the minimum length the email can be.
    if ( strlen( $email ) < 6 ) {
        /**
         * Filters whether an email address is valid.
         *
         * This filter is evaluated under several different contexts, such as 'email_too_short',
         * 'email_no_at', 'local_invalid_chars', 'domain_period_sequence', 'domain_period_limits',
         * 'domain_no_periods', 'sub_hyphen_limits', 'sub_invalid_chars', or no specific context.
         *
         * @since 2.8.0
         *
         * @param string|false $is_email The email address if successfully passed the is_email() checks, false otherwise.
         * @param string       $email    The email address being checked.
         * @param string       $context  Context under which the email was tested.
         */
        return apply_filters( 'is_email', false, $email, 'email_too_short' );
    }

    // Test for an @ character after the first position.
    if ( strpos( $email, '@', 1 ) === false ) {
        /** This filter is documented in wp-includes/formatting.php */
        return apply_filters( 'is_email', false, $email, 'email_no_at' );
    }

    // Split out the local and domain parts.
    list( $local, $domain ) = explode( '@', $email, 2 );

    // LOCAL PART
    // Test for invalid characters.
    if ( ! preg_match( '/^[a-zA-Z0-9!#$%&\'*+\/=?^_`{|}~.-]+$/', $local ) ) {
        /** This filter is documented in wp-includes/formatting.php */
        return apply_filters( 'is_email', false, $email, 'local_invalid_chars' );
    }

    // DOMAIN PART
    // Test for sequences of periods.
    if ( preg_match( '/\.{2,}/', $domain ) ) {
        /** This filter is documented in wp-includes/formatting.php */
        return apply_filters( 'is_email', false, $email, 'domain_period_sequence' );
    }

    // Test for leading and trailing periods and whitespace.
    if ( trim( $domain, " \t\n\r\0\x0B." ) !== $domain ) {
        /** This filter is documented in wp-includes/formatting.php */
        return apply_filters( 'is_email', false, $email, 'domain_period_limits' );
    }

    // Split the domain into subs.
    $subs = explode( '.', $domain );

    // Assume the domain will have at least two subs.
    if ( 2 > count( $subs ) ) {
        /** This filter is documented in wp-includes/formatting.php */
        return apply_filters( 'is_email', false, $email, 'domain_no_periods' );
    }

    // Loop through each sub.
    foreach ( $subs as $sub ) {
        // Test for leading and trailing hyphens and whitespace.
        if ( trim( $sub, " \t\n\r\0\x0B-" ) !== $sub ) {
            /** This filter is documented in wp-includes/formatting.php */
            return apply_filters( 'is_email', false, $email, 'sub_hyphen_limits' );
        }

        // Test for invalid characters.
        if ( ! preg_match( '/^[a-z0-9-]+$/i', $sub ) ) {
            /** This filter is documented in wp-includes/formatting.php */
            return apply_filters( 'is_email', false, $email, 'sub_invalid_chars' );
        }
    }

    // Congratulations, your email made it!
    /** This filter is documented in wp-includes/formatting.php */
    return apply_filters( 'is_email', $email, $email, null );
}

/**
 * Convert a value to non-negative integer.
 *
 * @since 2.5.0
 *
 * @param mixed $maybeint Data you wish to have converted to a non-negative integer.
 * @return int A non-negative integer.
 */
function absint( $maybeint ) {
    return abs( (int) $maybeint );
}

/**
 * Performs esc_url() for database or redirect usage.
 *
 * @since 2.8.0
 *
 * @see esc_url()
 *
 * @param string   $url       The URL to be cleaned.
 * @param string[] $protocols Optional. An array of acceptable protocols.
 *                            Defaults to return value of wp_allowed_protocols().
 * @return string The cleaned URL after esc_url() is run with the 'db' context.
 */
function esc_url_raw( $url, $protocols = null ) {
    return esc_url( $url, $protocols, 'db' );
}

/**
 * Parses a string into variables to be stored in an array.
 *
 * @since 2.2.1
 *
 * @param string $string The string to be parsed.
 * @param array  $array  Variables will be stored in this array.
 */
function wp_parse_str( $string, &$array ) {
    parse_str( (string) $string, $array );

    /**
     * Filters the array of variables derived from a parsed string.
     *
     * @since 2.2.1
     *
     * @param array $array The array populated with variables.
     */
    $array = apply_filters( 'wp_parse_str', $array );
}

/**
 * Escape single quotes, htmlspecialchar " < > &, and fix line endings.
 *
 * Escapes text strings for echoing in JS. It is intended to be used for inline JS
 * (in a tag attribute, for example onclick="..."). Note that the strings have to
 * be in single quotes. The {@see 'js_escape'} filter is also applied here.
 *
 * @since 2.8.0
 *
 * @param string $text The text to be escaped.
 * @return string Escaped text.
 */
function esc_js( $text ) {
    /** @noinspection PhpUndefinedFunctionInspection */
    $safe_text = wp_check_invalid_utf8( $text );
    /** @noinspection PhpUndefinedFunctionInspection */
    $safe_text = _wp_specialchars( $safe_text, ENT_COMPAT );
    $safe_text = preg_replace( '/&#(x)?0*(?(1)27|39);?/i', "'", stripslashes( $safe_text ) );
    $safe_text = str_replace( "\r", '', $safe_text );
    $safe_text = str_replace( "\n", '\\n', addslashes( $safe_text ) );
    /**
     * Filters a string cleaned and escaped for output in JavaScript.
     *
     * Text passed to esc_js() is stripped of invalid or special characters,
     * and properly slashed for output.
     *
     * @since 2.0.6
     *
     * @param string $safe_text The text after it has been escaped.
     * @param string $text      The text prior to being escaped.
     */
    return apply_filters( 'js_escape', $safe_text, $text );
}

/**
 * Adds slashes to a string or recursively adds slashes to string within an array.
 *
 * This should be used when preparing data for core API that expects slashed data.
 * This should not be used to escape data going directly into an SQL query.
 *
 * @since 3.6.0
 * @since 5.5.0 Non-string values are left untouched.
 *
 * @param string|array $value String or array of data to slash.
 * @return string|array Slashed `$value`.
 */
function wp_slash( $value ) {
    if ( is_array( $value ) ) {
        $value = array_map( 'wp_slash', $value );
    }

    if ( is_string( $value ) ) {
        return addslashes( $value );
    }

    return $value;
}

/**
 * Removes slashes from a string or recursively removes slashes from strings within an array.
 *
 * This should be used to remove slashes from data passed to core API that
 * expects data to be unslashed.
 *
 * @since 3.6.0
 *
 * @param string|array $value String or array of data to unslash.
 * @return string|array Unslashed `$value`.
 */
function wp_unslash( $value ) {
    return stripslashes_deep( $value );
}

/**
 * Navigates through an array, object, or scalar, and removes slashes from the values.
 *
 * @since 2.0.0
 *
 * @param mixed $value The value to be stripped.
 * @return mixed Stripped value.
 */
function stripslashes_deep( $value ) {
    return map_deep( $value, 'stripslashes_from_strings_only' );
}

/**
 * Maps a function to all non-iterable elements of an array or an object.
 *
 * This is similar to `array_walk_recursive()` but acts upon objects too.
 *
 * @since 4.4.0
 *
 * @param mixed    $value    The array, object, or scalar.
 * @param callable $callback The function to map onto $value.
 * @return mixed The value with the callback applied to all non-arrays and non-objects inside it.
 */
function map_deep( $value, $callback ) {
    if ( is_array( $value ) ) {
        foreach ( $value as $index => $item ) {
            $value[ $index ] = map_deep( $item, $callback );
        }
    } elseif ( is_object( $value ) ) {
        $object_vars = get_object_vars( $value );
        foreach ( $object_vars as $property_name => $property_value ) {
            $value->$property_name = map_deep( $property_value, $callback );
        }
    } else {
        $value = call_user_func( $callback, $value );
    }

    return $value;
}

/**
 * Sanitizes a string key.
 *
 * Keys are used as internal identifiers. Lowercase alphanumeric characters,
 * dashes, and underscores are allowed.
 *
 * @since 3.0.0
 *
 * @param string $key String key.
 * @return string Sanitized key.
 */
function sanitize_key( $key ) {
    $sanitized_key = '';

    if ( is_scalar( $key ) ) {
        $sanitized_key = strtolower( $key );
        $sanitized_key = preg_replace( '/[^a-z0-9_\-]/', '', $sanitized_key );
    }

    /**
     * Filters a sanitized key string.
     *
     * @since 3.0.0
     *
     * @param string $sanitized_key Sanitized key.
     * @param string $key           The key prior to sanitization.
     */
    return apply_filters( 'sanitize_key', $sanitized_key, $key );
}

/**
 * Sanitizes a string into a slug, which can be used in URLs or HTML attributes.
 *
 * By default, converts accent characters to ASCII characters and further
 * limits the output to alphanumeric characters, underscore (_) and dash (-)
 * through the {@see 'sanitize_title'} filter.
 *
 * If `$title` is empty and `$fallback_title` is set, the latter will be used.
 *
 * @since 1.0.0
 *
 * @param string $title          The string to be sanitized.
 * @param string $fallback_title Optional. A title to use if $title is empty. Default empty.
 * @param string $context        Optional. The operation for which the string is sanitized.
 *                               When set to 'save', the string runs through remove_accents().
 *                               Default 'save'.
 * @return string The sanitized string.
 */
function sanitize_title( $title, $fallback_title = '', $context = 'save' ) {
    $raw_title = $title;

    if ( 'save' === $context ) {
        /** @noinspection PhpUndefinedFunctionInspection */
        $title = remove_accents( $title );
    }

    /**
     * Filters a sanitized title string.
     *
     * @since 1.2.0
     *
     * @param string $title     Sanitized title.
     * @param string $raw_title The title prior to sanitization.
     * @param string $context   The context for which the title is being sanitized.
     */
    $title = apply_filters( 'sanitize_title', $title, $raw_title, $context );

    if ( '' === $title || false === $title ) {
        $title = $fallback_title;
    }

    return $title;
}

/**
 * Escapes data for use in a MySQL query.
 *
 * Usually you should prepare queries using wpdb::prepare().
 * Sometimes, spot-escaping is required or useful. One example
 * is preparing an array for use in an IN clause.
 *
 * NOTE: Since 4.8.3, '%' characters will be replaced with a placeholder string,
 * this prevents certain SQLi attacks from taking place. This change in behaviour
 * may cause issues for code that expects the return value of esc_sql() to be useable
 * for other purposes.
 *
 * @since 2.8.0
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param string|array $data Unescaped data
 * @return string|array Escaped data
 */
function esc_sql( $data ) {
    global $wpdb;
    return $wpdb->_escape( $data );
}

/**
 * i18n friendly version of basename()
 *
 * @since 3.1.0
 *
 * @param string $path   A path.
 * @param string $suffix If the filename ends in suffix this will also be cut off.
 * @return string
 */
function wp_basename( $path, $suffix = '' ) {
    return urldecode( basename( str_replace( array( '%2F', '%5C' ), '/', urlencode( $path ) ), $suffix ) );
}

/**
 * Sanitizes an HTML classname to ensure it only contains valid characters.
 *
 * Strips the string down to A-Z,a-z,0-9,_,-. If this results in an empty
 * string then it will return the alternative value supplied.
 *
 * @todo Expand to support the full range of CDATA that a class attribute can contain.
 *
 * @since 2.8.0
 *
 * @param string $class    The classname to be sanitized
 * @param string $fallback Optional. The value to return if the sanitization ends up as an empty string.
 *  Defaults to an empty string.
 * @return string The sanitized value
 */
function sanitize_html_class( $class, $fallback = '' ) {
    // Strip out any %-encoded octets.
    $sanitized = preg_replace( '|%[a-fA-F0-9][a-fA-F0-9]|', '', $class );

    // Limit to A-Z, a-z, 0-9, '_', '-'.
    $sanitized = preg_replace( '/[^A-Za-z0-9_-]/', '', $sanitized );

    if ( '' === $sanitized && $fallback ) {
        return sanitize_html_class( $fallback );
    }
    /**
     * Filters a sanitized HTML class string.
     *
     * @since 2.8.0
     *
     * @param string $sanitized The sanitized HTML class.
     * @param string $class     HTML class before sanitization.
     * @param string $fallback  The fallback string.
     */
    return apply_filters( 'sanitize_html_class', $sanitized, $class, $fallback );
}

/**
 * Add leading zeros when necessary.
 *
 * If you set the threshold to '4' and the number is '10', then you will get
 * back '0010'. If you set the threshold to '4' and the number is '5000', then you
 * will get back '5000'.
 *
 * Uses sprintf to append the amount of zeros based on the $threshold parameter
 * and the size of the number. If the number is large enough, then no zeros will
 * be appended.
 *
 * @since 0.71
 *
 * @param int $number     Number to append zeros to if not greater than threshold.
 * @param int $threshold  Digit places number needs to be to not have zeros added.
 * @return string Adds leading zeros to number if needed.
 */
function zeroise( $number, $threshold ) {
    return sprintf( '%0' . $threshold . 's', $number );
}

/**
 * Navigates through an array, object, or scalar, and encodes the values to be used in a URL.
 *
 * @since 2.2.0
 *
 * @param mixed $value The array or string to be encoded.
 * @return mixed The encoded value.
 */
function urlencode_deep( $value ) {
    return map_deep( $value, 'urlencode' );
}

/**
 * Converts lone & characters into `&#038;` (a.k.a. `&amp;`)
 *
 * @since 0.71
 *
 * @param string $content    String of characters to be converted.
 * @param string $deprecated Not used.
 * @return string Converted string.
 */
function convert_chars( $content, $deprecated = '' ) {
    if ( ! empty( $deprecated ) ) {
        _deprecated_argument( __FUNCTION__, '0.71' );
    }

    if ( strpos( $content, '&' ) !== false ) {
        $content = preg_replace( '/&([^#])(?![a-z1-4]{1,8};)/i', '&#038;$1', $content );
    }

    return $content;
}

/**
 * Sanitizes a URL for database or redirect usage.
 *
 * @since 2.3.1
 * @since 2.8.0 Deprecated in favor of esc_url_raw().
 * @since 5.9.0 Restored (un-deprecated).
 *
 * @see esc_url()
 *
 * @param string   $url       The URL to be cleaned.
 * @param string[] $protocols Optional. An array of acceptable protocols.
 *                            Defaults to return value of wp_allowed_protocols().
 * @return string The cleaned URL after esc_url() is run with the 'db' context.
 */
function sanitize_url( $url, $protocols = null ) {
    return esc_url( $url, $protocols, 'db' );
}

/**
 * WordPress implementation of PHP sprintf() with filters.
 *
 * @since 2.5.0
 * @since 5.3.0 Formalized the existing and already documented `...$args` parameter
 *              by adding it to the function signature.
 *
 * @link https://www.php.net/sprintf
 *
 * @param string $pattern The string which formatted args are inserted.
 * @param mixed  ...$args Arguments to be formatted into the $pattern string.
 * @return string The formatted string.
 */
function wp_sprintf( $pattern, ...$args ) {
    $len       = strlen( $pattern );
    $start     = 0;
    $result    = '';
    $arg_index = 0;
    while ( $len > $start ) {
        // Last character: append and break.
        if ( strlen( $pattern ) - 1 == $start ) {
            $result .= substr( $pattern, -1 );
            break;
        }

        // Literal %: append and continue.
        if ( '%%' === substr( $pattern, $start, 2 ) ) {
            $start  += 2;
            $result .= '%';
            continue;
        }

        // Get fragment before next %.
        $end = strpos( $pattern, '%', $start + 1 );
        if ( false === $end ) {
            $end = $len;
        }
        $fragment = substr( $pattern, $start, $end - $start );

        // Fragment has a specifier.
        if ( '%' === $pattern[ $start ] ) {
            // Find numbered arguments or take the next one in order.
            if ( preg_match( '/^%(\d+)\$/', $fragment, $matches ) ) {
                $index    = $matches[1] - 1; // 0-based array vs 1-based sprintf() arguments.
                $arg      = isset( $args[ $index ] ) ? $args[ $index ] : '';
                $fragment = str_replace( "%{$matches[1]}$", '%', $fragment );
            } else {
                $arg = isset( $args[ $arg_index ] ) ? $args[ $arg_index ] : '';
                ++$arg_index;
            }

            /**
             * Filters a fragment from the pattern passed to wp_sprintf().
             *
             * If the fragment is unchanged, then sprintf() will be run on the fragment.
             *
             * @since 2.5.0
             *
             * @param string $fragment A fragment from the pattern.
             * @param string $arg      The argument.
             */
            $_fragment = apply_filters( 'wp_sprintf', $fragment, $arg );
            if ( $_fragment != $fragment ) {
                $fragment = $_fragment;
            } else {
                $fragment = sprintf( $fragment, (string) $arg );
            }
        }

        // Append to result and move to next fragment.
        $result .= $fragment;
        $start   = $end;
    }

    return $result;
}

/**
 * Sanitizes a string from user input or from the database.
 *
 * - Checks for invalid UTF-8,
 * - Converts single `<` characters to entities
 * - Strips all tags
 * - Removes line breaks, tabs, and extra whitespace
 * - Strips octets
 *
 * @param string $str String to sanitize.
 * @return string Sanitized string.
 * @noinspection PhpRedundantOptionalArgumentInspection
 * @see wp_check_invalid_utf8()
 * @see wp_strip_all_tags()
 *
 * @since 2.9.0
 *
 * @see sanitize_textarea_field()
 */
function sanitize_text_field( $str ) {
    $filtered = _sanitize_text_fields( $str, false );

    /**
     * Filters a sanitized text field string.
     *
     * @since 2.9.0
     *
     * @param string $filtered The sanitized string.
     * @param string $str      The string prior to being sanitized.
     */
    return apply_filters( 'sanitize_text_field', $filtered, $str );
}

/**
 * Internal helper function to sanitize a string from user input or from the database.
 *
 * @param string $str           String to sanitize.
 * @param bool   $keep_newlines Optional. Whether to keep newlines. Default: false.
 * @return string Sanitized string.
 * @noinspection PhpUndefinedFunctionInspection
 * @since 4.7.0
 * @access private
 *
 * @noinspection PhpRedundantOptionalArgumentInspection
 */
function _sanitize_text_fields( $str, $keep_newlines = false ) {
    if ( is_object( $str ) || is_array( $str ) ) {
        return '';
    }

    $str = (string) $str;

    $filtered = wp_check_invalid_utf8( $str );

    if ( strpos( $filtered, '<' ) !== false ) {
        $filtered = wp_pre_kses_less_than( $filtered );
        // This will strip extra whitespace for us.
        $filtered = wp_strip_all_tags( $filtered, false );

        // Use HTML entities in a special case to make sure no later
        // newline stripping stage could lead to a functional tag.
        $filtered = str_replace( "<\n", "&lt;\n", $filtered );
    }

    if ( ! $keep_newlines ) {
        $filtered = preg_replace( '/[\r\n\t ]+/', ' ', $filtered );
    }
    $filtered = trim( $filtered );

    $found = false;
    while ( preg_match( '/%[a-f0-9]{2}/i', $filtered, $match ) ) {
        $filtered = str_replace( $match[0], '', $filtered );
        $found    = true;
    }

    if ( $found ) {
        // Strip out the whitespace that may now exist after removing the octets.
        $filtered = trim( preg_replace( '/ +/', ' ', $filtered ) );
    }

    return $filtered;
}

/**
 * Properly strips all HTML tags including script and style
 *
 * This differs from strip_tags() because it removes the contents of
 * the `<script>` and `<style>` tags. E.g. `strip_tags( '<script>something</script>' )`
 * will return 'something'. wp_strip_all_tags will return ''
 *
 * @since 2.9.0
 *
 * @param string $string        String containing HTML tags
 * @param bool   $remove_breaks Optional. Whether to remove left over line breaks and white space chars
 * @return string The processed string.
 */
function wp_strip_all_tags( $string, $remove_breaks = false ) {
    $string = preg_replace( '@<(script|style)[^>]*?>.*?</\\1>@si', '', $string );
    $string = strip_tags( $string );

    if ( $remove_breaks ) {
        $string = preg_replace( '/[\r\n\t ]+/', ' ', $string );
    }

    return trim( $string );
}

/**
 * Sanitizes a hex color.
 *
 * Returns either '', a 3 or 6 digit hex color (with #), or nothing.
 * For sanitizing values without a #, see sanitize_hex_color_no_hash().
 *
 * @since 3.4.0
 *
 * @param string $color
 * @return string|void
 */
function sanitize_hex_color( $color ) {
    if ( '' === $color ) {
        return '';
    }

    // 3 or 6 hex digits, or the empty string.
    if ( preg_match( '|^#([A-Fa-f0-9]{3}){1,2}$|', $color ) ) {
        return $color;
    }
}

/**
 * Sanitizes a multiline string from user input or from the database.
 *
 * The function is like sanitize_text_field(), but preserves
 * new lines (\n) and other whitespace, which are legitimate
 * input in textarea elements.
 *
 * @see sanitize_text_field()
 *
 * @since 4.7.0
 *
 * @param string $str String to sanitize.
 * @return string Sanitized string.
 */
function sanitize_textarea_field( $str ) {
    $filtered = _sanitize_text_fields( $str, true );

    /**
     * Filters a sanitized textarea field string.
     *
     * @since 4.7.0
     *
     * @param string $filtered The sanitized string.
     * @param string $str      The string prior to being sanitized.
     */
    return apply_filters( 'sanitize_textarea_field', $filtered, $str );
}