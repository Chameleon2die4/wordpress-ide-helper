<?php /** @noinspection GrazieInspection */
/** @noinspection PhpUnused */
/** @noinspection PhpMissingParamTypeInspection */
/** @noinspection PhpUndefinedConstantInspection */

/**
 * Theme, template, and stylesheet functions.
 *
 * @package WordPress
 * @subpackage Theme
 */


/**
 * Retrieves name of the current stylesheet.
 *
 * The theme name that is currently set as the front end theme.
 *
 * For all intents and purposes, the template name and the stylesheet name
 * are going to be the same for most cases.
 *
 * @since 1.5.0
 *
 * @return string Stylesheet name.
 */
function get_stylesheet() {
    /**
     * Filters the name of current stylesheet.
     *
     * @since 1.5.0
     *
     * @param string $stylesheet Name of the current stylesheet.
     */
    return apply_filters( 'stylesheet', get_option( 'stylesheet' ) );
}

/**
 * Retrieves stylesheet directory path for current theme.
 *
 * @since 1.5.0
 *
 * @return string Path to current theme's stylesheet directory.
 */
function get_stylesheet_directory() {
    $stylesheet     = get_stylesheet();
    $theme_root     = get_theme_root( $stylesheet );
    $stylesheet_dir = "$theme_root/$stylesheet";

    /**
     * Filters the stylesheet directory path for current theme.
     *
     * @since 1.5.0
     *
     * @param string $stylesheet_dir Absolute path to the current theme.
     * @param string $stylesheet     Directory name of the current theme.
     * @param string $theme_root     Absolute path to themes directory.
     */
    return apply_filters( 'stylesheet_directory', $stylesheet_dir, $stylesheet, $theme_root );
}

/**
 * Retrieves stylesheet directory URI for current theme.
 *
 * @since 1.5.0
 *
 * @return string URI to current theme's stylesheet directory.
 */
function get_stylesheet_directory_uri() {
    $stylesheet         = str_replace( '%2F', '/', rawurlencode( get_stylesheet() ) );
    $theme_root_uri     = get_theme_root_uri( $stylesheet );
    $stylesheet_dir_uri = "$theme_root_uri/$stylesheet";

    /**
     * Filters the stylesheet directory URI.
     *
     * @since 1.5.0
     *
     * @param string $stylesheet_dir_uri Stylesheet directory URI.
     * @param string $stylesheet         Name of the activated theme's directory.
     * @param string $theme_root_uri     Themes root URI.
     */
    return apply_filters( 'stylesheet_directory_uri', $stylesheet_dir_uri, $stylesheet, $theme_root_uri );
}

/**
 * Retrieves stylesheet URI for current theme.
 *
 * The stylesheet file name is 'style.css' which is appended to the stylesheet directory URI path.
 * See get_stylesheet_directory_uri().
 *
 * @since 1.5.0
 *
 * @return string URI to current theme's stylesheet.
 */
function get_stylesheet_uri() {
    $stylesheet_dir_uri = get_stylesheet_directory_uri();
    $stylesheet_uri     = $stylesheet_dir_uri . '/style.css';
    /**
     * Filters the URI of the current theme stylesheet.
     *
     * @since 1.5.0
     *
     * @param string $stylesheet_uri     Stylesheet URI for the current theme/child theme.
     * @param string $stylesheet_dir_uri Stylesheet directory URI for the current theme/child theme.
     */
    return apply_filters( 'stylesheet_uri', $stylesheet_uri, $stylesheet_dir_uri );
}


/**
 * Retrieves name of the current theme.
 *
 * @since 1.5.0
 *
 * @return string Template name.
 */
function get_template() {
    /**
     * Filters the name of the current theme.
     *
     * @since 1.5.0
     *
     * @param string $template Current theme's directory name.
     */
    return apply_filters( 'template', get_option( 'template' ) );
}

/**
 * Retrieves template directory path for current theme.
 *
 * @since 1.5.0
 *
 * @return string Path to current theme's template directory.
 */
function get_template_directory() {
    $template     = get_template();
    $theme_root   = get_theme_root( $template );
    $template_dir = "$theme_root/$template";

    /**
     * Filters the current theme directory path.
     *
     * @since 1.5.0
     *
     * @param string $template_dir The path of the current theme directory.
     * @param string $template     Directory name of the current theme.
     * @param string $theme_root   Absolute path to the themes directory.
     */
    return apply_filters( 'template_directory', $template_dir, $template, $theme_root );
}

/**
 * Retrieves template directory URI for current theme.
 *
 * @since 1.5.0
 *
 * @return string URI to current theme's template directory.
 */
function get_template_directory_uri() {
    $template         = str_replace( '%2F', '/', rawurlencode( get_template() ) );
    $theme_root_uri   = get_theme_root_uri( $template );
    $template_dir_uri = "$theme_root_uri/$template";

    /**
     * Filters the current theme directory URI.
     *
     * @since 1.5.0
     *
     * @param string $template_dir_uri The URI of the current theme directory.
     * @param string $template         Directory name of the current theme.
     * @param string $theme_root_uri   The themes root URI.
     */
    return apply_filters( 'template_directory_uri', $template_dir_uri, $template, $theme_root_uri );
}

/**
 * Retrieves theme roots.
 *
 * @since 2.9.0
 *
 * @global array $wp_theme_directories
 *
 * @return array|string An array of theme roots keyed by template/stylesheet
 *                      or a single theme root if all themes have the same root.
 */
