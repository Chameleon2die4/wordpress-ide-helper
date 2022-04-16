<?php /** @noinspection SpellCheckingInspection */
/** @noinspection PhpUnnecessaryStringCastInspection */
/** @noinspection PhpCastIsUnnecessaryInspection */
/** @noinspection PhpUnusedLocalVariableInspection */
/** @noinspection PhpUnused */

/**
 * Kills WordPress execution and displays HTML page with an error message.
 *
 * This function complements the `die()` PHP function. The difference is that
 * HTML will be displayed to the user. It is recommended to use this function
 * only when the execution should not continue any further. It is not recommended
 * to call this function very often, and try to handle as many errors as possible
 * silently or more gracefully.
 *
 * As a shorthand, the desired HTTP response code may be passed as an integer to
 * the `$title` parameter (the default title would apply) or the `$args` parameter.
 *
 * @since 2.0.4
 * @since 4.1.0 The `$title` and `$args` parameters were changed to optionally accept
 *              an integer to be used as the response code.
 * @since 5.1.0 The `$link_url`, `$link_text`, and `$exit` arguments were added.
 * @since 5.3.0 The `$charset` argument was added.
 * @since 5.5.0 The `$text_direction` argument has a priority over get_language_attributes()
 *              in the default handler.
 *
 * @global WP_Query $wp_query WordPress Query object.
 *
 * @param string|WP_Error  $message Optional. Error message. If this is a WP_Error object,
 *                                  and not an Ajax or XML-RPC request, the error's messages are used.
 *                                  Default empty.
 * @param string|int       $title   Optional. Error title. If `$message` is a `WP_Error` object,
 *                                  error data with the key 'title' may be used to specify the title.
 *                                  If `$title` is an integer, then it is treated as the response
 *                                  code. Default empty.
 * @param string|array|int $args {
 *     Optional. Arguments to control behavior. If `$args` is an integer, then it is treated
 *     as the response code. Default empty array.
 *
 *     @type int    $response       The HTTP response code. Default 200 for Ajax requests, 500 otherwise.
 *     @type string $link_url       A URL to include a link to. Only works in combination with $link_text.
 *                                  Default empty string.
 *     @type string $link_text      A label for the link to include. Only works in combination with $link_url.
 *                                  Default empty string.
 *     @type bool   $back_link      Whether to include a link to go back. Default false.
 *     @type string $text_direction The text direction. This is only useful internally, when WordPress is still
 *                                  loading and the site's locale is not set up yet. Accepts 'rtl' and 'ltr'.
 *                                  Default is the value of is_rtl().
 *     @type string $charset        Character set of the HTML output. Default 'utf-8'.
 *     @type string $code           Error code to use. Default is 'wp_die', or the main error code if $message
 *                                  is a WP_Error.
 *     @type bool   $exit           Whether to exit the process after completion. Default true.
 * }
 */
