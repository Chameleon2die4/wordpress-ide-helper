<?php /** @noinspection HttpUrlsUsage */
/** @noinspection PhpUnnecessaryStringCastInspection */
/** @noinspection PhpUnusedParameterInspection */
/** @noinspection GrazieInspection */
/** @noinspection PhpUnnecessaryLocalVariableInspection */
/** @noinspection PhpUnusedLocalVariableInspection */
/** @noinspection PhpRedundantOptionalArgumentInspection */
/** @noinspection PhpMissingBreakStatementInspection */
/** @noinspection PhpIncludeInspection */
/** @noinspection SpellCheckingInspection */
/** @noinspection PhpUnused */
/** @noinspection RegExpSimplifiable */
/** @noinspection HtmlUnknownTarget */
/** @noinspection PhpComposerExtensionStubsInspection */
/** @noinspection SqlNoDataSourceInspection */
/** @noinspection SqlNoDataSourceInspection */
/** @noinspection PhpUndefinedConstantInspection */
/** @noinspection PhpUnnecessaryCurlyVarSyntaxInspection */

/**
 * Main WordPress API
 *
 * @package WordPress
 */


/**
 * Serialize data, if needed.
 *
 * @since 2.0.5
 *
 * @param string|array|object $data Data that might be serialized.
 * @return object|string A scalar data.
 */
function maybe_serialize( $data ) {
    if ( is_array( $data ) || is_object( $data ) ) {
        return serialize( $data );
    }

    /*
	 * Double serialization is required for backward compatibility.
	 * See https://core.trac.wordpress.org/ticket/12930
	 * Also the world will end. See WP 3.6.1.
	 */
    if ( is_serialized( $data, false ) ) {
        return serialize( $data );
    }

    return $data;
}

/**
 * Unserialize data only if it was serialized.
 *
 * @since 2.0.0
 *
 * @param string $data Data that might be unserialized.
 * @return mixed Unserialized data can be any type.
 */
function maybe_unserialize( $data ) {
    if ( is_serialized( $data ) ) { // Don't attempt to unserialize data that wasn't serialized going in.
        return @unserialize( trim( $data ) );
    }

    return $data;
}

/**
 * Check value to find if it was serialized.
 *
 * If $data is not an string, then returned value will always be false.
 * Serialized data is always a string.
 *
 * @param string $data   Value to check to see if was serialized.
 * @param bool   $strict Optional. Whether to be strict about the end of the string. Default true.
 * @return bool False if not serialized and true if it was.
 * @noinspection PhpMissingBreakStatementInspection*@since 2.0.5
 *
 */
function is_serialized( $data, $strict = true ) {
    // If it isn't a string, it isn't serialized.
    if ( ! is_string( $data ) ) {
        return false;
    }
    $data = trim( $data );
    if ( 'N;' === $data ) {
        return true;
    }
    if ( strlen( $data ) < 4 ) {
        return false;
    }
    if ( ':' !== $data[1] ) {
        return false;
    }
    if ( $strict ) {
        $lastc = substr( $data, -1 );
        if ( ';' !== $lastc && '}' !== $lastc ) {
            return false;
        }
    } else {
        $semicolon = strpos( $data, ';' );
        $brace     = strpos( $data, '}' );
        // Either ; or } must exist.
        if ( false === $semicolon && false === $brace ) {
            return false;
        }
        // But neither must be in the first X characters.
        if ( false !== $semicolon && $semicolon < 3 ) {
            return false;
        }
        if ( false !== $brace && $brace < 4 ) {
            return false;
        }
    }
    $token = $data[0];
    switch ( $token ) {
        case 's':
            if ( $strict ) {
                if ( '"' !== substr( $data, -2, 1 ) ) {
                    return false;
                }
            } elseif ( false === strpos( $data, '"' ) ) {
                return false;
            }
        // Or else fall through.
        case 'a':
        case 'O':
            return (bool) preg_match( "/^{$token}:[0-9]+:/s", $data );
        case 'b':
        case 'i':
        case 'd':
            $end = $strict ? '$' : '';
            return (bool) preg_match( "/^{$token}:[0-9.E+-]+;$end/", $data );
    }
    return false;
}

/**
 * Check whether serialized data is of string type.
 *
 * @since 2.0.5
 *
 * @param string $data Serialized data.
 * @return bool False if not a serialized string, true if it is.
 */
function is_serialized_string( $data ) {
    // if it isn't a string, it isn't a serialized string.
    if ( ! is_string( $data ) ) {
        return false;
    }
    $data = trim( $data );
    if ( strlen( $data ) < 4 ) {
        return false;
    } elseif ( ':' !== $data[1] ) {
        return false;
    } elseif ( ';' !== substr( $data, -1 ) ) {
        return false;
    } elseif ( 's' !== $data[0] ) {
        return false;
    } elseif ( '"' !== substr( $data, -2, 1 ) ) {
        return false;
    } else {
        return true;
    }
}


/**
 * Walks the array while sanitizing the contents.
 *
 * @since 0.71
 * @since 5.5.0 Non-string values are left untouched.
 *
 * @param array $array Array to walk while sanitizing contents.
 * @return array Sanitized $array.
 */
function add_magic_quotes( $array ) {
    foreach ( (array) $array as $k => $v ) {
        if ( is_array( $v ) ) {
            $array[ $k ] = add_magic_quotes( $v );
        } elseif ( is_string( $v ) ) {
            $array[ $k ] = addslashes( $v );
        } else {
            continue;
        }
    }

    return $array;
}

/**
 * HTTP request for URI to retrieve content.
 *
 * @since 1.5.1
 *
 * @see wp_safe_remote_get()
 *
 * @param string $uri URI/URL of web page to retrieve.
 * @return string|false HTTP content. False on failure.
 */
function wp_remote_fopen( $uri ) {
    $parsed_url = parse_url( $uri );

    if ( ! $parsed_url || ! is_array( $parsed_url ) ) {
        return false;
    }

    $options            = array();
    $options['timeout'] = 10;

    $response = wp_safe_remote_get( $uri, $options );

    if ( is_wp_error( $response ) ) {
        return false;
    }

    return wp_remote_retrieve_body( $response );
}

/**
 * Set up the WordPress query.
 *
 * @since 2.0.0
 *
 * @global WP       $wp           Current WordPress environment instance.
 * @global WP_Query $wp_query     WordPress Query object.
 * @global WP_Query $wp_the_query Copy of the WordPress Query object.
 *
 * @param string|array $query_vars Default WP_Query arguments.
 */
function wp( $query_vars = '' ) {
    global $wp, $wp_query, $wp_the_query;

    $wp->main( $query_vars );

    if ( ! isset( $wp_the_query ) ) {
        $wp_the_query = $wp_query;
    }
}

/**
 * Retrieve the description for the HTTP status.
 *
 * @since 2.3.0
 * @since 3.9.0 Added status codes 418, 428, 429, 431, and 511.
 * @since 4.5.0 Added status codes 308, 421, and 451.
 * @since 5.1.0 Added status code 103.
 *
 * @global array $wp_header_to_desc
 *
 * @param int $code HTTP status code.
 * @return string Status description if found, an empty string otherwise.
 */
function get_status_header_desc( $code ) {
    global $wp_header_to_desc;

    $code = absint( $code );

    if ( ! isset( $wp_header_to_desc ) ) {
        $wp_header_to_desc = array(
          100 => 'Continue',
          101 => 'Switching Protocols',
          102 => 'Processing',
          103 => 'Early Hints',

          200 => 'OK',
          201 => 'Created',
          202 => 'Accepted',
          203 => 'Non-Authoritative Information',
          204 => 'No Content',
          205 => 'Reset Content',
          206 => 'Partial Content',
          207 => 'Multi-Status',
          226 => 'IM Used',

          300 => 'Multiple Choices',
          301 => 'Moved Permanently',
          302 => 'Found',
          303 => 'See Other',
          304 => 'Not Modified',
          305 => 'Use Proxy',
          306 => 'Reserved',
          307 => 'Temporary Redirect',
          308 => 'Permanent Redirect',

          400 => 'Bad Request',
          401 => 'Unauthorized',
          402 => 'Payment Required',
          403 => 'Forbidden',
          404 => 'Not Found',
          405 => 'Method Not Allowed',
          406 => 'Not Acceptable',
          407 => 'Proxy Authentication Required',
          408 => 'Request Timeout',
          409 => 'Conflict',
          410 => 'Gone',
          411 => 'Length Required',
          412 => 'Precondition Failed',
          413 => 'Request Entity Too Large',
          414 => 'Request-URI Too Long',
          415 => 'Unsupported Media Type',
          416 => 'Requested Range Not Satisfiable',
          417 => 'Expectation Failed',
          418 => 'I\'m a teapot',
          421 => 'Misdirected Request',
          422 => 'Unprocessable Entity',
          423 => 'Locked',
          424 => 'Failed Dependency',
          426 => 'Upgrade Required',
          428 => 'Precondition Required',
          429 => 'Too Many Requests',
          431 => 'Request Header Fields Too Large',
          451 => 'Unavailable For Legal Reasons',

          500 => 'Internal Server Error',
          501 => 'Not Implemented',
          502 => 'Bad Gateway',
          503 => 'Service Unavailable',
          504 => 'Gateway Timeout',
          505 => 'HTTP Version Not Supported',
          506 => 'Variant Also Negotiates',
          507 => 'Insufficient Storage',
          510 => 'Not Extended',
          511 => 'Network Authentication Required',
        );
    }

    if ( isset( $wp_header_to_desc[ $code ] ) ) {
        return $wp_header_to_desc[ $code ];
    } else {
        return '';
    }
}

/**
 * Set HTTP status header.
 *
 * @since 2.0.0
 * @since 4.4.0 Added the `$description` parameter.
 *
 * @see get_status_header_desc()
 *
 * @param int    $code        HTTP status code.
 * @param string $description Optional. A custom description for the HTTP status.
 */
function status_header( $code, $description = '' ) {
    if ( ! $description ) {
        $description = get_status_header_desc( $code );
    }

    if ( empty( $description ) ) {
        return;
    }

    $protocol      = wp_get_server_protocol();
    $status_header = "$protocol $code $description";
    if ( function_exists( 'apply_filters' ) ) {

        /**
         * Filters an HTTP status header.
         *
         * @since 2.2.0
         *
         * @param string $status_header HTTP status header.
         * @param int    $code          HTTP status code.
         * @param string $description   Description for the status code.
         * @param string $protocol      Server protocol.
         */
        $status_header = apply_filters( 'status_header', $status_header, $code, $description, $protocol );
    }

    if ( ! headers_sent() ) {
        header( $status_header, true, $code );
    }
}

/**
 * Get the header information to prevent caching.
 *
 * The several different headers cover the different ways cache prevention
 * is handled by different browsers
 *
 * @since 2.8.0
 *
 * @return array The associative array of header names and field values.
 */
function wp_get_nocache_headers() {
    $headers = array(
      'Expires'       => 'Wed, 11 Jan 1984 05:00:00 GMT',
      'Cache-Control' => 'no-cache, must-revalidate, max-age=0',
    );

    if ( function_exists( 'apply_filters' ) ) {
        /**
         * Filters the cache-controlling headers.
         *
         * @since 2.8.0
         *
         * @see wp_get_nocache_headers()
         *
         * @param array $headers {
         *     Header names and field values.
         *
         *     @type string $Expires       Expires header.
         *     @type string $Cache-Control Cache-Control header.
         * }
         */
        $headers = (array) apply_filters( 'nocache_headers', $headers );
    }
    $headers['Last-Modified'] = false;
    return $headers;
}

/**
 * Set the headers to prevent caching for the different browsers.
 *
 * Different browsers support different nocache headers, so several
 * headers must be sent so that all of them get the point that no
 * caching should occur.
 *
 * @since 2.0.0
 *
 * @see wp_get_nocache_headers()
 */
function nocache_headers() {
    if ( headers_sent() ) {
        return;
    }

    $headers = wp_get_nocache_headers();

    unset( $headers['Last-Modified'] );

    header_remove( 'Last-Modified' );

    foreach ( $headers as $name => $field_value ) {
        header( "{$name}: {$field_value}" );
    }
}

/**
 * Set the headers for caching for 10 days with JavaScript content type.
 *
 * @since 2.1.0
 */
function cache_javascript_headers() {
    $expiresOffset = 10 * DAY_IN_SECONDS;

    header( 'Content-Type: text/javascript; charset=' . get_bloginfo( 'charset' ) );
    header( 'Vary: Accept-Encoding' ); // Handle proxies.
    header( 'Expires: ' . gmdate( 'D, d M Y H:i:s', time() + $expiresOffset ) . ' GMT' );
}

/**
 * Retrieve the number of database queries during the WordPress execution.
 *
 * @since 2.0.0
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @return int Number of database queries.
 */
function get_num_queries() {
    global $wpdb;
    return $wpdb->num_queries;
}

/**
 * Whether input is yes or no.
 *
 * Must be 'y' to be true.
 *
 * @since 1.0.0
 *
 * @param string $yn Character string containing either 'y' (yes) or 'n' (no).
 * @return bool True if 'y', false on anything else.
 */
function bool_from_yn( $yn ) {
    return ( 'y' === strtolower( $yn ) );
}


/**
 * Displays the default robots.txt file content.
 *
 * @since 2.1.0
 * @since 5.3.0 Remove the "Disallow: /" output if search engine visiblity is
 *              discouraged in favor of robots meta HTML tag via wp_robots_no_robots()
 *              filter callback.
 */
