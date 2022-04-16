<?php
/**
 * Network API
 *
 * @package WordPress
 * @subpackage Multisite
 * @since 5.1.0
 */

/**
 * Retrieves network data given a network ID or network object.
 *
 * Network data will be cached and returned after being passed through a filter.
 * If the provided network is empty, the current network global will be used.
 *
 * @since 4.6.0
 *
 * @global WP_Network $current_site
 *
 * @param WP_Network|int|null $network Optional. Network to retrieve. Default is the current network.
 * @return WP_Network|null The network object or null if not found.
 */
function get_network( $network = null ) {
    global $current_site;
    if ( empty( $network ) && isset( $current_site ) ) {
        $network = $current_site;
    }

    if ( $network instanceof WP_Network ) {
        $_network = $network;
    } elseif ( is_object( $network ) ) {
        $_network = new WP_Network( $network );
    } else {
        $_network = WP_Network::get_instance( $network );
    }

    if ( ! $_network ) {
        return null;
    }

    /**
     * Fires after a network is retrieved.
     *
     * @since 4.6.0
     *
     * @param WP_Network $_network Network data.
     */
    return apply_filters( 'get_network', $_network );
}

/**
 * Retrieves a list of networks.
 *
 * @since 4.6.0
 *
 * @param string|array $args Optional. Array or string of arguments. See WP_Network_Query::parse_query()
 *                           for information on accepted arguments. Default empty array.
 * @return array|int List of WP_Network objects, a list of network IDs when 'fields' is set to 'ids',
 *                   or the number of networks when 'count' is passed as a query var.
 */
function get_networks( $args = array() ) {
    $query = new WP_Network_Query();

    return $query->query( $args );
}