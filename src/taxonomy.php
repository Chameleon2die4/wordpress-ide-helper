<?php /** @noinspection SqlNoDataSourceInspection */
/** @noinspection SqlDialectInspection */
/** @noinspection PhpUnnecessaryCurlyVarSyntaxInspection */
/** @noinspection PhpUnused */

/**
 * Core Taxonomy API
 *
 * @package WordPress
 * @subpackage Taxonomy
 */

/**
 * Get all Term data from database by Term ID.
 *
 * The usage of the get_term function is to apply filters to a term object. It
 * is possible to get a term object from the database before applying the
 * filters.
 *
 * $term ID must be part of $taxonomy, to get from the database. Failure, might
 * be able to be captured by the hooks. Failure would be the same value as $wpdb
 * returns for the get_row method.
 *
 * There are two hooks, one is specifically for each term, named 'get_term', and
 * the second is for the taxonomy name, 'term_$taxonomy'. Both hooks get the
 * term object, and the taxonomy name as parameters. Both hooks are expected to
 * return a Term object.
 *
 * {@see 'get_term'} hook - Takes two parameters the term Object and the taxonomy name.
 * Must return term object. Used in get_term() as a catch-all filter for every
 * $term.
 *
 * {@see 'get_$taxonomy'} hook - Takes two parameters the term Object and the taxonomy
 * name. Must return term object. $taxonomy will be the taxonomy name, so for
 * example, if 'category', it would be 'get_category' as the filter name. Useful
 * for custom taxonomies or plugging into default taxonomies.
 *
 * @todo Better formatting for DocBlock
 *
 * @since 2.3.0
 * @since 4.4.0 Converted to return a WP_Term object if `$output` is `OBJECT`.
 *              The `$taxonomy` parameter was made optional.
 *
 * @see sanitize_term_field() The $context param lists the available values for get_term_by() $filter param.
 *
 * @param int|WP_Term|object $term     If integer, term data will be fetched from the database,
 *                                     or from the cache if available.
 *                                     If stdClass object (as in the results of a database query),
 *                                     will apply filters and return a `WP_Term` object with the `$term` data.
 *                                     If `WP_Term`, will return `$term`.
 * @param string             $taxonomy Optional. Taxonomy name that `$term` is part of.
 * @param string             $output   Optional. The required return type. One of OBJECT, ARRAY_A, or ARRAY_N, which
 *                                     correspond to a WP_Term object, an associative array, or a numeric array,
 *                                     respectively. Default OBJECT.
 * @param string             $filter   Optional. How to sanitize term fields. Default 'raw'.
 * @return WP_Term|array|WP_Error|null WP_Term instance (or array) on success, depending on the `$output` value.
 *                                     WP_Error if `$taxonomy` does not exist. Null for miscellaneous failure.
 */
function get_term( $term, $taxonomy = '', $output = OBJECT, $filter = 'raw' ) {
    if ( empty( $term ) ) {
        return new WP_Error( 'invalid_term', __( 'Empty Term.' ) );
    }

    if ( $taxonomy && ! taxonomy_exists( $taxonomy ) ) {
        return new WP_Error( 'invalid_taxonomy', __( 'Invalid taxonomy.' ) );
    }

    if ( $term instanceof WP_Term ) {
        $_term = $term;
    } elseif ( is_object( $term ) ) {
        if ( empty( $term->filter ) || 'raw' === $term->filter ) {
            $_term = sanitize_term( $term, $taxonomy, 'raw' );
            $_term = new WP_Term( $_term );
        } else {
            $_term = WP_Term::get_instance( $term->term_id );
        }
    } else {
        $_term = WP_Term::get_instance( $term, $taxonomy );
    }

    if ( is_wp_error( $_term ) ) {
        return $_term;
    } elseif ( ! $_term ) {
        return null;
    }

    // Ensure for filters that this is not empty.
    $taxonomy = $_term->taxonomy;

    /**
     * Filters a taxonomy term object.
     *
     * The {@see 'get_$taxonomy'} hook is also available for targeting a specific
     * taxonomy.
     *
     * @since 2.3.0
     * @since 4.4.0 `$_term` is now a `WP_Term` object.
     *
     * @param WP_Term $_term    Term object.
     * @param string  $taxonomy The taxonomy slug.
     */
    $_term = apply_filters( 'get_term', $_term, $taxonomy );

    /**
     * Filters a taxonomy term object.
     *
     * The dynamic portion of the hook name, `$taxonomy`, refers
     * to the slug of the term's taxonomy.
     *
     * Possible hook names include:
     *
     *  - `get_category`
     *  - `get_post_tag`
     *
     * @since 2.3.0
     * @since 4.4.0 `$_term` is now a `WP_Term` object.
     *
     * @param WP_Term $_term    Term object.
     * @param string  $taxonomy The taxonomy slug.
     */
    $_term = apply_filters( "get_{$taxonomy}", $_term, $taxonomy );

    // Bail if a filter callback has changed the type of the `$_term` object.
    if ( ! ( $_term instanceof WP_Term ) ) {
        return $_term;
    }

    // Sanitize term, according to the specified filter.
    $_term->filter( $filter );

    if ( ARRAY_A === $output ) {
        return $_term->to_array();
    } elseif ( ARRAY_N === $output ) {
        return array_values( $_term->to_array() );
    }

    return $_term;
}

