<?php /** @noinspection SpellCheckingInspection */
/** @noinspection PhpUnused */
/** @noinspection PhpDocSignatureInspection */
/** @noinspection PhpArrayPushWithOneElementInspection */
/** @noinspection RegExpUnnecessaryNonCapturingGroup */
/** @noinspection PhpIncludeInspection */
/** @noinspection SqlNoDataSourceInspection */
/** @noinspection HttpUrlsUsage */

/**
 * Use RegEx to extract URLs from arbitrary content.
 *
 * @since 3.7.0
 *
 * @param string $content Content to extract URLs from.
 * @return string[] Array of URLs found in passed string.
 */
function wp_extract_urls( $content ) {
    /** @noinspection RegExpSimplifiable */
    preg_match_all(
      "#([\"']?)("
      . '(?:([\w-]+:)?//?)'
      . '[^\s()<>]+'
      . '[.]'
      . '(?:'
      . '\([\w\d]+\)|'
      . '(?:'
      . "[^`!()\[\]{};:'\".,<>«»“”‘’\s]|"
      . '(?:[:]\d+)?/?'
      . ')+'
      . ')'
      . ")\\1#",
      $content,
      $post_links
    );

    $post_links = array_unique( array_map( 'html_entity_decode', $post_links[2] ) );

    return array_values( $post_links );
}

/**
 * Check content for video and audio links to add as enclosures.
 *
 * Will not add enclosures that have already been added and will
 * remove enclosures that are no longer in the post. This is called as
 * pingbacks and trackbacks.
 *
 * @since 1.5.0
 * @since 5.3.0 The `$content` parameter was made optional, and the `$post` parameter was
 *              updated to accept a post ID or a WP_Post object.
 * @since 5.6.0 The `$content` parameter is no longer optional, but passing `null` to skip it
 *              is still supported.
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param string|null $content Post content. If `null`, the `post_content` field from `$post` is used.
 * @param int|WP_Post $post    Post ID or post object.
 * @return void|false Void on success, false if the post is not found.
 */
function do_enclose( $content, $post ) {
    global $wpdb;

    // @todo Tidy this code and make the debug code optional.
    include_once ABSPATH . WPINC . '/class-IXR.php';

    $post = get_post( $post );
    if ( ! $post ) {
        return false;
    }

    if ( null === $content ) {
        $content = $post->post_content;
    }

    $post_links = array();

    $pung = get_enclosed( $post->ID );

    $post_links_temp = wp_extract_urls( $content );

    foreach ( $pung as $link_test ) {
        // Link is no longer in post.
        if ( ! in_array( $link_test, $post_links_temp, true ) ) {
            $mids = $wpdb->get_col( $wpdb->prepare( "SELECT meta_id FROM $wpdb->postmeta WHERE post_id = %d AND meta_key = 'enclosure' AND meta_value LIKE %s", $post->ID, $wpdb->esc_like( $link_test ) . '%' ) );
            foreach ( $mids as $mid ) {
                /** @noinspection PhpUndefinedFunctionInspection */
                delete_metadata_by_mid( 'post', $mid );
            }
        }
    }

    /** @noinspection PhpCastIsUnnecessaryInspection */
    foreach ((array) $post_links_temp as $link_test ) {
        // If we haven't pung it already.
        if ( ! in_array( $link_test, $pung, true ) ) {
            $test = parse_url( $link_test );
            if ( false === $test ) {
                continue;
            }
            if ( isset( $test['query'] ) ) {
                $post_links[] = $link_test;
            } elseif ( isset( $test['path'] ) && ( '/' !== $test['path'] ) && ( '' !== $test['path'] ) ) {
                $post_links[] = $link_test;
            }
        }
    }

    /**
     * Filters the list of enclosure links before querying the database.
     *
     * Allows for the addition and/or removal of potential enclosures to save
     * to postmeta before checking the database for existing enclosures.
     *
     * @since 4.4.0
     *
     * @param string[] $post_links An array of enclosure links.
     * @param int      $post_ID    Post ID.
     */
    $post_links = apply_filters( 'enclosure_links', $post_links, $post->ID );

    foreach ( (array) $post_links as $url ) {
        $url = strip_fragment_from_url( $url );

        if ( '' !== $url && ! $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE post_id = %d AND meta_key = 'enclosure' AND meta_value LIKE %s", $post->ID, $wpdb->esc_like( $url ) . '%' ) ) ) {

            $headers = wp_get_http_headers( $url );
            if ( $headers ) {
                $len           = isset( $headers['content-length'] ) ? (int) $headers['content-length'] : 0;
                $type          = isset( $headers['content-type'] ) ? $headers['content-type'] : '';
                $allowed_types = array( 'video', 'audio' );

                // Check to see if we can figure out the mime type from the extension.
                $url_parts = parse_url( $url );
                if ( false !== $url_parts && ! empty( $url_parts['path'] ) ) {
                    $extension = pathinfo( $url_parts['path'], PATHINFO_EXTENSION );
                    if ( ! empty( $extension ) ) {
                        foreach ( wp_get_mime_types() as $exts => $mime ) {
                            if ( preg_match( '!^(' . $exts . ')$!i', $extension ) ) {
                                $type = $mime;
                                break;
                            }
                        }
                    }
                }

                if ( in_array( substr( $type, 0, strpos( $type, '/' ) ), $allowed_types, true ) ) {
                    /** @noinspection PhpUndefinedVariableInspection */
                    add_post_meta( $post->ID, 'enclosure', "$url\n$len\n$mime\n" );
                }
            }
        }
    }
}

