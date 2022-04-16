<?php
/**
 * Site API
 *
 * @package WordPress
 * @subpackage Multisite
 * @since 5.1.0
 */

/**
 * Retrieves site data given a site ID or site object.
 *
 * Site data will be cached and returned after being passed through a filter.
 * If the provided site is empty, the current site global will be used.
 *
 * @since 4.6.0
 *
 * @param WP_Site|int|null $site Optional. Site to retrieve. Default is the current site.
 * @return WP_Site|null The site object or null if not found.
 */
function get_site( $site = null ) {
    if ( empty( $site ) ) {
        $site = get_current_blog_id();
    }

    if ( $site instanceof WP_Site ) {
        $_site = $site;
    } elseif ( is_object( $site ) ) {
        $_site = new WP_Site( $site );
    } else {
        $_site = WP_Site::get_instance( $site );
    }

    if ( ! $_site ) {
        return null;
    }

    /**
     * Fires after a site is retrieved.
     *
     * @since 4.6.0
     *
     * @param WP_Site $_site Site data.
     */
    return apply_filters( 'get_site', $_site );
}

/**
 * Retrieves a list of sites matching requested arguments.
 *
 * @since 4.6.0
 * @since 4.8.0 Introduced the 'lang_id', 'lang__in', and 'lang__not_in' parameters.
 *
 * @see WP_Site_Query::parse_query()
 *
 * @param string|array $args Optional. Array or string of arguments. See WP_Site_Query::__construct()
 *                           for information on accepted arguments. Default empty array.
 * @return array|int List of WP_Site objects, a list of site IDs when 'fields' is set to 'ids',
 *                   or the number of sites when 'count' is passed as a query var.
 */
function get_sites( $args = array() ) {
    $query = new WP_Site_Query();

    return $query->query( $args );
}
