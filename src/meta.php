<?php /** @noinspection SpellCheckingInspection */
/** @noinspection PhpUnused */
/** @noinspection GrazieInspection */
/** @noinspection PhpUnnecessaryCurlyVarSyntaxInspection */
/** @noinspection DuplicatedCode */
/** @noinspection SqlDialectInspection */
/** @noinspection SqlNoDataSourceInspection */


/**
 * Core Metadata API
 *
 * Functions for retrieving and manipulating metadata of various WordPress object types. Metadata
 * for an object is a represented by a simple key-value pair. Objects may contain multiple
 * metadata entries that share the same key and differ only in their value.
 *
 * @package WordPress
 * @subpackage Meta
 */

/**
 * Adds metadata for the specified object.
 *
 * @since 2.9.0
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param string $meta_type  Type of object metadata is for. Accepts 'post', 'comment', 'term', 'user',
 *                           or any other object type with an associated meta table.
 * @param int    $object_id  ID of the object metadata is for.
 * @param string $meta_key   Metadata key.
 * @param mixed  $meta_value Metadata value. Must be serializable if non-scalar.
 * @param bool   $unique     Optional. Whether the specified metadata key should be unique for the object.
 *                           If true, and the object already has a value for the specified metadata key,
 *                           no change will be made. Default false.
 * @return int|false The meta ID on success, false on failure.
 */
function add_metadata( $meta_type, $object_id, $meta_key, $meta_value, $unique = false ) {
    global $wpdb;

    if ( ! $meta_type || ! $meta_key || ! is_numeric( $object_id ) ) {
        return false;
    }

    $object_id = absint( $object_id );
    if ( ! $object_id ) {
        return false;
    }

    $table = _get_meta_table( $meta_type );
    if ( ! $table ) {
        return false;
    }

    $meta_subtype = get_object_subtype( $meta_type, $object_id );

    $column = sanitize_key( $meta_type . '_id' );

    // expected_slashed ($meta_key)
    $meta_key   = wp_unslash( $meta_key );
    $meta_value = wp_unslash( $meta_value );
    $meta_value = sanitize_meta( $meta_key, $meta_value, $meta_type, $meta_subtype );

    /**
     * Short-circuits adding metadata of a specific type.
     *
     * The dynamic portion of the hook name, `$meta_type`, refers to the meta object type
     * (post, comment, term, user, or any other type with an associated meta table).
     * Returning a non-null value will effectively short-circuit the function.
     *
     * Possible hook names include:
     *
     *  - `add_post_metadata`
     *  - `add_comment_metadata`
     *  - `add_term_metadata`
     *  - `add_user_metadata`
     *
     * @since 3.1.0
     *
     * @param null|bool $check      Whether to allow adding metadata for the given type.
     * @param int       $object_id  ID of the object metadata is for.
     * @param string    $meta_key   Metadata key.
     * @param mixed     $meta_value Metadata value. Must be serializable if non-scalar.
     * @param bool      $unique     Whether the specified meta key should be unique for the object.
     */
    $check = apply_filters( "add_{$meta_type}_metadata", null, $object_id, $meta_key, $meta_value, $unique );
    if ( null !== $check ) {
        return $check;
    }

    if ( $unique && $wpdb->get_var(
        $wpdb->prepare(
          "SELECT COUNT(*) FROM $table WHERE meta_key = %s AND $column = %d",
          $meta_key,
          $object_id
        )
      ) ) {
        return false;
    }

    $_meta_value = $meta_value;
    $meta_value  = maybe_serialize( $meta_value );

    /**
     * Fires immediately before meta of a specific type is added.
     *
     * The dynamic portion of the hook name, `$meta_type`, refers to the meta object type
     * (post, comment, term, user, or any other type with an associated meta table).
     *
     * Possible hook names include:
     *
     *  - `add_post_meta`
     *  - `add_comment_meta`
     *  - `add_term_meta`
     *  - `add_user_meta`
     *
     * @since 3.1.0
     *
     * @param int    $object_id   ID of the object metadata is for.
     * @param string $meta_key    Metadata key.
     * @param mixed  $_meta_value Metadata value.
     */
    do_action( "add_{$meta_type}_meta", $object_id, $meta_key, $_meta_value );

    $result = $wpdb->insert(
      $table,
      array(
        $column      => $object_id,
        'meta_key'   => $meta_key,
        'meta_value' => $meta_value,
      )
    );

    if ( ! $result ) {
        return false;
    }

    $mid = (int) $wpdb->insert_id;

    wp_cache_delete( $object_id, $meta_type . '_meta' );

    /**
     * Fires immediately after meta of a specific type is added.
     *
     * The dynamic portion of the hook name, `$meta_type`, refers to the meta object type
     * (post, comment, term, user, or any other type with an associated meta table).
     *
     * Possible hook names include:
     *
     *  - `added_post_meta`
     *  - `added_comment_meta`
     *  - `added_term_meta`
     *  - `added_user_meta`
     *
     * @since 2.9.0
     *
     * @param int    $mid         The meta ID after successful update.
     * @param int    $object_id   ID of the object metadata is for.
     * @param string $meta_key    Metadata key.
     * @param mixed  $_meta_value Metadata value.
     */
    do_action( "added_{$meta_type}_meta", $mid, $object_id, $meta_key, $_meta_value );

    return $mid;
}