function wp_die( $message = '', $title = '', $args = array() ) {
    global $wp_query;

    if ( is_int( $args ) ) {
        $args = array( 'response' => $args );
    } elseif ( is_int( $title ) ) {
        $args  = array( 'response' => $title );
        $title = '';
    }

    if ( wp_doing_ajax() ) {
        /**
         * Filters the callback for killing WordPress execution for Ajax requests.
         *
         * @since 3.4.0
         *
         * @param callable $function Callback function name.
         */
        $function = apply_filters( 'wp_die_ajax_handler', '_ajax_wp_die_handler' );
    } elseif ( wp_is_json_request() ) {
        /**
         * Filters the callback for killing WordPress execution for JSON requests.
         *
         * @since 5.1.0
         *
         * @param callable $function Callback function name.
         */
        $function = apply_filters( 'wp_die_json_handler', '_json_wp_die_handler' );
    } elseif ( defined( 'REST_REQUEST' ) && REST_REQUEST && wp_is_jsonp_request() ) {
        /**
         * Filters the callback for killing WordPress execution for JSONP REST requests.
         *
         * @since 5.2.0
         *
         * @param callable $function Callback function name.
         */
        $function = apply_filters( 'wp_die_jsonp_handler', '_jsonp_wp_die_handler' );
    } elseif ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) {
        /**
         * Filters the callback for killing WordPress execution for XML-RPC requests.
         *
         * @since 3.4.0
         *
         * @param callable $function Callback function name.
         */
        $function = apply_filters( 'wp_die_xmlrpc_handler', '_xmlrpc_wp_die_handler' );
    } elseif ( wp_is_xml_request()
      || isset( $wp_query ) &&
      ( function_exists( 'is_feed' ) && is_feed()
        || function_exists( 'is_comment_feed' ) && is_comment_feed()
        || function_exists( 'is_trackback' ) && is_trackback() ) ) {
        /**
         * Filters the callback for killing WordPress execution for XML requests.
         *
         * @since 5.2.0
         *
         * @param callable $function Callback function name.
         */
        $function = apply_filters( 'wp_die_xml_handler', '_xml_wp_die_handler' );
    } else {
        /**
         * Filters the callback for killing WordPress execution for all non-Ajax, non-JSON, non-XML requests.
         *
         * @since 3.0.0
         *
         * @param callable $function Callback function name.
         */
        $function = apply_filters( 'wp_die_handler', '_default_wp_die_handler' );
    }

    call_user_func( $function, $message, $title, $args );
}

/**
 * Kills WordPress execution and displays HTML page with an error message.
 *
 * This is the default handler for wp_die(). If you want a custom one,
 * you can override this using the {@see 'wp_die_handler'} filter in wp_die().
 *
 * @since 3.0.0
 * @access private
 *
 * @param string|WP_Error $message Error message or WP_Error object.
 * @param string          $title   Optional. Error title. Default empty.
 * @param string|array    $args    Optional. Arguments to control behavior. Default empty array.
 */
