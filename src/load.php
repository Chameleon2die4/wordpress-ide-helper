<?php /** @noinspection SpellCheckingInspection */
/** @noinspection GrazieInspection */
/** @noinspection PhpIncludeInspection */
/** @noinspection PhpUnused */
/** @noinspection PhpMissingParamTypeInspection */

/**
 * Check or set whether WordPress is in "installation" mode.
 *
 * If the `WP_INSTALLING` constant is defined during the bootstrap, `wp_installing()` will default to `true`.
 *
 * @since 4.4.0
 *
 * @param bool $is_installing Optional. True to set WP into Installing mode, false to turn Installing mode off.
 *                            Omit this parameter if you only want to fetch the current status.
 * @return bool True if WP is installing, otherwise false. When a `$is_installing` is passed, the function will
 *              report whether WP was in installing mode prior to the change to `$is_installing`.
 */
function wp_installing( $is_installing = null ) {
    static $installing = null;

    // Support for the `WP_INSTALLING` constant, defined before WP is loaded.
    if ( is_null( $installing ) ) {
        $installing = defined( 'WP_INSTALLING' ) && WP_INSTALLING;
    }

    if ( ! is_null( $is_installing ) ) {
        $old_installing = $installing;
        $installing     = $is_installing;
        return (bool) $old_installing;
    }

    return (bool) $installing;
}

/**
 * Determines if SSL is used.
 *
 * @since 2.6.0
 * @since 4.6.0 Moved from functions.php to load.php.
 *
 * @return bool True if SSL, otherwise false.
 */
function is_ssl() {
    if ( isset( $_SERVER['HTTPS'] ) ) {
        if ( 'on' === strtolower( $_SERVER['HTTPS'] ) ) {
            return true;
        }

        if ( '1' == $_SERVER['HTTPS'] ) {
            return true;
        }
    } elseif ( isset( $_SERVER['SERVER_PORT'] ) && ( '443' == $_SERVER['SERVER_PORT'] ) ) {
        return true;
    }
    return false;
}

/**
 * If Multisite is enabled.
 *
 * @since 3.0.0
 *
 * @return bool True if Multisite is enabled, false otherwise.
 */
function is_multisite() {
    if ( defined( 'MULTISITE' ) ) {
        return MULTISITE;
    }

    if ( defined( 'SUBDOMAIN_INSTALL' ) || defined( 'VHOST' ) || defined( 'SUNRISE' ) ) {
        return true;
    }

    return false;
}

/**
 * Checks whether the given variable is a WordPress Error.
 *
 * Returns whether `$thing` is an instance of the `WP_Error` class.
 *
 * @since 2.1.0
 *
 * @param mixed $thing The variable to check.
 * @return bool Whether the variable is an instance of WP_Error.
 */
function is_wp_error( $thing ) {
    $is_wp_error = ( $thing instanceof WP_Error );

    if ( $is_wp_error ) {
        /**
         * Fires when `is_wp_error()` is called and its parameter is an instance of `WP_Error`.
         *
         * @since 5.6.0
         *
         * @param WP_Error $thing The error object passed to `is_wp_error()`.
         */
        do_action( 'is_wp_error_instance', $thing );
    }

    return $is_wp_error;
}

/**
 * Determines whether the current request is for an administrative interface page.
 *
 * Does not check if the user is an administrator; use current_user_can()
 * for checking roles and capabilities.
 *
 * For more information on this and similar theme functions, check out
 * the {@link https://developer.wordpress.org/themes/basics/conditional-tags/
 * Conditional Tags} article in the Theme Developer Handbook.
 *
 * @since 1.5.1
 *
 * @global WP_Screen $current_screen WordPress current screen object.
 *
 * @return bool True if inside WordPress administration interface, false otherwise.
 */
function is_admin() {
    if ( isset( $GLOBALS['current_screen'] ) ) {
        return $GLOBALS['current_screen']->in_admin();
    } elseif ( defined( 'WP_ADMIN' ) ) {
        return WP_ADMIN;
    }

    return false;
}

