<?php /** @noinspection GrazieInspection */
/** @noinspection RegExpSimplifiable */
/** @noinspection PhpUnnecessaryCurlyVarSyntaxInspection */
/** @noinspection SpellCheckingInspection */
/** @noinspection PhpRedundantOptionalArgumentInspection */
/** @noinspection PhpUnused */
/** @noinspection PhpMissingParamTypeInspection */
/** @noinspection HtmlUnknownTarget */
/** @noinspection SqlNoDataSourceInspection */
/** @noinspection PhpUndefinedConstantInspection */

/**
 * Main WordPress API
 *
 * @package WordPress
 */


/**
 * Displays information about the current site.
 *
 * @since 0.71
 *
 * @see get_bloginfo() For possible `$show` values
 *
 * @param string $show Optional. Site information to display. Default empty.
 */
if (!function_exists('bloginfo')) {
    function bloginfo($show = '')
    {
        echo get_bloginfo($show, 'display');
    }
}

/**
 * Retrieves information about the current site.
 *
 * Possible values for `$show` include:
 *
 * - 'name' - Site title (set in Settings > General)
 * - 'description' - Site tagline (set in Settings > General)
 * - 'wpurl' - The WordPress address (URL) (set in Settings > General)
 * - 'url' - The Site address (URL) (set in Settings > General)
 * - 'admin_email' - Admin email (set in Settings > General)
 * - 'charset' - The "Encoding for pages and feeds"  (set in Settings > Reading)
 * - 'version' - The current WordPress version
 * - 'html_type' - The content-type (default: "text/html"). Themes and plugins
 *   can override the default value using the {@see 'pre_option_html_type'} filter
 * - 'text_direction' - The text direction determined by the site's language. is_rtl()
 *   should be used instead
 * - 'language' - Language code for the current site
 * - 'stylesheet_url' - URL to the stylesheet for the active theme. An active child theme
 *   will take precedence over this value
 * - 'stylesheet_directory' - Directory path for the active theme.  An active child theme
 *   will take precedence over this value
 * - 'template_url' / 'template_directory' - URL of the active theme's directory. An active
 *   child theme will NOT take precedence over this value
 * - 'pingback_url' - The pingback XML-RPC file URL (xmlrpc.php)
 * - 'atom_url' - The Atom feed URL (/feed/atom)
 * - 'rdf_url' - The RDF/RSS 1.0 feed URL (/feed/rdf)
 * - 'rss_url' - The RSS 0.92 feed URL (/feed/rss)
 * - 'rss2_url' - The RSS 2.0 feed URL (/feed)
 * - 'comments_atom_url' - The comments Atom feed URL (/comments/feed)
 * - 'comments_rss2_url' - The comments RSS 2.0 feed URL (/comments/feed)
 *
 * Some `$show` values are deprecated and will be removed in future versions.
 * These options will trigger the _deprecated_argument() function.
 *
 * Deprecated arguments include:
 *
 * - 'siteurl' - Use 'url' instead
 * - 'home' - Use 'url' instead
 *
 * @since 0.71
 *
 * @global string $wp_version The WordPress version string.
 *
 * @param string $show   Optional. Site info to retrieve. Default empty (site name).
 * @param string $filter Optional. How to filter what is retrieved. Default 'raw'.
 * @return string Mostly string values, might be empty.
 */
