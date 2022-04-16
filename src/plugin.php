<?php /** @noinspection SpellCheckingInspection */
/** @noinspection PhpMissingParamTypeInspection */
/** @noinspection RegExpDuplicateAlternationBranch */
/** @noinspection PhpUndefinedConstantInspection */
/** @noinspection GrazieInspection */

/**
 * The plugin API is located in this file, which allows for creating actions
 * and filters and hooking functions, and methods. The functions or methods will
 * then be run when the action or filter is called.
 *
 * The API callback examples reference functions, but can be methods of classes.
 * To hook methods, you'll need to pass an array one of two ways.
 *
 * Any of the syntaxes explained in the PHP documentation for the
 * {@link https://www.php.net/manual/en/language.pseudo-types.php#language.types.callback 'callback'}
 * type are valid.
 *
 * Also see the {@link https://developer.wordpress.org/plugins/ Plugin API} for
 * more information and examples on how to use a lot of these functions.
 *
 * This file should have no external dependencies.
 *
 * @package WordPress
 * @subpackage Plugin
 * @since 1.5.0
 */

/**
 * Adds a callback function to a filter hook.
 *
 * WordPress offers filter hooks to allow plugins to modify
 * various types of internal data at runtime.
 *
 * A plugin can modify data by binding a callback to a filter hook. When the filter
 * is later applied, each bound callback is run in order of priority, and given
 * the opportunity to modify a value by returning a new value.
 *
 * The following example shows how a callback function is bound to a filter hook.
 *
 * Note that `$example` is passed to the callback, (maybe) modified, then returned:
 *
 *     function example_callback( $example ) {
 *         // Maybe modify $example in some way.
 *         return $example;
 *     }
 *     add_filter( 'example_filter', 'example_callback' );
 *
 * Bound callbacks can accept from none to the total number of arguments passed as parameters
 * in the corresponding apply_filters() call.
 *
 * In other words, if an apply_filters() call passes four total arguments, callbacks bound to
 * it can accept none (the same as 1) of the arguments or up to four. The important part is that
 * the `$accepted_args` value must reflect the number of arguments the bound callback *actually*
 * opted to accept. If no arguments were accepted by the callback that is considered to be the
 * same as accepting 1 argument. For example:
 *
 *     // Filter call.
 *     $value = apply_filters( 'hook', $value, $arg2, $arg3 );
 *
 *     // Accepting zero/one arguments.
 *     function example_callback() {
 *         ...
 *         return 'some value';
 *     }
 *     add_filter( 'hook', 'example_callback' ); // Where $priority is default 10, $accepted_args is default 1.
 *
 *     // Accepting two arguments (three possible).
 *     function example_callback( $value, $arg2 ) {
 *         ...
 *         return $maybe_modified_value;
 *     }
 *     add_filter( 'hook', 'example_callback', 10, 2 ); // Where $priority is 10, $accepted_args is 2.
 *
 * *Note:* The function will return true whether or not the callback is valid.
 * It is up to you to take care. This is done for optimization purposes, so
 * everything is as quick as possible.
 *
 * @param string $hook_name The name of the filter to add the callback to.
 * @param callable $callback The callback to be run when the filter is applied.
 * @param int $priority Optional. Used to specify the order in which the functions
 *                                associated with a particular filter are executed.
 *                                Lower numbers correspond with earlier execution,
 *                                and functions with the same priority are executed
 *                                in the order in which they were added to the filter. Default 10.
 * @param int $accepted_args Optional. The number of arguments the function accepts. Default 1.
 * @return true Always returns true.
 * @global WP_Hook[] $wp_filter A multidimensional array of all hooks and the callbacks hooked to them.
 *
 * @since 0.71
 *
 */
if (!function_exists('add_filter')) {
    function add_filter($hook_name, $callback, $priority = 10, $accepted_args = 1)
    {
        global $wp_filter;

        if (!isset($wp_filter[$hook_name])) {
            $wp_filter[$hook_name] = new WP_Hook();
        }

        $wp_filter[$hook_name]->add_filter($hook_name, $callback, $priority, $accepted_args);

        return true;
    }
}

/**
 * Checks if any filter has been registered for a hook.
 *
 * When using the `$callback` argument, this function may return a non-boolean value
 * that evaluates to false (e.g. 0), so use the `===` operator for testing the return value.
 *
 * @param string $hook_name The name of the filter hook.
 * @param callable|string|array|false $callback Optional. The callback to check for.
 *                                               This function can be called unconditionally to speculatively check
 *                                               a callback that may or may not exist. Default false.
 * @return bool|int If `$callback` is omitted, returns boolean for whether the hook has
 *                  anything registered. When checking a specific function, the priority
 *                  of that hook is returned, or false if the function is not attached.
 * @global WP_Hook[] $wp_filter Stores all of the filters and actions.
 *
 * @since 2.5.0
 *
 */
if (!function_exists('has_filter')) {
    function has_filter($hook_name, $callback = false)
    {
        global $wp_filter;

        if (!isset($wp_filter[$hook_name])) {
            return false;
        }

        return $wp_filter[$hook_name]->has_filter($hook_name, $callback);
    }
}

