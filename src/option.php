<?php /** @noinspection SqlNoDataSourceInspection */
/**
 * Option API
 *
 * @package WordPress
 * @subpackage Option
 */

/**
 * Retrieves an option value based on an option name.
 *
 * If the option does not exist, and a default value is not provided,
 * boolean false is returned. This could be used to check whether you need
 * to initialize an option during installation of a plugin, however that
 * can be done better by using add_option() which will not overwrite
 * existing options.
 *
 * Not initializing an option and using boolean `false` as a return value
 * is a bad practice as it triggers an additional database query.
 *
 * The type of the returned value can be different from the type that was passed
 * when saving or updating the option. If the option value was serialized,
 * then it will be unserialized when it is returned. In this case the type will
 * be the same. For example, storing a non-scalar value like an array will
 * return the same array.
 *
 * In most cases non-string scalar and null values will be converted and returned
 * as string equivalents.
 *
 * Exceptions:
 * 1. When the option has not been saved in the database, the `$default` value
 *    is returned if provided. If not, boolean `false` is returned.
 * 2. When one of the Options API filters is used: {@see 'pre_option_{$option}'},
 *    {@see 'default_option_{$option}'}, or {@see 'option_{$option}'}, the returned
 *    value may not match the expected type.
 * 3. When the option has just been saved in the database, and get_option()
 *    is used right after, non-string scalar and null values are not converted to
 *    string equivalents and the original type is returned.
 *
 * Examples:
 *
 * When adding options like this: `add_option( 'my_option_name', 'value' );`
 * and then retrieving them with `get_option( 'my_option_name' );`, the returned
 * values will be:
 *
 * `false` returns `string(0) ""`
 * `true`  returns `string(1) "1"`
 * `0`     returns `string(1) "0"`
 * `1`     returns `string(1) "1"`
 * `'0'`   returns `string(1) "0"`
 * `'1'`   returns `string(1) "1"`
 * `null`  returns `string(0) ""`
 *
 * When adding options with non-scalar values like
 * `add_option( 'my_array', array( false, 'str', null ) );`, the returned value
 * will be identical to the original as it is serialized before saving
 * it in the database:
 *
 *    array(3) {
 *        [0] => bool(false)
 *        [1] => string(3) "str"
 *        [2] => NULL
 *    }
 *
 * @since 1.5.0
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param string $option  Name of the option to retrieve. Expected to not be SQL-escaped.
 * @param mixed  $default Optional. Default value to return if the option does not exist.
 * @return mixed Value of the option. A value of any type may be returned, including
 *               scalar (string, boolean, float, integer), null, array, object.
 *               Scalar and null values will be returned as strings as long as they originate
 *               from a database stored option value. If there is no option in the database,
 *               boolean `false` is returned.
 */
function get_option($option, $default = false)
    {
        global $wpdb;

        if (is_scalar($option)) {
            $option = trim($option);
        }

        if (empty($option)) {
            return false;
        }

        /*
         * Until a proper _deprecated_option() function can be introduced,
         * redirect requests to deprecated keys to the new, correct ones.
         */
        $deprecated_keys = array(
          'blacklist_keys'    => 'disallowed_keys',
          'comment_whitelist' => 'comment_previously_approved',
        );

        if (!wp_installing() && isset($deprecated_keys[$option])) {
            _deprecated_argument(
              __FUNCTION__,
              '5.5.0',
              sprintf(
              /* translators: 1: Deprecated option key, 2: New option key. */
                __('The "%1$s" option key has been renamed to "%2$s".'),
                $option,
                $deprecated_keys[$option]
              )
            );
            return get_option($deprecated_keys[$option], $default);
        }

        /**
         * Filters the value of an existing option before it is retrieved.
         *
         * The dynamic portion of the hook name, `$option`, refers to the option name.
         *
         * Returning a truthy value from the filter will effectively short-circuit retrieval
         * and return the passed value instead.
         *
         * @param mixed $pre_option The value to return instead of the option value. This differs
         *                           from `$default`, which is used as the fallback value in the event
         *                           the option doesn't exist elsewhere in get_option().
         *                           Default false (to skip past the short-circuit).
         * @param string $option Option name.
         * @param mixed $default The fallback value to return if the option does not exist.
         *                           Default false.
         * @since 1.5.0
         * @since 4.4.0 The `$option` parameter was added.
         * @since 4.9.0 The `$default` parameter was added.
         *
         */
        $pre = apply_filters("pre_option_{$option}", false, $option, $default);

        if (false !== $pre) {
            return $pre;
        }

        if (defined('WP_SETUP_CONFIG')) {
            return false;
        }

        // Distinguish between `false` as a default, and not passing one.
        $passed_default = func_num_args() > 1;

        if (!wp_installing()) {
            // Prevent non-existent options from triggering multiple queries.
            $notoptions = wp_cache_get('notoptions', 'options');

            if (isset($notoptions[$option])) {
                /**
                 * Filters the default value for an option.
                 *
                 * The dynamic portion of the hook name, `$option`, refers to the option name.
                 *
                 * @param mixed $default The default value to return if the option does not exist
                 *                        in the database.
                 * @param string $option Option name.
                 * @param bool $passed_default Was `get_option()` passed a default value?
                 * @since 3.4.0
                 * @since 4.4.0 The `$option` parameter was added.
                 * @since 4.7.0 The `$passed_default` parameter was added to distinguish between a `false` value and the default parameter value.
                 *
                 */
                return apply_filters("default_option_{$option}", $default, $option, $passed_default);
            }

            $alloptions = wp_load_alloptions();

            if (isset($alloptions[$option])) {
                $value = $alloptions[$option];
            } else {
                $value = wp_cache_get($option, 'options');

                if (false === $value) {
                    $row = $wpdb->get_row($wpdb->prepare("SELECT option_value FROM $wpdb->options WHERE option_name = %s LIMIT 1",
                      $option));

                    // Has to be get_row() instead of get_var() because of funkiness with 0, false, null values.
                    if (is_object($row)) {
                        $value = $row->option_value;
                        wp_cache_add($option, $value, 'options');
                    } else { // Option does not exist, so we must cache its non-existence.
                        if (!is_array($notoptions)) {
                            $notoptions = array();
                        }

                        $notoptions[$option] = true;
                        wp_cache_set('notoptions', $notoptions, 'options');

                        /** This filter is documented in wp-includes/option.php */
                        return apply_filters("default_option_{$option}", $default, $option, $passed_default);
                    }
                }
            }
        } else {
            $suppress = $wpdb->suppress_errors();
            $row = $wpdb->get_row($wpdb->prepare("SELECT option_value FROM $wpdb->options WHERE option_name = %s LIMIT 1",
              $option));
            $wpdb->suppress_errors($suppress);

            if (is_object($row)) {
                $value = $row->option_value;
            } else {
                /** This filter is documented in wp-includes/option.php */
                return apply_filters("default_option_{$option}", $default, $option, $passed_default);
            }
        }

        // If home is not set, use siteurl.
        if ('home' === $option && '' === $value) {
            return get_option('siteurl');
        }

        if (in_array($option, array('siteurl', 'home', 'category_base', 'tag_base'), true)) {
            $value = untrailingslashit($value);
        }

        /**
         * Filters the value of an existing option.
         *
         * The dynamic portion of the hook name, `$option`, refers to the option name.
         *
         * @param mixed $value Value of the option. If stored serialized, it will be
         *                       unserialized prior to being returned.
         * @param string $option Option name.
         * @since 4.4.0 The `$option` parameter was added.
         *
         * @since 1.5.0 As 'option_' . $setting
         * @since 3.0.0
         */
        return apply_filters("option_{$option}", maybe_unserialize($value), $option);
    }