if (!function_exists('get_bloginfo')) {
    /**
     * @noinspection PhpUndefinedFunctionInspection
     * @noinspection PhpMissingBreakStatementInspection
     */
    function get_bloginfo($show = '', $filter = 'raw')
    {
        switch ($show) {
            case 'home':    // Deprecated.
            case 'siteurl': // Deprecated.
                _deprecated_argument(
                  __FUNCTION__,
                  '2.2.0',
                  sprintf(
                  /* translators: 1: 'siteurl'/'home' argument, 2: bloginfo() function name, 3: 'url' argument. */
                    __('The %1$s option is deprecated for the family of %2$s functions. Use the %3$s option instead.'),
                    '<code>' . $show . '</code>',
                    '<code>bloginfo()</code>',
                    '<code>url</code>'
                  )
                );
            // Intentional fall-through to be handled by the 'url' case.
            case 'url':
                $output = home_url();
                break;
            case 'wpurl':
                $output = site_url();
                break;
            case 'description':
                $output = get_option('blogdescription');
                break;
            case 'rdf_url':
                $output = get_feed_link('rdf');
                break;
            case 'rss_url':
                $output = get_feed_link('rss');
                break;
            case 'rss2_url':
                $output = get_feed_link('rss2');
                break;
            case 'atom_url':
                $output = get_feed_link('atom');
                break;
            case 'comments_atom_url':
                $output = get_feed_link('comments_atom');
                break;
            case 'comments_rss2_url':
                $output = get_feed_link('comments_rss2');
                break;
            case 'pingback_url':
                $output = site_url('xmlrpc.php');
                break;
            case 'stylesheet_url':
                $output = get_stylesheet_uri();
                break;
            case 'stylesheet_directory':
                $output = get_stylesheet_directory_uri();
                break;
            case 'template_directory':
            case 'template_url':
                $output = get_template_directory_uri();
                break;
            case 'admin_email':
                $output = get_option('admin_email');
                break;
            case 'charset':
                $output = get_option('blog_charset');
                if ('' === $output) {
                    $output = 'UTF-8';
                }
                break;
            case 'html_type':
                $output = get_option('html_type');
                break;
            case 'version':
                global $wp_version;
                $output = $wp_version;
                break;
            case 'language':
                /*
                 * translators: Translate this to the correct language tag for your locale,
                 * see https://www.w3.org/International/articles/language-tags/ for reference.
                 * Do not translate into your own language.
                 */
                $output = __('html_lang_attribute');
                /** @noinspection RegExpSimplifiable */
                if ('html_lang_attribute' === $output || preg_match('/[^a-zA-Z0-9-]/', $output)) {
                    /** @noinspection PhpUndefinedFunctionInspection */
                    $output = determine_locale();
                    $output = str_replace('_', '-', $output);
                }
                break;
            case 'text_direction':
                _deprecated_argument(
                  __FUNCTION__,
                  '2.2.0',
                  sprintf(
                  /* translators: 1: 'text_direction' argument, 2: bloginfo() function name, 3: is_rtl() function name. */
                    __('The %1$s option is deprecated for the family of %2$s functions. Use the %3$s function instead.'),
                    '<code>' . $show . '</code>',
                    '<code>bloginfo()</code>',
                    '<code>is_rtl()</code>'
                  )
                );
                if (function_exists('is_rtl')) {
                    $output = is_rtl() ? 'rtl' : 'ltr';
                } else {
                    $output = 'ltr';
                }
                break;
            case 'name':
            default:
                $output = get_option('blogname');
                break;
        }

        $url = true;
        if (strpos($show, 'url') === false &&
          strpos($show, 'directory') === false &&
          strpos($show, 'home') === false) {
            $url = false;
        }

        if ('display' === $filter) {
            if ($url) {
                /**
                 * Filters the URL returned by get_bloginfo().
                 *
                 * @param string $output The URL returned by bloginfo().
                 * @param string $show Type of information requested.
                 * @since 2.0.5
                 *
                 */
                $output = apply_filters('bloginfo_url', $output, $show);
            } else {
                /**
                 * Filters the site information returned by get_bloginfo().
                 *
                 * @param mixed $output The requested non-URL site information.
                 * @param string $show Type of information requested.
                 * @since 0.71
                 *
                 */
                $output = apply_filters('bloginfo', $output, $show);
            }
        }

        return $output;
    }
}



/**
 * Mark a function argument as deprecated and inform when it has been used.
 *
 * This function is to be used whenever a deprecated function argument is used.
 * Before this function is called, the argument must be checked for whether it was
 * used by comparing it to its default value or evaluating whether it is empty.
 * For example:
 *
 *     if ( ! empty( $deprecated ) ) {
 *         _deprecated_argument( __FUNCTION__, '3.0.0' );
 *     }
 *
 * There is a hook deprecated_argument_run that will be called that can be used
 * to get the backtrace up to what file and function used the deprecated
 * argument.
 *
 * The current behavior is to trigger a user error if WP_DEBUG is true.
 *
 * @since 3.0.0
 * @since 5.4.0 This function is no longer marked as "private".
 * @since 5.4.0 The error type is now classified as E_USER_DEPRECATED (used to default to E_USER_NOTICE).
 *
 * @param string $function The function that was called.
 * @param string $version  The version of WordPress that deprecated the argument used.
 * @param string $message  Optional. A message regarding the change. Default empty.
 */
