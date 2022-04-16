<?php /** @noinspection PhpRedundantOptionalArgumentInspection */
/** @noinspection PhpUnused */
/** @noinspection HtmlUnknownTarget */

/**
 * Mark a function as deprecated and inform when it has been used.
 *
 * There is a {@see 'hook deprecated_function_run'} that will be called that can be used
 * to get the backtrace up to what file and function called the deprecated
 * function.
 *
 * The current behavior is to trigger a user error if `WP_DEBUG` is true.
 *
 * This function is to be used in every function that is deprecated.
 *
 * @since 2.5.0
 * @since 5.4.0 This function is no longer marked as "private".
 * @since 5.4.0 The error type is now classified as E_USER_DEPRECATED (used to default to E_USER_NOTICE).
 *
 * @param string $function    The function that was called.
 * @param string $version     The version of WordPress that deprecated the function.
 * @param string $replacement Optional. The function that should have been called. Default empty.
 */
function _deprecated_function( $function, $version, $replacement = '' ) {

    /**
     * Fires when a deprecated function is called.
     *
     * @since 2.5.0
     *
     * @param string $function    The function that was called.
     * @param string $replacement The function that should have been called.
     * @param string $version     The version of WordPress that deprecated the function.
     */
    do_action( 'deprecated_function_run', $function, $replacement, $version );

    /**
     * Filters whether to trigger an error for deprecated functions.
     *
     * @since 2.5.0
     *
     * @param bool $trigger Whether to trigger the error for deprecated functions. Default true.
     */
    if ( WP_DEBUG && apply_filters( 'deprecated_function_trigger_error', true ) ) {
        if ( function_exists( '__' ) ) {
            if ( $replacement ) {
                trigger_error(
                  sprintf(
                  /* translators: 1: PHP function name, 2: Version number, 3: Alternative function name. */
                    __( '%1$s is <strong>deprecated</strong> since version %2$s! Use %3$s instead.' ),
                    $function,
                    $version,
                    $replacement
                  ),
                  E_USER_DEPRECATED
                );
            } else {
                trigger_error(
                  sprintf(
                  /* translators: 1: PHP function name, 2: Version number. */
                    __( '%1$s is <strong>deprecated</strong> since version %2$s with no alternative available.' ),
                    $function,
                    $version
                  ),
                  E_USER_DEPRECATED
                );
            }
        } else {
            if ( $replacement ) {
                trigger_error(
                  sprintf(
                    '%1$s is <strong>deprecated</strong> since version %2$s! Use %3$s instead.',
                    $function,
                    $version,
                    $replacement
                  ),
                  E_USER_DEPRECATED
                );
            } else {
                trigger_error(
                  sprintf(
                    '%1$s is <strong>deprecated</strong> since version %2$s with no alternative available.',
                    $function,
                    $version
                  ),
                  E_USER_DEPRECATED
                );
            }
        }
    }
}

/**
 * Marks a constructor as deprecated and informs when it has been used.
 *
 * Similar to _deprecated_function(), but with different strings. Used to
 * remove PHP4 style constructors.
 *
 * The current behavior is to trigger a user error if `WP_DEBUG` is true.
 *
 * This function is to be used in every PHP4 style constructor method that is deprecated.
 *
 * @since 4.3.0
 * @since 4.5.0 Added the `$parent_class` parameter.
 * @since 5.4.0 This function is no longer marked as "private".
 * @since 5.4.0 The error type is now classified as E_USER_DEPRECATED (used to default to E_USER_NOTICE).
 *
 * @param string $class        The class containing the deprecated constructor.
 * @param string $version      The version of WordPress that deprecated the function.
 * @param string $parent_class Optional. The parent class calling the deprecated constructor.
 *                             Default empty string.
 */
