<?php /** @noinspection GrazieInspection */
/** @noinspection PhpUnused */
/** @noinspection PhpComposerExtensionStubsInspection */

/**
 * Encode a variable into JSON, with some sanity checks.
 *
 * @since 4.1.0
 * @since 5.3.0 No longer handles support for PHP < 5.6.
 *
 * @param mixed $data    Variable (usually an array or object) to encode as JSON.
 * @param int   $options Optional. Options to be passed to json_encode(). Default 0.
 * @param int   $depth   Optional. Maximum depth to walk through $data. Must be
 *                       greater than 0. Default 512.
 * @return string|false The JSON encoded string, or false if it cannot be encoded.
 */
function wp_json_encode( $data, $options = 0, $depth = 512 ) {
    $json = json_encode( $data, $options, $depth );

    // If json_encode() was successful, no need to do more sanity checking.
    if ( false !== $json ) {
        return $json;
    }

    try {
        $data = _wp_json_sanity_check( $data, $depth );
    } catch ( Exception $e ) {
        return false;
    }

    return json_encode( $data, $options, $depth );
}

/**
 * Perform sanity checks on data that shall be encoded to JSON.
 *
 * @ignore
 * @since 4.1.0
 * @access private
 *
 * @see wp_json_encode()
 *
 * @throws Exception If depth limit is reached.
 *
 * @param mixed $data  Variable (usually an array or object) to encode as JSON.
 * @param int   $depth Maximum depth to walk through $data. Must be greater than 0.
 * @return mixed The sanitized data that shall be encoded to JSON.
 */
function _wp_json_sanity_check( $data, $depth ) {
    if ( $depth < 0 ) {
        throw new Exception( 'Reached depth limit' );
    }

    if ( is_array( $data ) ) {
        $output = array();
        foreach ( $data as $id => $el ) {
            // Don't forget to sanitize the ID!
            if ( is_string( $id ) ) {
                $clean_id = _wp_json_convert_string( $id );
            } else {
                $clean_id = $id;
            }

            // Check the element type, so that we're only recursing if we really have to.
            if ( is_array( $el ) || is_object( $el ) ) {
                $output[ $clean_id ] = _wp_json_sanity_check( $el, $depth - 1 );
            } elseif ( is_string( $el ) ) {
                $output[ $clean_id ] = _wp_json_convert_string( $el );
            } else {
                $output[ $clean_id ] = $el;
            }
        }
    } elseif ( is_object( $data ) ) {
        $output = new stdClass;
        foreach ( $data as $id => $el ) {
            if ( is_string( $id ) ) {
                $clean_id = _wp_json_convert_string( $id );
            } else {
                $clean_id = $id;
            }

            if ( is_array( $el ) || is_object( $el ) ) {
                $output->$clean_id = _wp_json_sanity_check( $el, $depth - 1 );
            } elseif ( is_string( $el ) ) {
                $output->$clean_id = _wp_json_convert_string( $el );
            } else {
                $output->$clean_id = $el;
            }
        }
    } elseif ( is_string( $data ) ) {
        return _wp_json_convert_string( $data );
    } else {
        return $data;
    }

    return $output;
}

/**
 * Convert a string to UTF-8, so that it can be safely encoded to JSON.
 *
 * @ignore
 * @since 4.1.0
 * @access private
 *
 * @see _wp_json_sanity_check()
 *
 * @param string $string The string which is to be converted.
 * @return string The checked string.
 */
function _wp_json_convert_string( $string ) {
    static $use_mb = null;
    if ( is_null( $use_mb ) ) {
        $use_mb = function_exists( 'mb_convert_encoding' );
    }

    if ( $use_mb ) {
        $encoding = mb_detect_encoding( $string, mb_detect_order(), true );
        if ( $encoding ) {
            return mb_convert_encoding( $string, 'UTF-8', $encoding );
        } else {
            return mb_convert_encoding( $string, 'UTF-8', 'UTF-8' );
        }
    } else {
        /** @noinspection PhpUndefinedFunctionInspection */
        return wp_check_invalid_utf8( $string, true );
    }
}

/**
 * Prepares response data to be serialized to JSON.
 *
 * This supports the JsonSerializable interface for PHP 5.2-5.3 as well.
 *
 * @ignore
 * @since 4.4.0
 * @deprecated 5.3.0 This function is no longer needed as support for PHP 5.2-5.3
 *                   has been dropped.
 * @access private
 *
 * @param mixed $data Native representation.
 * @return bool|int|float|null|string|array Data ready for `json_encode()`.
 */
function _wp_json_prepare_data( $data ) {
    _deprecated_function( __FUNCTION__, '5.3.0' );
    return $data;
}

