<?php /** @noinspection PhpUnused */

/**
 * Retrieve or display referer hidden field for forms.
 *
 * The referer link is the current Request URI from the server super global. The
 * input name is '_wp_http_referer', in case you wanted to check manually.
 *
 * @since 2.0.4
 *
 * @param bool $echo Optional. Whether to echo or return the referer field. Default true.
 * @return string Referer field HTML markup.
 */
function wp_referer_field( $echo = true ) {
    $referer_field = '<input type="hidden" name="_wp_http_referer" value="' . esc_attr( wp_unslash( $_SERVER['REQUEST_URI'] ) ) . '" />';

    if ( $echo ) {
        echo $referer_field;
    }

    return $referer_field;
}

/**
 * Retrieve or display original referer hidden field for forms.
 *
 * The input name is '_wp_original_http_referer' and will be either the same
 * value of wp_referer_field(), if that was posted already, or it will be the
 * current page, if it doesn't exist.
 *
 * @since 2.0.4
 *
 * @param bool   $echo         Optional. Whether to echo the original http referer. Default true.
 * @param string $jump_back_to Optional. Can be 'previous' or page you want to jump back to.
 *                             Default 'current'.
 * @return string Original referer field.
 */
function wp_original_referer_field( $echo = true, $jump_back_to = 'current' ) {
    $ref = wp_get_original_referer();

    if ( ! $ref ) {
        $ref = ( 'previous' === $jump_back_to ) ? wp_get_referer() : wp_unslash( $_SERVER['REQUEST_URI'] );
    }

    $orig_referer_field = '<input type="hidden" name="_wp_original_http_referer" value="' . esc_attr( $ref ) . '" />';

    if ( $echo ) {
        echo $orig_referer_field;
    }

    return $orig_referer_field;
}

/**
 * Retrieve referer from '_wp_http_referer' or HTTP referer.
 *
 * If it's the same as the current request URL, will return false.
 *
 * @since 2.0.4
 *
 * @return string|false Referer URL on success, false on failure.
 */
function wp_get_referer() {
    if ( ! function_exists( 'wp_validate_redirect' ) ) {
        return false;
    }

    $ref = wp_get_raw_referer();

    if ( $ref && wp_unslash( $_SERVER['REQUEST_URI'] ) !== $ref && home_url() . wp_unslash( $_SERVER['REQUEST_URI'] ) !== $ref ) {
        return wp_validate_redirect( $ref, false );
    }

    return false;
}

/**
 * Retrieves unvalidated referer from '_wp_http_referer' or HTTP referer.
 *
 * Do not use for redirects, use wp_get_referer() instead.
 *
 * @since 4.5.0
 *
 * @return string|false Referer URL on success, false on failure.
 */
function wp_get_raw_referer() {
    if ( ! empty( $_REQUEST['_wp_http_referer'] ) ) {
        return wp_unslash( $_REQUEST['_wp_http_referer'] );
    } elseif ( ! empty( $_SERVER['HTTP_REFERER'] ) ) {
        return wp_unslash( $_SERVER['HTTP_REFERER'] );
    }

    return false;
}

/**
 * Retrieve original referer that was posted, if it exists.
 *
 * @since 2.0.4
 *
 * @return string|false Original referer URL on success, false on failure.
 */
function wp_get_original_referer() {
    if ( ! empty( $_REQUEST['_wp_original_http_referer'] ) && function_exists( 'wp_validate_redirect' ) ) {
        return wp_validate_redirect( wp_unslash( $_REQUEST['_wp_original_http_referer'] ), false );
    }

    return false;
}