function _deprecated_constructor( $class, $version, $parent_class = '' ) {

    /**
     * Fires when a deprecated constructor is called.
     *
     * @since 4.3.0
     * @since 4.5.0 Added the `$parent_class` parameter.
     *
     * @param string $class        The class containing the deprecated constructor.
     * @param string $version      The version of WordPress that deprecated the function.
     * @param string $parent_class The parent class calling the deprecated constructor.
     */
    do_action( 'deprecated_constructor_run', $class, $version, $parent_class );

    /**
     * Filters whether to trigger an error for deprecated functions.
     *
     * `WP_DEBUG` must be true in addition to the filter evaluating to true.
     *
     * @since 4.3.0
     *
     * @param bool $trigger Whether to trigger the error for deprecated functions. Default true.
     */
    if ( WP_DEBUG && apply_filters( 'deprecated_constructor_trigger_error', true ) ) {
        if ( function_exists( '__' ) ) {
            if ( $parent_class ) {
                trigger_error(
                  sprintf(
                  /* translators: 1: PHP class name, 2: PHP parent class name, 3: Version number, 4: __construct() method. */
                    __( 'The called constructor method for %1$s in %2$s is <strong>deprecated</strong> since version %3$s! Use %4$s instead.' ),
                    $class,
                    $parent_class,
                    $version,
                    '<code>__construct()</code>'
                  ),
                  E_USER_DEPRECATED
                );
            } else {
                trigger_error(
                  sprintf(
                  /* translators: 1: PHP class name, 2: Version number, 3: __construct() method. */
                    __( 'The called constructor method for %1$s is <strong>deprecated</strong> since version %2$s! Use %3$s instead.' ),
                    $class,
                    $version,
                    '<code>__construct()</code>'
                  ),
                  E_USER_DEPRECATED
                );
            }
        } else {
            if ( $parent_class ) {
                trigger_error(
                  sprintf(
                    'The called constructor method for %1$s in %2$s is <strong>deprecated</strong> since version %3$s! Use %4$s instead.',
                    $class,
                    $parent_class,
                    $version,
                    '<code>__construct()</code>'
                  ),
                  E_USER_DEPRECATED
                );
            } else {
                trigger_error(
                  sprintf(
                    'The called constructor method for %1$s is <strong>deprecated</strong> since version %2$s! Use %3$s instead.',
                    $class,
                    $version,
                    '<code>__construct()</code>'
                  ),
                  E_USER_DEPRECATED
                );
            }
        }
    }

}

/**
 * Mark a file as deprecated and inform when it has been used.
 *
 * There is a hook {@see 'deprecated_file_included'} that will be called that can be used
 * to get the backtrace up to what file and function included the deprecated
 * file.
 *
 * The current behavior is to trigger a user error if `WP_DEBUG` is true.
 *
 * This function is to be used in every file that is deprecated.
 *
 * @since 2.5.0
 * @since 5.4.0 This function is no longer marked as "private".
 * @since 5.4.0 The error type is now classified as E_USER_DEPRECATED (used to default to E_USER_NOTICE).
 *
 * @param string $file        The file that was included.
 * @param string $version     The version of WordPress that deprecated the file.
 * @param string $replacement Optional. The file that should have been included based on ABSPATH.
 *                            Default empty.
 * @param string $message     Optional. A message regarding the change. Default empty.
 */
function _deprecated_file( $file, $version, $replacement = '', $message = '' ) {

    /**
     * Fires when a deprecated file is called.
     *
     * @since 2.5.0
     *
     * @param string $file        The file that was called.
     * @param string $replacement The file that should have been included based on ABSPATH.
     * @param string $version     The version of WordPress that deprecated the file.
     * @param string $message     A message regarding the change.
     */
    do_action( 'deprecated_file_included', $file, $replacement, $version, $message );

    /**
     * Filters whether to trigger an error for deprecated files.
     *
     * @since 2.5.0
     *
     * @param bool $trigger Whether to trigger the error for deprecated files. Default true.
     */
    if ( WP_DEBUG && apply_filters( 'deprecated_file_trigger_error', true ) ) {
        $message = empty( $message ) ? '' : ' ' . $message;

        if ( function_exists( '__' ) ) {
            if ( $replacement ) {
                trigger_error(
                  sprintf(
                  /* translators: 1: PHP file name, 2: Version number, 3: Alternative file name. */
                    __( '%1$s is <strong>deprecated</strong> since version %2$s! Use %3$s instead.' ),
                    $file,
                    $version,
                    $replacement
                  ) . $message,
                  E_USER_DEPRECATED
                );
            } else {
                trigger_error(
                  sprintf(
                  /* translators: 1: PHP file name, 2: Version number. */
                    __( '%1$s is <strong>deprecated</strong> since version %2$s with no alternative available.' ),
                    $file,
                    $version
                  ) . $message,
                  E_USER_DEPRECATED
                );
            }
        } else {
            if ( $replacement ) {
                trigger_error(
                  sprintf(
                    '%1$s is <strong>deprecated</strong> since version %2$s! Use %3$s instead.',
                    $file,
                    $version,
                    $replacement
                  ) . $message,
                  E_USER_DEPRECATED
                );
            } else {
                trigger_error(
                  sprintf(
                    '%1$s is <strong>deprecated</strong> since version %2$s with no alternative available.',
                    $file,
                    $version
                  ) . $message,
                  E_USER_DEPRECATED
                );
            }
        }
    }
}