function do_robots() {
    header( 'Content-Type: text/plain; charset=utf-8' );

    /**
     * Fires when displaying the robots.txt file.
     *
     * @since 2.1.0
     */
    do_action( 'do_robotstxt' );

    $output = "User-agent: *\n";
    $public = get_option( 'blog_public' );

    $site_url = parse_url( site_url() );
    $path     = ( ! empty( $site_url['path'] ) ) ? $site_url['path'] : '';
    $output  .= "Disallow: $path/wp-admin/\n";
    $output  .= "Allow: $path/wp-admin/admin-ajax.php\n";

    /**
     * Filters the robots.txt output.
     *
     * @since 3.0.0
     *
     * @param string $output The robots.txt output.
     * @param bool   $public Whether the site is considered "public".
     */
    echo apply_filters( 'robots_txt', $output, $public );
}

/**
 * Display the favicon.ico file content.
 *
 * @since 5.4.0
 */
function do_favicon() {
    /**
     * Fires when serving the favicon.ico file.
     *
     * @since 5.4.0
     */
    do_action( 'do_faviconico' );

    wp_redirect( get_site_icon_url( 32, includes_url( 'images/w-logo-blue-white-bg.png' ) ) );
    exit;
}

/**
 * Determines whether WordPress is already installed.
 *
 * The cache will be checked first. If you have a cache plugin, which saves
 * the cache values, then this will work. If you use the default WordPress
 * cache, and the database goes away, then you might have problems.
 *
 * Checks for the 'siteurl' option for whether WordPress is installed.
 *
 * For more information on this and similar theme functions, check out
 * the {@link https://developer.wordpress.org/themes/basics/conditional-tags/
 * Conditional Tags} article in the Theme Developer Handbook.
 *
 * @since 2.1.0
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @return bool Whether the site is already installed.
 */
function is_blog_installed() {
    global $wpdb;

    /*
	 * Check cache first. If options table goes away and we have true
	 * cached, oh well.
	 */
    if ( wp_cache_get( 'is_blog_installed' ) ) {
        return true;
    }

    $suppress = $wpdb->suppress_errors();
    if ( ! wp_installing() ) {
        $alloptions = wp_load_alloptions();
    }
    // If siteurl is not set to autoload, check it specifically.
    if ( ! isset( $alloptions['siteurl'] ) ) {
        $installed = $wpdb->get_var( "SELECT option_value FROM $wpdb->options WHERE option_name = 'siteurl'" );
    } else {
        $installed = $alloptions['siteurl'];
    }
    $wpdb->suppress_errors( $suppress );

    $installed = ! empty( $installed );
    wp_cache_set( 'is_blog_installed', $installed );

    if ( $installed ) {
        return true;
    }

    // If visiting repair.php, return true and let it take over.
    if ( defined( 'WP_REPAIRING' ) ) {
        return true;
    }

    $suppress = $wpdb->suppress_errors();

    /*
	 * Loop over the WP tables. If none exist, then scratch installation is allowed.
	 * If one or more exist, suggest table repair since we got here because the
	 * options table could not be accessed.
	 */
    $wp_tables = $wpdb->tables();
    foreach ( $wp_tables as $table ) {
        // The existence of custom user tables shouldn't suggest an unwise state or prevent a clean installation.
        if ( defined( 'CUSTOM_USER_TABLE' ) && CUSTOM_USER_TABLE == $table ) {
            continue;
        }
        if ( defined( 'CUSTOM_USER_META_TABLE' ) && CUSTOM_USER_META_TABLE == $table ) {
            continue;
        }

        $described_table = $wpdb->get_results( "DESCRIBE $table;" );
        if (
          ( ! $described_table && empty( $wpdb->last_error ) ) ||
          ( is_array( $described_table ) && 0 === count( $described_table ) )
        ) {
            continue;
        }

        // One or more tables exist. This is not good.

        wp_load_translations_early();

        // Die with a DB error.
        $wpdb->error = sprintf(
        /* translators: %s: Database repair URL. */
          __( 'One or more database tables are unavailable. The database may need to be <a href="%s">repaired</a>.' ),
          'maint/repair.php?referrer=is_blog_installed'
        );

        dead_db();
    }

    $wpdb->suppress_errors( $suppress );

    wp_cache_set( 'is_blog_installed', false );

    return false;
}

/**
 * Retrieve URL with nonce added to URL query.
 *
 * @since 2.0.4
 *
 * @param string     $actionurl URL to add nonce action.
 * @param int|string $action    Optional. Nonce action name. Default -1.
 * @param string     $name      Optional. Nonce name. Default '_wpnonce'.
 * @return string Escaped URL with nonce action added.
 */
function wp_nonce_url( $actionurl, $action = -1, $name = '_wpnonce' ) {
    $actionurl = str_replace( '&amp;', '&', $actionurl );
    return esc_html( add_query_arg( $name, wp_create_nonce( $action ), $actionurl ) );
}

/**
 * Retrieve or display nonce hidden field for forms.
 *
 * The nonce field is used to validate that the contents of the form came from
 * the location on the current site and not somewhere else. The nonce does not
 * offer absolute protection, but should protect against most cases. It is very
 * important to use nonce field in forms.
 *
 * The $action and $name are optional, but if you want to have better security,
 * it is strongly suggested to set those two parameters. It is easier to just
 * call the function without any parameters, because validation of the nonce
 * doesn't require any parameters, but since crackers know what the default is
 * it won't be difficult for them to find a way around your nonce and cause
 * damage.
 *
 * The input name will be whatever $name value you gave. The input value will be
 * the nonce creation value.
 *
 * @since 2.0.4
 *
 * @param int|string $action  Optional. Action name. Default -1.
 * @param string     $name    Optional. Nonce name. Default '_wpnonce'.
 * @param bool       $referer Optional. Whether to set the referer field for validation. Default true.
 * @param bool       $echo    Optional. Whether to display or return hidden form field. Default true.
 * @return string Nonce field HTML markup.
 */
function wp_nonce_field( $action = -1, $name = '_wpnonce', $referer = true, $echo = true ) {
    $name        = esc_attr( $name );
    $nonce_field = '<input type="hidden" id="' . $name . '" name="' . $name . '" value="' . wp_create_nonce( $action ) . '" />';

    if ( $referer ) {
        $nonce_field .= wp_referer_field( false );
    }

    if ( $echo ) {
        echo $nonce_field;
    }

    return $nonce_field;
}

/**
 * Display "Are You Sure" message to confirm the action being taken.
 *
 * If the action has the nonce explain message, then it will be displayed
 * along with the "Are you sure?" message.
 *
 * @since 2.0.4
 *
 * @param string $action The nonce action.
 */
function wp_nonce_ays( $action ) {
    // Default title and response code.
    $title         = __( 'Something went wrong.' );
    $response_code = 403;

    if ( 'log-out' === $action ) {
        $title = sprintf(
        /* translators: %s: Site title. */
          __( 'You are attempting to log out of %s' ),
          get_bloginfo( 'name' )
        );
        $html        = $title;
        $html       .= '</p><p>';
        $redirect_to = isset( $_REQUEST['redirect_to'] ) ? $_REQUEST['redirect_to'] : '';
        $html       .= sprintf(
        /* translators: %s: Logout URL. */
          __( 'Do you really want to <a href="%s">log out</a>?' ),
          wp_logout_url( $redirect_to )
        );
    } else {
        $html = __( 'The link you followed has expired.' );
        if ( wp_get_referer() ) {
            $html .= '</p><p>';
            $html .= sprintf(
              '<a href="%s">%s</a>',
              esc_url( remove_query_arg( 'updated', wp_get_referer() ) ),
              __( 'Please try again.' )
            );
        }
    }

    wp_die( $html, $title, $response_code );
}


/**
 * Retrieve the WordPress home page URL.
 *
 * If the constant named 'WP_HOME' exists, then it will be used and returned
 * by the function. This can be used to counter the redirection on your local
 * development environment.
 *
 * @since 2.2.0
 * @access private
 *
 * @see WP_HOME
 *
 * @param string $url URL for the home location.
 * @return string Homepage location.
 */
function _config_wp_home( $url = '' ) {
    if ( defined( 'WP_HOME' ) ) {
        return untrailingslashit( WP_HOME );
    }
    return $url;
}

/**
 * Retrieve the WordPress site URL.
 *
 * If the constant named 'WP_SITEURL' is defined, then the value in that
 * constant will always be returned. This can be used for debugging a site
 * on your localhost while not having to change the database to your URL.
 *
 * @since 2.2.0
 * @access private
 *
 * @see WP_SITEURL
 *
 * @param string $url URL to set the WordPress site location.
 * @return string The WordPress Site URL.
 */
function _config_wp_siteurl( $url = '' ) {
    if ( defined( 'WP_SITEURL' ) ) {
        return untrailingslashit( WP_SITEURL );
    }
    return $url;
}

/**
 * Delete the fresh site option.
 *
 * @since 4.7.0
 * @access private
 */
function _delete_option_fresh_site() {
    update_option( 'fresh_site', '0' );
}

/**
 * Set the localized direction for MCE plugin.
 *
 * Will only set the direction to 'rtl', if the WordPress locale has
 * the text direction set to 'rtl'.
 *
 * Fills in the 'directionality' setting, enables the 'directionality'
 * plugin, and adds the 'ltr' button to 'toolbar1', formerly
 * 'theme_advanced_buttons1' array keys. These keys are then returned
 * in the $mce_init (TinyMCE settings) array.
 *
 * @since 2.1.0
 * @access private
 *
 * @param array $mce_init MCE settings array.
 * @return array Direction set for 'rtl', if needed by locale.
 */
function _mce_set_direction( $mce_init ) {
    if ( is_rtl() ) {
        $mce_init['directionality'] = 'rtl';
        $mce_init['rtl_ui']         = true;

        if ( ! empty( $mce_init['plugins'] ) && strpos( $mce_init['plugins'], 'directionality' ) === false ) {
            $mce_init['plugins'] .= ',directionality';
        }

        if ( ! empty( $mce_init['toolbar1'] ) && ! preg_match( '/\bltr\b/', $mce_init['toolbar1'] ) ) {
            $mce_init['toolbar1'] .= ',ltr';
        }
    }

    return $mce_init;
}


/**
 * Convert smiley code to the icon graphic file equivalent.
 *
 * You can turn off smilies, by going to the write setting screen and unchecking
 * the box, or by setting 'use_smilies' option to false or removing the option.
 *
 * Plugins may override the default smiley list by setting the $wpsmiliestrans
 * to an array, with the key the code the blogger types in and the value the
 * image file.
 *
 * The $wp_smiliessearch global is for the regular expression and is set each
 * time the function is called.
 *
 * The full list of smilies can be found in the function and won't be listed in
 * the description. Probably should create a Codex page for it, so that it is
 * available.
 *
 * @global array $wpsmiliestrans
 * @global array $wp_smiliessearch
 *
 * @since 2.2.0
 */
function smilies_init() {
    global $wpsmiliestrans, $wp_smiliessearch;

    // Don't bother setting up smilies if they are disabled.
    if ( ! get_option( 'use_smilies' ) ) {
        return;
    }

    if ( ! isset( $wpsmiliestrans ) ) {
        $wpsmiliestrans = array(
          ':mrgreen:' => 'mrgreen.png',
          ':neutral:' => "\xf0\x9f\x98\x90",
          ':twisted:' => "\xf0\x9f\x98\x88",
          ':arrow:'   => "\xe2\x9e\xa1",
          ':shock:'   => "\xf0\x9f\x98\xaf",
          ':smile:'   => "\xf0\x9f\x99\x82",
          ':???:'     => "\xf0\x9f\x98\x95",
          ':cool:'    => "\xf0\x9f\x98\x8e",
          ':evil:'    => "\xf0\x9f\x91\xbf",
          ':grin:'    => "\xf0\x9f\x98\x80",
          ':idea:'    => "\xf0\x9f\x92\xa1",
          ':oops:'    => "\xf0\x9f\x98\xb3",
          ':razz:'    => "\xf0\x9f\x98\x9b",
          ':roll:'    => "\xf0\x9f\x99\x84",
          ':wink:'    => "\xf0\x9f\x98\x89",
          ':cry:'     => "\xf0\x9f\x98\xa5",
          ':eek:'     => "\xf0\x9f\x98\xae",
          ':lol:'     => "\xf0\x9f\x98\x86",
          ':mad:'     => "\xf0\x9f\x98\xa1",
          ':sad:'     => "\xf0\x9f\x99\x81",
          '8-)'       => "\xf0\x9f\x98\x8e",
          '8-O'       => "\xf0\x9f\x98\xaf",
          ':-('       => "\xf0\x9f\x99\x81",
          ':-)'       => "\xf0\x9f\x99\x82",
          ':-?'       => "\xf0\x9f\x98\x95",
          ':-D'       => "\xf0\x9f\x98\x80",
          ':-P'       => "\xf0\x9f\x98\x9b",
          ':-o'       => "\xf0\x9f\x98\xae",
          ':-x'       => "\xf0\x9f\x98\xa1",
          ':-|'       => "\xf0\x9f\x98\x90",
          ';-)'       => "\xf0\x9f\x98\x89",
          // This one transformation breaks regular text with frequency.
          //     '8)' => "\xf0\x9f\x98\x8e",
          '8O'        => "\xf0\x9f\x98\xaf",
          ':('        => "\xf0\x9f\x99\x81",
          ':)'        => "\xf0\x9f\x99\x82",
          ':?'        => "\xf0\x9f\x98\x95",
          ':D'        => "\xf0\x9f\x98\x80",
          ':P'        => "\xf0\x9f\x98\x9b",
          ':o'        => "\xf0\x9f\x98\xae",
          ':x'        => "\xf0\x9f\x98\xa1",
          ':|'        => "\xf0\x9f\x98\x90",
          ';)'        => "\xf0\x9f\x98\x89",
          ':!:'       => "\xe2\x9d\x97",
          ':?:'       => "\xe2\x9d\x93",
        );
    }

    /**
     * Filters all the smilies.
     *
     * This filter must be added before `smilies_init` is run, as
     * it is normally only run once to setup the smilies regex.
     *
     * @since 4.7.0
     *
     * @param string[] $wpsmiliestrans List of the smilies' hexadecimal representations, keyed by their smily code.
     */
    $wpsmiliestrans = apply_filters( 'smilies', $wpsmiliestrans );

    if ( count( $wpsmiliestrans ) == 0 ) {
        return;
    }

    /*
	 * NOTE: we sort the smilies in reverse key order. This is to make sure
	 * we match the longest possible smilie (:???: vs :?) as the regular
	 * expression used below is first-match
	 */
    krsort( $wpsmiliestrans );

    $spaces = wp_spaces_regexp();

    // Begin first "subpattern".
    $wp_smiliessearch = '/(?<=' . $spaces . '|^)';

    $subchar = '';
    foreach ( (array) $wpsmiliestrans as $smiley => $img ) {
        $firstchar = substr( $smiley, 0, 1 );
        $rest      = substr( $smiley, 1 );

        // New subpattern?
        if ( $firstchar != $subchar ) {
            if ( '' !== $subchar ) {
                $wp_smiliessearch .= ')(?=' . $spaces . '|$)';  // End previous "subpattern".
                $wp_smiliessearch .= '|(?<=' . $spaces . '|^)'; // Begin another "subpattern".
            }
            $subchar           = $firstchar;
            $wp_smiliessearch .= preg_quote( $firstchar, '/' ) . '(?:';
        } else {
            $wp_smiliessearch .= '|';
        }
        $wp_smiliessearch .= preg_quote( $rest, '/' );
    }

    $wp_smiliessearch .= ')(?=' . $spaces . '|$)/m';

}