/**
 * Updates the value of an option that was already added.
 *
 * You do not need to serialize values. If the value needs to be serialized,
 * then it will be serialized before it is inserted into the database.
 * Remember, resources cannot be serialized or added as an option.
 *
 * If the option does not exist, it will be created.

 * This function is designed to work with or without a logged-in user. In terms of security,
 * plugin developers should check the current user's capabilities before updating any options.
 *
 * @since 1.0.0
 * @since 4.2.0 The `$autoload` parameter was added.
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param string      $option   Name of the option to update. Expected to not be SQL-escaped.
 * @param mixed       $value    Option value. Must be serializable if non-scalar. Expected to not be SQL-escaped.
 * @param string|bool $autoload Optional. Whether to load the option when WordPress starts up. For existing options,
 *                              `$autoload` can only be updated using `update_option()` if `$value` is also changed.
 *                              Accepts 'yes'|true to enable or 'no'|false to disable. For non-existent options,
 *                              the default value is 'yes'. Default null.
 * @return bool True if the value was updated, false otherwise.
 */
function update_option( $option, $value, $autoload = null ) {
    global $wpdb;

    if ( is_scalar( $option ) ) {
        $option = trim( $option );
    }

    if ( empty( $option ) ) {
        return false;
    }

    /*
     * Until a proper _deprecated_option() function can be introduced,
     * redirect requests to deprecated keys to the new, correct ones.
     */
    $deprecated_keys = array(
      'blacklist_keys'    => 'disallowed_keys',
      'comment_whitelist' => 'comment_previously_approved',
    );

    if ( ! wp_installing() && isset( $deprecated_keys[ $option ] ) ) {
        _deprecated_argument(
          __FUNCTION__,
          '5.5.0',
          sprintf(
          /* translators: 1: Deprecated option key, 2: New option key. */
            __( 'The "%1$s" option key has been renamed to "%2$s".' ),
            $option,
            $deprecated_keys[ $option ]
          )
        );
        return update_option( $deprecated_keys[ $option ], $value, $autoload );
    }

    wp_protect_special_option( $option );

    if ( is_object( $value ) ) {
        $value = clone $value;
    }

    $value     = sanitize_option( $option, $value );
    $old_value = get_option( $option );

    /**
     * Filters a specific option before its value is (maybe) serialized and updated.
     *
     * The dynamic portion of the hook name, `$option`, refers to the option name.
     *
     * @since 2.6.0
     * @since 4.4.0 The `$option` parameter was added.
     *
     * @param mixed  $value     The new, unserialized option value.
     * @param mixed  $old_value The old option value.
     * @param string $option    Option name.
     */
    $value = apply_filters( "pre_update_option_{$option}", $value, $old_value, $option );

    /**
     * Filters an option before its value is (maybe) serialized and updated.
     *
     * @since 3.9.0
     *
     * @param mixed  $value     The new, unserialized option value.
     * @param string $option    Name of the option.
     * @param mixed  $old_value The old option value.
     */
    $value = apply_filters( 'pre_update_option', $value, $option, $old_value );

    /*
     * If the new and old values are the same, no need to update.
     *
     * Unserialized values will be adequate in most cases. If the unserialized
     * data differs, the (maybe) serialized data is checked to avoid
     * unnecessary database calls for otherwise identical object instances.
     *
     * See https://core.trac.wordpress.org/ticket/38903
     */
    if ( $value === $old_value || maybe_serialize( $value ) === maybe_serialize( $old_value ) ) {
        return false;
    }

    /** This filter is documented in wp-includes/option.php */
    if ( apply_filters( "default_option_{$option}", false, $option, false ) === $old_value ) {
        // Default setting for new options is 'yes'.
        if ( null === $autoload ) {
            $autoload = 'yes';
        }

        return add_option( $option, $value, '', $autoload );
    }

    $serialized_value = maybe_serialize( $value );

    /**
     * Fires immediately before an option value is updated.
     *
     * @since 2.9.0
     *
     * @param string $option    Name of the option to update.
     * @param mixed  $old_value The old option value.
     * @param mixed  $value     The new option value.
     */
    do_action( 'update_option', $option, $old_value, $value );

    $update_args = array(
      'option_value' => $serialized_value,
    );

    if ( null !== $autoload ) {
        $update_args['autoload'] = ( 'no' === $autoload || false === $autoload ) ? 'no' : 'yes';
    }

    $result = $wpdb->update( $wpdb->options, $update_args, array( 'option_name' => $option ) );
    if ( ! $result ) {
        return false;
    }

    $notoptions = wp_cache_get( 'notoptions', 'options' );

    if ( is_array( $notoptions ) && isset( $notoptions[ $option ] ) ) {
        unset( $notoptions[ $option ] );
        wp_cache_set( 'notoptions', $notoptions, 'options' );
    }

    if ( ! wp_installing() ) {
        $alloptions = wp_load_alloptions( true );
        if ( isset( $alloptions[ $option ] ) ) {
            $alloptions[ $option ] = $serialized_value;
            wp_cache_set( 'alloptions', $alloptions, 'options' );
        } else {
            wp_cache_set( $option, $serialized_value, 'options' );
        }
    }

    /**
     * Fires after the value of a specific option has been successfully updated.
     *
     * The dynamic portion of the hook name, `$option`, refers to the option name.
     *
     * @since 2.0.1
     * @since 4.4.0 The `$option` parameter was added.
     *
     * @param mixed  $old_value The old option value.
     * @param mixed  $value     The new option value.
     * @param string $option    Option name.
     */
    do_action( "update_option_{$option}", $old_value, $value, $option );

    /**
     * Fires after the value of an option has been successfully updated.
     *
     * @since 2.9.0
     *
     * @param string $option    Name of the updated option.
     * @param mixed  $old_value The old option value.
     * @param mixed  $value     The new option value.
     */
    do_action( 'updated_option', $option, $old_value, $value );

    return true;
}