/**
 * Get all Term data from database by Term field and data.
 *
 * Warning: $value is not escaped for 'name' $field. You must do it yourself, if
 * required.
 *
 * The default $field is 'id', therefore it is possible to also use null for
 * field, but not recommended that you do so.
 *
 * If $value does not exist, the return value will be false. If $taxonomy exists
 * and $field and $value combinations exist, the Term will be returned.
 *
 * This function will always return the first term that matches the `$field`-
 * `$value`-`$taxonomy` combination specified in the parameters. If your query
 * is likely to match more than one term (as is likely to be the case when
 * `$field` is 'name', for example), consider using get_terms() instead; that
 * way, you will get all matching terms, and can provide your own logic for
 * deciding which one was intended.
 *
 * @todo Better formatting for DocBlock.
 *
 * @since 2.3.0
 * @since 4.4.0 `$taxonomy` is optional if `$field` is 'term_taxonomy_id'. Converted to return
 *              a WP_Term object if `$output` is `OBJECT`.
 * @since 5.5.0 Added 'ID' as an alias of 'id' for the `$field` parameter.
 *
 * @see sanitize_term_field() The $context param lists the available values for get_term_by() $filter param.
 *
 * @param string     $field    Either 'slug', 'name', 'term_id' (or 'id', 'ID'), or 'term_taxonomy_id'.
 * @param string|int $value    Search for this term value.
 * @param string     $taxonomy Taxonomy name. Optional, if `$field` is 'term_taxonomy_id'.
 * @param string     $output   Optional. The required return type. One of OBJECT, ARRAY_A, or ARRAY_N, which
 *                             correspond to a WP_Term object, an associative array, or a numeric array,
 *                             respectively. Default OBJECT.
 * @param string     $filter   Optional. How to sanitize term fields. Default 'raw'.
 * @return WP_Term|array|false WP_Term instance (or array) on success, depending on the `$output` value.
 *                             False if `$taxonomy` does not exist or `$term` was not found.
 */
function get_term_by( $field, $value, $taxonomy = '', $output = OBJECT, $filter = 'raw' ) {

    // 'term_taxonomy_id' lookups don't require taxonomy checks.
    if ( 'term_taxonomy_id' !== $field && ! taxonomy_exists( $taxonomy ) ) {
        return false;
    }

    // No need to perform a query for empty 'slug' or 'name'.
    if ( 'slug' === $field || 'name' === $field ) {
        $value = (string) $value;

        if ( 0 === strlen( $value ) ) {
            return false;
        }
    }

    if ( 'id' === $field || 'ID' === $field || 'term_id' === $field ) {
        $term = get_term( (int) $value, $taxonomy, $output, $filter );
        if ( is_wp_error( $term ) || null === $term ) {
            $term = false;
        }
        return $term;
    }

    $args = array(
      'get'                    => 'all',
      'number'                 => 1,
      'taxonomy'               => $taxonomy,
      'update_term_meta_cache' => false,
      'orderby'                => 'none',
      'suppress_filter'        => true,
    );

    switch ( $field ) {
        case 'slug':
            $args['slug'] = $value;
            break;
        case 'name':
            $args['name'] = $value;
            break;
        case 'term_taxonomy_id':
            $args['term_taxonomy_id'] = $value;
            unset( $args['taxonomy'] );
            break;
        default:
            return false;
    }

    $terms = get_terms( $args );
    if ( is_wp_error( $terms ) || empty( $terms ) ) {
        return false;
    }

    $term = array_shift( $terms );

    // In the case of 'term_taxonomy_id', override the provided `$taxonomy` with whatever we find in the DB.
    if ( 'term_taxonomy_id' === $field ) {
        $taxonomy = $term->taxonomy;
    }

    return get_term( $term, $taxonomy, $output, $filter );
}