function _default_wp_die_handler( $message, $title = '', $args = array() ) {
    list( $message, $title, $parsed_args ) = _wp_die_process_input( $message, $title, $args );

    if ( is_string( $message ) ) {
        if ( ! empty( $parsed_args['additional_errors'] ) ) {
            $message = array_merge(
              array( $message ),
              wp_list_pluck( $parsed_args['additional_errors'], 'message' )
            );
            $message = "<ul>\n\t\t<li>" . implode( "</li>\n\t\t<li>", $message ) . "</li>\n\t</ul>";
        }

        $message = sprintf(
          '<div class="wp-die-message">%s</div>',
          $message
        );
    }

    $have_gettext = function_exists( '__' );

    if ( ! empty( $parsed_args['link_url'] ) && ! empty( $parsed_args['link_text'] ) ) {
        $link_url = $parsed_args['link_url'];
        if ( function_exists( 'esc_url' ) ) {
            $link_url = esc_url( $link_url );
        }
        $link_text = $parsed_args['link_text'];
        /** @noinspection PhpUnnecessaryCurlyVarSyntaxInspection */
        $message  .= "\n<p><a href='{$link_url}'>{$link_text}</a></p>";
    }

    if ( isset( $parsed_args['back_link'] ) && $parsed_args['back_link'] ) {
        $back_text = $have_gettext ? __( '&laquo; Back' ) : '&laquo; Back';
        $message  .= "\n<p><a href='javascript:history.back()'>$back_text</a></p>";
    }

    if ( ! did_action( 'admin_head' ) ) :
        if ( ! headers_sent() ) {
            header( "Content-Type: text/html; charset={$parsed_args['charset']}" );
            status_header( $parsed_args['response'] );
            nocache_headers();
        }

        $text_direction = $parsed_args['text_direction'];
        $dir_attr       = "dir='$text_direction'";

        // If `text_direction` was not explicitly passed,
        // use get_language_attributes() if available.
        if ( empty( $args['text_direction'] )
          && function_exists( 'language_attributes' ) && function_exists( 'is_rtl' )
        ) {
            $dir_attr = get_language_attributes();
        }
        ?>
        <!DOCTYPE html>
        <!--suppress HtmlRequiredLangAttribute -->
        <html <?php echo $dir_attr; ?>>
        <head>
            <meta http-equiv="Content-Type" content="text/html; charset=<?php echo $parsed_args['charset']; ?>" />
            <meta name="viewport" content="width=device-width">
            <?php
            if ( function_exists( 'wp_robots' ) && function_exists( 'wp_robots_no_robots' ) && function_exists( 'add_filter' ) ) {
                add_filter( 'wp_robots', 'wp_robots_no_robots' );
                wp_robots();
            }
            ?>
            <title><?php echo $title; ?></title>
            <!--suppress CssUnusedSymbol -->
            <style>
                html {
                    background: #f1f1f1;
                }
                body {
                    background: #fff;
                    border: 1px solid #ccd0d4;
                    color: #444;
                    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
                    margin: 2em auto;
                    padding: 1em 2em;
                    max-width: 700px;
                    -webkit-box-shadow: 0 1px 1px rgba(0, 0, 0, .04);
                    box-shadow: 0 1px 1px rgba(0, 0, 0, .04);
                }
                h1 {
                    border-bottom: 1px solid #dadada;
                    clear: both;
                    color: #666;
                    font-size: 24px;
                    margin: 30px 0 0 0;
                    padding: 0 0 7px;
                }
                #error-page {
                    margin-top: 50px;
                }
                #error-page p,
                #error-page .wp-die-message {
                    font-size: 14px;
                    line-height: 1.5;
                    margin: 25px 0 20px;
                }
                #error-page code {
                    font-family: Consolas, Monaco, monospace;
                }
                ul li {
                    margin-bottom: 10px;
                    font-size: 14px ;
                }
                a {
                    color: #0073aa;
                }
                a:hover,
                a:active {
                    color: #006799;
                }
                a:focus {
                    color: #124964;
                    -webkit-box-shadow:
                            0 0 0 1px #5b9dd9,
                            0 0 2px 1px rgba(30, 140, 190, 0.8);
                    box-shadow:
                            0 0 0 1px #5b9dd9,
                            0 0 2px 1px rgba(30, 140, 190, 0.8);
                    outline: none;
                }
                .button {
                    background: #f3f5f6;
                    border: 1px solid #016087;
                    color: #016087;
                    display: inline-block;
                    text-decoration: none;
                    font-size: 13px;
                    line-height: 2;
                    height: 28px;
                    margin: 0;
                    padding: 0 10px 1px;
                    cursor: pointer;
                    -webkit-border-radius: 3px;
                    -webkit-appearance: none;
                    border-radius: 3px;
                    white-space: nowrap;
                    -webkit-box-sizing: border-box;
                    -moz-box-sizing:    border-box;
                    box-sizing:         border-box;

                    vertical-align: top;
                }

                .button.button-large {
                    line-height: 2.30769231;
                    min-height: 32px;
                    padding: 0 12px;
                }

                .button:hover,
                .button:focus {
                    background: #f1f1f1;
                }

                .button:focus {
                    background: #f3f5f6;
                    border-color: #007cba;
                    -webkit-box-shadow: 0 0 0 1px #007cba;
                    box-shadow: 0 0 0 1px #007cba;
                    color: #016087;
                    outline: 2px solid transparent;
                    outline-offset: 0;
                }

                .button:active {
                    background: #f3f5f6;
                    border-color: #7e8993;
                    -webkit-box-shadow: none;
                    box-shadow: none;
                }

                <?php
		if ( 'rtl' === $text_direction ) {
			echo 'body { font-family: Tahoma, Arial; }';
		}
		?>
            </style>
        </head>
        <body id="error-page">
    <?php endif; // ! did_action( 'admin_head' ) ?>
    <?php echo $message; ?>