/**
 * Adds a new option.
 *
 * You do not need to serialize values. If the value needs to be serialized,
 * then it will be serialized before it is inserted into the database.
 * Remember, resources cannot be serialized or added as an option.
 *
 * You can create options without values and then update the values later.
 * Existing options will not be updated and checks are performed to ensure that you
 * aren't adding a protected WordPress option. Care should be taken to not name
 * options the same as the ones which are protected.
 *
 * @since 1.0.0
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param string      $option     Name of the option to add. Expected to not be SQL-escaped.
 * @param mixed       $value      Optional. Option value. Must be serializable if non-scalar.
 *                                Expected to not be SQL-escaped.
 * @param string      $deprecated Optional. Description. Not used anymore.
 * @param string|bool $autoload   Optional. Whether to load the option when WordPress starts up.
 *                                Default is enabled. Accepts 'no' to disable for legacy reasons.
 * @return bool True if the option was added, false otherwise.
 */
function add_option( $option, $value = '', $deprecated = '', $autoload = 'yes' ) {
    global $wpdb;

    if ( ! empty( $deprecated ) ) {
        _deprecated_argument( __FUNCTION__, '2.3.0' );
    }

    if ( is_scalar( $option ) ) {
        $option = trim( $option );
    }

    if ( empty( $option ) ) {
        return false;
    }

    /*
     * Until a proper _deprecated_option() function can be introduced,
     * redirect requests to deprecated keys to the new, correct ones.
     */
    $deprecated_keys = array(
      'blacklist_keys'    => 'disallowed_keys',
      'comment_whitelist' => 'comment_previously_approved',
    );

    if ( ! wp_installing() && isset( $deprecated_keys[ $option ] ) ) {
        _deprecated_argument(
          __FUNCTION__,
          '5.5.0',
          sprintf(
          /* translators: 1: Deprecated option key, 2: New option key. */
            __( 'The "%1$s" option key has been renamed to "%2$s".' ),
            $option,
            $deprecated_keys[ $option ]
          )
        );
        return add_option( $deprecated_keys[ $option ], $value, $deprecated, $autoload );
    }

    wp_protect_special_option( $option );

    if ( is_object( $value ) ) {
        $value = clone $value;
    }

    $value = sanitize_option( $option, $value );

    // Make sure the option doesn't already exist.
    // We can check the 'notoptions' cache before we ask for a DB query.
    $notoptions = wp_cache_get( 'notoptions', 'options' );

    if ( ! is_array( $notoptions ) || ! isset( $notoptions[ $option ] ) ) {
        /** This filter is documented in wp-includes/option.php */
        if ( apply_filters( "default_option_{$option}", false, $option, false ) !== get_option( $option ) ) {
            return false;
        }
    }

    $serialized_value = maybe_serialize( $value );
    $autoload         = ( 'no' === $autoload || false === $autoload ) ? 'no' : 'yes';

    /**
     * Fires before an option is added.
     *
     * @since 2.9.0
     *
     * @param string $option Name of the option to add.
     * @param mixed  $value  Value of the option.
     */
    do_action( 'add_option', $option, $value );

    $result = $wpdb->query( $wpdb->prepare( "INSERT INTO `$wpdb->options` (`option_name`, `option_value`, `autoload`) VALUES (%s, %s, %s) ON DUPLICATE KEY UPDATE `option_name` = VALUES(`option_name`), `option_value` = VALUES(`option_value`), `autoload` = VALUES(`autoload`)", $option, $serialized_value, $autoload ) );
    if ( ! $result ) {
        return false;
    }

    if ( ! wp_installing() ) {
        if ( 'yes' === $autoload ) {
            $alloptions            = wp_load_alloptions( true );
            $alloptions[ $option ] = $serialized_value;
            wp_cache_set( 'alloptions', $alloptions, 'options' );
        } else {
            wp_cache_set( $option, $serialized_value, 'options' );
        }
    }

    // This option exists now.
    $notoptions = wp_cache_get( 'notoptions', 'options' ); // Yes, again... we need it to be fresh.

    if ( is_array( $notoptions ) && isset( $notoptions[ $option ] ) ) {
        unset( $notoptions[ $option ] );
        wp_cache_set( 'notoptions', $notoptions, 'options' );
    }

    /**
     * Fires after a specific option has been added.
     *
     * The dynamic portion of the hook name, `$option`, refers to the option name.
     *
     * @since 2.5.0 As "add_option_{$name}"
     * @since 3.0.0
     *
     * @param string $option Name of the option to add.
     * @param mixed  $value  Value of the option.
     */
    do_action( "add_option_{$option}", $option, $value );

    /**
     * Fires after an option has been added.
     *
     * @since 2.9.0
     *
     * @param string $option Name of the added option.
     * @param mixed  $value  Value of the option.
     */
    do_action( 'added_option', $option, $value );

    return true;
}

