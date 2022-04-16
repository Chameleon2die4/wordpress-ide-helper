<?php /** @noinspection GrazieInspection */
/** @noinspection SpellCheckingInspection */
/** @noinspection PhpIncludeInspection */
/** @noinspection PhpUnused */
/** @noinspection RegExpSimplifiable */
/** @noinspection PhpUndefinedConstantInspection */

/**
 * Return an anonymized IPv4 or IPv6 address.
 *
 * @since 4.9.6 Abstracted from `WP_Community_Events::get_unsafe_client_ip()`.
 *
 * @param string $ip_addr       The IPv4 or IPv6 address to be anonymized.
 * @param bool   $ipv6_fallback Optional. Whether to return the original IPv6 address if the needed functions
 *                              to anonymize it is not present. Default false, return `::` (unspecified address).
 * @return string  The anonymized IP address.
 */
function wp_privacy_anonymize_ip( $ip_addr, $ipv6_fallback = false ) {
    if ( empty( $ip_addr ) ) {
        return '0.0.0.0';
    }

    // Detect what kind of IP address this is.
    $ip_prefix = '';
    $is_ipv6   = substr_count( $ip_addr, ':' ) > 1;
    $is_ipv4   = ( 3 === substr_count( $ip_addr, '.' ) );

    if ( $is_ipv6 && $is_ipv4 ) {
        // IPv6 compatibility mode, temporarily strip the IPv6 part, and treat it like IPv4.
        $ip_prefix = '::ffff:';
        $ip_addr   = preg_replace( '/^\[?[0-9a-f:]*:/i', '', $ip_addr );
        $ip_addr   = str_replace( ']', '', $ip_addr );
        $is_ipv6   = false;
    }

    if ( $is_ipv6 ) {
        // IPv6 addresses will always be enclosed in [] if there's a port.
        $left_bracket  = strpos( $ip_addr, '[' );
        $right_bracket = strpos( $ip_addr, ']' );
        $percent       = strpos( $ip_addr, '%' );
        $netmask       = 'ffff:ffff:ffff:ffff:0000:0000:0000:0000';

        // Strip the port (and [] from IPv6 addresses), if they exist.
        if ( false !== $left_bracket && false !== $right_bracket ) {
            $ip_addr = substr( $ip_addr, $left_bracket + 1, $right_bracket - $left_bracket - 1 );
        } elseif ( false !== $left_bracket || false !== $right_bracket ) {
            // The IP has one bracket, but not both, so it's malformed.
            return '::';
        }

        // Strip the reachability scope.
        if ( false !== $percent ) {
            $ip_addr = substr( $ip_addr, 0, $percent );
        }

        // No invalid characters should be left.
        if ( preg_match( '/[^0-9a-f:]/i', $ip_addr ) ) {
            return '::';
        }

        // Partially anonymize the IP by reducing it to the corresponding network ID.
        if ( function_exists( 'inet_pton' ) && function_exists( 'inet_ntop' ) ) {
            $ip_addr = inet_ntop( inet_pton( $ip_addr ) & inet_pton( $netmask ) );
            if ( false === $ip_addr ) {
                return '::';
            }
        } elseif ( ! $ipv6_fallback ) {
            return '::';
        }
    } elseif ( $is_ipv4 ) {
        // Strip any port and partially anonymize the IP.
        $last_octet_position = strrpos( $ip_addr, '.' );
        $ip_addr             = substr( $ip_addr, 0, $last_octet_position ) . '.0';
    } else {
        return '0.0.0.0';
    }

    // Restore the IPv6 prefix to compatibility mode addresses.
    return $ip_prefix . $ip_addr;
}

/**
 * Return uniform "anonymous" data by type.
 *
 * @since 4.9.6
 *
 * @param string $type The type of data to be anonymized.
 * @param string $data Optional The data to be anonymized.
 * @return string The anonymous data for the requested type.
 */