/**
 * Updates metadata for the specified object. If no value already exists for the specified object
 * ID and metadata key, the metadata will be added.
 *
 * @since 2.9.0
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param string $meta_type  Type of object metadata is for. Accepts 'post', 'comment', 'term', 'user',
 *                           or any other object type with an associated meta table.
 * @param int    $object_id  ID of the object metadata is for.
 * @param string $meta_key   Metadata key.
 * @param mixed  $meta_value Metadata value. Must be serializable if non-scalar.
 * @param mixed  $prev_value Optional. Previous value to check before updating.
 *                           If specified, only update existing metadata entries with
 *                           this value. Otherwise, update all entries. Default empty.
 * @return int|bool The new meta field ID if a field with the given key didn't exist
 *                  and was therefore added, true on successful update,
 *                  false on failure or if the value passed to the function
 *                  is the same as the one that is already in the database.
 */
function update_metadata( $meta_type, $object_id, $meta_key, $meta_value, $prev_value = '' ) {
    global $wpdb;

    if ( ! $meta_type || ! $meta_key || ! is_numeric( $object_id ) ) {
        return false;
    }

    $object_id = absint( $object_id );
    if ( ! $object_id ) {
        return false;
    }

    $table = _get_meta_table( $meta_type );
    if ( ! $table ) {
        return false;
    }

    $meta_subtype = get_object_subtype( $meta_type, $object_id );

    $column    = sanitize_key( $meta_type . '_id' );
    $id_column = ( 'user' === $meta_type ) ? 'umeta_id' : 'meta_id';

    // expected_slashed ($meta_key)
    $raw_meta_key = $meta_key;
    $meta_key     = wp_unslash( $meta_key );
    $passed_value = $meta_value;
    $meta_value   = wp_unslash( $meta_value );
    $meta_value   = sanitize_meta( $meta_key, $meta_value, $meta_type, $meta_subtype );

    /**
     * Short-circuits updating metadata of a specific type.
     *
     * The dynamic portion of the hook name, `$meta_type`, refers to the meta object type
     * (post, comment, term, user, or any other type with an associated meta table).
     * Returning a non-null value will effectively short-circuit the function.
     *
     * Possible hook names include:
     *
     *  - `update_post_metadata`
     *  - `update_comment_metadata`
     *  - `update_term_metadata`
     *  - `update_user_metadata`
     *
     * @since 3.1.0
     *
     * @param null|bool $check      Whether to allow updating metadata for the given type.
     * @param int       $object_id  ID of the object metadata is for.
     * @param string    $meta_key   Metadata key.
     * @param mixed     $meta_value Metadata value. Must be serializable if non-scalar.
     * @param mixed     $prev_value Optional. Previous value to check before updating.
     *                              If specified, only update existing metadata entries with
     *                              this value. Otherwise, update all entries.
     */
    $check = apply_filters( "update_{$meta_type}_metadata", null, $object_id, $meta_key, $meta_value, $prev_value );
    if ( null !== $check ) {
        return (bool) $check;
    }

    // Compare existing value to new value if no prev value given and the key exists only once.
    if ( empty( $prev_value ) ) {
        $old_value = get_metadata_raw( $meta_type, $object_id, $meta_key );
        /** @noinspection PhpElementIsNotAvailableInCurrentPhpVersionInspection */
        if ( is_countable( $old_value ) && count( $old_value ) === 1 ) {
            if ( $old_value[0] === $meta_value ) {
                return false;
            }
        }
    }

    $meta_ids = $wpdb->get_col( $wpdb->prepare( "SELECT $id_column FROM $table WHERE meta_key = %s AND $column = %d", $meta_key, $object_id ) );
    if ( empty( $meta_ids ) ) {
        return add_metadata( $meta_type, $object_id, $raw_meta_key, $passed_value );
    }

    $_meta_value = $meta_value;
    $meta_value  = maybe_serialize( $meta_value );

    $data  = compact( 'meta_value' );
    $where = array(
      $column    => $object_id,
      'meta_key' => $meta_key,
    );

    if ( ! empty( $prev_value ) ) {
        $prev_value          = maybe_serialize( $prev_value );
        $where['meta_value'] = $prev_value;
    }

    foreach ( $meta_ids as $meta_id ) {
        /**
         * Fires immediately before updating metadata of a specific type.
         *
         * The dynamic portion of the hook name, `$meta_type`, refers to the meta object type
         * (post, comment, term, user, or any other type with an associated meta table).
         *
         * Possible hook names include:
         *
         *  - `update_post_meta`
         *  - `update_comment_meta`
         *  - `update_term_meta`
         *  - `update_user_meta`
         *
         * @since 2.9.0
         *
         * @param int    $meta_id     ID of the metadata entry to update.
         * @param int    $object_id   ID of the object metadata is for.
         * @param string $meta_key    Metadata key.
         * @param mixed  $_meta_value Metadata value.
         */
        do_action( "update_{$meta_type}_meta", $meta_id, $object_id, $meta_key, $_meta_value );

        if ( 'post' === $meta_type ) {
            /**
             * Fires immediately before updating a post's metadata.
             *
             * @since 2.9.0
             *
             * @param int    $meta_id    ID of metadata entry to update.
             * @param int    $object_id  Post ID.
             * @param string $meta_key   Metadata key.
             * @param mixed  $meta_value Metadata value. This will be a PHP-serialized string representation of the value
             *                           if the value is an array, an object, or itself a PHP-serialized string.
             */
            do_action( 'update_postmeta', $meta_id, $object_id, $meta_key, $meta_value );
        }
    }

    $result = $wpdb->update( $table, $data, $where );
    if ( ! $result ) {
        return false;
    }

    wp_cache_delete( $object_id, $meta_type . '_meta' );

    foreach ( $meta_ids as $meta_id ) {
        /**
         * Fires immediately after updating metadata of a specific type.
         *
         * The dynamic portion of the hook name, `$meta_type`, refers to the meta object type
         * (post, comment, term, user, or any other type with an associated meta table).
         *
         * Possible hook names include:
         *
         *  - `updated_post_meta`
         *  - `updated_comment_meta`
         *  - `updated_term_meta`
         *  - `updated_user_meta`
         *
         * @since 2.9.0
         *
         * @param int    $meta_id     ID of updated metadata entry.
         * @param int    $object_id   ID of the object metadata is for.
         * @param string $meta_key    Metadata key.
         * @param mixed  $_meta_value Metadata value.
         */
        do_action( "updated_{$meta_type}_meta", $meta_id, $object_id, $meta_key, $_meta_value );

        if ( 'post' === $meta_type ) {
            /**
             * Fires immediately after updating a post's metadata.
             *
             * @since 2.9.0
             *
             * @param int    $meta_id    ID of updated metadata entry.
             * @param int    $object_id  Post ID.
             * @param string $meta_key   Metadata key.
             * @param mixed  $meta_value Metadata value. This will be a PHP-serialized string representation of the value
             *                           if the value is an array, an object, or itself a PHP-serialized string.
             */
            do_action( 'updated_postmeta', $meta_id, $object_id, $meta_key, $meta_value );
        }
    }

    return true;
}