/**
 * Merges user defined arguments into defaults array.
 *
 * This function is used throughout WordPress to allow for both string or array
 * to be merged into another array.
 *
 * @since 2.2.0
 * @since 2.3.0 `$args` can now also be an object.
 *
 * @param string|array|object $args     Value to merge with $defaults.
 * @param array               $defaults Optional. Array that serves as the defaults.
 *                                      Default empty array.
 * @return array Merged user defined values with defaults.
 */
function wp_parse_args( $args, $defaults = array() ) {
    if ( is_object( $args ) ) {
        $parsed_args = get_object_vars( $args );
    } elseif ( is_array( $args ) ) {
        $parsed_args =& $args;
    } else {
        wp_parse_str( $args, $parsed_args );
    }

    if ( is_array( $defaults ) && $defaults ) {
        return array_merge( $defaults, $parsed_args );
    }
    return $parsed_args;
}

/**
 * Converts a comma- or space-separated list of scalar values to an array.
 *
 * @since 5.1.0
 *
 * @param array|string $list List of values.
 * @return array Array of values.
 */
function wp_parse_list( $list ) {
    if ( ! is_array( $list ) ) {
        return preg_split( '/[\s,]+/', $list, -1, PREG_SPLIT_NO_EMPTY );
    }

    return $list;
}

/**
 * Cleans up an array, comma- or space-separated list of IDs.
 *
 * @since 3.0.0
 * @since 5.1.0 Refactored to use wp_parse_list().
 *
 * @param array|string $list List of IDs.
 * @return int[] Sanitized array of IDs.
 */
function wp_parse_id_list( $list ) {
    $list = wp_parse_list( $list );

    return array_unique( array_map( 'absint', $list ) );
}

/**
 * Cleans up an array, comma- or space-separated list of slugs.
 *
 * @since 4.7.0
 * @since 5.1.0 Refactored to use wp_parse_list().
 *
 * @param array|string $list List of slugs.
 * @return string[] Sanitized array of slugs.
 */
function wp_parse_slug_list( $list ) {
    $list = wp_parse_list( $list );

    return array_unique( array_map( 'sanitize_title', $list ) );
}

/**
 * Extract a slice of an array, given a list of keys.
 *
 * @since 3.1.0
 *
 * @param array $array The original array.
 * @param array $keys  The list of keys.
 * @return array The array slice.
 */
function wp_array_slice_assoc( $array, $keys ) {
    $slice = array();

    foreach ( $keys as $key ) {
        if ( isset( $array[ $key ] ) ) {
            $slice[ $key ] = $array[ $key ];
        }
    }

    return $slice;
}

/**
 * Accesses an array in depth based on a path of keys.
 *
 * It is the PHP equivalent of JavaScript's `lodash.get()` and mirroring it may help other components
 * retain some symmetry between client and server implementations.
 *
 * Example usage:
 *
 *     $array = array(
 *         'a' => array(
 *             'b' => array(
 *                 'c' => 1,
 *             ),
 *         ),
 *     );
 *     _wp_array_get( $array, array( 'a', 'b', 'c' ) );
 *
 * @internal
 *
 * @since 5.6.0
 * @access private
 *
 * @param array $array   An array from which we want to retrieve some information.
 * @param array $path    An array of keys describing the path with which to retrieve information.
 * @param mixed $default The return value if the path does not exist within the array,
 *                       or if `$array` or `$path` are not arrays.
 * @return mixed The value from the path specified.
 */
function _wp_array_get( $array, $path, $default = null ) {
    // Confirm $path is valid.
    if ( ! is_array( $path ) || 0 === count( $path ) ) {
        return $default;
    }

    foreach ( $path as $path_element ) {
        if (
          ! is_array( $array ) ||
          ( ! is_string( $path_element ) && ! is_integer( $path_element ) && ! is_null( $path_element ) ) ||
          ! array_key_exists( $path_element, $array )
        ) {
            return $default;
        }
        $array = $array[ $path_element ];
    }

    return $array;
}

/**
 * Sets an array in depth based on a path of keys.
 *
 * It is the PHP equivalent of JavaScript's `lodash.set()` and mirroring it may help other components
 * retain some symmetry between client and server implementations.
 *
 * Example usage:
 *
 *     $array = array();
 *     _wp_array_set( $array, array( 'a', 'b', 'c', 1 ) );
 *
 *     $array becomes:
 *     array(
 *         'a' => array(
 *             'b' => array(
 *                 'c' => 1,
 *             ),
 *         ),
 *     );
 *
 * @internal
 *
 * @since 5.8.0
 * @access private
 *
 * @param array $array An array that we want to mutate to include a specific value in a path.
 * @param array $path  An array of keys describing the path that we want to mutate.
 * @param mixed $value The value that will be set.
 */
function _wp_array_set( &$array, $path, $value = null ) {
    // Confirm $array is valid.
    if ( ! is_array( $array ) ) {
        return;
    }

    // Confirm $path is valid.
    if ( ! is_array( $path ) ) {
        return;
    }

    $path_length = count( $path );

    if ( 0 === $path_length ) {
        return;
    }

    foreach ( $path as $path_element ) {
        if (
          ! is_string( $path_element ) && ! is_integer( $path_element ) &&
          ! is_null( $path_element )
        ) {
            return;
        }
    }

    for ( $i = 0; $i < $path_length - 1; ++$i ) {
        $path_element = $path[ $i ];
        if (
          ! array_key_exists( $path_element, $array ) ||
          ! is_array( $array[ $path_element ] )
        ) {
            $array[ $path_element ] = array();
        }
        $array = &$array[ $path_element ]; // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.VariableRedeclaration
    }

    $array[ $path[ $i ] ] = $value;
}

/**
 * This function is trying to replicate what
 * lodash's kebabCase (JS library) does in the client.
 *
 * The reason we need this function is that we do some processing
 * in both the client and the server (e.g.: we generate
 * preset classes from preset slugs) that needs to
 * create the same output.
 *
 * We can't remove or update the client's library due to backward compatibility
 * (some of the output of lodash's kebabCase is saved in the post content).
 * We have to make the server behave like the client.
 *
 * Changes to this function should follow updates in the client
 * with the same logic.
 *
 * @link https://github.com/lodash/lodash/blob/4.17/dist/lodash.js#L14369
 * @link https://github.com/lodash/lodash/blob/4.17/dist/lodash.js#L278
 * @link https://github.com/lodash-php/lodash-php/blob/master/src/String/kebabCase.php
 * @link https://github.com/lodash-php/lodash-php/blob/master/src/internal/unicodeWords.php
 *
 * @param string $string The string to kebab-case.
 *
 * @return string kebab-cased-string.
 */
function _wp_to_kebab_case( $string ) {
    //phpcs:disable WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase
    // ignore the camelCase names for variables so the names are the same as lodash
    // so comparing and porting new changes is easier.

    /*
	 * Some notable things we've removed compared to the lodash version are:
	 *
	 * - non-alphanumeric characters: rsAstralRange, rsEmoji, etc
	 * - the groups that processed the apostrophe, as it's removed before passing the string to preg_match: rsApos, rsOptContrLower, and rsOptContrUpper
	 *
	 */

    /** Used to compose unicode character classes. */
    $rsLowerRange       = 'a-z\\xdf-\\xf6\\xf8-\\xff';
    $rsNonCharRange     = '\\x00-\\x2f\\x3a-\\x40\\x5b-\\x60\\x7b-\\xbf';
    $rsPunctuationRange = '\\x{2000}-\\x{206f}';
    $rsSpaceRange       = ' \\t\\x0b\\f\\xa0\\x{feff}\\n\\r\\x{2028}\\x{2029}\\x{1680}\\x{180e}\\x{2000}\\x{2001}\\x{2002}\\x{2003}\\x{2004}\\x{2005}\\x{2006}\\x{2007}\\x{2008}\\x{2009}\\x{200a}\\x{202f}\\x{205f}\\x{3000}';
    $rsUpperRange       = 'A-Z\\xc0-\\xd6\\xd8-\\xde';
    $rsBreakRange       = $rsNonCharRange . $rsPunctuationRange . $rsSpaceRange;

    /** Used to compose unicode capture groups. */
    $rsBreak  = '[' . $rsBreakRange . ']';
    $rsDigits = '\\d+'; // The last lodash version in GitHub uses a single digit here and expands it when in use.
    $rsLower  = '[' . $rsLowerRange . ']';
    $rsMisc   = '[^' . $rsBreakRange . $rsDigits . $rsLowerRange . $rsUpperRange . ']';
    $rsUpper  = '[' . $rsUpperRange . ']';

    /** Used to compose unicode regexes. */
    $rsMiscLower = '(?:' . $rsLower . '|' . $rsMisc . ')';
    $rsMiscUpper = '(?:' . $rsUpper . '|' . $rsMisc . ')';
    $rsOrdLower  = '\\d*(?:1st|2nd|3rd|(?![123])\\dth)(?=\\b|[A-Z_])';
    $rsOrdUpper  = '\\d*(?:1ST|2ND|3RD|(?![123])\\dTH)(?=\\b|[a-z_])';

    $regexp = '/' . implode(
        '|',
        array(
          $rsUpper . '?' . $rsLower . '+' . '(?=' . implode( '|', array( $rsBreak, $rsUpper, '$' ) ) . ')',
          $rsMiscUpper . '+' . '(?=' . implode( '|', array( $rsBreak, $rsUpper . $rsMiscLower, '$' ) ) . ')',
          $rsUpper . '?' . $rsMiscLower . '+',
          $rsUpper . '+',
          $rsOrdUpper,
          $rsOrdLower,
          $rsDigits,
        )
      ) . '/u';

    preg_match_all( $regexp, str_replace( "'", '', $string ), $matches );
    return strtolower( implode( '-', $matches[0] ) );
    //phpcs:enable WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase
}

/**
 * Determines if the variable is a numeric-indexed array.
 *
 * @since 4.4.0
 *
 * @param mixed $data Variable to check.
 * @return bool Whether the variable is a list.
 */
function wp_is_numeric_array( $data ) {
    if ( ! is_array( $data ) ) {
        return false;
    }

    $keys        = array_keys( $data );
    $string_keys = array_filter( $keys, 'is_string' );

    return count( $string_keys ) === 0;
}

/**
 * Filters a list of objects, based on a set of key => value arguments.
 *
 * Retrieves the objects from the list that match the given arguments.
 * Key represents property name, and value represents property value.
 *
 * If an object has more properties than those specified in arguments,
 * that will not disqualify it. When using the 'AND' operator,
 * any missing properties will disqualify it.
 *
 * When using the `$field` argument, this function can also retrieve
 * a particular field from all matching objects, whereas wp_list_filter()
 * only does the filtering.
 *
 * @since 3.0.0
 * @since 4.7.0 Uses `WP_List_Util` class.
 *
 * @param array       $list     An array of objects to filter.
 * @param array       $args     Optional. An array of key => value arguments to match
 *                              against each object. Default empty array.
 * @param string      $operator Optional. The logical operation to perform. 'AND' means
 *                              all elements from the array must match. 'OR' means only
 *                              one element needs to match. 'NOT' means no elements may
 *                              match. Default 'AND'.
 * @param bool|string $field    Optional. A field from the object to place instead
 *                              of the entire object. Default false.
 * @return array A list of objects or object fields.
 */
function wp_filter_object_list( $list, $args = array(), $operator = 'and', $field = false ) {
    if ( ! is_array( $list ) ) {
        return array();
    }

    $util = new WP_List_Util( $list );

    $util->filter( $args, $operator );

    if ( $field ) {
        $util->pluck( $field );
    }

    return $util->get_output();
}

/**
 * Filters a list of objects, based on a set of key => value arguments.
 *
 * Retrieves the objects from the list that match the given arguments.
 * Key represents property name, and value represents property value.
 *
 * If an object has more properties than those specified in arguments,
 * that will not disqualify it. When using the 'AND' operator,
 * any missing properties will disqualify it.
 *
 * If you want to retrieve a particular field from all matching objects,
 * use wp_filter_object_list() instead.
 *
 * @since 3.1.0
 * @since 4.7.0 Uses `WP_List_Util` class.
 * @since 5.9.0 Converted into a wrapper for `wp_filter_object_list()`.
 *
 * @param array  $list     An array of objects to filter.
 * @param array  $args     Optional. An array of key => value arguments to match
 *                         against each object. Default empty array.
 * @param string $operator Optional. The logical operation to perform. 'AND' means
 *                         all elements from the array must match. 'OR' means only
 *                         one element needs to match. 'NOT' means no elements may
 *                         match. Default 'AND'.
 * @return array Array of found values.
 */
