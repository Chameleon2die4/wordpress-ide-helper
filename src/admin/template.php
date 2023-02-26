<?php /** @noinspection PhpUnused */

/**
 * Adds a meta box to one or more screens.
 *
 * @since 2.5.0
 * @since 4.4.0 The `$screen` parameter now accepts an array of screen IDs.
 *
 * @global array $wp_meta_boxes
 *
 * @param string                 $id            Meta box ID (used in the 'id' attribute for the meta box).
 * @param string                 $title         Title of the meta box.
 * @param callable               $callback      Function that fills the box with the desired content.
 *                                              The function should echo its output.
 * @param string|array|WP_Screen $screen        Optional. The screen or screens on which to show the box
 *                                              (such as a post type, 'link', or 'comment'). Accepts a single
 *                                              screen ID, WP_Screen object, or array of screen IDs. Default
 *                                              is the current screen.  If you have used add_menu_page() or
 *                                              add_submenu_page() to create a new screen (and hence screen_id),
 *                                              make sure your menu slug conforms to the limits of sanitize_key()
 *                                              otherwise the 'screen' menu may not correctly render on your page.
 * @param string                 $context       Optional. The context within the screen where the box
 *                                              should display. Available contexts vary from screen to
 *                                              screen. Post edit screen contexts include 'normal', 'side',
 *                                              and 'advanced'. Comments screen contexts include 'normal'
 *                                              and 'side'. Menus meta boxes (accordion sections) all use
 *                                              the 'side' context. Global default is 'advanced'.
 * @param string                 $priority      Optional. The priority within the context where the box should show.
 *                                              Accepts 'high', 'core', 'default', or 'low'. Default 'default'.
 * @param array                  $callback_args Optional. Data that should be set as the $args property
 *                                              of the box array (which is the second parameter passed
 *                                              to your callback). Default null.
 */
function add_meta_box( $id, $title, $callback, $screen = null, $context = 'advanced', $priority = 'default', $callback_args = null ) {
    global $wp_meta_boxes;

    if ( empty( $screen ) ) {
        $screen = get_current_screen();
    } elseif ( is_string( $screen ) ) {
        $screen = convert_to_screen( $screen );
    } elseif ( is_array( $screen ) ) {
        foreach ( $screen as $single_screen ) {
            add_meta_box( $id, $title, $callback, $single_screen, $context, $priority, $callback_args );
        }
    }

    if ( ! isset( $screen->id ) ) {
        return;
    }

    $page = $screen->id;

    if ( ! isset( $wp_meta_boxes ) ) {
        $wp_meta_boxes = array();
    }
    if ( ! isset( $wp_meta_boxes[ $page ] ) ) {
        $wp_meta_boxes[ $page ] = array();
    }
    if ( ! isset( $wp_meta_boxes[ $page ][ $context ] ) ) {
        $wp_meta_boxes[ $page ][ $context ] = array();
    }

    foreach ( array_keys( $wp_meta_boxes[ $page ] ) as $a_context ) {
        foreach ( array( 'high', 'core', 'default', 'low' ) as $a_priority ) {
            if ( ! isset( $wp_meta_boxes[ $page ][ $a_context ][ $a_priority ][ $id ] ) ) {
                continue;
            }

            // If a core box was previously removed, don't add.
            if ( ( 'core' === $priority || 'sorted' === $priority )
              && false === $wp_meta_boxes[ $page ][ $a_context ][ $a_priority ][ $id ]
            ) {
                return;
            }

            // If a core box was previously added by a plugin, don't add.
            if ( 'core' === $priority ) {
                /*
                 * If the box was added with default priority, give it core priority
                 * to maintain sort order.
                 */
                if ( 'default' === $a_priority ) {
                    $wp_meta_boxes[ $page ][ $a_context ]['core'][ $id ] = $wp_meta_boxes[ $page ][ $a_context ]['default'][ $id ];
                    unset( $wp_meta_boxes[ $page ][ $a_context ]['default'][ $id ] );
                }
                return;
            }

            // If no priority given and ID already present, use existing priority.
            if ( empty( $priority ) ) {
                $priority = $a_priority;
                /*
                 * Else, if we're adding to the sorted priority, we don't know the title
                 * or callback. Grab them from the previously added context/priority.
                 */
            } elseif ( 'sorted' === $priority ) {
                $title         = $wp_meta_boxes[ $page ][ $a_context ][ $a_priority ][ $id ]['title'];
                $callback      = $wp_meta_boxes[ $page ][ $a_context ][ $a_priority ][ $id ]['callback'];
                $callback_args = $wp_meta_boxes[ $page ][ $a_context ][ $a_priority ][ $id ]['args'];
            }

            // An ID can be in only one priority and one context.
            if ( $priority !== $a_priority || $context !== $a_context ) {
                unset( $wp_meta_boxes[ $page ][ $a_context ][ $a_priority ][ $id ] );
            }
        }
    }

    if ( empty( $priority ) ) {
        $priority = 'low';
    }

    if ( ! isset( $wp_meta_boxes[ $page ][ $context ][ $priority ] ) ) {
        $wp_meta_boxes[ $page ][ $context ][ $priority ] = array();
    }

    $wp_meta_boxes[ $page ][ $context ][ $priority ][ $id ] = array(
      'id'       => $id,
      'title'    => $title,
      'callback' => $callback,
      'args'     => $callback_args,
    );
}

/**
 * Converts a screen string to a screen object.
 *
 * @since 3.0.0
 *
 * @param string $hook_name The hook name (also known as the hook suffix) used to determine the screen.
 * @return WP_Screen|null Screen object.
 */
function convert_to_screen( $hook_name ) {
    if ( ! class_exists( 'WP_Screen' ) ) {
        _doing_it_wrong(
          'convert_to_screen(), add_meta_box()',
          sprintf(
          /* translators: 1: wp-admin/includes/template.php, 2: add_meta_box(), 3: add_meta_boxes */
            __( 'Likely direct inclusion of %1$s in order to use %2$s. This is very wrong. Hook the %2$s call into the %3$s action instead.' ),
            '<code>wp-admin/includes/template.php</code>',
            '<code>add_meta_box()</code>',
            '<code>add_meta_boxes</code>'
          ),
          '3.3.0'
        );
        return null;
    }

    return WP_Screen::get( $hook_name );
}