/**
 * Removes option by name. Prevents removal of protected WordPress options.
 *
 * @since 1.2.0
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param string $option Name of the option to delete. Expected to not be SQL-escaped.
 * @return bool True if the option was deleted, false otherwise.
 */
function delete_option( $option ) {
    global $wpdb;

    if ( is_scalar( $option ) ) {
        $option = trim( $option );
    }

    if ( empty( $option ) ) {
        return false;
    }

    wp_protect_special_option( $option );

    // Get the ID, if no ID then return.
    $row = $wpdb->get_row( $wpdb->prepare( "SELECT autoload FROM $wpdb->options WHERE option_name = %s", $option ) );
    if ( is_null( $row ) ) {
        return false;
    }

    /**
     * Fires immediately before an option is deleted.
     *
     * @since 2.9.0
     *
     * @param string $option Name of the option to delete.
     */
    do_action( 'delete_option', $option );

    $result = $wpdb->delete( $wpdb->options, array( 'option_name' => $option ) );

    if ( ! wp_installing() ) {
        if ( 'yes' === $row->autoload ) {
            $alloptions = wp_load_alloptions( true );
            if ( is_array( $alloptions ) && isset( $alloptions[ $option ] ) ) {
                unset( $alloptions[ $option ] );
                wp_cache_set( 'alloptions', $alloptions, 'options' );
            }
        } else {
            wp_cache_delete( $option, 'options' );
        }
    }

    if ( $result ) {

        /**
         * Fires after a specific option has been deleted.
         *
         * The dynamic portion of the hook name, `$option`, refers to the option name.
         *
         * @since 3.0.0
         *
         * @param string $option Name of the deleted option.
         */
        do_action( "delete_option_{$option}", $option );

        /**
         * Fires after an option has been deleted.
         *
         * @since 2.9.0
         *
         * @param string $option Name of the deleted option.
         */
        do_action( 'deleted_option', $option );

        return true;
    }

    return false;
}

/**
 * Loads and caches all autoloaded options, if available or all options.
 *
 * @since 2.2.0
 * @since 5.3.1 The `$force_cache` parameter was added.
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param bool $force_cache Optional. Whether to force an update of the local cache
 *                          from the persistent cache. Default false.
 * @return array List of all options.
 */
function wp_load_alloptions( $force_cache = false ) {
    global $wpdb;

    if ( ! wp_installing() || ! is_multisite() ) {
        $alloptions = wp_cache_get( 'alloptions', 'options', $force_cache );
    } else {
        $alloptions = false;
    }

    if ( ! $alloptions ) {
        $suppress      = $wpdb->suppress_errors();
        $alloptions_db = $wpdb->get_results( "SELECT option_name, option_value FROM $wpdb->options WHERE autoload = 'yes'" );
        if ( ! $alloptions_db ) {
            $alloptions_db = $wpdb->get_results( "SELECT option_name, option_value FROM $wpdb->options" );
        }
        $wpdb->suppress_errors( $suppress );

        $alloptions = array();
        foreach ( (array) $alloptions_db as $o ) {
            $alloptions[ $o->option_name ] = $o->option_value;
        }

        if ( ! wp_installing() || ! is_multisite() ) {
            /**
             * Filters all options before caching them.
             *
             * @since 4.9.0
             *
             * @param array $alloptions Array with all options.
             */
            $alloptions = apply_filters( 'pre_cache_alloptions', $alloptions );

            wp_cache_add( 'alloptions', $alloptions, 'options' );
        }
    }

    /**
     * Filters all options after retrieving them.
     *
     * @since 4.9.0
     *
     * @param array $alloptions Array with all options.
     */
    return apply_filters( 'alloptions', $alloptions );
}

/**
 * Retrieve an option value for the current network based on name of option.
 *
 * @since 2.8.0
 * @since 4.4.0 The `$use_cache` parameter was deprecated.
 * @since 4.4.0 Modified into wrapper for get_network_option()
 *
 * @see get_network_option()
 *
 * @param string $option     Name of the option to retrieve. Expected to not be SQL-escaped.
 * @param mixed  $default    Optional. Value to return if the option doesn't exist. Default false.
 * @param bool   $deprecated Whether to use cache. Multisite only. Always set to true.
 * @return mixed Value set for the option.
 */
function get_site_option( $option, $default = false, $deprecated = true ) {
    return get_network_option( null, $option, $default );
}