/**
 * Calls the callback functions that have been added to a filter hook.
 *
 * This function invokes all functions attached to filter hook `$hook_name`.
 * It is possible to create new filter hooks by simply calling this function,
 * specifying the name of the new hook using the `$hook_name` parameter.
 *
 * The function also allows for multiple additional arguments to be passed to hooks.
 *
 * Example usage:
 *
 *     // The filter callback function.
 *     function example_callback( $string, $arg1, $arg2 ) {
 *         // (maybe) modify $string.
 *         return $string;
 *     }
 *     add_filter( 'example_filter', 'example_callback', 10, 3 );
 *
 *     /*
 *      * Apply the filters by calling the 'example_callback()' function
 *      * that's hooked onto `example_filter` above.
 *      *
 *      * - 'example_filter' is the filter hook.
 *      * - 'filter me' is the value being filtered.
 *      * - $arg1 and $arg2 are the additional arguments passed to the callback.
 *     $value = apply_filters( 'example_filter', 'filter me', $arg1, $arg2 );
 *
 * @param string $hook_name The name of the filter hook.
 * @param mixed $value The value to filter.
 * @param mixed ...$args Additional parameters to pass to the callback functions.
 * @return mixed The filtered value after all hooked functions are applied to it.
 * @global WP_Hook[] $wp_filter Stores all of the filters and actions.
 * @global string[] $wp_current_filter Stores the list of current filters with the current one last.
 *
 * @since 0.71
 *
 */
if (!function_exists('apply_filters')) {
    function apply_filters($hook_name, $value)
    {
        global $wp_filter, $wp_current_filter;

        $args = func_get_args();

        // Do 'all' actions first.
        if (isset($wp_filter['all'])) {
            $wp_current_filter[] = $hook_name;
            _wp_call_all_hook($args);
        }

        if (!isset($wp_filter[$hook_name])) {
            if (isset($wp_filter['all'])) {
                array_pop($wp_current_filter);
            }

            return $value;
        }

        if (!isset($wp_filter['all'])) {
            $wp_current_filter[] = $hook_name;
        }

        // Don't pass the tag name to WP_Hook.
        array_shift($args);

        $filtered = $wp_filter[$hook_name]->apply_filters($value, $args);

        array_pop($wp_current_filter);

        return $filtered;
    }
}

/**
 * Returns whether or not a filter hook is currently being processed.
 *
 * The function current_filter() only returns the most recent filter or action
 * being executed. did_action() returns true once the action is initially
 * processed.
 *
 * This function allows detection for any filter currently being executed
 * (regardless of whether it's the most recent filter to fire, in the case of
 * hooks called from hook callbacks) to be verified.
 *
 * @param string|null $hook_name Optional. Filter hook to check. Defaults to null,
 *                               which checks if any filter is currently being run.
 * @return bool Whether the filter is currently in the stack.
 * @see did_action()
 * @global string[] $wp_current_filter Current filter.
 *
 * @since 3.9.0
 *
 * @see current_filter()
 */
if (!function_exists('doing_filter')) {
    function doing_filter(string $hook_name = null)
    {
        global $wp_current_filter;

        if (null === $hook_name) {
            return !empty($wp_current_filter);
        }

        return in_array($hook_name, $wp_current_filter, true);
    }
}

/**
 * Adds a callback function to an action hook.
 *
 * Actions are the hooks that the WordPress core launches at specific points
 * during execution, or when specific events occur. Plugins can specify that
 * one or more of its PHP functions are executed at these points, using the
 * Action API.
 *
 * @param string $hook_name The name of the action to add the callback to.
 * @param callable $callback The callback to be run when the action is called.
 * @param int $priority Optional. Used to specify the order in which the functions
 *                                  associated with a particular action are executed.
 *                                  Lower numbers correspond with earlier execution,
 *                                  and functions with the same priority are executed
 *                                  in the order in which they were added to the action. Default 10.
 * @param int $accepted_args Optional. The number of arguments the function accepts. Default 1.
 * @return true Always returns true.
 * @since 1.2.0
 *
 */
if (!function_exists('add_action')) {
    function add_action($hook_name, $callback, $priority = 10, $accepted_args = 1)
    {
        return add_filter($hook_name, $callback, $priority, $accepted_args);
    }
}

/**
 * Retrieves the number of times an action has been fired during the current request.
 *
 * @param string $hook_name The name of the action hook.
 * @return int The number of times the action hook has been fired.
 * @since 2.1.0
 *
 * @global int[] $wp_actions Stores the number of times each action was triggered.
 *
 */
if (!function_exists('did_action')) {
    function did_action($hook_name)
    {
        global $wp_actions;

        if (!isset($wp_actions[$hook_name])) {
            return 0;
        }

        return $wp_actions[$hook_name];
    }
}

