<?php /** @noinspection PhpMissingParamTypeInspection */

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