/**
 * Adds a new option for the current network.
 *
 * Existing options will not be updated. Note that prior to 3.3 this wasn't the case.
 *
 * @since 2.8.0
 * @since 4.4.0 Modified into wrapper for add_network_option()
 *
 * @see add_network_option()
 *
 * @param string $option Name of the option to add. Expected to not be SQL-escaped.
 * @param mixed  $value  Option value, can be anything. Expected to not be SQL-escaped.
 * @return bool True if the option was added, false otherwise.
 */
function add_site_option( $option, $value ) {
    return add_network_option( null, $option, $value );
}

/**
 * Removes a option by name for the current network.
 *
 * @since 2.8.0
 * @since 4.4.0 Modified into wrapper for delete_network_option()
 *
 * @see delete_network_option()
 *
 * @param string $option Name of the option to delete. Expected to not be SQL-escaped.
 * @return bool True if the option was deleted, false otherwise.
 */
function delete_site_option( $option ) {
    return delete_network_option( null, $option );
}

/**
 * Updates the value of an option that was already added for the current network.
 *
 * @since 2.8.0
 * @since 4.4.0 Modified into wrapper for update_network_option()
 *
 * @see update_network_option()
 *
 * @param string $option Name of the option. Expected to not be SQL-escaped.
 * @param mixed  $value  Option value. Expected to not be SQL-escaped.
 * @return bool True if the value was updated, false otherwise.
 */
function update_site_option( $option, $value ) {
    return update_network_option( null, $option, $value );
}

/**
 * Retrieves a network's option value based on the option name.
 *
 * @since 4.4.0
 *
 * @see get_option()
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param int    $network_id ID of the network. Can be null to default to the current network ID.
 * @param string $option     Name of the option to retrieve. Expected to not be SQL-escaped.
 * @param mixed  $default    Optional. Value to return if the option doesn't exist. Default false.
 * @return mixed Value set for the option.
 */
function get_network_option( $network_id, $option, $default = false ) {
    global $wpdb;

    if ( $network_id && ! is_numeric( $network_id ) ) {
        return false;
    }

    $network_id = (int) $network_id;

    // Fallback to the current network if a network ID is not specified.
    if ( ! $network_id ) {
        $network_id = get_current_network_id();
    }

    /**
     * Filters the value of an existing network option before it is retrieved.
     *
     * The dynamic portion of the hook name, `$option`, refers to the option name.
     *
     * Returning a truthy value from the filter will effectively short-circuit retrieval
     * and return the passed value instead.
     *
     * @since 2.9.0 As 'pre_site_option_' . $key
     * @since 3.0.0
     * @since 4.4.0 The `$option` parameter was added.
     * @since 4.7.0 The `$network_id` parameter was added.
     * @since 4.9.0 The `$default` parameter was added.
     *
     * @param mixed  $pre_option The value to return instead of the option value. This differs
     *                           from `$default`, which is used as the fallback value in the event
     *                           the option doesn't exist elsewhere in get_network_option().
     *                           Default false (to skip past the short-circuit).
     * @param string $option     Option name.
     * @param int    $network_id ID of the network.
     * @param mixed  $default    The fallback value to return if the option does not exist.
     *                           Default false.
     */
    $pre = apply_filters( "pre_site_option_{$option}", false, $option, $network_id, $default );

    if ( false !== $pre ) {
        return $pre;
    }

    // Prevent non-existent options from triggering multiple queries.
    $notoptions_key = "$network_id:notoptions";
    $notoptions     = wp_cache_get( $notoptions_key, 'site-options' );

    if ( is_array( $notoptions ) && isset( $notoptions[ $option ] ) ) {

        /**
         * Filters a specific default network option.
         *
         * The dynamic portion of the hook name, `$option`, refers to the option name.
         *
         * @since 3.4.0
         * @since 4.4.0 The `$option` parameter was added.
         * @since 4.7.0 The `$network_id` parameter was added.
         *
         * @param mixed  $default    The value to return if the site option does not exist
         *                           in the database.
         * @param string $option     Option name.
         * @param int    $network_id ID of the network.
         */
        return apply_filters( "default_site_option_{$option}", $default, $option, $network_id );
    }

    if ( ! is_multisite() ) {
        /** This filter is documented in wp-includes/option.php */
        $default = apply_filters( 'default_site_option_' . $option, $default, $option, $network_id );
        $value   = get_option( $option, $default );
    } else {
        $cache_key = "$network_id:$option";
        $value     = wp_cache_get( $cache_key, 'site-options' );

        if ( ! isset( $value ) || false === $value ) {
            $row = $wpdb->get_row( $wpdb->prepare( "SELECT meta_value FROM $wpdb->sitemeta WHERE meta_key = %s AND site_id = %d", $option, $network_id ) );

            // Has to be get_row() instead of get_var() because of funkiness with 0, false, null values.
            if ( is_object( $row ) ) {
                $value = $row->meta_value;
                $value = maybe_unserialize( $value );
                wp_cache_set( $cache_key, $value, 'site-options' );
            } else {
                if ( ! is_array( $notoptions ) ) {
                    $notoptions = array();
                }

                $notoptions[ $option ] = true;
                wp_cache_set( $notoptions_key, $notoptions, 'site-options' );

                /** This filter is documented in wp-includes/option.php */
                $value = apply_filters( 'default_site_option_' . $option, $default, $option, $network_id );
            }
        }
    }

    if ( ! is_array( $notoptions ) ) {
        $notoptions = array();
        wp_cache_set( $notoptions_key, $notoptions, 'site-options' );
    }

    /**
     * Filters the value of an existing network option.
     *
     * The dynamic portion of the hook name, `$option`, refers to the option name.
     *
     * @since 2.9.0 As 'site_option_' . $key
     * @since 3.0.0
     * @since 4.4.0 The `$option` parameter was added.
     * @since 4.7.0 The `$network_id` parameter was added.
     *
     * @param mixed  $value      Value of network option.
     * @param string $option     Option name.
     * @param int    $network_id ID of the network.
     */
    return apply_filters( "site_option_{$option}", $value, $option, $network_id );
}