function wp_privacy_anonymize_data( $type, $data = '' ) {

    switch ( $type ) {
        case 'email':
            $anonymous = 'deleted@site.invalid';
            break;
        case 'url':
            $anonymous = 'https://site.invalid';
            break;
        case 'ip':
            $anonymous = wp_privacy_anonymize_ip( $data );
            break;
        case 'date':
            $anonymous = '0000-00-00 00:00:00';
            break;
        case 'text':
            /* translators: Deleted text. */
            $anonymous = __( '[deleted]' );
            break;
        case 'longtext':
            /* translators: Deleted long text. */
            $anonymous = __( 'This content was deleted by the author.' );
            break;
        default:
            $anonymous = '';
            break;
    }

    /**
     * Filters the anonymous data for each type.
     *
     * @since 4.9.6
     *
     * @param string $anonymous Anonymized data.
     * @param string $type      Type of the data.
     * @param string $data      Original data.
     */
    return apply_filters( 'wp_privacy_anonymize_data', $anonymous, $type, $data );
}

/**
 * Returns the directory used to store personal data export files.
 *
 * @since 4.9.6
 *
 * @see wp_privacy_exports_url
 *
 * @return string Exports directory.
 */
function wp_privacy_exports_dir() {
    $upload_dir  = wp_upload_dir();
    $exports_dir = trailingslashit( $upload_dir['basedir'] ) . 'wp-personal-data-exports/';

    /**
     * Filters the directory used to store personal data export files.
     *
     * @since 4.9.6
     * @since 5.5.0 Exports now use relative paths, so changes to the directory
     *              via this filter should be reflected on the server.
     *
     * @param string $exports_dir Exports directory.
     */
    return apply_filters( 'wp_privacy_exports_dir', $exports_dir );
}

/**
 * Returns the URL of the directory used to store personal data export files.
 *
 * @since 4.9.6
 *
 * @see wp_privacy_exports_dir
 *
 * @return string Exports directory URL.
 */
function wp_privacy_exports_url() {
    $upload_dir  = wp_upload_dir();
    $exports_url = trailingslashit( $upload_dir['baseurl'] ) . 'wp-personal-data-exports/';

    /**
     * Filters the URL of the directory used to store personal data export files.
     *
     * @since 4.9.6
     * @since 5.5.0 Exports now use relative paths, so changes to the directory URL
     *              via this filter should be reflected on the server.
     *
     * @param string $exports_url Exports directory URL.
     */
    return apply_filters( 'wp_privacy_exports_url', $exports_url );
}

/**
 * Schedule a `WP_Cron` job to delete expired export files.
 *
 * @since 4.9.6
 */
function wp_schedule_delete_old_privacy_export_files() {
    if ( wp_installing() ) {
        return;
    }

    if ( ! wp_next_scheduled( 'wp_privacy_delete_old_export_files' ) ) {
        wp_schedule_event( time(), 'hourly', 'wp_privacy_delete_old_export_files' );
    }
}

/**
 * Cleans up export files older than three days old.
 *
 * The export files are stored in `wp-content/uploads`, and are therefore publicly
 * accessible. A CSPRN is appended to the filename to mitigate the risk of an
 * unauthorized person downloading the file, but it is still possible. Deleting
 * the file after the data subject has had a chance to delete it adds an additional
 * layer of protection.
 *
 * @since 4.9.6
 */
function wp_privacy_delete_old_export_files() {
    $exports_dir = wp_privacy_exports_dir();
    if ( ! is_dir( $exports_dir ) ) {
        return;
    }

    require_once ABSPATH . 'wp-admin/includes/file.php';
    $export_files = list_files( $exports_dir, 100, array( 'index.php' ) );

    /**
     * Filters the lifetime, in seconds, of a personal data export file.
     *
     * By default, the lifetime is 3 days. Once the file reaches that age, it will automatically
     * be deleted by a cron job.
     *
     * @since 4.9.6
     *
     * @param int $expiration The expiration age of the export, in seconds.
     */
    $expiration = apply_filters( 'wp_privacy_export_expiration', 3 * DAY_IN_SECONDS );

    foreach ( (array) $export_files as $export_file ) {
        $file_age_in_seconds = time() - filemtime( $export_file );

        if ( $expiration < $file_age_in_seconds ) {
            unlink( $export_file );
        }
    }
}