/**
 * Deletes metadata for the specified object.
 *
 * @since 2.9.0
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param string $meta_type  Type of object metadata is for. Accepts 'post', 'comment', 'term', 'user',
 *                           or any other object type with an associated meta table.
 * @param int    $object_id  ID of the object metadata is for.
 * @param string $meta_key   Metadata key.
 * @param mixed  $meta_value Optional. Metadata value. Must be serializable if non-scalar.
 *                           If specified, only delete metadata entries with this value.
 *                           Otherwise, delete all entries with the specified meta_key.
 *                           Pass `null`, `false`, or an empty string to skip this check.
 *                           (For backward compatibility, it is not possible to pass an empty string
 *                           to delete those entries with an empty string for a value.)
 * @param bool   $delete_all Optional. If true, delete matching metadata entries for all objects,
 *                           ignoring the specified object_id. Otherwise, only delete
 *                           matching metadata entries for the specified object_id. Default false.
 * @return bool True on successful delete, false on failure.
 */
function delete_metadata( $meta_type, $object_id, $meta_key, $meta_value = '', $delete_all = false ) {
    global $wpdb;

    if ( ! $meta_type || ! $meta_key || ! is_numeric( $object_id ) && ! $delete_all ) {
        return false;
    }

    $object_id = absint( $object_id );
    if ( ! $object_id && ! $delete_all ) {
        return false;
    }

    $table = _get_meta_table( $meta_type );
    if ( ! $table ) {
        return false;
    }

    $type_column = sanitize_key( $meta_type . '_id' );
    $id_column   = ( 'user' === $meta_type ) ? 'umeta_id' : 'meta_id';

    // expected_slashed ($meta_key)
    $meta_key   = wp_unslash( $meta_key );
    $meta_value = wp_unslash( $meta_value );

    /**
     * Short-circuits deleting metadata of a specific type.
     *
     * The dynamic portion of the hook name, `$meta_type`, refers to the meta object type
     * (post, comment, term, user, or any other type with an associated meta table).
     * Returning a non-null value will effectively short-circuit the function.
     *
     * Possible hook names include:
     *
     *  - `delete_post_metadata`
     *  - `delete_comment_metadata`
     *  - `delete_term_metadata`
     *  - `delete_user_metadata`
     *
     * @since 3.1.0
     *
     * @param null|bool $delete     Whether to allow metadata deletion of the given type.
     * @param int       $object_id  ID of the object metadata is for.
     * @param string    $meta_key   Metadata key.
     * @param mixed     $meta_value Metadata value. Must be serializable if non-scalar.
     * @param bool      $delete_all Whether to delete the matching metadata entries
     *                              for all objects, ignoring the specified $object_id.
     *                              Default false.
     */
    $check = apply_filters( "delete_{$meta_type}_metadata", null, $object_id, $meta_key, $meta_value, $delete_all );
    if ( null !== $check ) {
        return (bool) $check;
    }

    $_meta_value = $meta_value;
    $meta_value  = maybe_serialize( $meta_value );

    $query = $wpdb->prepare( "SELECT $id_column FROM $table WHERE meta_key = %s", $meta_key );

    if ( ! $delete_all ) {
        $query .= $wpdb->prepare( " AND $type_column = %d", $object_id );
    }

    if ( '' !== $meta_value && null !== $meta_value && false !== $meta_value ) {
        $query .= $wpdb->prepare( ' AND meta_value = %s', $meta_value );
    }

    $meta_ids = $wpdb->get_col( $query );
    if ( ! count( $meta_ids ) ) {
        return false;
    }

    if ( $delete_all ) {
        if ( '' !== $meta_value && null !== $meta_value && false !== $meta_value ) {
            $object_ids = $wpdb->get_col( $wpdb->prepare( "SELECT $type_column FROM $table WHERE meta_key = %s AND meta_value = %s", $meta_key, $meta_value ) );
        } else {
            $object_ids = $wpdb->get_col( $wpdb->prepare( "SELECT $type_column FROM $table WHERE meta_key = %s", $meta_key ) );
        }
    }

    /**
     * Fires immediately before deleting metadata of a specific type.
     *
     * The dynamic portion of the hook name, `$meta_type`, refers to the meta object type
     * (post, comment, term, user, or any other type with an associated meta table).
     *
     * Possible hook names include:
     *
     *  - `delete_post_meta`
     *  - `delete_comment_meta`
     *  - `delete_term_meta`
     *  - `delete_user_meta`
     *
     * @since 3.1.0
     *
     * @param string[] $meta_ids    An array of metadata entry IDs to delete.
     * @param int      $object_id   ID of the object metadata is for.
     * @param string   $meta_key    Metadata key.
     * @param mixed    $_meta_value Metadata value.
     */
    do_action( "delete_{$meta_type}_meta", $meta_ids, $object_id, $meta_key, $_meta_value );

    // Old-style action.
    if ( 'post' === $meta_type ) {
        /**
         * Fires immediately before deleting metadata for a post.
         *
         * @since 2.9.0
         *
         * @param string[] $meta_ids An array of metadata entry IDs to delete.
         */
        do_action( 'delete_postmeta', $meta_ids );
    }

    $query = "DELETE FROM $table WHERE $id_column IN( " . implode( ',', $meta_ids ) . ' )';

    $count = $wpdb->query( $query );

    if ( ! $count ) {
        return false;
    }

    if ( $delete_all ) {
        foreach ( (array) $object_ids as $o_id ) {
            wp_cache_delete( $o_id, $meta_type . '_meta' );
        }
    } else {
        wp_cache_delete( $object_id, $meta_type . '_meta' );
    }

    /**
     * Fires immediately after deleting metadata of a specific type.
     *
     * The dynamic portion of the hook name, `$meta_type`, refers to the meta object type
     * (post, comment, term, user, or any other type with an associated meta table).
     *
     * Possible hook names include:
     *
     *  - `deleted_post_meta`
     *  - `deleted_comment_meta`
     *  - `deleted_term_meta`
     *  - `deleted_user_meta`
     *
     * @since 2.9.0
     *
     * @param string[] $meta_ids    An array of metadata entry IDs to delete.
     * @param int      $object_id   ID of the object metadata is for.
     * @param string   $meta_key    Metadata key.
     * @param mixed    $_meta_value Metadata value.
     */
    do_action( "deleted_{$meta_type}_meta", $meta_ids, $object_id, $meta_key, $_meta_value );

    // Old-style action.
    if ( 'post' === $meta_type ) {
        /**
         * Fires immediately after deleting metadata for a post.
         *
         * @since 2.9.0
         *
         * @param string[] $meta_ids An array of metadata entry IDs to delete.
         */
        do_action( 'deleted_postmeta', $meta_ids );
    }

    return true;
}

