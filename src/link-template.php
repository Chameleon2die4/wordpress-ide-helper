<?php /** @noinspection PhpUndefinedConstantInspection */

/**
 * Retrieves the URL for the current site where the front end is accessible.
 *
 * Returns the 'home' option with the appropriate protocol. The protocol will be 'https'
 * if is_ssl() evaluates to true; otherwise, it will be the same as the 'home' option.
 * If `$scheme` is 'http' or 'https', is_ssl() is overridden.
 *
 * @since 3.0.0
 *
 * @param string      $path   Optional. Path relative to the home URL. Default empty.
 * @param string|null $scheme Optional. Scheme to give the home URL context. Accepts
 *                            'http', 'https', 'relative', 'rest', or null. Default null.
 * @return string Home URL link with optional path appended.
 */
function home_url( $path = '', $scheme = null ) {
    return get_home_url( null, $path, $scheme );
}

/**
 * Retrieves the URL for a given site where the front end is accessible.
 *
 * Returns the 'home' option with the appropriate protocol. The protocol will be 'https'
 * if is_ssl() evaluates to true; otherwise, it will be the same as the 'home' option.
 * If `$scheme` is 'http' or 'https', is_ssl() is overridden.
 *
 * @since 3.0.0
 *
 * @param int|null    $blog_id Optional. Site ID. Default null (current site).
 * @param string      $path    Optional. Path relative to the home URL. Default empty.
 * @param string|null $scheme  Optional. Scheme to give the home URL context. Accepts
 *                             'http', 'https', 'relative', 'rest', or null. Default null.
 * @return string Home URL link with optional path appended.
 */
function get_home_url( $blog_id = null, $path = '', $scheme = null ) {
    $orig_scheme = $scheme;

    if ( empty( $blog_id ) || ! is_multisite() ) {
        $url = get_option( 'home' );
    } else {
        switch_to_blog( $blog_id );
        $url = get_option( 'home' );
        restore_current_blog();
    }

    if ( ! in_array( $scheme, array( 'http', 'https', 'relative' ), true ) ) {
        if ( is_ssl() ) {
            $scheme = 'https';
        } else {
            $scheme = parse_url( $url, PHP_URL_SCHEME );
        }
    }

    $url = set_url_scheme( $url, $scheme );

    if ( $path && is_string( $path ) ) {
        $url .= '/' . ltrim( $path, '/' );
    }

    /**
     * Filters the home URL.
     *
     * @since 3.0.0
     *
     * @param string      $url         The complete home URL including scheme and path.
     * @param string      $path        Path relative to the home URL. Blank string if no path is specified.
     * @param string|null $orig_scheme Scheme to give the home URL context. Accepts 'http', 'https',
     *                                 'relative', 'rest', or null.
     * @param int|null    $blog_id     Site ID, or null for the current site.
     */
    return apply_filters( 'home_url', $url, $path, $orig_scheme, $blog_id );
}

/**
 * Retrieves the URL for the current site where WordPress application files
 * (e.g. wp-blog-header.php or the wp-admin/ folder) are accessible.
 *
 * Returns the 'site_url' option with the appropriate protocol, 'https' if
 * is_ssl() and 'http' otherwise. If $scheme is 'http' or 'https', is_ssl() is
 * overridden.
 *
 * @since 3.0.0
 *
 * @param string      $path   Optional. Path relative to the site URL. Default empty.
 * @param string|null $scheme Optional. Scheme to give the site URL context. See set_url_scheme().
 * @return string Site URL link with optional path appended.
 */
function site_url( $path = '', $scheme = null ) {
    return get_site_url( null, $path, $scheme );
}

/**
 * Retrieves the URL for a given site where WordPress application files
 * (e.g. wp-blog-header.php or the wp-admin/ folder) are accessible.
 *
 * Returns the 'site_url' option with the appropriate protocol, 'https' if
 * is_ssl() and 'http' otherwise. If `$scheme` is 'http' or 'https',
 * `is_ssl()` is overridden.
 *
 * @since 3.0.0
 *
 * @param int|null    $blog_id Optional. Site ID. Default null (current site).
 * @param string      $path    Optional. Path relative to the site URL. Default empty.
 * @param string|null $scheme  Optional. Scheme to give the site URL context. Accepts
 *                             'http', 'https', 'login', 'login_post', 'admin', or
 *                             'relative'. Default null.
 * @return string Site URL link with optional path appended.
 */
