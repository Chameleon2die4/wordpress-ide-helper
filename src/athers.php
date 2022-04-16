<?php /** @noinspection PhpUnused */
/** @noinspection SpellCheckingInspection */
/** @noinspection PhpUnusedParameterInspection */
/** @noinspection PhpUndefinedConstantInspection */

/**
 * Retrieve the format slug for a post
 *
 * @since 3.1.0
 *
 * @param int|WP_Post|null $post Optional. Post ID or post object. Defaults to the current post in the loop.
 * @return string|false The format if successful. False otherwise.
 */
function get_post_format( $post = null ) {
    $post = get_post( $post );

    if ( ! $post ) {
        return false;
    }

    if ( ! post_type_supports( $post->post_type, 'post-formats' ) ) {
        return false;
    }

    $_format = get_the_terms( $post->ID, 'post_format' );

    if ( empty( $_format ) ) {
        return false;
    }

    $format = reset( $_format );

    return str_replace( 'post-format-', '', $format->slug );
}

/**
 * Retrieve the name of the highest priority template file that exists.
 *
 * Searches in the STYLESHEETPATH before TEMPLATEPATH and wp-includes/theme-compat
 * so that themes which inherit from a parent theme can just overload one file.
 *
 * @since 2.7.0
 * @since 5.5.0 The `$args` parameter was added.
 *
 * @param string|array $template_names Template file(s) to search for, in order.
 * @param bool         $load           If true the template file will be loaded if it is found.
 * @param bool         $require_once   Whether to require_once or require. Has no effect if `$load` is false.
 *                                     Default true.
 * @param array        $args           Optional. Additional arguments passed to the template.
 *                                     Default empty array.
 * @return string The template filename if one is located.
 */
function locate_template( $template_names, $load = false, $require_once = true, $args = array() ) {
    $located = '';
    foreach ( (array) $template_names as $template_name ) {
        if ( ! $template_name ) {
            continue;
        }
        if ( file_exists( STYLESHEETPATH . '/' . $template_name ) ) {
            $located = STYLESHEETPATH . '/' . $template_name;
            break;
        } elseif ( file_exists( TEMPLATEPATH . '/' . $template_name ) ) {
            $located = TEMPLATEPATH . '/' . $template_name;
            break;
        } elseif ( file_exists( ABSPATH . WPINC . '/theme-compat/' . $template_name ) ) {
            $located = ABSPATH . WPINC . '/theme-compat/' . $template_name;
            break;
        }
    }

    if ( $load && '' !== $located ) {
        load_template( $located, $require_once, $args );
    }

    return $located;
}

/**
 * Require the template file with WordPress environment.
 *
 * The globals are set up for the template file to ensure that the WordPress
 * environment is available from within the function. The query variables are
 * also available.
 *
 * @param string $_template_file Path to template file.
 * @param bool   $require_once   Whether to require_once or require. Default true.
 * @param array  $args           Optional. Additional arguments passed to the template.
 *                               Default empty array.
 * @noinspection SpellCheckingInspection*@since 1.5.0
 * @since 5.5.0 The `$args` parameter was added.
 *
 * @global array      $posts
 * @global WP_Post    $post          Global post object.
 * @global bool       $wp_did_header
 * @global WP_Query   $wp_query      WordPress Query object.
 * @global WP_Rewrite $wp_rewrite    WordPress rewrite component.
 * @global wpdb       $wpdb          WordPress database abstraction object.
 * @global string     $wp_version
 * @global WP         $wp            Current WordPress environment instance.
 * @global int        $id
 * @global WP_Comment $comment       Global comment object.
 * @global int $user_ID
 *
 */
function load_template( $_template_file, $require_once = true, $args = array() ) {
    global /** @noinspection PhpUnusedLocalVariableInspection */
    $posts, $post, $wp_did_header, $wp_query, $wp_rewrite, $wpdb, $wp_version, $wp, $id, $comment, $user_ID;

    if ( is_array( $wp_query->query_vars ) ) {
        /*
         * This use of extract() cannot be removed. There are many possible ways that
         * templates could depend on variables that it creates existing, and no way to
         * detect and deprecate it.
         *
         * Passing the EXTR_SKIP flag is the safest option, ensuring globals and
         * function variables cannot be overwritten.
         */
        // phpcs:ignore WordPress.PHP.DontExtract.extract_extract
        extract( $wp_query->query_vars, EXTR_SKIP );
    }

    if ( isset( $s ) ) {
        $s = esc_attr( $s );
    }

    if ( $require_once ) {
        require_once $_template_file;
    } else {
        require $_template_file;
    }
}