/**
 * Retrieve HTTP Headers from URL.
 *
 * @since 1.5.1
 *
 * @param string $url        URL to retrieve HTTP headers from.
 * @param bool   $deprecated Not Used.
 * @return string|array|false Headers on success, false on failure.
 */
function wp_get_http_headers( $url, $deprecated = false ) {
    if ( ! empty( $deprecated ) ) {
        _deprecated_argument( __FUNCTION__, '2.7.0' );
    }

    /** @noinspection PhpUndefinedFunctionInspection */
    $response = wp_safe_remote_head( $url );

    if ( is_wp_error( $response ) ) {
        return false;
    }

    /** @noinspection PhpUndefinedFunctionInspection */
    return wp_remote_retrieve_headers( $response );
}

/**
 * Build URL query based on an associative and, or indexed array.
 *
 * This is a convenient function for easily building url queries. It sets the
 * separator to '&' and uses _http_build_query() function.
 *
 * @since 2.3.0
 *
 * @see _http_build_query() Used to build the query
 * @link https://www.php.net/manual/en/function.http-build-query.php for more on what
 *       http_build_query() does.
 *
 * @param array $data URL-encode key/value pairs.
 * @return string URL-encoded string.
 */
function build_query( $data ) {
    return _http_build_query( $data, null, '&', '', false );
}

/**
 * From php.net (modified by Mark Jaquith to behave like the native PHP5 function).
 *
 * @since 3.2.0
 * @access private
 *
 * @see https://www.php.net/manual/en/function.http-build-query.php
 *
 * @param array|object $data      An array or object of data. Converted to array.
 * @param string       $prefix    Optional. Numeric index. If set, start parameter numbering with it.
 *                                Default null.
 * @param string       $sep       Optional. Argument separator; defaults to 'arg_separator.output'.
 *                                Default null.
 * @param string       $key       Optional. Used to prefix key name. Default empty.
 * @param bool         $urlencode Optional. Whether to use urlencode() in the result. Default true.
 * @return string The query string.
 */
function _http_build_query( $data, $prefix = null, $sep = null, $key = '', $urlencode = true ) {
    $ret = array();

    foreach ( (array) $data as $k => $v ) {
        if ( $urlencode ) {
            $k = urlencode( $k );
        }
        if ( is_int( $k ) && null != $prefix ) {
            $k = $prefix . $k;
        }
        if ( ! empty( $key ) ) {
            $k = $key . '%5B' . $k . '%5D';
        }
        if ( null === $v ) {
            continue;
        } elseif ( false === $v ) {
            $v = '0';
        }

        if ( is_array( $v ) || is_object( $v ) ) {
            array_push( $ret, _http_build_query( $v, '', $sep, $k, $urlencode ) );
        } elseif ( $urlencode ) {
            array_push( $ret, $k . '=' . urlencode( $v ) );
        } else {
            array_push( $ret, $k . '=' . $v );
        }
    }

    if ( null === $sep ) {
        $sep = ini_get( 'arg_separator.output' );
    }

    return implode( $sep, $ret );
}