/**
 * Retrieves the value of a metadata field for the specified object type and ID.
 *
 * If the meta field exists, a single value is returned if `$single` is true,
 * or an array of values if it's false.
 *
 * If the meta field does not exist, the result depends on get_metadata_default().
 * By default, an empty string is returned if `$single` is true, or an empty array
 * if it's false.
 *
 * @since 2.9.0
 *
 * @see get_metadata_raw()
 * @see get_metadata_default()
 *
 * @param string $meta_type Type of object metadata is for. Accepts 'post', 'comment', 'term', 'user',
 *                          or any other object type with an associated meta table.
 * @param int    $object_id ID of the object metadata is for.
 * @param string $meta_key  Optional. Metadata key. If not specified, retrieve all metadata for
 *                          the specified object. Default empty.
 * @param bool   $single    Optional. If true, return only the first value of the specified `$meta_key`.
 *                          This parameter has no effect if `$meta_key` is not specified. Default false.
 * @return mixed An array of values if `$single` is false.
 *               The value of the meta field if `$single` is true.
 *               False for an invalid `$object_id` (non-numeric, zero, or negative value),
 *               or if `$meta_type` is not specified.
 *               An empty string if a valid but non-existing object ID is passed.
 */
function get_metadata( $meta_type, $object_id, $meta_key = '', $single = false ) {
    $value = get_metadata_raw( $meta_type, $object_id, $meta_key, $single );
    if ( ! is_null( $value ) ) {
        return $value;
    }

    return get_metadata_default( $meta_type, $object_id, $meta_key, $single );
}