/**
 * Retrieves the terms in a given taxonomy or list of taxonomies.
 *
 * You can fully inject any customizations to the query before it is sent, as
 * well as control the output with a filter.
 *
 * The return type varies depending on the value passed to `$args['fields']`. See
 * WP_Term_Query::get_terms() for details. In all cases, a `WP_Error` object will
 * be returned if an invalid taxonomy is requested.
 *
 * The {@see 'get_terms'} filter will be called when the cache has the term and will
 * pass the found term along with the array of $taxonomies and array of $args.
 * This filter is also called before the array of terms is passed and will pass
 * the array of terms, along with the $taxonomies and $args.
 *
 * The {@see 'list_terms_exclusions'} filter passes the compiled exclusions along with
 * the $args.
 *
 * The {@see 'get_terms_orderby'} filter passes the `ORDER BY` clause for the query
 * along with the $args array.
 *
 * Prior to 4.5.0, the first parameter of `get_terms()` was a taxonomy or list of taxonomies:
 *
 *     $terms = get_terms( 'post_tag', array(
 *         'hide_empty' => false,
 *     ) );
 *
 * Since 4.5.0, taxonomies should be passed via the 'taxonomy' argument in the `$args` array:
 *
 *     $terms = get_terms( array(
 *         'taxonomy' => 'post_tag',
 *         'hide_empty' => false,
 *     ) );
 *
 * @since 2.3.0
 * @since 4.2.0 Introduced 'name' and 'childless' parameters.
 * @since 4.4.0 Introduced the ability to pass 'term_id' as an alias of 'id' for the `orderby` parameter.
 *              Introduced the 'meta_query' and 'update_term_meta_cache' parameters. Converted to return
 *              a list of WP_Term objects.
 * @since 4.5.0 Changed the function signature so that the `$args` array can be provided as the first parameter.
 *              Introduced 'meta_key' and 'meta_value' parameters. Introduced the ability to order results by metadata.
 * @since 4.8.0 Introduced 'suppress_filter' parameter.
 *
 * @internal The `$deprecated` parameter is parsed for backward compatibility only.
 *
 * @param array|string $args       Optional. Array or string of arguments. See WP_Term_Query::__construct()
 *                                 for information on accepted arguments. Default empty array.
 * @param array|string $deprecated Optional. Argument array, when using the legacy function parameter format.
 *                                 If present, this parameter will be interpreted as `$args`, and the first
 *                                 function parameter will be parsed as a taxonomy or array of taxonomies.
 *                                 Default empty.
 * @return WP_Term[]|int[]|string[]|string|WP_Error Array of terms, a count thereof as a numeric string,
 *                                                  or WP_Error if any of the taxonomies do not exist.
 *                                                  See the function description for more information.
 */
function get_terms( $args = array(), $deprecated = '' ) {
    $term_query = new WP_Term_Query();

    $defaults = array(
      'suppress_filter' => false,
    );

    /*
     * Legacy argument format ($taxonomy, $args) takes precedence.
     *
     * We detect legacy argument format by checking if
     * (a) a second non-empty parameter is passed, or
     * (b) the first parameter shares no keys with the default array (ie, it's a list of taxonomies)
     */
    $_args          = wp_parse_args( $args );
    /** @noinspection PhpCastIsUnnecessaryInspection */
    $key_intersect  = array_intersect_key( $term_query->query_var_defaults, (array) $_args );
    $do_legacy_args = $deprecated || empty( $key_intersect );

    if ( $do_legacy_args ) {
        $taxonomies       = (array) $args;
        $args             = wp_parse_args( $deprecated, $defaults );
        $args['taxonomy'] = $taxonomies;
    } else {
        $args = wp_parse_args( $args, $defaults );
        if (isset($args['taxonomy'] )) {
            $args['taxonomy'] = (array) $args['taxonomy'];
        }
    }

    if ( ! empty( $args['taxonomy'] ) ) {
        foreach ( $args['taxonomy'] as $taxonomy ) {
            if ( ! taxonomy_exists( $taxonomy ) ) {
                return new WP_Error( 'invalid_taxonomy', __( 'Invalid taxonomy.' ) );
            }
        }
    }

    // Don't pass suppress_filter to WP_Term_Query.
    $suppress_filter = $args['suppress_filter'];
    unset( $args['suppress_filter'] );

    $terms = $term_query->query( $args );

    // Count queries are not filtered, for legacy reasons.
    if ( ! is_array( $terms ) ) {
        return $terms;
    }

    if ( $suppress_filter ) {
        return $terms;
    }

    /**
     * Filters the found terms.
     *
     * @since 2.3.0
     * @since 4.6.0 Added the `$term_query` parameter.
     *
     * @param array         $terms      Array of found terms.
     * @param array|null    $taxonomies An array of taxonomies if known.
     * @param array         $args       An array of get_terms() arguments.
     * @param WP_Term_Query $term_query The WP_Term_Query object.
     */
    return apply_filters( 'get_terms', $terms, $term_query->query_vars['taxonomy'], $term_query->query_vars, $term_query );
}

/**
 * Adds metadata to a term.
 *
 * @since 4.4.0
 *
 * @param int    $term_id    Term ID.
 * @param string $meta_key   Metadata name.
 * @param mixed  $meta_value Metadata value. Must be serializable if non-scalar.
 * @param bool   $unique     Optional. Whether the same key should not be added.
 *                           Default false.
 * @return int|false|WP_Error Meta ID on success, false on failure.
 *                            WP_Error when term_id is ambiguous between taxonomies.
 */
function add_term_meta( $term_id, $meta_key, $meta_value, $unique = false ) {
    if ( wp_term_is_shared( $term_id ) ) {
        return new WP_Error( 'ambiguous_term_id', __( 'Term meta cannot be added to terms that are shared between taxonomies.' ), $term_id );
    }

    return add_metadata( 'term', $term_id, $meta_key, $meta_value, $unique );
}