/**
 * Mark a function argument as deprecated and inform when it has been used.
 *
 * This function is to be used whenever a deprecated function argument is used.
 * Before this function is called, the argument must be checked for whether it was
 * used by comparing it to its default value or evaluating whether it is empty.
 * For example:
 *
 *     if ( ! empty( $deprecated ) ) {
 *         _deprecated_argument( __FUNCTION__, '3.0.0' );
 *     }
 *
 * There is a hook deprecated_argument_run that will be called that can be used
 * to get the backtrace up to what file and function used the deprecated
 * argument.
 *
 * The current behavior is to trigger a user error if WP_DEBUG is true.
 *
 * @since 3.0.0
 * @since 5.4.0 This function is no longer marked as "private".
 * @since 5.4.0 The error type is now classified as E_USER_DEPRECATED (used to default to E_USER_NOTICE).
 *
 * @param string $function The function that was called.
 * @param string $version  The version of WordPress that deprecated the argument used.
 * @param string $message  Optional. A message regarding the change. Default empty.
 */
function _deprecated_argument( $function, $version, $message = '' ) {

    /**
     * Fires when a deprecated argument is called.
     *
     * @since 3.0.0
     *
     * @param string $function The function that was called.
     * @param string $message  A message regarding the change.
     * @param string $version  The version of WordPress that deprecated the argument used.
     */
    do_action( 'deprecated_argument_run', $function, $message, $version );

    /**
     * Filters whether to trigger an error for deprecated arguments.
     *
     * @since 3.0.0
     *
     * @param bool $trigger Whether to trigger the error for deprecated arguments. Default true.
     */
    if ( WP_DEBUG && apply_filters( 'deprecated_argument_trigger_error', true ) ) {
        if ( function_exists( '__' ) ) {
            if ( $message ) {
                trigger_error(
                  sprintf(
                  /* translators: 1: PHP function name, 2: Version number, 3: Optional message regarding the change. */
                    __( '%1$s was called with an argument that is <strong>deprecated</strong> since version %2$s! %3$s' ),
                    $function,
                    $version,
                    $message
                  ),
                  E_USER_DEPRECATED
                );
            } else {
                trigger_error(
                  sprintf(
                  /* translators: 1: PHP function name, 2: Version number. */
                    __( '%1$s was called with an argument that is <strong>deprecated</strong> since version %2$s with no alternative available.' ),
                    $function,
                    $version
                  ),
                  E_USER_DEPRECATED
                );
            }
        } else {
            if ( $message ) {
                trigger_error(
                  sprintf(
                    '%1$s was called with an argument that is <strong>deprecated</strong> since version %2$s! %3$s',
                    $function,
                    $version,
                    $message
                  ),
                  E_USER_DEPRECATED
                );
            } else {
                trigger_error(
                  sprintf(
                    '%1$s was called with an argument that is <strong>deprecated</strong> since version %2$s with no alternative available.',
                    $function,
                    $version
                  ),
                  E_USER_DEPRECATED
                );
            }
        }
    }
}

/**
 * Marks a deprecated action or filter hook as deprecated and throws a notice.
 *
 * Use the {@see 'deprecated_hook_run'} action to get the backtrace describing where
 * the deprecated hook was called.
 *
 * Default behavior is to trigger a user error if `WP_DEBUG` is true.
 *
 * This function is called by the do_action_deprecated() and apply_filters_deprecated()
 * functions, and so generally does not need to be called directly.
 *
 * @since 4.6.0
 * @since 5.4.0 The error type is now classified as E_USER_DEPRECATED (used to default to E_USER_NOTICE).
 * @access private
 *
 * @param string $hook        The hook that was used.
 * @param string $version     The version of WordPress that deprecated the hook.
 * @param string $replacement Optional. The hook that should have been used. Default empty.
 * @param string $message     Optional. A message regarding the change. Default empty.
 */