function wp_list_filter( $list, $args = array(), $operator = 'AND' ) {
    return wp_filter_object_list( $list, $args, $operator );
}

/**
 * Plucks a certain field out of each object or array in an array.
 *
 * This has the same functionality and prototype of
 * array_column() (PHP 5.5) but also supports objects.
 *
 * @since 3.1.0
 * @since 4.0.0 $index_key parameter added.
 * @since 4.7.0 Uses `WP_List_Util` class.
 *
 * @param array      $list      List of objects or arrays.
 * @param int|string $field     Field from the object to place instead of the entire object.
 * @param int|string $index_key Optional. Field from the object to use as keys for the new array.
 *                              Default null.
 * @return array Array of found values. If `$index_key` is set, an array of found values with keys
 *               corresponding to `$index_key`. If `$index_key` is null, array keys from the original
 *               `$list` will be preserved in the results.
 */
function wp_list_pluck( $list, $field, $index_key = null ) {
    $util = new WP_List_Util( $list );

    return $util->pluck( $field, $index_key );
}

/**
 * Sorts an array of objects or arrays based on one or more orderby arguments.
 *
 * @since 4.7.0
 *
 * @param array        $list          An array of objects to sort.
 * @param string|array $orderby       Optional. Either the field name to order by or an array
 *                                    of multiple orderby fields as $orderby => $order.
 * @param string       $order         Optional. Either 'ASC' or 'DESC'. Only used if $orderby
 *                                    is a string.
 * @param bool         $preserve_keys Optional. Whether to preserve keys. Default false.
 * @return array The sorted array.
 */
function wp_list_sort( $list, $orderby = array(), $order = 'ASC', $preserve_keys = false ) {
    if ( ! is_array( $list ) ) {
        return array();
    }

    $util = new WP_List_Util( $list );

    return $util->sort( $orderby, $order, $preserve_keys );
}

/**
 * Determines if Widgets library should be loaded.
 *
 * Checks to make sure that the widgets library hasn't already been loaded.
 * If it hasn't, then it will load the widgets library and run an action hook.
 *
 * @since 2.2.0
 */
function wp_maybe_load_widgets() {
    /**
     * Filters whether to load the Widgets library.
     *
     * Returning a falsey value from the filter will effectively short-circuit
     * the Widgets library from loading.
     *
     * @since 2.8.0
     *
     * @param bool $wp_maybe_load_widgets Whether to load the Widgets library.
     *                                    Default true.
     */
    if ( ! apply_filters( 'load_default_widgets', true ) ) {
        return;
    }

    require_once ABSPATH . WPINC . '/default-widgets.php';

    add_action( '_admin_menu', 'wp_widgets_add_menu' );
}

/**
 * Append the Widgets menu to the themes main menu.
 *
 * @since 2.2.0
 * @since 5.9.3 Don't specify menu order when the active theme is a block theme.
 *
 * @global array $submenu
 */
function wp_widgets_add_menu() {
    global $submenu;

    if ( ! current_theme_supports( 'widgets' ) ) {
        return;
    }

    $menu_name = __( 'Widgets' );
    if ( wp_is_block_theme() ) {
        $submenu['themes.php'][] = array( $menu_name, 'edit_theme_options', 'widgets.php' );
    } else {
        $submenu['themes.php'][7] = array( $menu_name, 'edit_theme_options', 'widgets.php' );
    }

    ksort( $submenu['themes.php'], SORT_NUMERIC );
}

/**
 * Flush all output buffers for PHP 5.2.
 *
 * Make sure all output buffers are flushed before our singletons are destroyed.
 *
 * @since 2.2.0
 */
function wp_ob_end_flush_all() {
    $levels = ob_get_level();
    for ( $i = 0; $i < $levels; $i++ ) {
        ob_end_flush();
    }
}

/**
 * Load custom DB error or display WordPress DB error.
 *
 * If a file exists in the wp-content directory named db-error.php, then it will
 * be loaded instead of displaying the WordPress DB error. If it is not found,
 * then the WordPress DB error will be displayed instead.
 *
 * The WordPress DB error sets the HTTP status header to 500 to try to prevent
 * search engines from caching the message. Custom DB messages should do the
 * same.
 *
 * This function was backported to WordPress 2.3.2, but originally was added
 * in WordPress 2.5.0.
 *
 * @since 2.3.2
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 */
function dead_db() {
    global $wpdb;

    wp_load_translations_early();

    // Load custom DB error template, if present.
    if ( file_exists( WP_CONTENT_DIR . '/db-error.php' ) ) {
        require_once WP_CONTENT_DIR . '/db-error.php';
        die();
    }

    // If installing or in the admin, provide the verbose message.
    if ( wp_installing() || defined( 'WP_ADMIN' ) ) {
        wp_die( $wpdb->error );
    }

    // Otherwise, be terse.
    wp_die( '<h1>' . __( 'Error establishing a database connection' ) . '</h1>', __( 'Database Error' ) );
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
 * Is the server running earlier than 1.5.0 version of lighttpd?
 *
 * @since 2.5.0
 *
 * @return bool Whether the server is running lighttpd < 1.5.0.
 */
function is_lighttpd_before_150() {
    $server_parts    = explode( '/', isset( $_SERVER['SERVER_SOFTWARE'] ) ? $_SERVER['SERVER_SOFTWARE'] : '' );
    $server_parts[1] = isset( $server_parts[1] ) ? $server_parts[1] : '';

    return ( 'lighttpd' === $server_parts[0] && -1 == version_compare( $server_parts[1], '1.5.0' ) );
}

/**
 * Does the specified module exist in the Apache config?
 *
 * @since 2.5.0
 *
 * @global bool $is_apache
 *
 * @param string $mod     The module, e.g. mod_rewrite.
 * @param bool   $default Optional. The default return value if the module is not found. Default false.
 * @return bool Whether the specified module is loaded.
 */
function apache_mod_loaded( $mod, $default = false ) {
    global $is_apache;

    if ( ! $is_apache ) {
        return false;
    }

    if ( function_exists( 'apache_get_modules' ) ) {
        $mods = apache_get_modules();
        if ( in_array( $mod, $mods, true ) ) {
            return true;
        }
    } elseif ( function_exists( 'phpinfo' ) && false === strpos( ini_get( 'disable_functions' ), 'phpinfo' ) ) {
        ob_start();
        phpinfo( 8 );
        $phpinfo = ob_get_clean();
        if ( false !== strpos( $phpinfo, $mod ) ) {
            return true;
        }
    }

    return $default;
}

/**
 * Check if IIS 7+ supports pretty permalinks.
 *
 * @since 2.8.0
 *
 * @global bool $is_iis7
 *
 * @return bool Whether IIS7 supports permalinks.
 */
function iis7_supports_permalinks() {
    global $is_iis7;

    $supports_permalinks = false;
    if ( $is_iis7 ) {
        /* First we check if the DOMDocument class exists. If it does not exist, then we cannot
		 * easily update the xml configuration file, hence we just bail out and tell user that
		 * pretty permalinks cannot be used.
		 *
		 * Next we check if the URL Rewrite Module 1.1 is loaded and enabled for the web site. When
		 * URL Rewrite 1.1 is loaded it always sets a server variable called 'IIS_UrlRewriteModule'.
		 * Lastly we make sure that PHP is running via FastCGI. This is important because if it runs
		 * via ISAPI then pretty permalinks will not work.
		 */
        $supports_permalinks = class_exists( 'DOMDocument', false ) && isset( $_SERVER['IIS_UrlRewriteModule'] ) && ( 'cgi-fcgi' === PHP_SAPI );
    }

    /**
     * Filters whether IIS 7+ supports pretty permalinks.
     *
     * @since 2.8.0
     *
     * @param bool $supports_permalinks Whether IIS7 supports permalinks. Default false.
     */
    return apply_filters( 'iis7_supports_permalinks', $supports_permalinks );
}

/**
 * Validates a file name and path against an allowed set of rules.
 *
 * A return value of `1` means the file path contains directory traversal.
 *
 * A return value of `2` means the file path contains a Windows drive path.
 *
 * A return value of `3` means the file is not in the allowed files list.
 *
 * @since 1.2.0
 *
 * @param string   $file          File path.
 * @param string[] $allowed_files Optional. Array of allowed files.
 * @return int 0 means nothing is wrong, greater than 0 means something was wrong.
 */
function validate_file( $file, $allowed_files = array() ) {
    if ( ! is_scalar( $file ) || '' === $file ) {
        return 0;
    }

    // `../` on its own is not allowed:
    if ( '../' === $file ) {
        return 1;
    }

    // More than one occurrence of `../` is not allowed:
    if ( preg_match_all( '#\.\./#', $file, $matches, PREG_SET_ORDER ) && ( count( $matches ) > 1 ) ) {
        return 1;
    }

    // `../` which does not occur at the end of the path is not allowed:
    if ( false !== strpos( $file, '../' ) && '../' !== mb_substr( $file, -3, 3 ) ) {
        return 1;
    }

    // Files not in the allowed file list are not allowed:
    if ( ! empty( $allowed_files ) && ! in_array( $file, $allowed_files, true ) ) {
        return 3;
    }

    // Absolute Windows drive paths are not allowed:
    if ( ':' === substr( $file, 1, 1 ) ) {
        return 2;
    }

    return 0;
}

/**
 * Whether to force SSL used for the Administration Screens.
 *
 * @since 2.6.0
 *
 * @param string|bool $force Optional. Whether to force SSL in admin screens. Default null.
 * @return bool True if forced, false if not forced.
 */
function force_ssl_admin( $force = null ) {
    static $forced = false;

    if ( ! is_null( $force ) ) {
        $old_forced = $forced;
        $forced     = $force;
        return $old_forced;
    }

    return $forced;
}

/**
 * Guess the URL for the site.
 *
 * Will remove wp-admin links to retrieve only return URLs not in the wp-admin
 * directory.
 *
 * @since 2.6.0
 *
 * @return string The guessed URL.
 */
function wp_guess_url() {
    if ( defined( 'WP_SITEURL' ) && '' !== WP_SITEURL ) {
        $url = WP_SITEURL;
    } else {
        $abspath_fix         = str_replace( '\\', '/', ABSPATH );
        $script_filename_dir = dirname( $_SERVER['SCRIPT_FILENAME'] );

        // The request is for the admin.
        if ( strpos( $_SERVER['REQUEST_URI'], 'wp-admin' ) !== false || strpos( $_SERVER['REQUEST_URI'], 'wp-login.php' ) !== false ) {
            $path = preg_replace( '#/(wp-admin/.*|wp-login.php)#i', '', $_SERVER['REQUEST_URI'] );

            // The request is for a file in ABSPATH.
        } elseif ( $script_filename_dir . '/' === $abspath_fix ) {
            // Strip off any file/query params in the path.
            $path = preg_replace( '#/[^/]*$#i', '', $_SERVER['PHP_SELF'] );

        } else {
            if ( false !== strpos( $_SERVER['SCRIPT_FILENAME'], $abspath_fix ) ) {
                // Request is hitting a file inside ABSPATH.
                $directory = str_replace( ABSPATH, '', $script_filename_dir );
                // Strip off the subdirectory, and any file/query params.
                $path = preg_replace( '#/' . preg_quote( $directory, '#' ) . '/[^/]*$#i', '', $_SERVER['REQUEST_URI'] );
            } elseif ( false !== strpos( $abspath_fix, $script_filename_dir ) ) {
                // Request is hitting a file above ABSPATH.
                $subdirectory = substr( $abspath_fix, strpos( $abspath_fix, $script_filename_dir ) + strlen( $script_filename_dir ) );
                // Strip off any file/query params from the path, appending the subdirectory to the installation.
                $path = preg_replace( '#/[^/]*$#i', '', $_SERVER['REQUEST_URI'] ) . $subdirectory;
            } else {
                $path = $_SERVER['REQUEST_URI'];
            }
        }

        $schema = is_ssl() ? 'https://' : 'http://'; // set_url_scheme() is not defined yet.
        $url    = $schema . $_SERVER['HTTP_HOST'] . $path;
    }

    return rtrim( $url, '/' );
}

/**
 * Temporarily suspend cache additions.
 *
 * Stops more data being added to the cache, but still allows cache retrieval.
 * This is useful for actions, such as imports, when a lot of data would otherwise
 * be almost uselessly added to the cache.
 *
 * Suspension lasts for a single page load at most. Remember to call this
 * function again if you wish to re-enable cache adds earlier.
 *
 * @since 3.3.0
 *
 * @param bool $suspend Optional. Suspends additions if true, re-enables them if false.
 * @return bool The current suspend setting
 */
function wp_suspend_cache_addition( $suspend = null ) {
    static $_suspend = false;

    if ( is_bool( $suspend ) ) {
        $_suspend = $suspend;
    }

    return $_suspend;
}

/**
 * Suspend cache invalidation.
 *
 * Turns cache invalidation on and off. Useful during imports where you don't want to do
 * invalidations every time a post is inserted. Callers must be sure that what they are
 * doing won't lead to an inconsistent cache when invalidation is suspended.
 *
 * @since 2.7.0
 *
 * @global bool $_wp_suspend_cache_invalidation
 *
 * @param bool $suspend Optional. Whether to suspend or enable cache invalidation. Default true.
 * @return bool The current suspend setting.
 */
function wp_suspend_cache_invalidation( $suspend = true ) {
    global $_wp_suspend_cache_invalidation;

    $current_suspend                = $_wp_suspend_cache_invalidation;
    $_wp_suspend_cache_invalidation = $suspend;
    return $current_suspend;
}

/**
 * Determine whether a site is the main site of the current network.
 *
 * @since 3.0.0
 * @since 4.9.0 The `$network_id` parameter was added.
 *
 * @param int $site_id    Optional. Site ID to test. Defaults to current site.
 * @param int $network_id Optional. Network ID of the network to check for.
 *                        Defaults to current network.
 * @return bool True if $site_id is the main site of the network, or if not
 *              running Multisite.
 */
function is_main_site( $site_id = null, $network_id = null ) {
    if ( ! is_multisite() ) {
        return true;
    }

    if ( ! $site_id ) {
        $site_id = get_current_blog_id();
    }

    $site_id = (int) $site_id;

    return get_main_site_id( $network_id ) === $site_id;
}

/**
 * Gets the main site ID.
 *
 * @since 4.9.0
 *
 * @param int $network_id Optional. The ID of the network for which to get the main site.
 *                        Defaults to the current network.
 * @return int The ID of the main site.
 */
function get_main_site_id( $network_id = null ) {
    if ( ! is_multisite() ) {
        return get_current_blog_id();
    }

    $network = get_network( $network_id );
    if ( ! $network ) {
        return 0;
    }

    return $network->site_id;
}

/**
 * Determine whether a network is the main network of the Multisite installation.
 *
 * @since 3.7.0
 *
 * @param int $network_id Optional. Network ID to test. Defaults to current network.
 * @return bool True if $network_id is the main network, or if not running Multisite.
 */
function is_main_network( $network_id = null ) {
    if ( ! is_multisite() ) {
        return true;
    }

    if ( null === $network_id ) {
        $network_id = get_current_network_id();
    }

    $network_id = (int) $network_id;

    return ( get_main_network_id() === $network_id );
}

/**
 * Get the main network ID.
 *
 * @since 4.3.0
 *
 * @return int The ID of the main network.
 */
function get_main_network_id() {
    if ( ! is_multisite() ) {
        return 1;
    }

    $current_network = get_network();

    if ( defined( 'PRIMARY_NETWORK_ID' ) ) {
        $main_network_id = PRIMARY_NETWORK_ID;
    } elseif ( isset( $current_network->id ) && 1 === (int) $current_network->id ) {
        // If the current network has an ID of 1, assume it is the main network.
        $main_network_id = 1;
    } else {
        $_networks       = get_networks(
          array(
            'fields' => 'ids',
            'number' => 1,
          )
        );
        $main_network_id = array_shift( $_networks );
    }

    /**
     * Filters the main network ID.
     *
     * @since 4.3.0
     *
     * @param int $main_network_id The ID of the main network.
     */
    return (int) apply_filters( 'get_main_network_id', $main_network_id );
}

/**
 * Determine whether global terms are enabled.
 *
 * @since 3.0.0
 *
 * @return bool True if multisite and global terms enabled.
 */
function global_terms_enabled() {
    if ( ! is_multisite() ) {
        return false;
    }

    static $global_terms = null;
    if ( is_null( $global_terms ) ) {

        /**
         * Filters whether global terms are enabled.
         *
         * Returning a non-null value from the filter will effectively short-circuit the function
         * and return the value of the 'global_terms_enabled' site option instead.
         *
         * @since 3.0.0
         *
         * @param null $enabled Whether global terms are enabled.
         */
        $filter = apply_filters( 'global_terms_enabled', null );
        if ( ! is_null( $filter ) ) {
            $global_terms = (bool) $filter;
        } else {
            $global_terms = (bool) get_site_option( 'global_terms_enabled', false );
        }
    }
    return $global_terms;
}

/**
 * Determines whether site meta is enabled.
 *
 * This function checks whether the 'blogmeta' database table exists. The result is saved as
 * a setting for the main network, making it essentially a global setting. Subsequent requests
 * will refer to this setting instead of running the query.
 *
 * @since 5.1.0
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @return bool True if site meta is supported, false otherwise.
 */
function is_site_meta_supported() {
    global $wpdb;

    if ( ! is_multisite() ) {
        return false;
    }

    $network_id = get_main_network_id();

    $supported = get_network_option( $network_id, 'site_meta_supported', false );
    if ( false === $supported ) {
        $supported = $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->blogmeta}'" ) ? 1 : 0;

        update_network_option( $network_id, 'site_meta_supported', $supported );
    }

    return (bool) $supported;
}