function _deprecated_argument( $function, $version, $message = '' ) {

    /**
     * Fires when a deprecated argument is called.
     *
     * @since 3.0.0
     *
     * @param string $function The function that was called.
     * @param string $message  A message regarding the change.
     * @param string $version  The version of WordPress that deprecated the argument used.
     */
    do_action( 'deprecated_argument_run', $function, $message, $version );

    /**
     * Filters whether to trigger an error for deprecated arguments.
     *
     * @since 3.0.0
     *
     * @param bool $trigger Whether to trigger the error for deprecated arguments. Default true.
     */
    if ( WP_DEBUG && apply_filters( 'deprecated_argument_trigger_error', true ) ) {
        if ( function_exists( '__' ) ) {
            if ( $message ) {
                trigger_error(
                  sprintf(
                  /* translators: 1: PHP function name, 2: Version number, 3: Optional message regarding the change. */
                    __( '%1$s was called with an argument that is <strong>deprecated</strong> since version %2$s! %3$s' ),
                    $function,
                    $version,
                    $message
                  ),
                  E_USER_DEPRECATED
                );
            } else {
                trigger_error(
                  sprintf(
                  /* translators: 1: PHP function name, 2: Version number. */
                    __( '%1$s was called with an argument that is <strong>deprecated</strong> since version %2$s with no alternative available.' ),
                    $function,
                    $version
                  ),
                  E_USER_DEPRECATED
                );
            }
        } else {
            if ( $message ) {
                trigger_error(
                  sprintf(
                    '%1$s was called with an argument that is <strong>deprecated</strong> since version %2$s! %3$s',
                    $function,
                    $version,
                    $message
                  ),
                  E_USER_DEPRECATED
                );
            } else {
                trigger_error(
                  sprintf(
                    '%1$s was called with an argument that is <strong>deprecated</strong> since version %2$s with no alternative available.',
                    $function,
                    $version
                  ),
                  E_USER_DEPRECATED
                );
            }
        }
    }
}

/**
 * Marks a deprecated action or filter hook as deprecated and throws a notice.
 *
 * Use the {@see 'deprecated_hook_run'} action to get the backtrace describing where
 * the deprecated hook was called.
 *
 * Default behavior is to trigger a user error if `WP_DEBUG` is true.
 *
 * This function is called by the do_action_deprecated() and apply_filters_deprecated()
 * functions, and so generally does not need to be called directly.
 *
 * @since 4.6.0
 * @since 5.4.0 The error type is now classified as E_USER_DEPRECATED (used to default to E_USER_NOTICE).
 * @access private
 *
 * @param string $hook        The hook that was used.
 * @param string $version     The version of WordPress that deprecated the hook.
 * @param string $replacement Optional. The hook that should have been used. Default empty.
 * @param string $message     Optional. A message regarding the change. Default empty.
 */
function _deprecated_hook( $hook, $version, $replacement = '', $message = '' ) {
    /**
     * Fires when a deprecated hook is called.
     *
     * @since 4.6.0
     *
     * @param string $hook        The hook that was called.
     * @param string $replacement The hook that should be used as a replacement.
     * @param string $version     The version of WordPress that deprecated the argument used.
     * @param string $message     A message regarding the change.
     */
    do_action( 'deprecated_hook_run', $hook, $replacement, $version, $message );

    /**
     * Filters whether to trigger deprecated hook errors.
     *
     * @since 4.6.0
     *
     * @param bool $trigger Whether to trigger deprecated hook errors. Requires
     *                      `WP_DEBUG` to be defined true.
     */
    if ( WP_DEBUG && apply_filters( 'deprecated_hook_trigger_error', true ) ) {
        $message = empty( $message ) ? '' : ' ' . $message;

        if ( $replacement ) {
            trigger_error(
              sprintf(
              /* translators: 1: WordPress hook name, 2: Version number, 3: Alternative hook name. */
                __( '%1$s is <strong>deprecated</strong> since version %2$s! Use %3$s instead.' ),
                $hook,
                $version,
                $replacement
              ) . $message,
              E_USER_DEPRECATED
            );
        } else {
            trigger_error(
              sprintf(
              /* translators: 1: WordPress hook name, 2: Version number. */
                __( '%1$s is <strong>deprecated</strong> since version %2$s with no alternative available.' ),
                $hook,
                $version
              ) . $message,
              E_USER_DEPRECATED
            );
        }
    }
}