/**
 * Retrieves raw metadata value for the specified object.
 *
 * @since 5.5.0
 *
 * @param string $meta_type Type of object metadata is for. Accepts 'post', 'comment', 'term', 'user',
 *                          or any other object type with an associated meta table.
 * @param int    $object_id ID of the object metadata is for.
 * @param string $meta_key  Optional. Metadata key. If not specified, retrieve all metadata for
 *                          the specified object. Default empty.
 * @param bool   $single    Optional. If true, return only the first value of the specified `$meta_key`.
 *                          This parameter has no effect if `$meta_key` is not specified. Default false.
 * @return mixed An array of values if `$single` is false.
 *               The value of the meta field if `$single` is true.
 *               False for an invalid `$object_id` (non-numeric, zero, or negative value),
 *               or if `$meta_type` is not specified.
 *               Null if the value does not exist.
 */
function get_metadata_raw( $meta_type, $object_id, $meta_key = '', $single = false ) {
    if ( ! $meta_type || ! is_numeric( $object_id ) ) {
        return false;
    }

    $object_id = absint( $object_id );
    if ( ! $object_id ) {
        return false;
    }

    /**
     * Short-circuits the return value of a meta field.
     *
     * The dynamic portion of the hook name, `$meta_type`, refers to the meta object type
     * (post, comment, term, user, or any other type with an associated meta table).
     * Returning a non-null value will effectively short-circuit the function.
     *
     * Possible filter names include:
     *
     *  - `get_post_metadata`
     *  - `get_comment_metadata`
     *  - `get_term_metadata`
     *  - `get_user_metadata`
     *
     * @since 3.1.0
     * @since 5.5.0 Added the `$meta_type` parameter.
     *
     * @param mixed  $value     The value to return, either a single metadata value or an array
     *                          of values depending on the value of `$single`. Default null.
     * @param int    $object_id ID of the object metadata is for.
     * @param string $meta_key  Metadata key.
     * @param bool   $single    Whether to return only the first value of the specified `$meta_key`.
     * @param string $meta_type Type of object metadata is for. Accepts 'post', 'comment', 'term', 'user',
     *                          or any other object type with an associated meta table.
     */
    $check = apply_filters( "get_{$meta_type}_metadata", null, $object_id, $meta_key, $single, $meta_type );
    if ( null !== $check ) {
        if ( $single && is_array( $check ) ) {
            return $check[0];
        } else {
            return $check;
        }
    }

    $meta_cache = wp_cache_get( $object_id, $meta_type . '_meta' );

    if ( ! $meta_cache ) {
        $meta_cache = update_meta_cache( $meta_type, array( $object_id ) );
        if ( isset( $meta_cache[ $object_id ] ) ) {
            $meta_cache = $meta_cache[ $object_id ];
        } else {
            $meta_cache = null;
        }
    }

    if ( ! $meta_key ) {
        return $meta_cache;
    }

    if ( isset( $meta_cache[ $meta_key ] ) ) {
        if ( $single ) {
            return maybe_unserialize( $meta_cache[ $meta_key ][0] );
        } else {
            return array_map( 'maybe_unserialize', $meta_cache[ $meta_key ] );
        }
    }

    return null;
}