</body>
    </html>
    <?php
    if ( $parsed_args['exit'] ) {
        die();
    }
}

/**
 * Kills WordPress execution and displays Ajax response with an error message.
 *
 * This is the handler for wp_die() when processing Ajax requests.
 *
 * @since 3.4.0
 * @access private
 *
 * @param string       $message Error message.
 * @param string       $title   Optional. Error title (unused). Default empty.
 * @param string|array $args    Optional. Arguments to control behavior. Default empty array.
 */
function _ajax_wp_die_handler( $message, $title = '', $args = array() ) {
    // Set default 'response' to 200 for Ajax requests.
    $args = wp_parse_args(
      $args,
      array( 'response' => 200 )
    );

    list( $message, $title, $parsed_args ) = _wp_die_process_input( $message, $title, $args );

    if ( ! headers_sent() ) {
        // This is intentional. For backward-compatibility, support passing null here.
        if ( null !== $args['response'] ) {
            status_header( $parsed_args['response'] );
        }
        nocache_headers();
    }

    if ( is_scalar( $message ) ) {
        $message = (string) $message;
    } else {
        $message = '0';
    }

    if ( $parsed_args['exit'] ) {
        die( $message );
    }

    echo $message;
}

/**
 * Kills WordPress execution and displays JSON response with an error message.
 *
 * This is the handler for wp_die() when processing JSON requests.
 *
 * @since 5.1.0
 * @access private
 *
 * @param string       $message Error message.
 * @param string       $title   Optional. Error title. Default empty.
 * @param string|array $args    Optional. Arguments to control behavior. Default empty array.
 */
function _json_wp_die_handler( $message, $title = '', $args = array() ) {
    list( $message, $title, $parsed_args ) = _wp_die_process_input( $message, $title, $args );

    $data = array(
      'code'              => $parsed_args['code'],
      'message'           => $message,
      'data'              => array(
        'status' => $parsed_args['response'],
      ),
      'additional_errors' => $parsed_args['additional_errors'],
    );

    if ( ! headers_sent() ) {
        header( "Content-Type: application/json; charset={$parsed_args['charset']}" );
        if ( null !== $parsed_args['response'] ) {
            status_header( $parsed_args['response'] );
        }
        nocache_headers();
    }

    echo wp_json_encode( $data );
    if ( $parsed_args['exit'] ) {
        die();
    }
}

/**
 * Kills WordPress execution and displays JSONP response with an error message.
 *
 * This is the handler for wp_die() when processing JSONP requests.
 *
 * @since 5.2.0
 * @access private
 *
 * @param string       $message Error message.
 * @param string       $title   Optional. Error title. Default empty.
 * @param string|array $args    Optional. Arguments to control behavior. Default empty array.
 */
function _jsonp_wp_die_handler( $message, $title = '', $args = array() ) {
    list( $message, $title, $parsed_args ) = _wp_die_process_input( $message, $title, $args );

    $data = array(
      'code'              => $parsed_args['code'],
      'message'           => $message,
      'data'              => array(
        'status' => $parsed_args['response'],
      ),
      'additional_errors' => $parsed_args['additional_errors'],
    );

    if ( ! headers_sent() ) {
        header( "Content-Type: application/javascript; charset={$parsed_args['charset']}" );
        header( 'X-Content-Type-Options: nosniff' );
        header( 'X-Robots-Tag: noindex' );
        if ( null !== $parsed_args['response'] ) {
            status_header( $parsed_args['response'] );
        }
        nocache_headers();
    }

    $result         = wp_json_encode( $data );
    $jsonp_callback = $_GET['_jsonp'];
    echo '/**/' . $jsonp_callback . '(' . $result . ')';
    if ( $parsed_args['exit'] ) {
        die();
    }
}