/**
 * Removes metadata matching criteria from a term.
 *
 * @since 4.4.0
 *
 * @param int    $term_id    Term ID.
 * @param string $meta_key   Metadata name.
 * @param mixed  $meta_value Optional. Metadata value. If provided,
 *                           rows will only be removed that match the value.
 *                           Must be serializable if non-scalar. Default empty.
 * @return bool True on success, false on failure.
 */
function delete_term_meta( $term_id, $meta_key, $meta_value = '' ) {
    return delete_metadata( 'term', $term_id, $meta_key, $meta_value );
}

/**
 * Retrieves metadata for a term.
 *
 * @since 4.4.0
 *
 * @param int    $term_id Term ID.
 * @param string $key     Optional. The meta key to retrieve. By default,
 *                        returns data for all keys. Default empty.
 * @param bool   $single  Optional. Whether to return a single value.
 *                        This parameter has no effect if `$key` is not specified.
 *                        Default false.
 * @return mixed An array of values if `$single` is false.
 *               The value of the meta field if `$single` is true.
 *               False for an invalid `$term_id` (non-numeric, zero, or negative value).
 *               An empty string if a valid but non-existing term ID is passed.
 */
function get_term_meta( $term_id, $key = '', $single = false ) {
    return get_metadata( 'term', $term_id, $key, $single );
}

/**
 * Updates term metadata.
 *
 * Use the `$prev_value` parameter to differentiate between meta fields with the same key and term ID.
 *
 * If the meta field for the term does not exist, it will be added.
 *
 * @since 4.4.0
 *
 * @param int    $term_id    Term ID.
 * @param string $meta_key   Metadata key.
 * @param mixed  $meta_value Metadata value. Must be serializable if non-scalar.
 * @param mixed  $prev_value Optional. Previous value to check before updating.
 *                           If specified, only update existing metadata entries with
 *                           this value. Otherwise, update all entries. Default empty.
 * @return int|bool|WP_Error Meta ID if the key didn't exist. true on successful update,
 *                           false on failure or if the value passed to the function
 *                           is the same as the one that is already in the database.
 *                           WP_Error when term_id is ambiguous between taxonomies.
 */
function update_term_meta( $term_id, $meta_key, $meta_value, $prev_value = '' ) {
    if ( wp_term_is_shared( $term_id ) ) {
        return new WP_Error( 'ambiguous_term_id', __( 'Term meta cannot be added to terms that are shared between taxonomies.' ), $term_id );
    }

    return update_metadata( 'term', $term_id, $meta_key, $meta_value, $prev_value );
}

/**
 * Sanitize all term fields.
 *
 * Relies on sanitize_term_field() to sanitize the term. The difference is that
 * this function will sanitize **all** fields. The context is based
 * on sanitize_term_field().
 *
 * The `$term` is expected to be either an array or an object.
 *
 * @since 2.3.0
 *
 * @param array|object $term     The term to check.
 * @param string       $taxonomy The taxonomy name to use.
 * @param string       $context  Optional. Context in which to sanitize the term.
 *                               Accepts 'raw', 'edit', 'db', 'display', 'rss',
 *                               'attribute', or 'js'. Default 'display'.
 * @return array|object Term with all fields sanitized.
 */
function sanitize_term( $term, $taxonomy, $context = 'display' ) {
    $fields = array( 'term_id', 'name', 'description', 'slug', 'count', 'parent', 'term_group', 'term_taxonomy_id', 'object_id' );

    $do_object = is_object( $term );

    $term_id = $do_object ? $term->term_id : ( isset( $term['term_id'] ) ? $term['term_id'] : 0 );

    /** @noinspection PhpCastIsUnnecessaryInspection */
    foreach ((array) $fields as $field ) {
        if ( $do_object ) {
            if ( isset( $term->$field ) ) {
                $term->$field = sanitize_term_field( $field, $term->$field, $term_id, $taxonomy, $context );
            }
        } else {
            if ( isset( $term[ $field ] ) ) {
                $term[ $field ] = sanitize_term_field( $field, $term[ $field ], $term_id, $taxonomy, $context );
            }
        }
    }

    if ( $do_object ) {
        $term->filter = $context;
    } else {
        $term['filter'] = $context;
    }

    return $term;
}

/**
 * Cleanse the field value in the term based on the context.
 *
 * Passing a term field value through the function should be assumed to have
 * cleansed the value for whatever context the term field is going to be used.
 *
 * If no context or an unsupported context is given, then default filters will
 * be applied.
 *
 * There are enough filters for each context to support a custom filtering
 * without creating your own filter function. Simply create a function that
 * hooks into the filter you need.
 *
 * @since 2.3.0
 *
 * @param string $field    Term field to sanitize.
 * @param string $value    Search for this term value.
 * @param int    $term_id  Term ID.
 * @param string $taxonomy Taxonomy name.
 * @param string $context  Context in which to sanitize the term field.
 *                         Accepts 'raw', 'edit', 'db', 'display', 'rss',
 *                         'attribute', or 'js'. Default 'display'.
 * @return mixed Sanitized field.
 */