/**
 * Retrieve the current site ID.
 *
 * @since 3.1.0
 *
 * @global int $blog_id
 *
 * @return int Site ID.
 */
function get_current_blog_id() {
    global $blog_id;
    return absint( $blog_id );
}

/**
 * Retrieves the current network ID.
 *
 * @since 4.6.0
 *
 * @return int The ID of the current network.
 */
function get_current_network_id() {
    if ( ! is_multisite() ) {
        return 1;
    }

    $current_network = get_network();

    if ( ! isset( $current_network->id ) ) {
        return get_main_network_id();
    }

    return absint( $current_network->id );
}

/**
 * Toggle `$_wp_using_ext_object_cache` on and off without directly
 * touching global.
 *
 * @since 3.7.0
 *
 * @global bool $_wp_using_ext_object_cache
 *
 * @param bool $using Whether external object cache is being used.
 * @return bool The current 'using' setting.
 */
function wp_using_ext_object_cache( $using = null ) {
    global $_wp_using_ext_object_cache;
    $current_using = $_wp_using_ext_object_cache;
    if ( null !== $using ) {
        $_wp_using_ext_object_cache = $using;
    }
    return $current_using;
}

/**
 * Start the WordPress object cache.
 *
 * If an object-cache.php file exists in the wp-content directory,
 * it uses that drop-in as an external object cache.
 *
 * @since 3.0.0
 * @access private
 *
 * @global array $wp_filter Stores all of the filters.
 */
function wp_start_object_cache() {
    global $wp_filter;
    static $first_init = true;

    // Only perform the following checks once.

    /**
     * Filters whether to enable loading of the object-cache.php drop-in.
     *
     * This filter runs before it can be used by plugins. It is designed for non-web
     * runtimes. If false is returned, object-cache.php will never be loaded.
     *
     * @since 5.8.0
     *
     * @param bool $enable_object_cache Whether to enable loading object-cache.php (if present).
     *                                  Default true.
     */
    if ( $first_init && apply_filters( 'enable_loading_object_cache_dropin', true ) ) {
        if ( ! function_exists( 'wp_cache_init' ) ) {
            /*
             * This is the normal situation. First-run of this function. No
             * caching backend has been loaded.
             *
             * We try to load a custom caching backend, and then, if it
             * results in a wp_cache_init() function existing, we note
             * that an external object cache is being used.
             */
            if ( file_exists( WP_CONTENT_DIR . '/object-cache.php' ) ) {
                require_once WP_CONTENT_DIR . '/object-cache.php';
                if ( function_exists( 'wp_cache_init' ) ) {
                    wp_using_ext_object_cache( true );
                }

                // Re-initialize any hooks added manually by object-cache.php.
                if ( $wp_filter ) {
                    $wp_filter = WP_Hook::build_preinitialized_hooks( $wp_filter );
                }
            }
        } elseif ( ! wp_using_ext_object_cache() && file_exists( WP_CONTENT_DIR . '/object-cache.php' ) ) {
            /*
             * Sometimes advanced-cache.php can load object-cache.php before
             * this function is run. This breaks the function_exists() check
             * above and can result in wp_using_ext_object_cache() returning
             * false when actually an external cache is in use.
             */
            wp_using_ext_object_cache( true );
        }
    }

    if ( ! wp_using_ext_object_cache() ) {
        require_once ABSPATH . WPINC . '/cache.php';
    }

    require_once ABSPATH . WPINC . '/cache-compat.php';

    /*
     * If cache supports reset, reset instead of init if already
     * initialized. Reset signals to the cache that global IDs
     * have changed and it may need to update keys and cleanup caches.
     */
    if ( ! $first_init && function_exists( 'wp_cache_switch_to_blog' ) ) {
        wp_cache_switch_to_blog( get_current_blog_id() );
    } elseif ( function_exists( 'wp_cache_init' ) ) {
        wp_cache_init();
    }

    if ( function_exists( 'wp_cache_add_global_groups' ) ) {
        wp_cache_add_global_groups( array( 'users', 'userlogins', 'usermeta', 'user_meta', 'useremail', 'userslugs', 'site-transient', 'site-options', 'blog-lookup', 'blog-details', 'site-details', 'rss', 'global-posts', 'blog-id-cache', 'networks', 'sites', 'blog_meta' ) );
        /** @noinspection PhpUndefinedFunctionInspection */
        wp_cache_add_non_persistent_groups( array('counts', 'plugins' ) );
    }

    $first_init = false;
}