/**
 * Adds a new network option.
 *
 * Existing options will not be updated.
 *
 * @since 4.4.0
 *
 * @see add_option()
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param int    $network_id ID of the network. Can be null to default to the current network ID.
 * @param string $option     Name of the option to add. Expected to not be SQL-escaped.
 * @param mixed  $value      Option value, can be anything. Expected to not be SQL-escaped.
 * @return bool True if the option was added, false otherwise.
 */
function add_network_option( $network_id, $option, $value ) {
    global $wpdb;

    if ( $network_id && ! is_numeric( $network_id ) ) {
        return false;
    }

    $network_id = (int) $network_id;

    // Fallback to the current network if a network ID is not specified.
    if ( ! $network_id ) {
        $network_id = get_current_network_id();
    }

    wp_protect_special_option( $option );

    /**
     * Filters the value of a specific network option before it is added.
     *
     * The dynamic portion of the hook name, `$option`, refers to the option name.
     *
     * @since 2.9.0 As 'pre_add_site_option_' . $key
     * @since 3.0.0
     * @since 4.4.0 The `$option` parameter was added.
     * @since 4.7.0 The `$network_id` parameter was added.
     *
     * @param mixed  $value      Value of network option.
     * @param string $option     Option name.
     * @param int    $network_id ID of the network.
     */
    $value = apply_filters( "pre_add_site_option_{$option}", $value, $option, $network_id );

    $notoptions_key = "$network_id:notoptions";

    if ( ! is_multisite() ) {
        $result = add_option( $option, $value, '', 'no' );
    } else {
        $cache_key = "$network_id:$option";

        // Make sure the option doesn't already exist.
        // We can check the 'notoptions' cache before we ask for a DB query.
        $notoptions = wp_cache_get( $notoptions_key, 'site-options' );

        if ( ! is_array( $notoptions ) || ! isset( $notoptions[ $option ] ) ) {
            if ( false !== get_network_option( $network_id, $option, false ) ) {
                return false;
            }
        }

        $value = sanitize_option( $option, $value );

        $serialized_value = maybe_serialize( $value );
        $result           = $wpdb->insert(
          $wpdb->sitemeta,
          array(
            'site_id'    => $network_id,
            'meta_key'   => $option,
            'meta_value' => $serialized_value,
          )
        );

        if ( ! $result ) {
            return false;
        }

        wp_cache_set( $cache_key, $value, 'site-options' );

        // This option exists now.
        $notoptions = wp_cache_get( $notoptions_key, 'site-options' ); // Yes, again... we need it to be fresh.

        if ( is_array( $notoptions ) && isset( $notoptions[ $option ] ) ) {
            unset( $notoptions[ $option ] );
            wp_cache_set( $notoptions_key, $notoptions, 'site-options' );
        }
    }

    if ( $result ) {

        /**
         * Fires after a specific network option has been successfully added.
         *
         * The dynamic portion of the hook name, `$option`, refers to the option name.
         *
         * @since 2.9.0 As "add_site_option_{$key}"
         * @since 3.0.0
         * @since 4.7.0 The `$network_id` parameter was added.
         *
         * @param string $option     Name of the network option.
         * @param mixed  $value      Value of the network option.
         * @param int    $network_id ID of the network.
         */
        do_action( "add_site_option_{$option}", $option, $value, $network_id );

        /**
         * Fires after a network option has been successfully added.
         *
         * @since 3.0.0
         * @since 4.7.0 The `$network_id` parameter was added.
         *
         * @param string $option     Name of the network option.
         * @param mixed  $value      Value of the network option.
         * @param int    $network_id ID of the network.
         */
        do_action( 'add_site_option', $option, $value, $network_id );

        return true;
    }

    return false;
}

/**
 * Removes a network option by name.
 *
 * @since 4.4.0
 *
 * @see delete_option()
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param int    $network_id ID of the network. Can be null to default to the current network ID.
 * @param string $option     Name of the option to delete. Expected to not be SQL-escaped.
 * @return bool True if the option was deleted, false otherwise.
 */
function delete_network_option( $network_id, $option ) {
    global $wpdb;

    if ( $network_id && ! is_numeric( $network_id ) ) {
        return false;
    }

    $network_id = (int) $network_id;

    // Fallback to the current network if a network ID is not specified.
    if ( ! $network_id ) {
        $network_id = get_current_network_id();
    }

    /**
     * Fires immediately before a specific network option is deleted.
     *
     * The dynamic portion of the hook name, `$option`, refers to the option name.
     *
     * @since 3.0.0
     * @since 4.4.0 The `$option` parameter was added.
     * @since 4.7.0 The `$network_id` parameter was added.
     *
     * @param string $option     Option name.
     * @param int    $network_id ID of the network.
     */
    do_action( "pre_delete_site_option_{$option}", $option, $network_id );

    if ( ! is_multisite() ) {
        $result = delete_option( $option );
    } else {
        $row = $wpdb->get_row( $wpdb->prepare( "SELECT meta_id FROM {$wpdb->sitemeta} WHERE meta_key = %s AND site_id = %d", $option, $network_id ) );
        if ( is_null( $row ) || ! $row->meta_id ) {
            return false;
        }
        $cache_key = "$network_id:$option";
        wp_cache_delete( $cache_key, 'site-options' );

        $result = $wpdb->delete(
          $wpdb->sitemeta,
          array(
            'meta_key' => $option,
            'site_id'  => $network_id,
          )
        );
    }

    if ( $result ) {

        /**
         * Fires after a specific network option has been deleted.
         *
         * The dynamic portion of the hook name, `$option`, refers to the option name.
         *
         * @since 2.9.0 As "delete_site_option_{$key}"
         * @since 3.0.0
         * @since 4.7.0 The `$network_id` parameter was added.
         *
         * @param string $option     Name of the network option.
         * @param int    $network_id ID of the network.
         */
        do_action( "delete_site_option_{$option}", $option, $network_id );

        /**
         * Fires after a network option has been deleted.
         *
         * @since 3.0.0
         * @since 4.7.0 The `$network_id` parameter was added.
         *
         * @param string $option     Name of the network option.
         * @param int    $network_id ID of the network.
         */
        do_action( 'delete_site_option', $option, $network_id );

        return true;
    }

    return false;
}