/**
 * Strips the #fragment from a URL, if one is present.
 *
 * @since 4.4.0
 *
 * @param string $url The URL to strip.
 * @return string The altered URL.
 */
function strip_fragment_from_url( $url ) {
    $parsed_url = parse_url( $url );

    if ( ! empty( $parsed_url['host'] ) ) {
        // These mirrors code in redirect_canonical(). It does not handle every case.
        $url = $parsed_url['scheme'] . '://' . $parsed_url['host'];
        if ( ! empty( $parsed_url['port'] ) ) {
            $url .= ':' . $parsed_url['port'];
        }

        if ( ! empty( $parsed_url['path'] ) ) {
            $url .= $parsed_url['path'];
        }

        if ( ! empty( $parsed_url['query'] ) ) {
            $url .= '?' . $parsed_url['query'];
        }
    }

    return $url;
}

/**
 * Retrieve the current session token from the logged_in cookie.
 *
 * @since 4.0.0
 *
 * @return string Token.
 */
function wp_get_session_token() {
    $cookie = wp_parse_auth_cookie( '', 'logged_in' );
    return ! empty( $cookie['token'] ) ? $cookie['token'] : '';
}


/**
 * RSS container for the bloginfo function.
 *
 * You can retrieve anything that you can using the get_bloginfo() function.
 * Everything will be stripped of tags and characters converted, when the values
 * are retrieved for use in the feeds.
 *
 * @since 1.5.1
 *
 * @see get_bloginfo() For the list of possible values to display.
 *
 * @param string $show See get_bloginfo() for possible values.
 * @return string
 */
function get_bloginfo_rss( $show = '' ) {
    $info = strip_tags( get_bloginfo( $show ) );
    /**
     * Filters the bloginfo for use in RSS feeds.
     *
     * @since 2.2.0
     *
     * @see convert_chars()
     * @see get_bloginfo()
     *
     * @param string $info Converted string value of the blog information.
     * @param string $show The type of blog information to retrieve.
     */
    return apply_filters( 'get_bloginfo_rss', convert_chars( $info ), $show );
}

/**
 * Display RSS container for the bloginfo function.
 *
 * You can retrieve anything that you can using the get_bloginfo() function.
 * Everything will be stripped of tags and characters converted, when the values
 * are retrieved for use in the feeds.
 *
 * @since 0.71
 *
 * @see get_bloginfo() For the list of possible values to display.
 *
 * @param string $show See get_bloginfo() for possible values.
 */
function bloginfo_rss( $show = '' ) {
    /**
     * Filters the bloginfo for display in RSS feeds.
     *
     * @since 2.1.0
     *
     * @see get_bloginfo()
     *
     * @param string $rss_container RSS container for the blog information.
     * @param string $show          The type of blog information to retrieve.
     */
    echo apply_filters( 'bloginfo_rss', get_bloginfo_rss( $show ), $show );
}

/**
 * Retrieve the default feed.
 *
 * The default feed is 'rss2', unless a plugin changes it through the
 * {@see 'default_feed'} filter.
 *
 * @since 2.5.0
 *
 * @return string Default feed, or for example 'rss2', 'atom', etc.
 */
function get_default_feed() {
    /**
     * Filters the default feed type.
     *
     * @since 2.5.0
     *
     * @param string $feed_type Type of default feed. Possible values include 'rss2', 'atom'.
     *                          Default 'rss2'.
     */
    $default_feed = apply_filters( 'default_feed', 'rss2' );

    return ( 'rss' === $default_feed ) ? 'rss2' : $default_feed;
}


/**
 * Retrieves post categories.
 *
 * This tag may be used outside The Loop by passing a post ID as the parameter.
 *
 * Note: This function only returns results from the default "category" taxonomy.
 * For custom taxonomies use get_the_terms().
 *
 * @since 0.71
 *
 * @param int $post_id Optional. The post ID. Defaults to current post ID.
 * @return WP_Term[] Array of WP_Term objects, one for each category assigned to the post.
 */
function get_the_category( $post_id = false ) {
    $categories = get_the_terms( $post_id, 'category' );
    if ( ! $categories || is_wp_error( $categories ) ) {
        $categories = array();
    }

    $categories = array_values( $categories );

    foreach ( array_keys( $categories ) as $key ) {
        /** @noinspection PhpUndefinedFunctionInspection */
        _make_cat_compat($categories[$key ] );
    }

    /**
     * Filters the array of categories to return for a post.
     *
     * @since 3.1.0
     * @since 4.4.0 Added `$post_id` parameter.
     *
     * @param WP_Term[] $categories An array of categories to return for the post.
     * @param int|false $post_id    ID of the post.
     */
    return apply_filters( 'get_the_categories', $categories, $post_id );
}