function get_theme_roots() {
    global $wp_theme_directories;

    if ( ! is_array( $wp_theme_directories ) || count( $wp_theme_directories ) <= 1 ) {
        return '/themes';
    }

    $theme_roots = get_site_transient( 'theme_roots' );
    if ( false === $theme_roots ) {
        /** @noinspection PhpUndefinedFunctionInspection */
        search_theme_directories( true ); // Regenerate the transient.
        $theme_roots = get_site_transient( 'theme_roots' );
    }
    return $theme_roots;
}


/**
 * Retrieves path to themes directory.
 *
 * Does not have trailing slash.
 *
 * @since 1.5.0
 *
 * @global array $wp_theme_directories
 *
 * @param string $stylesheet_or_template Optional. The stylesheet or template name of the theme.
 *                                       Default is to leverage the main theme root.
 * @return string Themes directory path.
 */
function get_theme_root( $stylesheet_or_template = '' ) {
    global $wp_theme_directories;

    $theme_root = '';

    if ( $stylesheet_or_template ) {
        $theme_root = get_raw_theme_root( $stylesheet_or_template );
        if ( $theme_root ) {
            // Always prepend WP_CONTENT_DIR unless the root currently registered as a theme directory.
            // This gives relative theme roots the benefit of the doubt when things go haywire.
            if ( ! in_array( $theme_root, (array) $wp_theme_directories, true ) ) {
                $theme_root = WP_CONTENT_DIR . $theme_root;
            }
        }
    }

    if ( ! $theme_root ) {
        $theme_root = WP_CONTENT_DIR . '/themes';
    }

    /**
     * Filters the absolute path to the themes directory.
     *
     * @since 1.5.0
     *
     * @param string $theme_root Absolute path to themes directory.
     */
    return apply_filters( 'theme_root', $theme_root );
}

/**
 * Retrieves URI for themes directory.
 *
 * Does not have trailing slash.
 *
 * @since 1.5.0
 *
 * @global array $wp_theme_directories
 *
 * @param string $stylesheet_or_template Optional. The stylesheet or template name of the theme.
 *                                       Default is to leverage the main theme root.
 * @param string $theme_root             Optional. The theme root for which calculations will be based,
 *                                       preventing the need for a get_raw_theme_root() call. Default empty.
 * @return string Themes directory URI.
 */
function get_theme_root_uri( $stylesheet_or_template = '', $theme_root = '' ) {
    global $wp_theme_directories;

    if ( $stylesheet_or_template && ! $theme_root ) {
        $theme_root = get_raw_theme_root( $stylesheet_or_template );
    }

    if ( $stylesheet_or_template && $theme_root ) {
        if ( in_array( $theme_root, (array) $wp_theme_directories, true ) ) {
            // Absolute path. Make an educated guess. YMMV -- but note the filter below.
            if ( 0 === strpos( $theme_root, WP_CONTENT_DIR ) ) {
                $theme_root_uri = content_url( str_replace( WP_CONTENT_DIR, '', $theme_root ) );
            } elseif ( 0 === strpos( $theme_root, ABSPATH ) ) {
                $theme_root_uri = site_url( str_replace( ABSPATH, '', $theme_root ) );
            } elseif ( 0 === strpos( $theme_root, WP_PLUGIN_DIR ) || 0 === strpos( $theme_root, WPMU_PLUGIN_DIR ) ) {
                $theme_root_uri = plugins_url( basename( $theme_root ), $theme_root );
            } else {
                $theme_root_uri = $theme_root;
            }
        } else {
            $theme_root_uri = content_url( $theme_root );
        }
    } else {
        $theme_root_uri = content_url( 'themes' );
    }

    /**
     * Filters the URI for themes directory.
     *
     * @since 1.5.0
     *
     * @param string $theme_root_uri         The URI for themes directory.
     * @param string $siteurl                WordPress web address which is set in General Options.
     * @param string $stylesheet_or_template The stylesheet or template name of the theme.
     */
    return apply_filters( 'theme_root_uri', $theme_root_uri, get_option( 'siteurl' ), $stylesheet_or_template );
}

/**
 * Gets the raw theme root relative to the content directory with no filters applied.
 *
 * @since 3.1.0
 *
 * @global array $wp_theme_directories
 *
 * @param string $stylesheet_or_template The stylesheet or template name of the theme.
 * @param bool   $skip_cache             Optional. Whether to skip the cache.
 *                                       Defaults to false, meaning the cache is used.
 * @return string Theme root.
 */
function get_raw_theme_root( $stylesheet_or_template, $skip_cache = false ) {
    global $wp_theme_directories;

    if ( ! is_array( $wp_theme_directories ) || count( $wp_theme_directories ) <= 1 ) {
        return '/themes';
    }

    $theme_root = false;

    // If requesting the root for the current theme, consult options to avoid calling get_theme_roots().
    if ( ! $skip_cache ) {
        if ( get_option( 'stylesheet' ) == $stylesheet_or_template ) {
            $theme_root = get_option( 'stylesheet_root' );
        } elseif ( get_option( 'template' ) == $stylesheet_or_template ) {
            $theme_root = get_option( 'template_root' );
        }
    }

    if ( empty( $theme_root ) ) {
        $theme_roots = get_theme_roots();
        if ( ! empty( $theme_roots[ $stylesheet_or_template ] ) ) {
            $theme_root = $theme_roots[ $stylesheet_or_template ];
        }
    }

    return $theme_root;
}