function _deprecated_hook( $hook, $version, $replacement = '', $message = '' ) {
    /**
     * Fires when a deprecated hook is called.
     *
     * @since 4.6.0
     *
     * @param string $hook        The hook that was called.
     * @param string $replacement The hook that should be used as a replacement.
     * @param string $version     The version of WordPress that deprecated the argument used.
     * @param string $message     A message regarding the change.
     */
    do_action( 'deprecated_hook_run', $hook, $replacement, $version, $message );

    /**
     * Filters whether to trigger deprecated hook errors.
     *
     * @since 4.6.0
     *
     * @param bool $trigger Whether to trigger deprecated hook errors. Requires
     *                      `WP_DEBUG` to be defined true.
     */
    if ( WP_DEBUG && apply_filters( 'deprecated_hook_trigger_error', true ) ) {
        $message = empty( $message ) ? '' : ' ' . $message;

        if ( $replacement ) {
            trigger_error(
              sprintf(
              /* translators: 1: WordPress hook name, 2: Version number, 3: Alternative hook name. */
                __( '%1$s is <strong>deprecated</strong> since version %2$s! Use %3$s instead.' ),
                $hook,
                $version,
                $replacement
              ) . $message,
              E_USER_DEPRECATED
            );
        } else {
            trigger_error(
              sprintf(
              /* translators: 1: WordPress hook name, 2: Version number. */
                __( '%1$s is <strong>deprecated</strong> since version %2$s with no alternative available.' ),
                $hook,
                $version
              ) . $message,
              E_USER_DEPRECATED
            );
        }
    }
}

/**
 * Mark something as being incorrectly called.
 *
 * There is a hook {@see 'doing_it_wrong_run'} that will be called that can be used
 * to get the backtrace up to what file and function called the deprecated
 * function.
 *
 * The current behavior is to trigger a user error if `WP_DEBUG` is true.
 *
 * @since 3.1.0
 * @since 5.4.0 This function is no longer marked as "private".
 *
 * @param string $function The function that was called.
 * @param string $message  A message explaining what has been done incorrectly.
 * @param string $version  The version of WordPress where the message was added.
 */
function _doing_it_wrong( $function, $message, $version ) {

    /**
     * Fires when the given function is being used incorrectly.
     *
     * @since 3.1.0
     *
     * @param string $function The function that was called.
     * @param string $message  A message explaining what has been done incorrectly.
     * @param string $version  The version of WordPress where the message was added.
     */
    do_action( 'doing_it_wrong_run', $function, $message, $version );

    /**
     * Filters whether to trigger an error for _doing_it_wrong() calls.
     *
     * @since 3.1.0
     * @since 5.1.0 Added the $function, $message and $version parameters.
     *
     * @param bool   $trigger  Whether to trigger the error for _doing_it_wrong() calls. Default true.
     * @param string $function The function that was called.
     * @param string $message  A message explaining what has been done incorrectly.
     * @param string $version  The version of WordPress where the message was added.
     */
    if ( WP_DEBUG && apply_filters( 'doing_it_wrong_trigger_error', true, $function, $message, $version ) ) {
        if ( function_exists( '__' ) ) {
            if ( $version ) {
                /* translators: %s: Version number. */
                $version = sprintf( __( '(This message was added in version %s.)' ), $version );
            }

            $message .= ' ' . sprintf(
              /* translators: %s: Documentation URL. */
                __( 'Please see <a href="%s">Debugging in WordPress</a> for more information.' ),
                __( 'https://wordpress.org/support/article/debugging-in-wordpress/' )
              );

            trigger_error(
              sprintf(
              /* translators: Developer debugging message. 1: PHP function name, 2: Explanatory message, 3: WordPress version number. */
                __( '%1$s was called <strong>incorrectly</strong>. %2$s %3$s' ),
                $function,
                $message,
                $version
              ),
              E_USER_NOTICE
            );
        } else {
            if ( $version ) {
                $version = sprintf( '(This message was added in version %s.)', $version );
            }

            $message .= sprintf(
              ' Please see <a href="%s">Debugging in WordPress</a> for more information.',
              'https://wordpress.org/support/article/debugging-in-wordpress/'
            );

            trigger_error(
              sprintf(
                '%1$s was called <strong>incorrectly</strong>. %2$s %3$s',
                $function,
                $message,
                $version
              ),
              E_USER_NOTICE
            );
        }
    }
}