/**
 * Kills WordPress execution and displays an error message.
 *
 * This is the handler for wp_die() when processing APP requests.
 *
 * @since 3.4.0
 * @since 5.1.0 Added the $title and $args parameters.
 * @access private
 *
 * @param string       $message Optional. Response to print. Default empty.
 * @param string       $title   Optional. Error title (unused). Default empty.
 * @param string|array $args    Optional. Arguments to control behavior. Default empty array.
 */
function _scalar_wp_die_handler( $message = '', $title = '', $args = array() ) {
    list( $message, $title, $parsed_args ) = _wp_die_process_input( $message, $title, $args );

    if ( $parsed_args['exit'] ) {
        if ( is_scalar( $message ) ) {
            die( (string) $message );
        }
        die();
    }

    if ( is_scalar( $message ) ) {
        echo (string) $message;
    }
}

/**
 * Processes arguments passed to wp_die() consistently for its handlers.
 *
 * @since 5.1.0
 * @access private
 *
 * @param string|WP_Error $message Error message or WP_Error object.
 * @param string          $title   Optional. Error title. Default empty.
 * @param string|array    $args    Optional. Arguments to control behavior. Default empty array.
 * @return array {
 *     Processed arguments.
 *
 *     @type string $0 Error message.
 *     @type string $1 Error title.
 *     @type array  $2 Arguments to control behavior.
 * }
 */
function _wp_die_process_input( $message, $title = '', $args = array() ) {
    $defaults = array(
      'response'          => 0,
      'code'              => '',
      'exit'              => true,
      'back_link'         => false,
      'link_url'          => '',
      'link_text'         => '',
      'text_direction'    => '',
      'charset'           => 'utf-8',
      'additional_errors' => array(),
    );

    $args = wp_parse_args( $args, $defaults );

    if ( function_exists( 'is_wp_error' ) && is_wp_error( $message ) ) {
        if ( ! empty( $message->errors ) ) {
            $errors = array();
            foreach ( (array) $message->errors as $error_code => $error_messages ) {
                foreach ( (array) $error_messages as $error_message ) {
                    $errors[] = array(
                      'code'    => $error_code,
                      'message' => $error_message,
                      'data'    => $message->get_error_data( $error_code ),
                    );
                }
            }

            $message = $errors[0]['message'];
            if ( empty( $args['code'] ) ) {
                $args['code'] = $errors[0]['code'];
            }
            if ( empty( $args['response'] ) && is_array( $errors[0]['data'] ) && ! empty( $errors[0]['data']['status'] ) ) {
                $args['response'] = $errors[0]['data']['status'];
            }
            if ( empty( $title ) && is_array( $errors[0]['data'] ) && ! empty( $errors[0]['data']['title'] ) ) {
                $title = $errors[0]['data']['title'];
            }

            unset( $errors[0] );
            $args['additional_errors'] = array_values( $errors );
        } else {
            $message = '';
        }
    }

    $have_gettext = function_exists( '__' );

    // The $title and these specific $args must always have a non-empty value.
    if ( empty( $args['code'] ) ) {
        $args['code'] = 'wp_die';
    }
    if ( empty( $args['response'] ) ) {
        $args['response'] = 500;
    }
    if ( empty( $title ) ) {
        $title = $have_gettext ? __( 'WordPress &rsaquo; Error' ) : 'WordPress &rsaquo; Error';
    }
    if ( empty( $args['text_direction'] ) || ! in_array( $args['text_direction'], array( 'ltr', 'rtl' ), true ) ) {
        $args['text_direction'] = 'ltr';
        if ( function_exists( 'is_rtl' ) && is_rtl() ) {
            $args['text_direction'] = 'rtl';
        }
    }

    if ( ! empty( $args['charset'] ) ) {
        $args['charset'] = _canonical_charset( $args['charset'] );
    }

    return array( $message, $title, $args );
}