/**
 * Updates the value of a network option that was already added.
 *
 * @since 4.4.0
 *
 * @see update_option()
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param int    $network_id ID of the network. Can be null to default to the current network ID.
 * @param string $option     Name of the option. Expected to not be SQL-escaped.
 * @param mixed  $value      Option value. Expected to not be SQL-escaped.
 * @return bool True if the value was updated, false otherwise.
 */
function update_network_option( $network_id, $option, $value ) {
    global $wpdb;

    if ( $network_id && ! is_numeric( $network_id ) ) {
        return false;
    }

    $network_id = (int) $network_id;

    // Fallback to the current network if a network ID is not specified.
    if ( ! $network_id ) {
        $network_id = get_current_network_id();
    }

    wp_protect_special_option( $option );

    $old_value = get_network_option( $network_id, $option, false );

    /**
     * Filters a specific network option before its value is updated.
     *
     * The dynamic portion of the hook name, `$option`, refers to the option name.
     *
     * @since 2.9.0 As 'pre_update_site_option_' . $key
     * @since 3.0.0
     * @since 4.4.0 The `$option` parameter was added.
     * @since 4.7.0 The `$network_id` parameter was added.
     *
     * @param mixed  $value      New value of the network option.
     * @param mixed  $old_value  Old value of the network option.
     * @param string $option     Option name.
     * @param int    $network_id ID of the network.
     */
    $value = apply_filters( "pre_update_site_option_{$option}", $value, $old_value, $option, $network_id );

    /*
     * If the new and old values are the same, no need to update.
     *
     * Unserialized values will be adequate in most cases. If the unserialized
     * data differs, the (maybe) serialized data is checked to avoid
     * unnecessary database calls for otherwise identical object instances.
     *
     * See https://core.trac.wordpress.org/ticket/44956
     */
    if ( $value === $old_value || maybe_serialize( $value ) === maybe_serialize( $old_value ) ) {
        return false;
    }

    if ( false === $old_value ) {
        return add_network_option( $network_id, $option, $value );
    }

    $notoptions_key = "$network_id:notoptions";
    $notoptions     = wp_cache_get( $notoptions_key, 'site-options' );

    if ( is_array( $notoptions ) && isset( $notoptions[ $option ] ) ) {
        unset( $notoptions[ $option ] );
        wp_cache_set( $notoptions_key, $notoptions, 'site-options' );
    }

    if ( ! is_multisite() ) {
        $result = update_option( $option, $value, 'no' );
    } else {
        $value = sanitize_option( $option, $value );

        $serialized_value = maybe_serialize( $value );
        $result           = $wpdb->update(
          $wpdb->sitemeta,
          array( 'meta_value' => $serialized_value ),
          array(
            'site_id'  => $network_id,
            'meta_key' => $option,
          )
        );

        if ( $result ) {
            $cache_key = "$network_id:$option";
            wp_cache_set( $cache_key, $value, 'site-options' );
        }
    }

    if ( $result ) {

        /**
         * Fires after the value of a specific network option has been successfully updated.
         *
         * The dynamic portion of the hook name, `$option`, refers to the option name.
         *
         * @since 2.9.0 As "update_site_option_{$key}"
         * @since 3.0.0
         * @since 4.7.0 The `$network_id` parameter was added.
         *
         * @param string $option     Name of the network option.
         * @param mixed  $value      Current value of the network option.
         * @param mixed  $old_value  Old value of the network option.
         * @param int    $network_id ID of the network.
         */
        do_action( "update_site_option_{$option}", $option, $value, $old_value, $network_id );

        /**
         * Fires after the value of a network option has been successfully updated.
         *
         * @since 3.0.0
         * @since 4.7.0 The `$network_id` parameter was added.
         *
         * @param string $option     Name of the network option.
         * @param mixed  $value      Current value of the network option.
         * @param mixed  $old_value  Old value of the network option.
         * @param int    $network_id ID of the network.
         */
        do_action( 'update_site_option', $option, $value, $old_value, $network_id );

        return true;
    }

    return false;
}

/**
 * Deletes a site transient.
 *
 * @since 2.9.0
 *
 * @param string $transient Transient name. Expected to not be SQL-escaped.
 * @return bool True if the transient was deleted, false otherwise.
 */
function delete_site_transient( $transient ) {

    /**
     * Fires immediately before a specific site transient is deleted.
     *
     * The dynamic portion of the hook name, `$transient`, refers to the transient name.
     *
     * @since 3.0.0
     *
     * @param string $transient Transient name.
     */
    do_action( "delete_site_transient_{$transient}", $transient );

    if ( wp_using_ext_object_cache() || wp_installing() ) {
        $result = wp_cache_delete( $transient, 'site-transient' );
    } else {
        $option_timeout = '_site_transient_timeout_' . $transient;
        $option         = '_site_transient_' . $transient;
        $result         = delete_site_option( $option );

        if ( $result ) {
            delete_site_option( $option_timeout );
        }
    }

    if ( $result ) {

        /**
         * Fires after a transient is deleted.
         *
         * @since 3.0.0
         *
         * @param string $transient Deleted transient name.
         */
        do_action( 'deleted_site_transient', $transient );
    }

    return $result;
}