/**
 * Send a JSON response back to an Ajax request.
 *
 * @since 3.5.0
 * @since 4.7.0 The `$status_code` parameter was added.
 * @since 5.6.0 The `$options` parameter was added.
 *
 * @param mixed $response    Variable (usually an array or object) to encode as JSON,
 *                           then print and die.
 * @param int   $status_code Optional. The HTTP status code to output. Default null.
 * @param int   $options     Optional. Options to be passed to json_encode(). Default 0.
 */
function wp_send_json( $response, $status_code = null, $options = 0 ) {
    if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
        _doing_it_wrong(
          __FUNCTION__,
          sprintf(
          /* translators: 1: WP_REST_Response, 2: WP_Error */
            __( 'Return a %1$s or %2$s object from your callback when using the REST API.' ),
            'WP_REST_Response',
            'WP_Error'
          ),
          '5.5.0'
        );
    }

    if ( ! headers_sent() ) {
        header( 'Content-Type: application/json; charset=' . get_option( 'blog_charset' ) );
        if ( null !== $status_code ) {
            status_header( $status_code );
        }
    }

    echo wp_json_encode( $response, $options );

    if ( wp_doing_ajax() ) {
        wp_die(
          '',
          '',
          array(
            'response' => null,
          )
        );
    } else {
        die;
    }
}

/**
 * Send a JSON response back to an Ajax request, indicating success.
 *
 * @since 3.5.0
 * @since 4.7.0 The `$status_code` parameter was added.
 * @since 5.6.0 The `$options` parameter was added.
 *
 * @param mixed $data        Optional. Data to encode as JSON, then print and die. Default null.
 * @param int   $status_code Optional. The HTTP status code to output. Default null.
 * @param int   $options     Optional. Options to be passed to json_encode(). Default 0.
 */
function wp_send_json_success( $data = null, $status_code = null, $options = 0 ) {
    $response = array( 'success' => true );

    if ( isset( $data ) ) {
        $response['data'] = $data;
    }

    wp_send_json( $response, $status_code, $options );
}

/**
 * Send a JSON response back to an Ajax request, indicating failure.
 *
 * If the `$data` parameter is a WP_Error object, the errors
 * within the object are processed and output as an array of error
 * codes and corresponding messages. All other types are output
 * without further processing.
 *
 * @since 3.5.0
 * @since 4.1.0 The `$data` parameter is now processed if a WP_Error object is passed in.
 * @since 4.7.0 The `$status_code` parameter was added.
 * @since 5.6.0 The `$options` parameter was added.
 *
 * @param mixed $data        Optional. Data to encode as JSON, then print and die. Default null.
 * @param int   $status_code Optional. The HTTP status code to output. Default null.
 * @param int   $options     Optional. Options to be passed to json_encode(). Default 0.
 */
function wp_send_json_error( $data = null, $status_code = null, $options = 0 ) {
    $response = array( 'success' => false );

    if ( isset( $data ) ) {
        if ( is_wp_error( $data ) ) {
            $result = array();
            foreach ( $data->errors as $code => $messages ) {
                foreach ( $messages as $message ) {
                    $result[] = array(
                      'code'    => $code,
                      'message' => $message,
                    );
                }
            }

            $response['data'] = $result;
        } else {
            $response['data'] = $data;
        }
    }

    wp_send_json( $response, $status_code, $options );
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
 * Reads and decodes a JSON file.
 *
 * @since 5.9.0
 *
 * @param string $filename Path to the JSON file.
 * @param array  $options  {
 *     Optional. Options to be used with `json_decode()`.
 *
 *     @type bool associative Optional. When `true`, JSON objects will be returned as associative arrays.
 *                            When `false`, JSON objects will be returned as objects.
 * }
 *
 * @return mixed Returns the value encoded in JSON in appropriate PHP type.
 *               `null` is returned if the file is not found, or its content can't be decoded.
 */
function wp_json_file_decode( $filename, $options = array() ) {
    $filename = wp_normalize_path( realpath( $filename ) );
    if ( ! file_exists( $filename ) ) {
        trigger_error(
          sprintf(
          /* translators: %s: Path to the JSON file. */
            __( "File %s doesn't exist!" ),
            $filename
          )
        );

        return null;
    }

    $options      = wp_parse_args( $options, array( 'associative' => false ) );
    $decoded_file = json_decode( file_get_contents( $filename ), $options['associative'] );

    if ( JSON_ERROR_NONE !== json_last_error() ) {
        trigger_error(
          sprintf(
          /* translators: 1: Path to the JSON file, 2: Error message. */
            __( 'Error when decoding a JSON file at path %1$s: %2$s' ),
            $filename,
            json_last_error_msg()
          )
        );

        return null;
    }

    return $decoded_file;
}