/**
 * Strip close comment and close php tags from file headers used by WP.
 *
 * @since 2.8.0
 * @access private
 *
 * @see https://core.trac.wordpress.org/ticket/8497
 *
 * @param string $str Header comment to clean up.
 * @return string
 */
function _cleanup_header_comment( $str ) {
    return trim( preg_replace( '/\s*(?:\*\/|\?>).*/', '', $str ) );
}

/**
 * Permanently delete comments or posts of any type that have held a status
 * of 'trash' for the number of days defined in EMPTY_TRASH_DAYS.
 *
 * The default value of `EMPTY_TRASH_DAYS` is 30 (days).
 *
 * @since 2.9.0
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 */
function wp_scheduled_delete() {
    global $wpdb;

    $delete_timestamp = time() - ( DAY_IN_SECONDS * EMPTY_TRASH_DAYS );

    $posts_to_delete = $wpdb->get_results( $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_wp_trash_meta_time' AND meta_value < %d", $delete_timestamp ), ARRAY_A );

    foreach ( (array) $posts_to_delete as $post ) {
        $post_id = (int) $post['post_id'];
        if ( ! $post_id ) {
            continue;
        }

        $del_post = get_post( $post_id );

        if ( ! $del_post || 'trash' !== $del_post->post_status ) {
            delete_post_meta( $post_id, '_wp_trash_meta_status' );
            delete_post_meta( $post_id, '_wp_trash_meta_time' );
        } else {
            wp_delete_post( $post_id );
        }
    }

    $comments_to_delete = $wpdb->get_results( $wpdb->prepare( "SELECT comment_id FROM $wpdb->commentmeta WHERE meta_key = '_wp_trash_meta_time' AND meta_value < %d", $delete_timestamp ), ARRAY_A );

    foreach ( (array) $comments_to_delete as $comment ) {
        $comment_id = (int) $comment['comment_id'];
        if ( ! $comment_id ) {
            continue;
        }

        $del_comment = get_comment( $comment_id );

        if ( ! $del_comment || 'trash' !== $del_comment->comment_approved ) {
            delete_comment_meta( $comment_id, '_wp_trash_meta_time' );
            delete_comment_meta( $comment_id, '_wp_trash_meta_status' );
        } else {
            wp_delete_comment( $del_comment );
        }
    }
}

/**
 * Retrieve metadata from a file.
 *
 * Searches for metadata in the first 8 KB of a file, such as a plugin or theme.
 * Each piece of metadata must be on its own line. Fields can not span multiple
 * lines, the value will get cut at the end of the first line.
 *
 * If the file data is not within that first 8 KB, then the author should correct
 * their plugin file and move the data headers to the top.
 *
 * @link https://codex.wordpress.org/File_Header
 *
 * @since 2.9.0
 *
 * @param string $file            Absolute path to the file.
 * @param array  $default_headers List of headers, in the format `array( 'HeaderKey' => 'Header Name' )`.
 * @param string $context         Optional. If specified adds filter hook {@see 'extra_$context_headers'}.
 *                                Default empty.
 * @return string[] Array of file header values keyed by header name.
 */
function get_file_data( $file, $default_headers, $context = '' ) {
    // We don't need to write to the file, so just open for reading.
    $fp = fopen( $file, 'r' );

    if ( $fp ) {
        // Pull only the first 8 KB of the file in.
        $file_data = fread( $fp, 8 * KB_IN_BYTES );

        // PHP will close file handle, but we are good citizens.
        fclose( $fp );
    } else {
        $file_data = '';
    }

    // Make sure we catch CR-only line endings.
    $file_data = str_replace( "\r", "\n", $file_data );

    /**
     * Filters extra file headers by context.
     *
     * The dynamic portion of the hook name, `$context`, refers to
     * the context where extra headers might be loaded.
     *
     * @since 2.9.0
     *
     * @param array $extra_context_headers Empty array by default.
     */
    $extra_headers = $context ? apply_filters( "extra_{$context}_headers", array() ) : array();
    if ( $extra_headers ) {
        $extra_headers = array_combine( $extra_headers, $extra_headers ); // Keys equal values.
        $all_headers   = array_merge( $extra_headers, (array) $default_headers );
    } else {
        $all_headers = $default_headers;
    }

    foreach ( $all_headers as $field => $regex ) {
        if ( preg_match( '/^(?:[ \t]*<\?php)?[ \t\/*#@]*' . preg_quote( $regex, '/' ) . ':(.*)$/mi', $file_data, $match ) && $match[1] ) {
            $all_headers[ $field ] = _cleanup_header_comment( $match[1] );
        } else {
            $all_headers[ $field ] = '';
        }
    }

    return $all_headers;
}


/**
 * Send a HTTP header to disable content type sniffing in browsers which support it.
 *
 * @since 3.0.0
 *
 * @see https://blogs.msdn.com/ie/archive/2008/07/02/ie8-security-part-v-comprehensive-protection.aspx
 * @see https://src.chromium.org/viewvc/chrome?view=rev&revision=6985
 */
function send_nosniff_header() {
    header( 'X-Content-Type-Options: nosniff' );
}

/**
 * Return a MySQL expression for selecting the week number based on the start_of_week option.
 *
 * @ignore
 * @since 3.0.0
 *
 * @param string $column Database column.
 * @return string SQL clause.
 */
function _wp_mysql_week( $column ) {
    $start_of_week = (int) get_option( 'start_of_week' );
    switch ( $start_of_week ) {
        case 1:
            return "WEEK( $column, 1 )";
        case 2:
        case 3:
        case 4:
        case 5:
        case 6:
            return "WEEK( DATE_SUB( $column, INTERVAL $start_of_week DAY ), 0 )";
        case 0:
        default:
            return "WEEK( $column, 0 )";
    }
}

/**
 * Find hierarchy loops using a callback function that maps object IDs to parent IDs.
 *
 * @since 3.1.0
 * @access private
 *
 * @param callable $callback      Function that accepts ( ID, $callback_args ) and outputs parent_ID.
 * @param int      $start         The ID to start the loop check at.
 * @param int      $start_parent  The parent_ID of $start to use instead of calling $callback( $start ).
 *                                Use null to always use $callback
 * @param array    $callback_args Optional. Additional arguments to send to $callback.
 * @return array IDs of all members of loop.
 */
function wp_find_hierarchy_loop( $callback, $start, $start_parent, $callback_args = array() ) {
    $override = is_null( $start_parent ) ? array() : array( $start => $start_parent );

    $arbitrary_loop_member = wp_find_hierarchy_loop_tortoise_hare( $callback, $start, $override, $callback_args );
    if ( ! $arbitrary_loop_member ) {
        return array();
    }

    return wp_find_hierarchy_loop_tortoise_hare( $callback, $arbitrary_loop_member, $override, $callback_args, true );
}

/**
 * Use the "The Tortoise and the Hare" algorithm to detect loops.
 *
 * For every step of the algorithm, the hare takes two steps and the tortoise one.
 * If the hare ever laps the tortoise, there must be a loop.
 *
 * @since 3.1.0
 * @access private
 *
 * @param callable $callback      Function that accepts ( ID, callback_arg, ... ) and outputs parent_ID.
 * @param int      $start         The ID to start the loop check at.
 * @param array    $override      Optional. An array of ( ID => parent_ID, ... ) to use instead of $callback.
 *                                Default empty array.
 * @param array    $callback_args Optional. Additional arguments to send to $callback. Default empty array.
 * @param bool     $_return_loop  Optional. Return loop members or just detect presence of loop? Only set
 *                                to true if you already know the given $start is part of a loop (otherwise
 *                                the returned array might include branches). Default false.
 * @return mixed Scalar ID of some arbitrary member of the loop, or array of IDs of all members of loop if
 *               $_return_loop
 */
function wp_find_hierarchy_loop_tortoise_hare( $callback, $start, $override = array(), $callback_args = array(), $_return_loop = false ) {
    $tortoise        = $start;
    $hare            = $start;
    $evanescent_hare = $start;
    $return          = array();

    // Set evanescent_hare to one past hare.
    // Increment hare two steps.
    while (
      $tortoise
      &&
      ( $evanescent_hare = isset( $override[ $hare ] ) ? $override[ $hare ] : call_user_func_array( $callback, array_merge( array( $hare ), $callback_args ) ) )
      &&
      ( $hare = isset( $override[ $evanescent_hare ] ) ? $override[ $evanescent_hare ] : call_user_func_array( $callback, array_merge( array( $evanescent_hare ), $callback_args ) ) )
    ) {
        if ( $_return_loop ) {
            $return[ $tortoise ]        = true;
            $return[ $evanescent_hare ] = true;
            $return[ $hare ]            = true;
        }

        // Tortoise got lapped - must be a loop.
        if ( $tortoise == $evanescent_hare || $tortoise == $hare ) {
            return $_return_loop ? $return : $tortoise;
        }

        // Increment tortoise by one step.
        $tortoise = isset( $override[ $tortoise ] ) ? $override[ $tortoise ] : call_user_func_array( $callback, array_merge( array( $tortoise ), $callback_args ) );
    }

    return false;
}

/**
 * Send a HTTP header to limit rendering of pages to same origin iframes.
 *
 * @since 3.1.3
 *
 * @see https://developer.mozilla.org/en/the_x-frame-options_response_header
 */
function send_frame_options_header() {
    header( 'X-Frame-Options: SAMEORIGIN' );
}

/**
 * Retrieve a list of protocols to allow in HTML attributes.
 *
 * @since 3.3.0
 * @since 4.3.0 Added 'webcal' to the protocols array.
 * @since 4.7.0 Added 'urn' to the protocols array.
 * @since 5.3.0 Added 'sms' to the protocols array.
 * @since 5.6.0 Added 'irc6' and 'ircs' to the protocols array.
 *
 * @see wp_kses()
 * @see esc_url()
 *
 * @return string[] Array of allowed protocols. Defaults to an array containing 'http', 'https',
 *                  'ftp', 'ftps', 'mailto', 'news', 'irc', 'irc6', 'ircs', 'gopher', 'nntp', 'feed',
 *                  'telnet', 'mms', 'rtsp', 'sms', 'svn', 'tel', 'fax', 'xmpp', 'webcal', and 'urn'.
 *                  This covers all common link protocols, except for 'javascript' which should not
 *                  be allowed for untrusted users.
 */
function wp_allowed_protocols() {
    static $protocols = array();

    if ( empty( $protocols ) ) {
        $protocols = array( 'http', 'https', 'ftp', 'ftps', 'mailto', 'news', 'irc', 'irc6', 'ircs', 'gopher', 'nntp', 'feed', 'telnet', 'mms', 'rtsp', 'sms', 'svn', 'tel', 'fax', 'xmpp', 'webcal', 'urn' );
    }

    if ( ! did_action( 'wp_loaded' ) ) {
        /**
         * Filters the list of protocols allowed in HTML attributes.
         *
         * @since 3.0.0
         *
         * @param string[] $protocols Array of allowed protocols e.g. 'http', 'ftp', 'tel', and more.
         */
        $protocols = array_unique( (array) apply_filters( 'kses_allowed_protocols', $protocols ) );
    }

    return $protocols;
}

/**
 * Return a comma-separated string of functions that have been called to get
 * to the current point in code.
 *
 * @since 3.4.0
 *
 * @see https://core.trac.wordpress.org/ticket/19589
 *
 * @param string $ignore_class Optional. A class to ignore all function calls within - useful
 *                             when you want to just give info about the callee. Default null.
 * @param int    $skip_frames  Optional. A number of stack frames to skip - useful for unwinding
 *                             back to the source of the issue. Default 0.
 * @param bool   $pretty       Optional. Whether or not you want a comma separated string or raw
 *                             array returned. Default true.
 * @return string|array Either a string containing a reversed comma separated trace or an array
 *                      of individual calls.
 */
function wp_debug_backtrace_summary( $ignore_class = null, $skip_frames = 0, $pretty = true ) {
    static $truncate_paths;

    $trace       = debug_backtrace( false );
    $caller      = array();
    $check_class = ! is_null( $ignore_class );
    $skip_frames++; // Skip this function.

    if ( ! isset( $truncate_paths ) ) {
        $truncate_paths = array(
          wp_normalize_path( WP_CONTENT_DIR ),
          wp_normalize_path( ABSPATH ),
        );
    }

    foreach ( $trace as $call ) {
        if ( $skip_frames > 0 ) {
            $skip_frames--;
        } elseif ( isset( $call['class'] ) ) {
            if ( $check_class && $ignore_class == $call['class'] ) {
                continue; // Filter out calls.
            }

            $caller[] = "{$call['class']}{$call['type']}{$call['function']}";
        } else {
            if ( in_array( $call['function'], array( 'do_action', 'apply_filters', 'do_action_ref_array', 'apply_filters_ref_array' ), true ) ) {
                $caller[] = "{$call['function']}('{$call['args'][0]}')";
            } elseif ( in_array( $call['function'], array( 'include', 'include_once', 'require', 'require_once' ), true ) ) {
                $filename = isset( $call['args'][0] ) ? $call['args'][0] : '';
                $caller[] = $call['function'] . "('" . str_replace( $truncate_paths, '', wp_normalize_path( $filename ) ) . "')";
            } else {
                $caller[] = $call['function'];
            }
        }
    }
    if ( $pretty ) {
        return implode( ', ', array_reverse( $caller ) );
    } else {
        return $caller;
    }
}

/**
 * Retrieve IDs that are not already present in the cache.
 *
 * @since 3.4.0
 * @access private
 *
 * @param int[]  $object_ids Array of IDs.
 * @param string $cache_key  The cache bucket to check against.
 * @return int[] Array of IDs not present in the cache.
 */
function _get_non_cached_ids( $object_ids, $cache_key ) {
    $non_cached_ids = array();
    $cache_values   = wp_cache_get_multiple( $object_ids, $cache_key );

    foreach ( $cache_values as $id => $value ) {
        if ( ! $value ) {
            $non_cached_ids[] = (int) $id;
        }
    }

    return $non_cached_ids;
}

/**
 * Test if the current device has the capability to upload files.
 *
 * @since 3.4.0
 * @access private
 *
 * @return bool Whether the device is able to upload files.
 */
function _device_can_upload() {
    if ( ! wp_is_mobile() ) {
        return true;
    }

    $ua = $_SERVER['HTTP_USER_AGENT'];

    if ( strpos( $ua, 'iPhone' ) !== false
      || strpos( $ua, 'iPad' ) !== false
      || strpos( $ua, 'iPod' ) !== false ) {
        return preg_match( '#OS ([\d_]+) like Mac OS X#', $ua, $version ) && version_compare( $version[1], '6', '>=' );
    }

    return true;
}

/**
 * Test if a given path is a stream URL
 *
 * @since 3.5.0
 *
 * @param string $path The resource path or URL.
 * @return bool True if the path is a stream URL.
 */
function wp_is_stream( $path ) {
    $scheme_separator = strpos( $path, '://' );

    if ( false === $scheme_separator ) {
        // $path isn't a stream.
        return false;
    }

    $stream = substr( $path, 0, $scheme_separator );

    return in_array( $stream, stream_get_wrappers(), true );
}


/**
 * Load the auth check for monitoring whether the user is still logged in.
 *
 * Can be disabled with remove_action( 'admin_enqueue_scripts', 'wp_auth_check_load' );
 *
 * This is disabled for certain screens where a login screen could cause an
 * inconvenient interruption. A filter called {@see 'wp_auth_check_load'} can be used
 * for fine-grained control.
 *
 * @since 3.6.0
 */
function wp_auth_check_load() {
    if ( ! is_admin() && ! is_user_logged_in() ) {
        return;
    }

    if ( defined( 'IFRAME_REQUEST' ) ) {
        return;
    }

    $screen = get_current_screen();
    $hidden = array( 'update', 'update-network', 'update-core', 'update-core-network', 'upgrade', 'upgrade-network', 'network' );
    $show   = ! in_array( $screen->id, $hidden, true );

    /**
     * Filters whether to load the authentication check.
     *
     * Returning a falsey value from the filter will effectively short-circuit
     * loading the authentication check.
     *
     * @since 3.6.0
     *
     * @param bool      $show   Whether to load the authentication check.
     * @param WP_Screen $screen The current screen object.
     */
    if ( apply_filters( 'wp_auth_check_load', $show, $screen ) ) {
        wp_enqueue_style( 'wp-auth-check' );
        wp_enqueue_script( 'wp-auth-check' );

        add_action( 'admin_print_footer_scripts', 'wp_auth_check_html', 5 );
        add_action( 'wp_print_footer_scripts', 'wp_auth_check_html', 5 );
    }
}

/**
 * Output the HTML that shows the wp-login dialog when the user is no longer logged in.
 *
 * @since 3.6.0
 */
function wp_auth_check_html() {
    $login_url      = wp_login_url();
    $current_domain = ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'];
    $same_domain    = ( strpos( $login_url, $current_domain ) === 0 );

    /**
     * Filters whether the authentication check originated at the same domain.
     *
     * @since 3.6.0
     *
     * @param bool $same_domain Whether the authentication check originated at the same domain.
     */
    $same_domain = apply_filters( 'wp_auth_check_same_domain', $same_domain );
    $wrap_class  = $same_domain ? 'hidden' : 'hidden fallback';

    ?>
    <div id="wp-auth-check-wrap" class="<?php echo $wrap_class; ?>">
        <div id="wp-auth-check-bg"></div>
        <div id="wp-auth-check">
            <button type="button" class="wp-auth-check-close button-link"><span class="screen-reader-text"><?php _e( 'Close dialog' ); ?></span></button>
            <?php

            if ( $same_domain ) {
                $login_src = add_query_arg(
                  array(
                    'interim-login' => '1',
                    'wp_lang'       => get_user_locale(),
                  ),
                  $login_url
                );
                ?>
                <div id="wp-auth-check-form" class="loading" data-src="<?php echo esc_url( $login_src ); ?>"></div>
                <?php
            }

            ?>
            <div class="wp-auth-fallback">
                <p><b class="wp-auth-fallback-expired" tabindex="0"><?php _e( 'Session expired' ); ?></b></p>
                <p><a href="<?php echo esc_url( $login_url ); ?>" target="_blank"><?php _e( 'Please log in again.' ); ?></a>
                    <?php _e( 'The login page will open in a new tab. After logging in you can close it and return to this page.' ); ?></p>
            </div>
        </div>
    </div>
    <?php
}

/**
 * Check whether a user is still logged in, for the heartbeat.
 *
 * Send a result that shows a log-in box if the user is no longer logged in,
 * or if their cookie is within the grace period.
 *
 * @since 3.6.0
 *
 * @global int $login_grace_period
 *
 * @param array $response  The Heartbeat response.
 * @return array The Heartbeat response with 'wp-auth-check' value set.
 */
function wp_auth_check( $response ) {
    $response['wp-auth-check'] = is_user_logged_in() && empty( $GLOBALS['login_grace_period'] );
    return $response;
}

/**
 * Return RegEx body to liberally match an opening HTML tag.
 *
 * Matches an opening HTML tag that:
 * 1. Is self-closing or
 * 2. Has no body but has a closing tag of the same name or
 * 3. Contains a body and a closing tag of the same name
 *
 * Note: this RegEx does not balance inner tags and does not attempt
 * to produce valid HTML
 *
 * @since 3.6.0
 *
 * @param string $tag An HTML tag name. Example: 'video'.
 * @return string Tag RegEx.
 */
function get_tag_regex( $tag ) {
    if ( empty( $tag ) ) {
        return '';
    }
    return sprintf( '<%1$s[^<]*(?:>[\s\S]*<\/%1$s>|\s*\/>)', tag_escape( $tag ) );
}

/**
 * Retrieve a canonical form of the provided charset appropriate for passing to PHP
 * functions such as htmlspecialchars() and charset HTML attributes.
 *
 * @since 3.6.0
 * @access private
 *
 * @see https://core.trac.wordpress.org/ticket/23688
 *
 * @param string $charset A charset name.
 * @return string The canonical form of the charset.
 */
function _canonical_charset( $charset ) {
    if ( 'utf-8' === strtolower( $charset ) || 'utf8' === strtolower( $charset ) ) {

        return 'UTF-8';
    }

    if ( 'iso-8859-1' === strtolower( $charset ) || 'iso8859-1' === strtolower( $charset ) ) {

        return 'ISO-8859-1';
    }

    return $charset;
}

/**
 * Set the mbstring internal encoding to a binary safe encoding when func_overload
 * is enabled.
 *
 * When mbstring.func_overload is in use for multi-byte encodings, the results from
 * strlen() and similar functions respect the utf8 characters, causing binary data
 * to return incorrect lengths.
 *
 * This function overrides the mbstring encoding to a binary-safe encoding, and
 * resets it to the users expected encoding afterwards through the
 * `reset_mbstring_encoding` function.
 *
 * It is safe to recursively call this function, however each
 * `mbstring_binary_safe_encoding()` call must be followed up with an equal number
 * of `reset_mbstring_encoding()` calls.
 *
 * @since 3.7.0
 *
 * @see reset_mbstring_encoding()
 *
 * @param bool $reset Optional. Whether to reset the encoding back to a previously-set encoding.
 *                    Default false.
 */
function mbstring_binary_safe_encoding( $reset = false ) {
    static $encodings  = array();
    static $overloaded = null;

    if ( is_null( $overloaded ) ) {
        if ( function_exists( 'mb_internal_encoding' )
          && ( (int) ini_get( 'mbstring.func_overload' ) & 2 ) // phpcs:ignore PHPCompatibility.IniDirectives.RemovedIniDirectives.mbstring_func_overloadDeprecated
        ) {
            $overloaded = true;
        } else {
            $overloaded = false;
        }
    }

    if ( false === $overloaded ) {
        return;
    }

    if ( ! $reset ) {
        $encoding = mb_internal_encoding();
        $encodings[] = $encoding;
        mb_internal_encoding( 'ISO-8859-1' );
    }

    if ( $reset && $encodings ) {
        $encoding = array_pop( $encodings );
        mb_internal_encoding( $encoding );
    }
}

/**
 * Reset the mbstring internal encoding to a users previously set encoding.
 *
 * @see mbstring_binary_safe_encoding()
 *
 * @since 3.7.0
 */
function reset_mbstring_encoding() {
    mbstring_binary_safe_encoding( true );
}

/**
 * Filter/validate a variable as a boolean.
 *
 * Alternative to `filter_var( $var, FILTER_VALIDATE_BOOLEAN )`.
 *
 * @since 4.0.0
 *
 * @param mixed $var Boolean value to validate.
 * @return bool Whether the value is validated.
 */
function wp_validate_boolean( $var ) {
    if ( is_bool( $var ) ) {
        return $var;
    }

    if ( is_string( $var ) && 'false' === strtolower( $var ) ) {
        return false;
    }

    return (bool) $var;
}

/**
 * Delete a file
 *
 * @since 4.2.0
 *
 * @param string $file The path to the file to delete.
 */
function wp_delete_file( $file ) {
    /**
     * Filters the path of the file to delete.
     *
     * @since 2.1.0
     *
     * @param string $file Path to the file to delete.
     */
    $delete = apply_filters( 'wp_delete_file', $file );
    if ( ! empty( $delete ) ) {
        @unlink( $delete );
    }
}

/**
 * Deletes a file if its path is within the given directory.
 *
 * @since 4.9.7
 *
 * @param string $file      Absolute path to the file to delete.
 * @param string $directory Absolute path to a directory.
 * @return bool True on success, false on failure.
 */
function wp_delete_file_from_directory( $file, $directory ) {
    if ( wp_is_stream( $file ) ) {
        $real_file      = $file;
        $real_directory = $directory;
    } else {
        $real_file      = realpath( wp_normalize_path( $file ) );
        $real_directory = realpath( wp_normalize_path( $directory ) );
    }

    if ( false !== $real_file ) {
        $real_file = wp_normalize_path( $real_file );
    }

    if ( false !== $real_directory ) {
        $real_directory = wp_normalize_path( $real_directory );
    }

    if ( false === $real_file || false === $real_directory || strpos( $real_file, trailingslashit( $real_directory ) ) !== 0 ) {
        return false;
    }

    wp_delete_file( $file );

    return true;
}

/**
 * Outputs a small JS snippet on preview tabs/windows to remove `window.name` on unload.
 *
 * This prevents reusing the same tab for a preview when the user has navigated away.
 *
 * @since 4.3.0
 *
 * @global WP_Post $post Global post object.
 */
function wp_post_preview_js() {
    global $post;

    if ( ! is_preview() || empty( $post ) ) {
        return;
    }

    // Has to match the window name used in post_submit_meta_box().
    $name = 'wp-preview-' . (int) $post->ID;

    ?>
    <script>
      ( function() {
        var query = document.location.search;

        if ( query && query.indexOf( 'preview=true' ) !== -1 ) {
          window.name = '<?php echo $name; ?>';
        }

        if ( window.addEventListener ) {
          window.addEventListener( 'unload', function() { window.name = ''; }, false );
        }
      }());
    </script>
    <?php
}

/**
 * Parses and formats a MySQL datetime (Y-m-d H:i:s) for ISO8601 (Y-m-d\TH:i:s).
 *
 * Explicitly strips timezones, as datetimes are not saved with any timezone
 * information. Including any information on the offset could be misleading.
 *
 * Despite historical function name, the output does not conform to RFC3339 format,
 * which must contain timezone.
 *
 * @since 4.4.0
 *
 * @param string $date_string Date string to parse and format.
 * @return string Date formatted for ISO8601 without time zone.
 */
function mysql_to_rfc3339( $date_string ) {
    return mysql2date( 'Y-m-d\TH:i:s', $date_string, false );
}

/**
 * Attempts to raise the PHP memory limit for memory intensive processes.
 *
 * Only allows raising the existing limit and prevents lowering it.
 *
 * @since 4.6.0
 *
 * @param string $context Optional. Context in which the function is called. Accepts either 'admin',
 *                        'image', or an arbitrary other context. If an arbitrary context is passed,
 *                        the similarly arbitrary {@see '$context_memory_limit'} filter will be
 *                        invoked. Default 'admin'.
 * @return int|string|false The limit that was set or false on failure.
 */
function wp_raise_memory_limit( $context = 'admin' ) {
    // Exit early if the limit cannot be changed.
    if ( false === wp_is_ini_value_changeable( 'memory_limit' ) ) {
        return false;
    }

    $current_limit     = ini_get( 'memory_limit' );
    $current_limit_int = wp_convert_hr_to_bytes( $current_limit );

    if ( -1 === $current_limit_int ) {
        return false;
    }

    $wp_max_limit     = WP_MAX_MEMORY_LIMIT;
    $wp_max_limit_int = wp_convert_hr_to_bytes( $wp_max_limit );
    $filtered_limit   = $wp_max_limit;

    switch ( $context ) {
        case 'admin':
            /**
             * Filters the maximum memory limit available for administration screens.
             *
             * This only applies to administrators, who may require more memory for tasks
             * like updates. Memory limits when processing images (uploaded or edited by
             * users of any role) are handled separately.
             *
             * The `WP_MAX_MEMORY_LIMIT` constant specifically defines the maximum memory
             * limit available when in the administration back end. The default is 256M
             * (256 megabytes of memory) or the original `memory_limit` php.ini value if
             * this is higher.
             *
             * @since 3.0.0
             * @since 4.6.0 The default now takes the original `memory_limit` into account.
             *
             * @param int|string $filtered_limit The maximum WordPress memory limit. Accepts an integer
             *                                   (bytes), or a shorthand string notation, such as '256M'.
             */
            $filtered_limit = apply_filters( 'admin_memory_limit', $filtered_limit );
            break;

        case 'image':
            /**
             * Filters the memory limit allocated for image manipulation.
             *
             * @since 3.5.0
             * @since 4.6.0 The default now takes the original `memory_limit` into account.
             *
             * @param int|string $filtered_limit Maximum memory limit to allocate for images.
             *                                   Default `WP_MAX_MEMORY_LIMIT` or the original
             *                                   php.ini `memory_limit`, whichever is higher.
             *                                   Accepts an integer (bytes), or a shorthand string
             *                                   notation, such as '256M'.
             */
            $filtered_limit = apply_filters( 'image_memory_limit', $filtered_limit );
            break;

        default:
            /**
             * Filters the memory limit allocated for arbitrary contexts.
             *
             * The dynamic portion of the hook name, `$context`, refers to an arbitrary
             * context passed on calling the function. This allows for plugins to define
             * their own contexts for raising the memory limit.
             *
             * @since 4.6.0
             *
             * @param int|string $filtered_limit Maximum memory limit to allocate for images.
             *                                   Default '256M' or the original php.ini `memory_limit`,
             *                                   whichever is higher. Accepts an integer (bytes), or a
             *                                   shorthand string notation, such as '256M'.
             */
            $filtered_limit = apply_filters( "{$context}_memory_limit", $filtered_limit );
            break;
    }

    $filtered_limit_int = wp_convert_hr_to_bytes( $filtered_limit );

    if ( -1 === $filtered_limit_int || ( $filtered_limit_int > $wp_max_limit_int && $filtered_limit_int > $current_limit_int ) ) {
        if ( false !== ini_set( 'memory_limit', $filtered_limit ) ) {
            return $filtered_limit;
        } else {
            return false;
        }
    } elseif ( -1 === $wp_max_limit_int || $wp_max_limit_int > $current_limit_int ) {
        if ( false !== ini_set( 'memory_limit', $wp_max_limit ) ) {
            return $wp_max_limit;
        } else {
            return false;
        }
    }

    return false;
}

/**
 * Generate a random UUID (version 4).
 *
 * @since 4.7.0
 *
 * @return string UUID.
 */
function wp_generate_uuid4() {
    return sprintf(
      '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
      mt_rand( 0, 0xffff ),
      mt_rand( 0, 0xffff ),
      mt_rand( 0, 0xffff ),
      mt_rand( 0, 0x0fff ) | 0x4000,
      mt_rand( 0, 0x3fff ) | 0x8000,
      mt_rand( 0, 0xffff ),
      mt_rand( 0, 0xffff ),
      mt_rand( 0, 0xffff )
    );
}

/**
 * Validates that a UUID is valid.
 *
 * @since 4.9.0
 *
 * @param mixed $uuid    UUID to check.
 * @param int   $version Specify which version of UUID to check against. Default is none,
 *                       to accept any UUID version. Otherwise, only version allowed is `4`.
 * @return bool The string is a valid UUID or false on failure.
 */
function wp_is_uuid( $uuid, $version = null ) {

    if ( ! is_string( $uuid ) ) {
        return false;
    }

    if ( is_numeric( $version ) ) {
        if ( 4 !== (int) $version ) {
            _doing_it_wrong( __FUNCTION__, __( 'Only UUID V4 is supported at this time.' ), '4.9.0' );
            return false;
        }
        $regex = '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/';
    } else {
        $regex = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/';
    }

    return (bool) preg_match( $regex, $uuid );
}

/**
 * Gets unique ID.
 *
 * This is a PHP implementation of Underscore's uniqueId method. A static variable
 * contains an integer that is incremented with each call. This number is returned
 * with the optional prefix. As such the returned value is not universally unique,
 * but it is unique across the life of the PHP process.
 *
 * @since 5.0.3
 *
 * @param string $prefix Prefix for the returned ID.
 * @return string Unique ID.
 */
function wp_unique_id( $prefix = '' ) {
    static $id_counter = 0;
    return $prefix . (string) ++$id_counter;
}

/**
 * Gets last changed date for the specified cache group.
 *
 * @since 4.7.0
 *
 * @param string $group Where the cache contents are grouped.
 * @return string UNIX timestamp with microseconds representing when the group was last changed.
 */
function wp_cache_get_last_changed( $group ) {
    $last_changed = wp_cache_get( 'last_changed', $group );

    if ( ! $last_changed ) {
        $last_changed = microtime();
        wp_cache_set( 'last_changed', $last_changed, $group );
    }

    return $last_changed;
}

/**
 * Send an email to the old site admin email address when the site admin email address changes.
 *
 * @since 4.9.0
 *
 * @param string $old_email   The old site admin email address.
 * @param string $new_email   The new site admin email address.
 * @param string $option_name The relevant database option name.
 */
function wp_site_admin_email_change_notification( $old_email, $new_email, $option_name ) {
    $send = true;

    // Don't send the notification to the default 'admin_email' value.
    if ( 'you@example.com' === $old_email ) {
        $send = false;
    }

    /**
     * Filters whether to send the site admin email change notification email.
     *
     * @since 4.9.0
     *
     * @param bool   $send      Whether to send the email notification.
     * @param string $old_email The old site admin email address.
     * @param string $new_email The new site admin email address.
     */
    $send = apply_filters( 'send_site_admin_email_change_email', $send, $old_email, $new_email );

    if ( ! $send ) {
        return;
    }

    /* translators: Do not translate OLD_EMAIL, NEW_EMAIL, SITENAME, SITEURL: those are placeholders. */
    $email_change_text = __(
      'Hi,

This notice confirms that the admin email address was changed on ###SITENAME###.

The new admin email address is ###NEW_EMAIL###.

This email has been sent to ###OLD_EMAIL###

Regards,
All at ###SITENAME###
###SITEURL###'
    );

    $email_change_email = array(
      'to'      => $old_email,
      /* translators: Site admin email change notification email subject. %s: Site title. */
      'subject' => __( '[%s] Admin Email Changed' ),
      'message' => $email_change_text,
      'headers' => '',
    );

    // Get site name.
    $site_name = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );

    /**
     * Filters the contents of the email notification sent when the site admin email address is changed.
     *
     * @since 4.9.0
     *
     * @param array $email_change_email {
     *     Used to build wp_mail().
     *
     *     @type string $to      The intended recipient.
     *     @type string $subject The subject of the email.
     *     @type string $message The content of the email.
     *         The following strings have a special meaning and will get replaced dynamically:
     *         - ###OLD_EMAIL### The old site admin email address.
     *         - ###NEW_EMAIL### The new site admin email address.
     *         - ###SITENAME###  The name of the site.
     *         - ###SITEURL###   The URL to the site.
     *     @type string $headers Headers.
     * }
     * @param string $old_email The old site admin email address.
     * @param string $new_email The new site admin email address.
     */
    $email_change_email = apply_filters( 'site_admin_email_change_email', $email_change_email, $old_email, $new_email );

    $email_change_email['message'] = str_replace( '###OLD_EMAIL###', $old_email, $email_change_email['message'] );
    $email_change_email['message'] = str_replace( '###NEW_EMAIL###', $new_email, $email_change_email['message'] );
    $email_change_email['message'] = str_replace( '###SITENAME###', $site_name, $email_change_email['message'] );
    $email_change_email['message'] = str_replace( '###SITEURL###', home_url(), $email_change_email['message'] );

    wp_mail(
      $email_change_email['to'],
      sprintf(
        $email_change_email['subject'],
        $site_name
      ),
      $email_change_email['message'],
      $email_change_email['headers']
    );
}