function get_site_url( $blog_id = null, $path = '', $scheme = null ) {
    if ( empty( $blog_id ) || ! is_multisite() ) {
        $url = get_option( 'siteurl' );
    } else {
        switch_to_blog( $blog_id );
        $url = get_option( 'siteurl' );
        restore_current_blog();
    }

    $url = set_url_scheme( $url, $scheme );

    if ( $path && is_string( $path ) ) {
        $url .= '/' . ltrim( $path, '/' );
    }

    /**
     * Filters the site URL.
     *
     * @since 2.7.0
     *
     * @param string      $url     The complete site URL including scheme and path.
     * @param string      $path    Path relative to the site URL. Blank string if no path is specified.
     * @param string|null $scheme  Scheme to give the site URL context. Accepts 'http', 'https', 'login',
     *                             'login_post', 'admin', 'relative' or null.
     * @param int|null    $blog_id Site ID, or null for the current site.
     */
    return apply_filters( 'site_url', $url, $path, $scheme, $blog_id );
}

/**
 * Retrieves the URL to the admin area for the current site.
 *
 * @since 2.6.0
 *
 * @param string $path   Optional. Path relative to the admin URL. Default 'admin'.
 * @param string $scheme The scheme to use. Default is 'admin', which obeys force_ssl_admin() and is_ssl().
 *                       'http' or 'https' can be passed to force those schemes.
 * @return string Admin URL link with optional path appended.
 */
function admin_url( $path = '', $scheme = 'admin' ) {
    return get_admin_url( null, $path, $scheme );
}

/**
 * Retrieves the URL to the admin area for a given site.
 *
 * @since 3.0.0
 *
 * @param int|null $blog_id Optional. Site ID. Default null (current site).
 * @param string   $path    Optional. Path relative to the admin URL. Default empty.
 * @param string   $scheme  Optional. The scheme to use. Accepts 'http' or 'https',
 *                          to force those schemes. Default 'admin', which obeys
 *                          force_ssl_admin() and is_ssl().
 * @return string Admin URL link with optional path appended.
 */
function get_admin_url( $blog_id = null, $path = '', $scheme = 'admin' ) {
    $url = get_site_url( $blog_id, 'wp-admin/', $scheme );

    if ( $path && is_string( $path ) ) {
        $url .= ltrim( $path, '/' );
    }

    /**
     * Filters the admin area URL.
     *
     * @since 2.8.0
     * @since 5.8.0 The `$scheme` parameter was added.
     *
     * @param string      $url     The complete admin area URL including scheme and path.
     * @param string      $path    Path relative to the admin area URL. Blank string if no path is specified.
     * @param int|null    $blog_id Site ID, or null for the current site.
     * @param string|null $scheme  The scheme to use. Accepts 'http', 'https',
     *                             'admin', or null. Default 'admin', which obeys force_ssl_admin() and is_ssl().
     */
    return apply_filters( 'admin_url', $url, $path, $blog_id, $scheme );
}

/**
 * Retrieves the URL to the includes directory.
 *
 * @since 2.6.0
 *
 * @param string      $path   Optional. Path relative to the includes URL. Default empty.
 * @param string|null $scheme Optional. Scheme to give the includes URL context. Accepts
 *                            'http', 'https', or 'relative'. Default null.
 * @return string Includes URL link with optional path appended.
 */
function includes_url( $path = '', $scheme = null ) {
    $url = site_url( '/' . WPINC . '/', $scheme );

    if ( $path && is_string( $path ) ) {
        $url .= ltrim( $path, '/' );
    }

    /**
     * Filters the URL to the includes directory.
     *
     * @since 2.8.0
     * @since 5.8.0 The `$scheme` parameter was added.
     *
     * @param string      $url    The complete URL to the includes directory including scheme and path.
     * @param string      $path   Path relative to the URL to the wp-includes directory. Blank string
     *                            if no path is specified.
     * @param string|null $scheme Scheme to give the includes URL context. Accepts
     *                            'http', 'https', 'relative', or null. Default null.
     */
    return apply_filters( 'includes_url', $url, $path, $scheme );
}

/**
 * Retrieves the URL to the content directory.
 *
 * @since 2.6.0
 *
 * @param string $path Optional. Path relative to the content URL. Default empty.
 * @return string Content URL link with optional path appended.
 */