/**
 * Retrieves default metadata value for the specified meta key and object.
 *
 * By default, an empty string is returned if `$single` is true, or an empty array
 * if it's false.
 *
 * @since 5.5.0
 *
 * @param string $meta_type Type of object metadata is for. Accepts 'post', 'comment', 'term', 'user',
 *                          or any other object type with an associated meta table.
 * @param int    $object_id ID of the object metadata is for.
 * @param string $meta_key  Metadata key.
 * @param bool   $single    Optional. If true, return only the first value of the specified `$meta_key`.
 *                          This parameter has no effect if `$meta_key` is not specified. Default false.
 * @return mixed An array of default values if `$single` is false.
 *               The default value of the meta field if `$single` is true.
 */
function get_metadata_default( $meta_type, $object_id, $meta_key, $single = false ) {
    if ( $single ) {
        $value = '';
    } else {
        $value = array();
    }

    /**
     * Filters the default metadata value for a specified meta key and object.
     *
     * The dynamic portion of the hook name, `$meta_type`, refers to the meta object type
     * (post, comment, term, user, or any other type with an associated meta table).
     *
     * Possible filter names include:
     *
     *  - `default_post_metadata`
     *  - `default_comment_metadata`
     *  - `default_term_metadata`
     *  - `default_user_metadata`
     *
     * @since 5.5.0
     *
     * @param mixed  $value     The value to return, either a single metadata value or an array
     *                          of values depending on the value of `$single`.
     * @param int    $object_id ID of the object metadata is for.
     * @param string $meta_key  Metadata key.
     * @param bool   $single    Whether to return only the first value of the specified `$meta_key`.
     * @param string $meta_type Type of object metadata is for. Accepts 'post', 'comment', 'term', 'user',
     *                          or any other object type with an associated meta table.
     */
    $value = apply_filters( "default_{$meta_type}_metadata", $value, $object_id, $meta_key, $single, $meta_type );

    if ( ! $single && ! wp_is_numeric_array( $value ) ) {
        $value = array( $value );
    }

    return $value;
}

/**
 * Determines if a meta field with the given key exists for the given object ID.
 *
 * @since 3.3.0
 *
 * @param string $meta_type Type of object metadata is for. Accepts 'post', 'comment', 'term', 'user',
 *                          or any other object type with an associated meta table.
 * @param int    $object_id ID of the object metadata is for.
 * @param string $meta_key  Metadata key.
 * @return bool Whether a meta field with the given key exists.
 */
function metadata_exists( $meta_type, $object_id, $meta_key ) {
    if ( ! $meta_type || ! is_numeric( $object_id ) ) {
        return false;
    }

    $object_id = absint( $object_id );
    if ( ! $object_id ) {
        return false;
    }

    /** This filter is documented in wp-includes/meta.php */
    $check = apply_filters( "get_{$meta_type}_metadata", null, $object_id, $meta_key, true, $meta_type );
    if ( null !== $check ) {
        return (bool) $check;
    }

    $meta_cache = wp_cache_get( $object_id, $meta_type . '_meta' );

    if ( ! $meta_cache ) {
        $meta_cache = update_meta_cache( $meta_type, array( $object_id ) );
        $meta_cache = $meta_cache[ $object_id ];
    }

    if ( isset( $meta_cache[ $meta_key ] ) ) {
        return true;
    }

    return false;
}