/**
 * Gets the URL to learn more about updating the PHP version the site is running on.
 *
 * This URL can be overridden by specifying an environment variable `WP_UPDATE_PHP_URL` or by using the
 * {@see 'wp_update_php_url'} filter. Providing an empty string is not allowed and will result in the
 * default URL being used. Furthermore the page the URL links to should preferably be localized in the
 * site language.
 *
 * @since 5.1.0
 *
 * @return string URL to learn more about updating PHP.
 */
function wp_get_update_php_url() {
    $default_url = wp_get_default_update_php_url();

    $update_url = $default_url;
    if ( false !== getenv( 'WP_UPDATE_PHP_URL' ) ) {
        $update_url = getenv( 'WP_UPDATE_PHP_URL' );
    }

    /**
     * Filters the URL to learn more about updating the PHP version the site is running on.
     *
     * Providing an empty string is not allowed and will result in the default URL being used. Furthermore
     * the page the URL links to should preferably be localized in the site language.
     *
     * @since 5.1.0
     *
     * @param string $update_url URL to learn more about updating PHP.
     */
    $update_url = apply_filters( 'wp_update_php_url', $update_url );

    if ( empty( $update_url ) ) {
        $update_url = $default_url;
    }

    return $update_url;
}

/**
 * Gets the default URL to learn more about updating the PHP version the site is running on.
 *
 * Do not use this function to retrieve this URL. Instead, use {@see wp_get_update_php_url()} when relying on the URL.
 * This function does not allow modifying the returned URL, and is only used to compare the actually used URL with the
 * default one.
 *
 * @since 5.1.0
 * @access private
 *
 * @return string Default URL to learn more about updating PHP.
 */