/**
 * Retrieves the value of a site transient.
 *
 * If the transient does not exist, does not have a value, or has expired,
 * then the return value will be false.
 *
 * @since 2.9.0
 *
 * @see get_transient()
 *
 * @param string $transient Transient name. Expected to not be SQL-escaped.
 * @return mixed Value of transient.
 */
function get_site_transient( $transient ) {

    /**
     * Filters the value of an existing site transient before it is retrieved.
     *
     * The dynamic portion of the hook name, `$transient`, refers to the transient name.
     *
     * Returning a truthy value from the filter will effectively short-circuit retrieval
     * and return the passed value instead.
     *
     * @since 2.9.0
     * @since 4.4.0 The `$transient` parameter was added.
     *
     * @param mixed  $pre_site_transient The default value to return if the site transient does not exist.
     *                                   Any value other than false will short-circuit the retrieval
     *                                   of the transient, and return that value.
     * @param string $transient          Transient name.
     */
    $pre = apply_filters( "pre_site_transient_{$transient}", false, $transient );

    if ( false !== $pre ) {
        return $pre;
    }

    if ( wp_using_ext_object_cache() || wp_installing() ) {
        $value = wp_cache_get( $transient, 'site-transient' );
    } else {
        // Core transients that do not have a timeout. Listed here so querying timeouts can be avoided.
        $no_timeout       = array( 'update_core', 'update_plugins', 'update_themes' );
        $transient_option = '_site_transient_' . $transient;
        if ( ! in_array( $transient, $no_timeout, true ) ) {
            $transient_timeout = '_site_transient_timeout_' . $transient;
            $timeout           = get_site_option( $transient_timeout );
            if ( false !== $timeout && $timeout < time() ) {
                delete_site_option( $transient_option );
                delete_site_option( $transient_timeout );
                $value = false;
            }
        }

        if ( ! isset( $value ) ) {
            $value = get_site_option( $transient_option );
        }
    }

    /**
     * Filters the value of an existing site transient.
     *
     * The dynamic portion of the hook name, `$transient`, refers to the transient name.
     *
     * @since 2.9.0
     * @since 4.4.0 The `$transient` parameter was added.
     *
     * @param mixed  $value     Value of site transient.
     * @param string $transient Transient name.
     */
    return apply_filters( "site_transient_{$transient}", $value, $transient );
}

/**
 * Sets/updates the value of a site transient.
 *
 * You do not need to serialize values. If the value needs to be serialized,
 * then it will be serialized before it is set.
 *
 * @since 2.9.0
 *
 * @see set_transient()
 *
 * @param string $transient  Transient name. Expected to not be SQL-escaped. Must be
 *                           167 characters or fewer in length.
 * @param mixed  $value      Transient value. Expected to not be SQL-escaped.
 * @param int    $expiration Optional. Time until expiration in seconds. Default 0 (no expiration).
 * @return bool True if the value was set, false otherwise.
 */
function set_site_transient( $transient, $value, $expiration = 0 ) {

    /**
     * Filters the value of a specific site transient before it is set.
     *
     * The dynamic portion of the hook name, `$transient`, refers to the transient name.
     *
     * @since 3.0.0
     * @since 4.4.0 The `$transient` parameter was added.
     *
     * @param mixed  $value     New value of site transient.
     * @param string $transient Transient name.
     */
    $value = apply_filters( "pre_set_site_transient_{$transient}", $value, $transient );

    $expiration = (int) $expiration;

    /**
     * Filters the expiration for a site transient before its value is set.
     *
     * The dynamic portion of the hook name, `$transient`, refers to the transient name.
     *
     * @since 4.4.0
     *
     * @param int    $expiration Time until expiration in seconds. Use 0 for no expiration.
     * @param mixed  $value      New value of site transient.
     * @param string $transient  Transient name.
     */
    $expiration = apply_filters( "expiration_of_site_transient_{$transient}", $expiration, $value, $transient );

    if ( wp_using_ext_object_cache() || wp_installing() ) {
        $result = wp_cache_set( $transient, $value, 'site-transient', $expiration );
    } else {
        $transient_timeout = '_site_transient_timeout_' . $transient;
        $option            = '_site_transient_' . $transient;

        if ( false === get_site_option( $option ) ) {
            if ( $expiration ) {
                add_site_option( $transient_timeout, time() + $expiration );
            }
            $result = add_site_option( $option, $value );
        } else {
            if ( $expiration ) {
                update_site_option( $transient_timeout, time() + $expiration );
            }
            $result = update_site_option( $option, $value );
        }
    }

    if ( $result ) {

        /**
         * Fires after the value for a specific site transient has been set.
         *
         * The dynamic portion of the hook name, `$transient`, refers to the transient name.
         *
         * @since 3.0.0
         * @since 4.4.0 The `$transient` parameter was added
         *
         * @param mixed  $value      Site transient value.
         * @param int    $expiration Time until expiration in seconds.
         * @param string $transient  Transient name.
         */
        do_action( "set_site_transient_{$transient}", $value, $expiration, $transient );

        /**
         * Fires after the value for a site transient has been set.
         *
         * @since 3.0.0
         *
         * @param string $transient  The name of the site transient.
         * @param mixed  $value      Site transient value.
         * @param int    $expiration Time until expiration in seconds.
         */
        do_action( 'setted_site_transient', $transient, $value, $expiration );
    }

    return $result;
}