/**
 * Sanitizes meta value.
 *
 * @since 3.1.3
 * @since 4.9.8 The `$object_subtype` parameter was added.
 *
 * @param string $meta_key       Metadata key.
 * @param mixed  $meta_value     Metadata value to sanitize.
 * @param string $object_type    Type of object metadata is for. Accepts 'post', 'comment', 'term', 'user',
 *                               or any other object type with an associated meta table.
 * @param string $object_subtype Optional. The subtype of the object type.
 * @return mixed Sanitized $meta_value.
 */
function sanitize_meta( $meta_key, $meta_value, $object_type, $object_subtype = '' ) {
    if ( ! empty( $object_subtype ) && has_filter( "sanitize_{$object_type}_meta_{$meta_key}_for_{$object_subtype}" ) ) {

        /**
         * Filters the sanitization of a specific meta key of a specific meta type and subtype.
         *
         * The dynamic portions of the hook name, `$object_type`, `$meta_key`,
         * and `$object_subtype`, refer to the metadata object type (comment, post, term, or user),
         * the meta key value, and the object subtype respectively.
         *
         * @since 4.9.8
         *
         * @param mixed  $meta_value     Metadata value to sanitize.
         * @param string $meta_key       Metadata key.
         * @param string $object_type    Type of object metadata is for. Accepts 'post', 'comment', 'term', 'user',
         *                               or any other object type with an associated meta table.
         * @param string $object_subtype Object subtype.
         */
        return apply_filters( "sanitize_{$object_type}_meta_{$meta_key}_for_{$object_subtype}", $meta_value, $meta_key, $object_type, $object_subtype );
    }

    /**
     * Filters the sanitization of a specific meta key of a specific meta type.
     *
     * The dynamic portions of the hook name, `$meta_type`, and `$meta_key`,
     * refer to the metadata object type (comment, post, term, or user) and the meta
     * key value, respectively.
     *
     * @since 3.3.0
     *
     * @param mixed  $meta_value  Metadata value to sanitize.
     * @param string $meta_key    Metadata key.
     * @param string $object_type Type of object metadata is for. Accepts 'post', 'comment', 'term', 'user',
     *                            or any other object type with an associated meta table.
     */
    return apply_filters( "sanitize_{$object_type}_meta_{$meta_key}", $meta_value, $meta_key, $object_type );
}

/**
 * Retrieves the name of the metadata table for the specified object type.
 *
 * @since 2.9.0
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param string $type Type of object metadata is for. Accepts 'post', 'comment', 'term', 'user',
 *                     or any other object type with an associated meta table.
 * @return string|false Metadata table name, or false if no metadata table exists
 */
function _get_meta_table( $type ) {
    global $wpdb;

    $table_name = $type . 'meta';

    if ( empty( $wpdb->$table_name ) ) {
        return false;
    }

    return $wpdb->$table_name;
}

/**
 * Returns the object subtype for a given object ID of a specific type.
 *
 * @since 4.9.8
 *
 * @param string $object_type Type of object metadata is for. Accepts 'post', 'comment', 'term', 'user',
 *                            or any other object type with an associated meta table.
 * @param int    $object_id   ID of the object to retrieve its subtype.
 * @return string The object subtype or an empty string if unspecified subtype.
 */
function get_object_subtype( $object_type, $object_id ) {
    $object_id      = (int) $object_id;
    $object_subtype = '';

    switch ( $object_type ) {
        case 'post':
            $post_type = get_post_type( $object_id );

            if ( ! empty( $post_type ) ) {
                $object_subtype = $post_type;
            }
            break;

        case 'term':
            $term = get_term( $object_id );
            if ( ! $term instanceof WP_Term ) {
                break;
            }

            $object_subtype = $term->taxonomy;
            break;

        case 'comment':
            /** @noinspection PhpUndefinedFunctionInspection */
            $comment = get_comment( $object_id );
            if ( ! $comment ) {
                break;
            }

            $object_subtype = 'comment';
            break;

        case 'user':
            /** @noinspection PhpUndefinedFunctionInspection */
            $user = get_user_by( 'id', $object_id );
            if ( ! $user ) {
                break;
            }

            $object_subtype = 'user';
            break;
    }

    /**
     * Filters the object subtype identifier for a non-standard object type.
     *
     * The dynamic portion of the hook name, `$object_type`, refers to the meta object type
     * (post, comment, term, user, or any other type with an associated meta table).
     *
     * Possible hook names include:
     *
     *  - `get_object_subtype_post`
     *  - `get_object_subtype_comment`
     *  - `get_object_subtype_term`
     *  - `get_object_subtype_user`
     *
     * @since 4.9.8
     *
     * @param string $object_subtype Empty string to override.
     * @param int    $object_id      ID of the object to get the subtype for.
     */
    return apply_filters( "get_object_subtype_{$object_type}", $object_subtype, $object_id );
}