function sanitize_term_field( $field, $value, $term_id, $taxonomy, $context ) {
    $int_fields = array( 'parent', 'term_id', 'count', 'term_group', 'term_taxonomy_id', 'object_id' );
    if ( in_array( $field, $int_fields, true ) ) {
        $value = (int) $value;
        if ( $value < 0 ) {
            $value = 0;
        }
    }

    $context = strtolower( $context );

    if ( 'raw' === $context ) {
        return $value;
    }

    if ( 'edit' === $context ) {

        /**
         * Filters a term field to edit before it is sanitized.
         *
         * The dynamic portion of the hook name, `$field`, refers to the term field.
         *
         * @since 2.3.0
         *
         * @param mixed $value     Value of the term field.
         * @param int   $term_id   Term ID.
         * @param string $taxonomy Taxonomy slug.
         */
        $value = apply_filters( "edit_term_{$field}", $value, $term_id, $taxonomy );

        /**
         * Filters the taxonomy field to edit before it is sanitized.
         *
         * The dynamic portions of the filter name, `$taxonomy` and `$field`, refer
         * to the taxonomy slug and taxonomy field, respectively.
         *
         * @since 2.3.0
         *
         * @param mixed $value   Value of the taxonomy field to edit.
         * @param int   $term_id Term ID.
         */
        $value = apply_filters( "edit_{$taxonomy}_{$field}", $value, $term_id );

        if ( 'description' === $field ) {
            $value = esc_html( $value ); // textarea_escaped
        } else {
            $value = esc_attr( $value );
        }
    } elseif ( 'db' === $context ) {

        /**
         * Filters a term field value before it is sanitized.
         *
         * The dynamic portion of the hook name, `$field`, refers to the term field.
         *
         * @since 2.3.0
         *
         * @param mixed  $value    Value of the term field.
         * @param string $taxonomy Taxonomy slug.
         */
        $value = apply_filters( "pre_term_{$field}", $value, $taxonomy );

        /**
         * Filters a taxonomy field before it is sanitized.
         *
         * The dynamic portions of the filter name, `$taxonomy` and `$field`, refer
         * to the taxonomy slug and field name, respectively.
         *
         * @since 2.3.0
         *
         * @param mixed $value Value of the taxonomy field.
         */
        $value = apply_filters( "pre_{$taxonomy}_{$field}", $value );

        // Back compat filters.
        if ( 'slug' === $field ) {
            /**
             * Filters the category nicename before it is sanitized.
             *
             * Use the {@see 'pre_$taxonomy_$field'} hook instead.
             *
             * @since 2.0.3
             *
             * @param string $value The category nicename.
             */
            $value = apply_filters( 'pre_category_nicename', $value );
        }
    } elseif ( 'rss' === $context ) {

        /**
         * Filters the term field for use in RSS.
         *
         * The dynamic portion of the hook name, `$field`, refers to the term field.
         *
         * @since 2.3.0
         *
         * @param mixed  $value    Value of the term field.
         * @param string $taxonomy Taxonomy slug.
         */
        $value = apply_filters( "term_{$field}_rss", $value, $taxonomy );

        /**
         * Filters the taxonomy field for use in RSS.
         *
         * The dynamic portions of the hook name, `$taxonomy`, and `$field`, refer
         * to the taxonomy slug and field name, respectively.
         *
         * @since 2.3.0
         *
         * @param mixed $value Value of the taxonomy field.
         */
        $value = apply_filters( "{$taxonomy}_{$field}_rss", $value );
    } else {
        // Use display filters by default.

        /**
         * Filters the term field sanitized for display.
         *
         * The dynamic portion of the hook name, `$field`, refers to the term field name.
         *
         * @since 2.3.0
         *
         * @param mixed  $value    Value of the term field.
         * @param int    $term_id  Term ID.
         * @param string $taxonomy Taxonomy slug.
         * @param string $context  Context to retrieve the term field value.
         */
        $value = apply_filters( "term_{$field}", $value, $term_id, $taxonomy, $context );

        /**
         * Filters the taxonomy field sanitized for display.
         *
         * The dynamic portions of the filter name, `$taxonomy`, and `$field`, refer
         * to the taxonomy slug and taxonomy field, respectively.
         *
         * @since 2.3.0
         *
         * @param mixed  $value   Value of the taxonomy field.
         * @param int    $term_id Term ID.
         * @param string $context Context to retrieve the taxonomy field value.
         */
        $value = apply_filters( "{$taxonomy}_{$field}", $value, $term_id, $context );
    }

    if ( 'attribute' === $context ) {
        $value = esc_attr( $value );
    } elseif ( 'js' === $context ) {
        $value = esc_js( $value );
    }

    // Restore the type for integer fields after esc_attr().
    if ( in_array( $field, $int_fields, true ) ) {
        $value = (int) $value;
    }

    return $value;
}