function content_url( $path = '' ) {
    $url = set_url_scheme( WP_CONTENT_URL );

    if ( $path && is_string( $path ) ) {
        $url .= '/' . ltrim( $path, '/' );
    }

    /**
     * Filters the URL to the content directory.
     *
     * @since 2.8.0
     *
     * @param string $url  The complete URL to the content directory including scheme and path.
     * @param string $path Path relative to the URL to the content directory. Blank string
     *                     if no path is specified.
     */
    return apply_filters( 'content_url', $url, $path );
}

/**
 * Retrieves a URL within the plugins or mu-plugins directory.
 *
 * Defaults to the plugins directory URL if no arguments are supplied.
 *
 * @since 2.6.0
 *
 * @param string $path   Optional. Extra path appended to the end of the URL, including
 *                       the relative directory if $plugin is supplied. Default empty.
 * @param string $plugin Optional. A full path to a file inside a plugin or mu-plugin.
 *                       The URL will be relative to its directory. Default empty.
 *                       Typically this is done by passing `__FILE__` as the argument.
 * @return string Plugins URL link with optional paths appended.
 */
function plugins_url( $path = '', $plugin = '' ) {

    $path          = wp_normalize_path( $path );
    $plugin        = wp_normalize_path( $plugin );
    $mu_plugin_dir = wp_normalize_path( WPMU_PLUGIN_DIR );

    if ( ! empty( $plugin ) && 0 === strpos( $plugin, $mu_plugin_dir ) ) {
        $url = WPMU_PLUGIN_URL;
    } else {
        $url = WP_PLUGIN_URL;
    }

    $url = set_url_scheme( $url );

    if ( ! empty( $plugin ) && is_string( $plugin ) ) {
        $folder = dirname( plugin_basename( $plugin ) );
        if ( '.' !== $folder ) {
            $url .= '/' . ltrim( $folder, '/' );
        }
    }

    if ( $path && is_string( $path ) ) {
        $url .= '/' . ltrim( $path, '/' );
    }

    /**
     * Filters the URL to the plugins directory.
     *
     * @since 2.8.0
     *
     * @param string $url    The complete URL to the plugins directory including scheme and path.
     * @param string $path   Path relative to the URL to the plugins directory. Blank string
     *                       if no path is specified.
     * @param string $plugin The plugin file path to be relative to. Blank string if no plugin
     *                       is specified.
     */
    return apply_filters( 'plugins_url', $url, $path, $plugin );
}

/**
 * Sets the scheme for a URL.
 *
 * @since 3.4.0
 * @since 4.4.0 The 'rest' scheme was added.
 *
 * @param string      $url    Absolute URL that includes a scheme
 * @param string|null $scheme Optional. Scheme to give $url. Currently 'http', 'https', 'login',
 *                            'login_post', 'admin', 'relative', 'rest', 'rpc', or null. Default null.
 * @return string URL with chosen scheme.
 */
function set_url_scheme( $url, $scheme = null ) {
    $orig_scheme = $scheme;

    if ( ! $scheme ) {
        $scheme = is_ssl() ? 'https' : 'http';
    } elseif ( 'admin' === $scheme || 'login' === $scheme || 'login_post' === $scheme || 'rpc' === $scheme ) {
        $scheme = is_ssl() || force_ssl_admin() ? 'https' : 'http';
    } elseif ( 'http' !== $scheme && 'https' !== $scheme && 'relative' !== $scheme ) {
        $scheme = is_ssl() ? 'https' : 'http';
    }

    $url = trim( $url );
    if ( substr( $url, 0, 2 ) === '//' ) {
        $url = 'http:' . $url;
    }

    if ( 'relative' === $scheme ) {
        $url = ltrim( preg_replace( '#^\w+://[^/]*#', '', $url ) );
        if ( '' !== $url && '/' === $url[0] ) {
            $url = '/' . ltrim( $url, "/ \t\n\r\0\x0B" );
        }
    } else {
        $url = preg_replace( '#^\w+://#', $scheme . '://', $url );
    }

    /**
     * Filters the resulting URL after setting the scheme.
     *
     * @since 3.4.0
     *
     * @param string      $url         The complete URL including scheme and path.
     * @param string      $scheme      Scheme applied to the URL. One of 'http', 'https', or 'relative'.
     * @param string|null $orig_scheme Scheme requested for the URL. One of 'http', 'https', 'login',
     *                                 'login_post', 'admin', 'relative', 'rest', 'rpc', or null.
     */
    return apply_filters( 'set_url_scheme', $url, $scheme, $orig_scheme );
}