/**
 * Determines whether the current request is a WordPress Ajax request.
 *
 * @since 4.7.0
 *
 * @return bool True if it's a WordPress Ajax request, false otherwise.
 */
function wp_doing_ajax() {
    /**
     * Filters whether the current request is a WordPress Ajax request.
     *
     * @since 4.7.0
     *
     * @param bool $wp_doing_ajax Whether the current request is a WordPress Ajax request.
     */
    return apply_filters( 'wp_doing_ajax', defined( 'DOING_AJAX' ) && DOING_AJAX );
}

/**
 * Checks whether current request is a JSON request, or is expecting a JSON response.
 *
 * @since 5.0.0
 *
 * @return bool True if `Accepts` or `Content-Type` headers contain `application/json`.
 *              False otherwise.
 */
function wp_is_json_request() {

    if ( isset( $_SERVER['HTTP_ACCEPT'] ) && wp_is_json_media_type( $_SERVER['HTTP_ACCEPT'] ) ) {
        return true;
    }

    if ( isset( $_SERVER['CONTENT_TYPE'] ) && wp_is_json_media_type( $_SERVER['CONTENT_TYPE'] ) ) {
        return true;
    }

    return false;

}

/**
 * Checks whether a string is a valid JSON Media Type.
 *
 * @since 5.6.0
 *
 * @param string $media_type A Media Type string to check.
 * @return bool True if string is a valid JSON Media Type.
 */
function wp_is_json_media_type( $media_type ) {
    static $cache = array();

    if ( ! isset( $cache[ $media_type ] ) ) {
        /** @noinspection RegExpRedundantEscape */
        $cache[$media_type ] = (bool) preg_match( '/(^|\s|,)application\/([\w!#\$&-\^]+\+)?json(\+oembed)?($|\s|;|,)/i', $media_type );
    }

    return $cache[ $media_type ];
}

/**
 * Checks whether current request is a JSONP request, or is expecting a JSONP response.
 *
 * @since 5.2.0
 *
 * @return bool True if JSONP request, false otherwise.
 */
function wp_is_jsonp_request() {
    if ( ! isset( $_GET['_jsonp'] ) ) {
        return false;
    }

    if ( ! function_exists( 'wp_check_jsonp_callback' ) ) {
        require_once ABSPATH . WPINC . '/functions.php';
    }

    $jsonp_callback = $_GET['_jsonp'];
    if ( ! wp_check_jsonp_callback( $jsonp_callback ) ) {
        return false;
    }

    /** This filter is documented in wp-includes/rest-api/class-wp-rest-server.php */
    return apply_filters( 'rest_jsonp_enabled', true );
}

/**
 * Checks that a JSONP callback is a valid JavaScript callback name.
 *
 * Only allows alphanumeric characters and the dot character in callback
 * function names. This helps to mitigate XSS attacks caused by directly
 * outputting user input.
 *
 * @since 4.6.0
 *
 * @param string $callback Supplied JSONP callback function name.
 * @return bool Whether the callback function name is valid.
 */
function wp_check_jsonp_callback( $callback ) {
    if ( ! is_string( $callback ) ) {
        return false;
    }

    preg_replace( '/[^\w.]/', '', $callback, -1, $illegal_char_count );

    return 0 === $illegal_char_count;
}