/**
 * Retrieves a modified URL query string.
 *
 * You can rebuild the URL and append query variables to the URL query by using this function.
 * There are two ways to use this function; either a single key and value, or an associative array.
 *
 * Using a single key and value:
 *
 *     add_query_arg( 'key', 'value', 'http://example.com' );
 *
 * Using an associative array:
 *
 *     add_query_arg( array(
 *         'key1' => 'value1',
 *         'key2' => 'value2',
 *     ), 'http://example.com' );
 *
 * Omitting the URL from either use results in the current URL being used
 * (the value of `$_SERVER['REQUEST_URI']`).
 *
 * Values are expected to be encoded appropriately with urlencode() or rawurlencode().
 *
 * Setting any query variable's value to boolean false removes the key (see remove_query_arg()).
 *
 * Important: The return value of add_query_arg() is not escaped by default. Output should be
 * late-escaped with esc_url() or similar to help prevent vulnerability to cross-site scripting
 * (XSS) attacks.
 *
 * @since 1.5.0
 * @since 5.3.0 Formalized the existing and already documented parameters
 *              by adding `...$args` to the function signature.
 *
 * @param string|array $key   Either a query variable key, or an associative array of query variables.
 * @param string       $value Optional. Either a query variable value, or a URL to act upon.
 * @param string       $url   Optional. A URL to act upon.
 * @return string New URL query string (unescaped).
 */
function add_query_arg( ...$args ) {
    if ( is_array( $args[0] ) ) {
        if ( count( $args ) < 2 || false === $args[1] ) {
            $uri = $_SERVER['REQUEST_URI'];
        } else {
            $uri = $args[1];
        }
    } else {
        if ( count( $args ) < 3 || false === $args[2] ) {
            $uri = $_SERVER['REQUEST_URI'];
        } else {
            $uri = $args[2];
        }
    }

    $frag = strstr( $uri, '#' );
    if ( $frag ) {
        $uri = substr( $uri, 0, -strlen( $frag ) );
    } else {
        $frag = '';
    }

    if ( 0 === stripos( $uri, 'http://' ) ) {
        $protocol = 'http://';
        $uri      = substr( $uri, 7 );
    } elseif ( 0 === stripos( $uri, 'https://' ) ) {
        $protocol = 'https://';
        $uri      = substr( $uri, 8 );
    } else {
        $protocol = '';
    }

    if ( strpos( $uri, '?' ) !== false ) {
        list( $base, $query ) = explode( '?', $uri, 2 );
        $base                .= '?';
    } elseif ( $protocol || strpos( $uri, '=' ) === false ) {
        $base  = $uri . '?';
        $query = '';
    } else {
        $base  = '';
        $query = $uri;
    }

    wp_parse_str( $query, $qs );
    $qs = urlencode_deep( $qs ); // This re-URL-encodes things that were already in the query string.
    if ( is_array( $args[0] ) ) {
        foreach ( $args[0] as $k => $v ) {
            $qs[ $k ] = $v;
        }
    } else {
        $qs[ $args[0] ] = $args[1];
    }

    foreach ( $qs as $k => $v ) {
        if ( false === $v ) {
            unset( $qs[ $k ] );
        }
    }

    $ret = build_query( $qs );
    $ret = trim( $ret, '?' );
    $ret = preg_replace( '#=(&|$)#', '$1', $ret );
    $ret = $protocol . $base . $ret . $frag;
    $ret = rtrim( $ret, '?' );
    return str_replace( '?#', '#', $ret );
}

/**
 * Removes an item or items from a query string.
 *
 * @since 1.5.0
 *
 * @param string|string[] $key   Query key or keys to remove.
 * @param false|string    $query Optional. When false uses the current URL. Default false.
 * @return string New URL query string.
 */
function remove_query_arg( $key, $query = false ) {
    if ( is_array( $key ) ) { // Removing multiple keys.
        foreach ( $key as $k ) {
            $query = add_query_arg( $k, false, $query );
        }
        return $query;
    }
    return add_query_arg( $key, false, $query );
}

/**
 * Returns an array of single-use query variable names that can be removed from a URL.
 *
 * @since 4.4.0
 *
 * @return string[] An array of query variable names to remove from the URL.
 */
function wp_removable_query_args() {
    $removable_query_args = array(
      'activate',
      'activated',
      'admin_email_remind_later',
      'approved',
      'core-major-auto-updates-saved',
      'deactivate',
      'delete_count',
      'deleted',
      'disabled',
      'doing_wp_cron',
      'enabled',
      'error',
      'hotkeys_highlight_first',
      'hotkeys_highlight_last',
      'ids',
      'locked',
      'message',
      'same',
      'saved',
      'settings-updated',
      'skipped',
      'spammed',
      'trashed',
      'unspammed',
      'untrashed',
      'update',
      'updated',
      'wp-post-new-reload',
    );

    /**
     * Filters the list of query variable names to remove.
     *
     * @since 4.2.0
     *
     * @param string[] $removable_query_args An array of query variable names to remove from a URL.
     */
    return apply_filters( 'removable_query_args', $removable_query_args );
}