/**
 * Retrieves the terms associated with the given object(s), in the supplied taxonomies.
 *
 * @since 2.3.0
 * @since 4.2.0 Added support for 'taxonomy', 'parent', and 'term_taxonomy_id' values of `$orderby`.
 *              Introduced `$parent` argument.
 * @since 4.4.0 Introduced `$meta_query` and `$update_term_meta_cache` arguments. When `$fields` is 'all' or
 *              'all_with_object_id', an array of `WP_Term` objects will be returned.
 * @since 4.7.0 Refactored to use WP_Term_Query, and to support any WP_Term_Query arguments.
 *
 * @param int|int[]       $object_ids The ID(s) of the object(s) to retrieve.
 * @param string|string[] $taxonomies The taxonomy names to retrieve terms from.
 * @param array|string    $args       See WP_Term_Query::__construct() for supported arguments.
 * @return WP_Term[]|WP_Error Array of terms or empty array if no terms found.
 *                            WP_Error if any of the taxonomies don't exist.
 */
function wp_get_object_terms( $object_ids, $taxonomies, $args = array() ) {
    if ( empty( $object_ids ) || empty( $taxonomies ) ) {
        return array();
    }

    if ( ! is_array( $taxonomies ) ) {
        $taxonomies = array( $taxonomies );
    }

    foreach ( $taxonomies as $taxonomy ) {
        if ( ! taxonomy_exists( $taxonomy ) ) {
            return new WP_Error( 'invalid_taxonomy', __( 'Invalid taxonomy.' ) );
        }
    }

    if ( ! is_array( $object_ids ) ) {
        $object_ids = array( $object_ids );
    }
    $object_ids = array_map( 'intval', $object_ids );

    $args = wp_parse_args( $args );

    /**
     * Filters arguments for retrieving object terms.
     *
     * @since 4.9.0
     *
     * @param array    $args       An array of arguments for retrieving terms for the given object(s).
     *                             See {@see wp_get_object_terms()} for details.
     * @param int[]    $object_ids Array of object IDs.
     * @param string[] $taxonomies Array of taxonomy names to retrieve terms from.
     */
    $args = apply_filters( 'wp_get_object_terms_args', $args, $object_ids, $taxonomies );

    /*
     * When one or more queried taxonomies is registered with an 'args' array,
     * those params override the `$args` passed to this function.
     */
    $terms = array();
    if ( count( $taxonomies ) > 1 ) {
        foreach ( $taxonomies as $index => $taxonomy ) {
            $t = get_taxonomy( $taxonomy );
            if ( isset( $t->args ) && is_array( $t->args ) && array_merge( $args, $t->args ) != $args ) {
                unset( $taxonomies[ $index ] );
                $terms = array_merge( $terms, wp_get_object_terms( $object_ids, $taxonomy, array_merge( $args, $t->args ) ) );
            }
        }
    } else {
        $t = get_taxonomy( $taxonomies[0] );
        if ( isset( $t->args ) && is_array( $t->args ) ) {
            $args = array_merge( $args, $t->args );
        }
    }

    $args['taxonomy']   = $taxonomies;
    $args['object_ids'] = $object_ids;

    // Taxonomies registered without an 'args' param are handled here.
    if ( ! empty( $taxonomies ) ) {
        $terms_from_remaining_taxonomies = get_terms( $args );

        // Array keys should be preserved for values of $fields that use term_id for keys.
        if ( ! empty( $args['fields'] ) && 0 === strpos( $args['fields'], 'id=>' ) ) {
            $terms = $terms + $terms_from_remaining_taxonomies;
        } else {
            $terms = array_merge( $terms, $terms_from_remaining_taxonomies );
        }
    }

    /**
     * Filters the terms for a given object or objects.
     *
     * @since 4.2.0
     *
     * @param WP_Term[] $terms      Array of terms for the given object or objects.
     * @param int[]     $object_ids Array of object IDs for which terms were retrieved.
     * @param string[]  $taxonomies Array of taxonomy names from which terms were retrieved.
     * @param array     $args       Array of arguments for retrieving terms for the given
     *                              object(s). See wp_get_object_terms() for details.
     */
    $terms = apply_filters( 'get_object_terms', $terms, $object_ids, $taxonomies, $args );

    $object_ids = implode( ',', $object_ids );
    $taxonomies = "'" . implode( "', '", array_map( 'esc_sql', $taxonomies ) ) . "'";

    /**
     * Filters the terms for a given object or objects.
     *
     * The `$taxonomies` parameter passed to this filter is formatted as a SQL fragment. The
     * {@see 'get_object_terms'} filter is recommended as an alternative.
     *
     * @since 2.8.0
     *
     * @param WP_Term[] $terms      Array of terms for the given object or objects.
     * @param string    $object_ids Comma separated list of object IDs for which terms were retrieved.
     * @param string    $taxonomies SQL fragment of taxonomy names from which terms were retrieved.
     * @param array     $args       Array of arguments for retrieving terms for the given
     *                              object(s). See wp_get_object_terms() for details.
     */
    return apply_filters( 'wp_get_object_terms', $terms, $object_ids, $taxonomies, $args );
}