/**
 * Checks whether current request is an XML request, or is expecting an XML response.
 *
 * @since 5.2.0
 *
 * @return bool True if `Accepts` or `Content-Type` headers contain `text/xml`
 *              or one of the related MIME types. False otherwise.
 */
function wp_is_xml_request() {
    $accepted = array(
      'text/xml',
      'application/rss+xml',
      'application/atom+xml',
      'application/rdf+xml',
      'text/xml+oembed',
      'application/xml+oembed',
    );

    if ( isset( $_SERVER['HTTP_ACCEPT'] ) ) {
        foreach ( $accepted as $type ) {
            if ( false !== strpos( $_SERVER['HTTP_ACCEPT'], $type ) ) {
                return true;
            }
        }
    }

    if ( isset( $_SERVER['CONTENT_TYPE'] ) && in_array( $_SERVER['CONTENT_TYPE'], $accepted, true ) ) {
        return true;
    }

    return false;
}

/**
 * Attempt an early load of translations.
 *
 * Used for errors encountered during the initial loading process, before
 * the locale has been properly detected and loaded.
 *
 * Designed for unusual load sequences (like setup-config.php) or for when
 * the script will then terminate with an error, otherwise there is a risk
 * that a file can be double-included.
 *
 * @since 3.4.0
 * @access private
 *
 * @global WP_Locale $wp_locale WordPress date and time locale object.
 */
function wp_load_translations_early() {
    global $wp_locale;

    static $loaded = false;
    if ( $loaded ) {
        return;
    }
    $loaded = true;

    if ( function_exists( 'did_action' ) && did_action( 'init' ) ) {
        return;
    }

    // We need $wp_local_package.
    require ABSPATH . WPINC . '/version.php';

    // Translation and localization.
    require_once ABSPATH . WPINC . '/pomo/mo.php';
    require_once ABSPATH . WPINC . '/l10n.php';
    require_once ABSPATH . WPINC . '/class-wp-locale.php';
    require_once ABSPATH . WPINC . '/class-wp-locale-switcher.php';

    // General libraries.
    require_once ABSPATH . WPINC . '/plugin.php';

    $locales   = array();
    $locations = array();

    /** @noinspection PhpLoopNeverIteratesInspection */
    while ( true ) {
        if ( defined( 'WPLANG' ) ) {
            if ( '' === WPLANG ) {
                break;
            }
            $locales[] = WPLANG;
        }

        if ( isset( $wp_local_package ) ) {
            $locales[] = $wp_local_package;
        }

        if ( ! $locales ) {
            break;
        }

        if ( defined( 'WP_LANG_DIR' ) && @is_dir( WP_LANG_DIR ) ) {
            $locations[] = WP_LANG_DIR;
        }

        if ( defined( 'WP_CONTENT_DIR' ) && @is_dir( WP_CONTENT_DIR . '/languages' ) ) {
            $locations[] = WP_CONTENT_DIR . '/languages';
        }

        if ( @is_dir( ABSPATH . 'wp-content/languages' ) ) {
            $locations[] = ABSPATH . 'wp-content/languages';
        }

        if ( @is_dir( ABSPATH . WPINC . '/languages' ) ) {
            $locations[] = ABSPATH . WPINC . '/languages';
        }

        if ( ! $locations ) {
            break;
        }

        $locations = array_unique( $locations );

        foreach ( $locales as $locale ) {
            foreach ( $locations as $location ) {
                if ( file_exists( $location . '/' . $locale . '.mo' ) ) {
                    load_textdomain( 'default', $location . '/' . $locale . '.mo' );
                    if ( defined( 'WP_SETUP_CONFIG' ) && file_exists( $location . '/admin-' . $locale . '.mo' ) ) {
                        load_textdomain( 'default', $location . '/admin-' . $locale . '.mo' );
                    }
                    break 2;
                }
            }
        }

        break;
    }

    $wp_locale = new WP_Locale();
}
