<?php /** @noinspection PhpUnnecessaryCurlyVarSyntaxInspection */
/** @noinspection PhpUnused */

/**
 * Load the feed template from the use of an action hook.
 *
 * If the feed action does not have a hook, then the function will die with a
 * message telling the visitor that the feed is not valid.
 *
 * It is better to only have one hook for each feed.
 *
 * @since 2.1.0
 *
 * @global WP_Query $wp_query WordPress Query object.
 */
function do_feed() {
    global $wp_query;

    $feed = get_query_var( 'feed' );

    // Remove the pad, if present.
    $feed = preg_replace( '/^_+/', '', $feed );

    if ( '' === $feed || 'feed' === $feed ) {
        $feed = get_default_feed();
    }

    if ( ! has_action( "do_feed_{$feed}" ) ) {
        wp_die( __( 'Error: This is not a valid feed template.' ), '', array( 'response' => 404 ) );
    }

    /**
     * Fires once the given feed is loaded.
     *
     * The dynamic portion of the hook name, `$feed`, refers to the feed template name.
     *
     * Possible hook names include:
     *
     *  - `do_feed_atom`
     *  - `do_feed_rdf`
     *  - `do_feed_rss`
     *  - `do_feed_rss2`
     *
     * @since 2.1.0
     * @since 4.4.0 The `$feed` parameter was added.
     *
     * @param bool   $is_comment_feed Whether the feed is a comment feed.
     * @param string $feed            The feed name.
     */
    do_action( "do_feed_{$feed}", $wp_query->is_comment_feed, $feed );
}

/**
 * Load the RDF RSS 0.91 Feed template.
 *
 * @since 2.1.0
 *
 * @see load_template()
 */
function do_feed_rdf() {
    load_template( ABSPATH . WPINC . '/feed-rdf.php' );
}

/**
 * Load the RSS 1.0 Feed Template.
 *
 * @since 2.1.0
 *
 * @see load_template()
 */
function do_feed_rss() {
    load_template( ABSPATH . WPINC . '/feed-rss.php' );
}

/**
 * Load either the RSS2 comment feed or the RSS2 posts feed.
 *
 * @since 2.1.0
 *
 * @see load_template()
 *
 * @param bool $for_comments True for the comment feed, false for normal feed.
 */
function do_feed_rss2( $for_comments ) {
    if ( $for_comments ) {
        load_template( ABSPATH . WPINC . '/feed-rss2-comments.php' );
    } else {
        load_template( ABSPATH . WPINC . '/feed-rss2.php' );
    }
}

/**
 * Load either Atom comment feed or Atom posts feed.
 *
 * @since 2.1.0
 *
 * @see load_template()
 *
 * @param bool $for_comments True for the comment feed, false for normal feed.
 */
function do_feed_atom( $for_comments ) {
    if ( $for_comments ) {
        load_template( ABSPATH . WPINC . '/feed-atom-comments.php' );
    } else {
        load_template( ABSPATH . WPINC . '/feed-atom.php' );
    }
}