/**
 * Determines whether the taxonomy name exists.
 *
 * Formerly is_taxonomy(), introduced in 2.3.0.
 *
 * For more information on this and similar theme functions, check out
 * the {@link https://developer.wordpress.org/themes/basics/conditional-tags/
 * Conditional Tags} article in the Theme Developer Handbook.
 *
 * @since 3.0.0
 *
 * @global WP_Taxonomy[] $wp_taxonomies The registered taxonomies.
 *
 * @param string $taxonomy Name of taxonomy object.
 * @return bool Whether the taxonomy exists.
 */
function taxonomy_exists( $taxonomy ) {
    global $wp_taxonomies;

    return isset( $wp_taxonomies[ $taxonomy ] );
}

/**
 * Retrieves the cached term objects for the given object ID.
 *
 * Upstream functions (like get_the_terms() and is_object_in_term()) are
 * responsible for populating the object-term relationship cache. The current
 * function only fetches relationship data that is already in the cache.
 *
 * @since 2.3.0
 * @since 4.7.0 Returns a `WP_Error` object if there's an error with
 *              any of the matched terms.
 *
 * @param int    $id       Term object ID, for example a post, comment, or user ID.
 * @param string $taxonomy Taxonomy name.
 * @return bool|WP_Term[]|WP_Error Array of `WP_Term` objects, if cached.
 *                                 False if cache is empty for `$taxonomy` and `$id`.
 *                                 WP_Error if the get_term() returns an error object for any term.
 */
function get_object_term_cache( $id, $taxonomy ) {
    $_term_ids = wp_cache_get( $id, "{$taxonomy}_relationships" );

    // We leave the priming of relationship caches to upstream functions.
    if ( false === $_term_ids ) {
        return false;
    }

    // Backward compatibility for if a plugin is putting objects into the cache, rather than IDs.
    $term_ids = array();
    foreach ( $_term_ids as $term_id ) {
        if ( is_numeric( $term_id ) ) {
            $term_ids[] = (int) $term_id;
        } elseif ( isset( $term_id->term_id ) ) {
            $term_ids[] = (int) $term_id->term_id;
        }
    }

    // Fill the term objects.
    /** @noinspection PhpUndefinedFunctionInspection */
    _prime_term_caches( $term_ids );

    $terms = array();
    foreach ( $term_ids as $term_id ) {
        $term = get_term( $term_id, $taxonomy );
        if ( is_wp_error( $term ) ) {
            return $term;
        }

        $terms[] = $term;
    }

    return $terms;
}

/**
 * Retrieves the taxonomy object of $taxonomy.
 *
 * The get_taxonomy function will first check that the parameter string given
 * is a taxonomy object and if it is, it will return it.
 *
 * @since 2.3.0
 *
 * @global WP_Taxonomy[] $wp_taxonomies The registered taxonomies.
 *
 * @param string $taxonomy Name of taxonomy object to return.
 * @return WP_Taxonomy|false The Taxonomy Object or false if $taxonomy doesn't exist.
 */
function get_taxonomy( $taxonomy ) {
    global $wp_taxonomies;

    if ( ! taxonomy_exists( $taxonomy ) ) {
        return false;
    }

    return $wp_taxonomies[ $taxonomy ];
}

/**
 * Determine whether a term is shared between multiple taxonomies.
 *
 * Shared taxonomy terms began to be split in 4.3, but failed cron tasks or
 * other delays in upgrade routines may cause shared terms to remain.
 *
 * @since 4.4.0
 *
 * @param int $term_id Term ID.
 * @return bool Returns false if a term is not shared between multiple taxonomies or
 *              if splitting shared taxonomy terms is finished.
 */
function wp_term_is_shared( $term_id ) {
    global $wpdb;

    if ( get_option( 'finished_splitting_shared_terms' ) ) {
        return false;
    }

    $tt_count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $wpdb->term_taxonomy WHERE term_id = %d", $term_id ) );

    return $tt_count > 1;
}

/**
 * Determine if the given object type is associated with the given taxonomy.
 *
 * @since 3.0.0
 *
 * @param string $object_type Object type string.
 * @param string $taxonomy    Single taxonomy name.
 * @return bool True if object is associated with the taxonomy, otherwise false.
 */
function is_object_in_taxonomy( $object_type, $taxonomy ) {
    $taxonomies = get_object_taxonomies( $object_type );
    if ( empty( $taxonomies ) ) {
        return false;
    }
    return in_array( $taxonomy, $taxonomies, true );
}