/**
 * Mark something as being incorrectly called.
 *
 * There is a hook {@see 'doing_it_wrong_run'} that will be called that can be used
 * to get the backtrace up to what file and function called the deprecated
 * function.
 *
 * The current behavior is to trigger a user error if `WP_DEBUG` is true.
 *
 * @since 3.1.0
 * @since 5.4.0 This function is no longer marked as "private".
 *
 * @param string $function The function that was called.
 * @param string $message  A message explaining what has been done incorrectly.
 * @param string $version  The version of WordPress where the message was added.
 */
function _doing_it_wrong( $function, $message, $version ) {

    /**
     * Fires when the given function is being used incorrectly.
     *
     * @since 3.1.0
     *
     * @param string $function The function that was called.
     * @param string $message  A message explaining what has been done incorrectly.
     * @param string $version  The version of WordPress where the message was added.
     */
    do_action( 'doing_it_wrong_run', $function, $message, $version );

    /**
     * Filters whether to trigger an error for _doing_it_wrong() calls.
     *
     * @since 3.1.0
     * @since 5.1.0 Added the $function, $message and $version parameters.
     *
     * @param bool   $trigger  Whether to trigger the error for _doing_it_wrong() calls. Default true.
     * @param string $function The function that was called.
     * @param string $message  A message explaining what has been done incorrectly.
     * @param string $version  The version of WordPress where the message was added.
     */
    if ( WP_DEBUG && apply_filters( 'doing_it_wrong_trigger_error', true, $function, $message, $version ) ) {
        if ( function_exists( '__' ) ) {
            if ( $version ) {
                /* translators: %s: Version number. */
                $version = sprintf( __( '(This message was added in version %s.)' ), $version );
            }

            $message .= ' ' . sprintf(
              /* translators: %s: Documentation URL. */
                __( 'Please see <a href="%s">Debugging in WordPress</a> for more information.' ),
                __( 'https://wordpress.org/support/article/debugging-in-wordpress/' )
              );

            trigger_error(
              sprintf(
              /* translators: Developer debugging message. 1: PHP function name, 2: Explanatory message, 3: WordPress version number. */
                __( '%1$s was called <strong>incorrectly</strong>. %2$s %3$s' ),
                $function,
                $message,
                $version
              ),
              E_USER_NOTICE
            );
        } else {
            if ( $version ) {
                $version = sprintf( '(This message was added in version %s.)', $version );
            }

            $message .= sprintf(
              ' Please see <a href="%s">Debugging in WordPress</a> for more information.',
              'https://wordpress.org/support/article/debugging-in-wordpress/'
            );

            trigger_error(
              sprintf(
                '%1$s was called <strong>incorrectly</strong>. %2$s %3$s',
                $function,
                $message,
                $version
              ),
              E_USER_NOTICE
            );
        }
    }
}

/**
 * Normalize a filesystem path.
 *
 * On windows systems, replaces backslashes with forward slashes
 * and forces upper-case drive letters.
 * Allows for two leading slashes for Windows network shares, but
 * ensures that all other duplicate slashes are reduced to a single.
 *
 * @since 3.9.0
 * @since 4.4.0 Ensures upper-case drive letters on Windows systems.
 * @since 4.5.0 Allows for Windows network shares.
 * @since 4.9.7 Allows for PHP file wrappers.
 *
 * @param string $path Path to normalize.
 * @return string Normalized path.
 */
function wp_normalize_path( $path ) {
    $wrapper = '';

    if ( wp_is_stream( $path ) ) {
        list( $wrapper, $path ) = explode( '://', $path, 2 );

        $wrapper .= '://';
    }

    // Standardise all paths to use '/'.
    $path = str_replace( '\\', '/', $path );

    // Replace multiple slashes down to a singular, allowing for network shares having two slashes.
    $path = preg_replace( '|(?<=.)/+|', '/', $path );

    // Windows paths should uppercase the drive letter.
    if ( ':' === substr( $path, 1, 1 ) ) {
        $path = ucfirst( $path );
    }

    return $wrapper . $path;
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
 * @noinspection GrazieInspection*@since 2.0.5
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
        /** @noinspection PhpMissingBreakStatementInspection */
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