/**
 * Calls the callback functions that have been added to an action hook.
 *
 * This function invokes all functions attached to action hook `$hook_name`.
 * It is possible to create new action hooks by simply calling this function,
 * specifying the name of the new hook using the `$hook_name` parameter.
 *
 * You can pass extra arguments to the hooks, much like you can with `apply_filters()`.
 *
 * Example usage:
 *
 *     // The action callback function.
 *     function example_callback( $arg1, $arg2 ) {
 *         // (maybe) do something with the args.
 *     }
 *     add_action( 'example_action', 'example_callback', 10, 2 );
 *
 *     /*
 *      * Trigger the actions by calling the 'example_callback()' function
 *      * that's hooked onto `example_action` above.
 *      *
 *      * - 'example_action' is the action hook.
 *      * - $arg1 and $arg2 are the additional arguments passed to the callback.
 *     $value = do_action( 'example_action', $arg1, $arg2 );
 *
 * @since 1.2.0
 * @since 5.3.0 Formalized the existing and already documented `...$arg` parameter
 *              by adding it to the function signature.
 *
 * @global WP_Hook[] $wp_filter         Stores all of the filters and actions.
 * @global int[]     $wp_actions        Stores the number of times each action was triggered.
 * @global string[]  $wp_current_filter Stores the list of current filters with the current one last.
 *
 * @param string $hook_name The name of the action to be executed.
 * @param mixed  ...$arg    Optional. Additional arguments which are passed on to the
 *                          functions hooked to the action. Default empty.
 */
if (!function_exists('do_action')) {
    function do_action($hook_name, ...$arg)
    {
        global $wp_filter, $wp_actions, $wp_current_filter;

        if (!isset($wp_actions[$hook_name])) {
            $wp_actions[$hook_name] = 1;
        } else {
            ++$wp_actions[$hook_name];
        }

        // Do 'all' actions first.
        if (isset($wp_filter['all'])) {
            $wp_current_filter[] = $hook_name;
            $all_args = func_get_args(); // phpcs:ignore PHPCompatibility.FunctionUse.ArgumentFunctionsReportCurrentValue.NeedsInspection
            _wp_call_all_hook($all_args);
        }

        if (!isset($wp_filter[$hook_name])) {
            if (isset($wp_filter['all'])) {
                array_pop($wp_current_filter);
            }

            return;
        }

        if (!isset($wp_filter['all'])) {
            $wp_current_filter[] = $hook_name;
        }

        if (empty($arg)) {
            $arg[] = '';
        } elseif (is_array($arg[0]) && 1 === count($arg[0]) && isset($arg[0][0]) && is_object($arg[0][0])) {
            // Backward compatibility for PHP4-style passing of `array( &$this )` as action `$arg`.
            $arg[0] = $arg[0][0];
        }

        $wp_filter[$hook_name]->do_action($arg);

        array_pop($wp_current_filter);
    }
}

/**
 * Returns whether or not an action hook is currently being processed.
 *
 * @param string|null $hook_name Optional. Action hook to check. Defaults to null,
 *                               which checks if any action is currently being run.
 * @return bool Whether the action is currently in the stack.
 * @since 3.9.0
 *
 */
if (!function_exists('doing_action')) {
    function doing_action($hook_name = null)
    {
        return doing_filter($hook_name);
    }
}

/**
 * Calls the 'all' hook, which will process the functions hooked into it.
 *
 * The 'all' hook passes all of the arguments or parameters that were used for
 * the hook, which this function was called for.
 *
 * This function is used internally for apply_filters(), do_action(), and
 * do_action_ref_array() and is not meant to be used from outside those
 * functions. This function does not check for the existence of the all hook, so
 * it will fail unless the all hook exists prior to this function call.
 *
 * @since 2.5.0
 * @access private
 *
 * @global WP_Hook[] $wp_filter Stores all of the filters and actions.
 *
 * @param array $args The collected parameters from the hook that was called.
 */
if (!function_exists('_wp_call_all_hook')) {
    function _wp_call_all_hook($args)
    {
        global $wp_filter;

        $wp_filter['all']->do_all_hook($args);
    }
}

/**
 * Gets the basename of a plugin.
 *
 * This method extracts the name of a plugin from its filename.
 *
 * @since 1.5.0
 *
 * @global array $wp_plugin_paths
 *
 * @param string $file The filename of plugin.
 * @return string The name of a plugin.
 */
if (!function_exists('plugin_basename')) {
    function plugin_basename($file)
    {
        global $wp_plugin_paths;

        // $wp_plugin_paths contains normalized paths.
        $file = wp_normalize_path($file);

        arsort($wp_plugin_paths);

        /** @noinspection SpellCheckingInspection */
        foreach ($wp_plugin_paths as $dir => $realdir) {
            if (strpos($file, $realdir) === 0) {
                $file = $dir . substr($file, strlen($realdir));
            }
        }

        $plugin_dir = wp_normalize_path(WP_PLUGIN_DIR);
        $mu_plugin_dir = wp_normalize_path(WPMU_PLUGIN_DIR);

        // Get relative path from plugins directory.
        $file = preg_replace('#^' . preg_quote($plugin_dir, '#') . '/|^' . preg_quote($mu_plugin_dir, '#') . '/#', '',
          $file);
        return trim($file, '/');
    }
}