function wp_get_default_update_php_url() {
    return _x( 'https://wordpress.org/support/update-php/', 'localized PHP upgrade information page' );
}

/**
 * Prints the default annotation for the web host altering the "Update PHP" page URL.
 *
 * This function is to be used after {@see wp_get_update_php_url()} to display a consistent
 * annotation if the web host has altered the default "Update PHP" page URL.
 *
 * @since 5.1.0
 * @since 5.2.0 Added the `$before` and `$after` parameters.
 *
 * @param string $before Markup to output before the annotation. Default `<p class="description">`.
 * @param string $after  Markup to output after the annotation. Default `</p>`.
 */
function wp_update_php_annotation( $before = '<p class="description">', $after = '</p>' ) {
    $annotation = wp_get_update_php_annotation();

    if ( $annotation ) {
        echo $before . $annotation . $after;
    }
}

/**
 * Returns the default annotation for the web hosting altering the "Update PHP" page URL.
 *
 * This function is to be used after {@see wp_get_update_php_url()} to return a consistent
 * annotation if the web host has altered the default "Update PHP" page URL.
 *
 * @since 5.2.0
 *
 * @return string Update PHP page annotation. An empty string if no custom URLs are provided.
 */
function wp_get_update_php_annotation() {
    $update_url  = wp_get_update_php_url();
    $default_url = wp_get_default_update_php_url();

    if ( $update_url === $default_url ) {
        return '';
    }

    $annotation = sprintf(
    /* translators: %s: Default Update PHP page URL. */
      __( 'This resource is provided by your web host, and is specific to your site. For more information, <a href="%s" target="_blank">see the official WordPress documentation</a>.' ),
      esc_url( $default_url )
    );

    return $annotation;
}