/**
 * Retrieves a list of registered taxonomy names or objects.
 *
 * @since 3.0.0
 *
 * @global WP_Taxonomy[] $wp_taxonomies The registered taxonomies.
 *
 * @param array  $args     Optional. An array of `key => value` arguments to match against the taxonomy objects.
 *                         Default empty array.
 * @param string $output   Optional. The type of output to return to the array. Accepts either taxonomy 'names'
 *                         or 'objects'. Default 'names'.
 * @param string $operator Optional. The logical operation to perform. Accepts 'and' or 'or'. 'or' means only
 *                         one element from the array needs to match; 'and' means all elements must match.
 *                         Default 'and'.
 * @return string[]|WP_Taxonomy[] An array of taxonomy names or objects.
 */
function get_taxonomies( $args = array(), $output = 'names', $operator = 'and' ) {
    global $wp_taxonomies;

    $field = ( 'names' === $output ) ? 'name' : false;

    return wp_filter_object_list( $wp_taxonomies, $args, $operator, $field );
}

/**
 * Return the names or objects of the taxonomies which are registered for the requested object or object type, such as
 * a post object or post type name.
 *
 * Example:
 *
 *     $taxonomies = get_object_taxonomies( 'post' );
 *
 * This results in:
 *
 *     Array( 'category', 'post_tag' )
 *
 * @since 2.3.0
 *
 * @global WP_Taxonomy[] $wp_taxonomies The registered taxonomies.
 *
 * @param string|string[]|WP_Post $object Name of the type of taxonomy object, or an object (row from posts)
 * @param string                  $output Optional. The type of output to return to the array. Accepts either
 *                                        'names' or 'objects'. Default 'names'.
 * @return string[]|WP_Taxonomy[] The names or objects of all taxonomies of `$object_type`.
 */
function get_object_taxonomies( $object, $output = 'names' ) {
    global $wp_taxonomies;

    if ( is_object( $object ) ) {
        if ( 'attachment' === $object->post_type ) {
            /** @noinspection PhpUndefinedFunctionInspection */
            return get_attachment_taxonomies( $object, $output );
        }
        $object = $object->post_type;
    }

    $object = (array) $object;

    $taxonomies = array();
    foreach ( (array) $wp_taxonomies as $tax_name => $tax_obj ) {
        if ( array_intersect( $object, (array) $tax_obj->object_type ) ) {
            if ( 'names' === $output ) {
                $taxonomies[] = $tax_name;
            } else {
                $taxonomies[ $tax_name ] = $tax_obj;
            }
        }
    }

    return $taxonomies;
}

/**
 * Add an already registered taxonomy to an object type.
 *
 * @since 3.0.0
 *
 * @global WP_Taxonomy[] $wp_taxonomies The registered taxonomies.
 *
 * @param string $taxonomy    Name of taxonomy object.
 * @param string $object_type Name of the object type.
 * @return bool True if successful, false if not.
 */
function register_taxonomy_for_object_type( $taxonomy, $object_type ) {
    global $wp_taxonomies;

    if ( ! isset( $wp_taxonomies[ $taxonomy ] ) ) {
        return false;
    }

    if ( ! get_post_type_object( $object_type ) ) {
        return false;
    }

    if ( ! in_array( $object_type, $wp_taxonomies[ $taxonomy ]->object_type, true ) ) {
        $wp_taxonomies[ $taxonomy ]->object_type[] = $object_type;
    }

    // Filter out empties.
    $wp_taxonomies[ $taxonomy ]->object_type = array_filter( $wp_taxonomies[ $taxonomy ]->object_type );

    /**
     * Fires after a taxonomy is registered for an object type.
     *
     * @since 5.1.0
     *
     * @param string $taxonomy    Taxonomy name.
     * @param string $object_type Name of the object type.
     */
    do_action( 'registered_taxonomy_for_object_type', $taxonomy, $object_type );

    return true;
}


/**
 * Retrieves the terms of the taxonomy that are attached to the post.
 *
 * @since 2.5.0
 *
 * @param int|WP_Post $post     Post ID or object.
 * @param string      $taxonomy Taxonomy name.
 * @return WP_Term[]|false|WP_Error Array of WP_Term objects on success, false if there are no terms
 *                                  or the post does not exist, WP_Error on failure.
 */
function get_the_terms( $post, $taxonomy ) {
    $post = get_post( $post );
    if ( ! $post ) {
        return false;
    }

    $terms = get_object_term_cache( $post->ID, $taxonomy );
    if ( false === $terms ) {
        $terms = wp_get_object_terms( $post->ID, $taxonomy );
        if ( ! is_wp_error( $terms ) ) {
            $term_ids = wp_list_pluck( $terms, 'term_id' );
            wp_cache_add( $post->ID, $term_ids, $taxonomy . '_relationships' );
        }
    }

    /**
     * Filters the list of terms attached to the given post.
     *
     * @since 3.1.0
     *
     * @param WP_Term[]|WP_Error $terms    Array of attached terms, or WP_Error on failure.
     * @param int                $post_id  Post ID.
     * @param string             $taxonomy Name of the taxonomy.
     */
    $terms = apply_filters( 'get_the_terms', $terms, $post->ID, $taxonomy );

    if ( empty( $terms ) ) {
        return false;
    }

    return $terms;
}