/**
 * Updates the metadata cache for the specified objects.
 *
 * @param string       $meta_type  Type of object metadata is for. Accepts 'post', 'comment', 'term', 'user',
 *                                 or any other object type with an associated meta table.
 * @param string|int[] $object_ids Array or comma delimited list of object IDs to update cache for.
 * @return array|false Metadata cache for the specified objects, or false on failure.
 * @noinspection SpellCheckingInspection*@global wpdb $wpdb WordPress database abstraction object.
 *
 * @since 2.9.0
 *
 */
function update_meta_cache( $meta_type, $object_ids ) {
    global $wpdb;

    if ( ! $meta_type || ! $object_ids ) {
        return false;
    }

    $table = _get_meta_table( $meta_type );
    if ( ! $table ) {
        return false;
    }

    $column = sanitize_key( $meta_type . '_id' );

    if ( ! is_array( $object_ids ) ) {
        /** @noinspection RegExpSimplifiable */
        $object_ids = preg_replace( '|[^0-9,]|', '', $object_ids );
        $object_ids = explode( ',', $object_ids );
    }

    $object_ids = array_map( 'intval', $object_ids );

    /**
     * Short-circuits updating the metadata cache of a specific type.
     *
     * The dynamic portion of the hook name, `$meta_type`, refers to the meta object type
     * (post, comment, term, user, or any other type with an associated meta table).
     * Returning a non-null value will effectively short-circuit the function.
     *
     * Possible hook names include:
     *
     *  - `update_post_metadata_cache`
     *  - `update_comment_metadata_cache`
     *  - `update_term_metadata_cache`
     *  - `update_user_metadata_cache`
     *
     * @since 5.0.0
     *
     * @param mixed $check      Whether to allow updating the meta cache of the given type.
     * @param int[] $object_ids Array of object IDs to update the meta cache for.
     */
    $check = apply_filters( "update_{$meta_type}_metadata_cache", null, $object_ids );
    if ( null !== $check ) {
        return (bool) $check;
    }

    $cache_key      = $meta_type . '_meta';
    $non_cached_ids = array();
    $cache          = array();
    /** @noinspection PhpUndefinedFunctionInspection */
    $cache_values   = wp_cache_get_multiple( $object_ids, $cache_key );

    foreach ( $cache_values as $id => $cached_object ) {
        if ( false === $cached_object ) {
            $non_cached_ids[] = $id;
        } else {
            $cache[ $id ] = $cached_object;
        }
    }

    if ( empty( $non_cached_ids ) ) {
        return $cache;
    }

    // Get meta info.
    $id_list   = implode( ',', $non_cached_ids );
    $id_column = ( 'user' === $meta_type ) ? 'umeta_id' : 'meta_id';

    $meta_list = $wpdb->get_results( "SELECT $column, meta_key, meta_value FROM $table WHERE $column IN ($id_list) ORDER BY $id_column ASC", ARRAY_A );

    if ( ! empty( $meta_list ) ) {
        foreach ( $meta_list as $metarow ) {
            $mpid = (int) $metarow[ $column ];
            $mkey = $metarow['meta_key'];
            $mval = $metarow['meta_value'];

            // Force subkeys to be array type.
            if ( ! isset( $cache[ $mpid ] ) || ! is_array( $cache[ $mpid ] ) ) {
                $cache[ $mpid ] = array();
            }
            if ( ! isset( $cache[ $mpid ][ $mkey ] ) || ! is_array( $cache[ $mpid ][ $mkey ] ) ) {
                $cache[ $mpid ][ $mkey ] = array();
            }

            // Add a value to the current pid/key.
            $cache[ $mpid ][ $mkey ][] = $mval;
        }
    }

    foreach ( $non_cached_ids as $id ) {
        if ( ! isset( $cache[ $id ] ) ) {
            $cache[ $id ] = array();
        }
        wp_cache_add( $id, $cache[ $id ], $cache_key );
    }

    return $cache;
}