/**
 * Gets the URL for directly updating the PHP version the site is running on.
 *
 * A URL will only be returned if the `WP_DIRECT_UPDATE_PHP_URL` environment variable is specified or
 * by using the {@see 'wp_direct_php_update_url'} filter. This allows hosts to send users directly to
 * the page where they can update PHP to a newer version.
 *
 * @since 5.1.1
 *
 * @return string URL for directly updating PHP or empty string.
 */
function wp_get_direct_php_update_url() {
    $direct_update_url = '';

    if ( false !== getenv( 'WP_DIRECT_UPDATE_PHP_URL' ) ) {
        $direct_update_url = getenv( 'WP_DIRECT_UPDATE_PHP_URL' );
    }

    /**
     * Filters the URL for directly updating the PHP version the site is running on from the host.
     *
     * @since 5.1.1
     *
     * @param string $direct_update_url URL for directly updating PHP.
     */
    $direct_update_url = apply_filters( 'wp_direct_php_update_url', $direct_update_url );

    return $direct_update_url;
}

/**
 * Display a button directly linking to a PHP update process.
 *
 * This provides hosts with a way for users to be sent directly to their PHP update process.
 *
 * The button is only displayed if a URL is returned by `wp_get_direct_php_update_url()`.
 *
 * @since 5.1.1
 */
function wp_direct_php_update_button() {
    $direct_update_url = wp_get_direct_php_update_url();

    if ( empty( $direct_update_url ) ) {
        return;
    }

    echo '<p class="button-container">';
    printf(
      '<a class="button button-primary" href="%1$s" target="_blank" rel="noopener">%2$s <span class="screen-reader-text">%3$s</span><span aria-hidden="true" class="dashicons dashicons-external"></span></a>',
      esc_url( $direct_update_url ),
      __( 'Update PHP' ),
      /* translators: Accessibility text. */
      __( '(opens in a new tab)' )
    );
    echo '</p>';
}

/**
 * Gets the URL to learn more about updating the site to use HTTPS.
 *
 * This URL can be overridden by specifying an environment variable `WP_UPDATE_HTTPS_URL` or by using the
 * {@see 'wp_update_https_url'} filter. Providing an empty string is not allowed and will result in the
 * default URL being used. Furthermore the page the URL links to should preferably be localized in the
 * site language.
 *
 * @since 5.7.0
 *
 * @return string URL to learn more about updating to HTTPS.
 */
function wp_get_update_https_url() {
    $default_url = wp_get_default_update_https_url();

    $update_url = $default_url;
    if ( false !== getenv( 'WP_UPDATE_HTTPS_URL' ) ) {
        $update_url = getenv( 'WP_UPDATE_HTTPS_URL' );
    }

    /**
     * Filters the URL to learn more about updating the HTTPS version the site is running on.
     *
     * Providing an empty string is not allowed and will result in the default URL being used. Furthermore
     * the page the URL links to should preferably be localized in the site language.
     *
     * @since 5.7.0
     *
     * @param string $update_url URL to learn more about updating HTTPS.
     */
    $update_url = apply_filters( 'wp_update_https_url', $update_url );
    if ( empty( $update_url ) ) {
        $update_url = $default_url;
    }

    return $update_url;
}

/**
 * Gets the default URL to learn more about updating the site to use HTTPS.
 *
 * Do not use this function to retrieve this URL. Instead, use {@see wp_get_update_https_url()} when relying on the URL.
 * This function does not allow modifying the returned URL, and is only used to compare the actually used URL with the
 * default one.
 *
 * @since 5.7.0
 * @access private
 *
 * @return string Default URL to learn more about updating to HTTPS.
 */
function wp_get_default_update_https_url() {
    /* translators: Documentation explaining HTTPS and why it should be used. */
    return __( 'https://wordpress.org/support/article/why-should-i-use-https/' );
}

/**
 * Gets the URL for directly updating the site to use HTTPS.
 *
 * A URL will only be returned if the `WP_DIRECT_UPDATE_HTTPS_URL` environment variable is specified or
 * by using the {@see 'wp_direct_update_https_url'} filter. This allows hosts to send users directly to
 * the page where they can update their site to use HTTPS.
 *
 * @since 5.7.0
 *
 * @return string URL for directly updating to HTTPS or empty string.
 */
function wp_get_direct_update_https_url() {
    $direct_update_url = '';

    if ( false !== getenv( 'WP_DIRECT_UPDATE_HTTPS_URL' ) ) {
        $direct_update_url = getenv( 'WP_DIRECT_UPDATE_HTTPS_URL' );
    }

    /**
     * Filters the URL for directly updating the PHP version the site is running on from the host.
     *
     * @since 5.7.0
     *
     * @param string $direct_update_url URL for directly updating PHP.
     */
    $direct_update_url = apply_filters( 'wp_direct_update_https_url', $direct_update_url );

    return $direct_update_url;
}

/**
 * Get the size of a directory.
 *
 * A helper function that is used primarily to check whether
 * a blog has exceeded its allowed upload space.
 *
 * @since MU (3.0.0)
 * @since 5.2.0 $max_execution_time parameter added.
 *
 * @param string $directory Full path of a directory.
 * @param int    $max_execution_time Maximum time to run before giving up. In seconds.
 *                                   The timeout is global and is measured from the moment WordPress started to load.
 * @return int|false|null Size in bytes if a valid directory. False if not. Null if timeout.
 */
function get_dirsize( $directory, $max_execution_time = null ) {

    // Exclude individual site directories from the total when checking the main site of a network,
    // as they are subdirectories and should not be counted.
    if ( is_multisite() && is_main_site() ) {
        $size = recurse_dirsize( $directory, $directory . '/sites', $max_execution_time );
    } else {
        $size = recurse_dirsize( $directory, null, $max_execution_time );
    }

    return $size;
}

/**
 * Get the size of a directory recursively.
 *
 * Used by get_dirsize() to get a directory size when it contains other directories.
 *
 * @since MU (3.0.0)
 * @since 4.3.0 The `$exclude` parameter was added.
 * @since 5.2.0 The `$max_execution_time` parameter was added.
 * @since 5.6.0 The `$directory_cache` parameter was added.
 *
 * @param string          $directory          Full path of a directory.
 * @param string|string[] $exclude            Optional. Full path of a subdirectory to exclude from the total,
 *                                            or array of paths. Expected without trailing slash(es).
 * @param int             $max_execution_time Optional. Maximum time to run before giving up. In seconds.
 *                                            The timeout is global and is measured from the moment
 *                                            WordPress started to load.
 * @param array           $directory_cache    Optional. Array of cached directory paths.
 *
 * @return int|false|null Size in bytes if a valid directory. False if not. Null if timeout.
 */
function recurse_dirsize( $directory, $exclude = null, $max_execution_time = null, &$directory_cache = null ) {
    $directory  = untrailingslashit( $directory );
    $save_cache = false;

    if ( ! isset( $directory_cache ) ) {
        $directory_cache = get_transient( 'dirsize_cache' );
        $save_cache      = true;
    }

    if ( isset( $directory_cache[ $directory ] ) && is_int( $directory_cache[ $directory ] ) ) {
        return $directory_cache[ $directory ];
    }

    if ( ! file_exists( $directory ) || ! is_dir( $directory ) || ! is_readable( $directory ) ) {
        return false;
    }

    if (
      ( is_string( $exclude ) && $directory === $exclude ) ||
      ( is_array( $exclude ) && in_array( $directory, $exclude, true ) )
    ) {
        return false;
    }

    if ( null === $max_execution_time ) {
        // Keep the previous behavior but attempt to prevent fatal errors from timeout if possible.
        if ( function_exists( 'ini_get' ) ) {
            $max_execution_time = ini_get( 'max_execution_time' );
        } else {
            // Disable...
            $max_execution_time = 0;
        }

        // Leave 1 second "buffer" for other operations if $max_execution_time has reasonable value.
        if ( $max_execution_time > 10 ) {
            $max_execution_time -= 1;
        }
    }

    /**
     * Filters the amount of storage space used by one directory and all its children, in megabytes.
     *
     * Return the actual used space to short-circuit the recursive PHP file size calculation
     * and use something else, like a CDN API or native operating system tools for better performance.
     *
     * @since 5.6.0
     *
     * @param int|false            $space_used         The amount of used space, in bytes. Default false.
     * @param string               $directory          Full path of a directory.
     * @param string|string[]|null $exclude            Full path of a subdirectory to exclude from the total,
     *                                                 or array of paths.
     * @param int                  $max_execution_time Maximum time to run before giving up. In seconds.
     * @param array                $directory_cache    Array of cached directory paths.
     */
    $size = apply_filters( 'pre_recurse_dirsize', false, $directory, $exclude, $max_execution_time, $directory_cache );

    if ( false === $size ) {
        $size = 0;

        $handle = opendir( $directory );
        if ( $handle ) {
            while ( ( $file = readdir( $handle ) ) !== false ) {
                $path = $directory . '/' . $file;
                if ( '.' !== $file && '..' !== $file ) {
                    if ( is_file( $path ) ) {
                        $size += filesize( $path );
                    } elseif ( is_dir( $path ) ) {
                        $handlesize = recurse_dirsize( $path, $exclude, $max_execution_time, $directory_cache );
                        if ( $handlesize > 0 ) {
                            $size += $handlesize;
                        }
                    }

                    if ( $max_execution_time > 0 &&
                      ( microtime( true ) - WP_START_TIMESTAMP ) > $max_execution_time
                    ) {
                        // Time exceeded. Give up instead of risking a fatal timeout.
                        $size = null;
                        break;
                    }
                }
            }
            closedir( $handle );
        }
    }

    if ( ! is_array( $directory_cache ) ) {
        $directory_cache = array();
    }

    $directory_cache[ $directory ] = $size;

    // Only write the transient on the top level call and not on recursive calls.
    if ( $save_cache ) {
        set_transient( 'dirsize_cache', $directory_cache );
    }

    return $size;
}

/**
 * Cleans directory size cache used by recurse_dirsize().
 *
 * Removes the current directory and all parent directories from the `dirsize_cache` transient.
 *
 * @since 5.6.0
 * @since 5.9.0 Added input validation with a notice for invalid input.
 *
 * @param string $path Full path of a directory or file.
 */
function clean_dirsize_cache( $path ) {
    if ( ! is_string( $path ) || empty( $path ) ) {
        trigger_error(
          sprintf(
          /* translators: 1: Function name, 2: A variable type, like "boolean" or "integer". */
            __( '%1$s only accepts a non-empty path string, received %2$s.' ),
            '<code>clean_dirsize_cache()</code>',
            '<code>' . gettype( $path ) . '</code>'
          )
        );
        return;
    }

    $directory_cache = get_transient( 'dirsize_cache' );

    if ( empty( $directory_cache ) ) {
        return;
    }

    if (
      strpos( $path, '/' ) === false &&
      strpos( $path, '\\' ) === false
    ) {
        unset( $directory_cache[ $path ] );
        set_transient( 'dirsize_cache', $directory_cache );
        return;
    }

    $last_path = null;
    $path      = untrailingslashit( $path );
    unset( $directory_cache[ $path ] );

    while (
      $last_path !== $path &&
      DIRECTORY_SEPARATOR !== $path &&
      '.' !== $path &&
      '..' !== $path
    ) {
        $last_path = $path;
        $path      = dirname( $path );
        unset( $directory_cache[ $path ] );
    }

    set_transient( 'dirsize_cache', $directory_cache );
}

/**
 * Checks compatibility with the current WordPress version.
 *
 * @since 5.2.0
 *
 * @global string $wp_version WordPress version.
 *
 * @param string $required Minimum required WordPress version.
 * @return bool True if required version is compatible or empty, false if not.
 */
function is_wp_version_compatible( $required ) {
    global $wp_version;

    // Strip off any -alpha, -RC, -beta, -src suffixes.
    list( $version ) = explode( '-', $wp_version );

    return empty( $required ) || version_compare( $version, $required, '>=' );
}

/**
 * Checks compatibility with the current PHP version.
 *
 * @since 5.2.0
 *
 * @param string $required Minimum required PHP version.
 * @return bool True if required version is compatible or empty, false if not.
 */
function is_php_version_compatible( $required ) {
    return empty( $required ) || version_compare( phpversion(), $required, '>=' );
}

/**
 * Check if two numbers are nearly the same.
 *
 * This is similar to using `round()` but the precision is more fine-grained.
 *
 * @since 5.3.0
 *
 * @param int|float $expected  The expected value.
 * @param int|float $actual    The actual number.
 * @param int|float $precision The allowed variation.
 * @return bool Whether the numbers match whithin the specified precision.
 */
function wp_fuzzy_number_match( $expected, $actual, $precision = 1 ) {
    return abs( (float) $expected - (float) $actual ) <= $precision;
}
