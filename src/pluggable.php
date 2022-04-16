<?php /** @noinspection PhpIncludeInspection */
/** @noinspection PhpUndefinedClassInspection */
/** @noinspection PhpUndefinedNamespaceInspection */
/** @noinspection RegExpSimplifiable */
/** @noinspection GrazieInspection */
/** @noinspection SpellCheckingInspection */
/** @noinspection PhpUnnecessaryCurlyVarSyntaxInspection */
/** @noinspection PhpUndefinedConstantInspection */
/** @noinspection PhpElementIsNotAvailableInCurrentPhpVersionInspection */

if ( ! function_exists( 'is_user_logged_in' ) ) :
    /**
     * Determines whether the current visitor is a logged-in user.
     *
     * For more information on this and similar theme functions, check out
     * the {@link https://developer.wordpress.org/themes/basics/conditional-tags/
     * Conditional Tags} article in the Theme Developer Handbook.
     *
     * @since 2.0.0
     *
     * @return bool True if user is logged in, false if not logged in.
     */
    function is_user_logged_in() {
        $user = wp_get_current_user();

        return $user->exists();
    }
endif;

if ( ! function_exists( 'wp_get_current_user' ) ) :
    /**
     * Retrieve the current user object.
     *
     * Will set the current user, if the current user is not set. The current user
     * will be set to the logged-in person. If no user is logged-in, then it will
     * set the current user to 0, which is invalid and won't have any permissions.
     *
     * @since 2.0.3
     *
     * @see _wp_get_current_user()
     * @global WP_User $current_user Checks if the current user is set.
     *
     * @return WP_User Current WP_User instance.
     */
    function wp_get_current_user() {
        /** @noinspection PhpUndefinedFunctionInspection */
        return _wp_get_current_user();
    }
endif;

if ( ! function_exists( 'wp_nonce_tick' ) ) :
    /**
     * Returns the time-dependent variable for nonce creation.
     *
     * A nonce has a lifespan of two ticks. Nonces in their second tick may be
     * updated, e.g. by autosave.
     *
     * @since 2.5.0
     *
     * @return float Float value rounded up to the next highest integer.
     */
    function wp_nonce_tick() {
        /**
         * Filters the lifespan of nonces in seconds.
         *
         * @since 2.5.0
         *
         * @param int $lifespan Lifespan of nonces in seconds. Default 86,400 seconds, or one day.
         */
        $nonce_life = apply_filters( 'nonce_life', DAY_IN_SECONDS );

        return ceil( time() / ( $nonce_life / 2 ) );
    }
endif;

if ( ! function_exists( 'wp_verify_nonce' ) ) :
    /**
     * Verifies that a correct security nonce was used with time limit.
     *
     * A nonce is valid for 24 hours (by default).
     *
     * @since 2.0.3
     *
     * @param string     $nonce  Nonce value that was used for verification, usually via a form field.
     * @param string|int $action Should give context to what is taking place and be the same when nonce was created.
     * @return int|false 1 if the nonce is valid and generated between 0-12 hours ago,
     *                   2 if the nonce is valid and generated between 12-24 hours ago.
     *                   False if the nonce is invalid.
     */
    function wp_verify_nonce( $nonce, $action = -1 ) {
        $nonce = (string) $nonce;
        $user  = wp_get_current_user();
        $uid   = (int) $user->ID;
        if ( ! $uid ) {
            /**
             * Filters whether the user who generated the nonce is logged out.
             *
             * @since 3.5.0
             *
             * @param int    $uid    ID of the nonce-owning user.
             * @param string $action The nonce action.
             */
            $uid = apply_filters( 'nonce_user_logged_out', $uid, $action );
        }

        if ( empty( $nonce ) ) {
            return false;
        }

        $token = wp_get_session_token();
        $i     = wp_nonce_tick();

        // Nonce generated 0-12 hours ago.
        $expected = substr( wp_hash( $i . '|' . $action . '|' . $uid . '|' . $token, 'nonce' ), -12, 10 );
        if ( hash_equals( $expected, $nonce ) ) {
            return 1;
        }

        // Nonce generated 12-24 hours ago.
        $expected = substr( wp_hash( ( $i - 1 ) . '|' . $action . '|' . $uid . '|' . $token, 'nonce' ), -12, 10 );
        if ( hash_equals( $expected, $nonce ) ) {
            return 2;
        }

        /**
         * Fires when nonce verification fails.
         *
         * @since 4.4.0
         *
         * @param string     $nonce  The invalid nonce.
         * @param string|int $action The nonce action.
         * @param WP_User    $user   The current user object.
         * @param string     $token  The user's session token.
         */
        do_action( 'wp_verify_nonce_failed', $nonce, $action, $user, $token );

        // Invalid nonce.
        return false;
    }
endif;

if ( ! function_exists( 'wp_create_nonce' ) ) :
    /**
     * Creates a cryptographic token tied to a specific action, user, user session,
     * and window of time.
     *
     * @since 2.0.3
     * @since 4.0.0 Session tokens were integrated with nonce creation
     *
     * @param string|int $action Scalar value to add context to the nonce.
     * @return string The token.
     */
    function wp_create_nonce( $action = -1 ) {
        $user = wp_get_current_user();
        $uid  = (int) $user->ID;
        if ( ! $uid ) {
            /** This filter is documented in wp-includes/pluggable.php */
            $uid = apply_filters( 'nonce_user_logged_out', $uid, $action );
        }

        $token = wp_get_session_token();
        $i     = wp_nonce_tick();

        return substr( wp_hash( $i . '|' . $action . '|' . $uid . '|' . $token, 'nonce' ), -12, 10 );
    }
endif;

if ( ! function_exists( 'wp_salt' ) ) :
    /**
     * Returns a salt to add to hashes.
     *
     * Salts are created using secret keys. Secret keys are located in two places:
     * in the database and in the wp-config.php file. The secret key in the database
     * is randomly generated and will be appended to the secret keys in wp-config.php.
     *
     * The secret keys in wp-config.php should be updated to strong, random keys to maximize
     * security. Below is an example of how the secret key constants are defined.
     * Do not paste this example directly into wp-config.php. Instead, have a
     * {@link https://api.wordpress.org/secret-key/1.1/salt/ secret key created} just
     * for you.
     *
     *     define('AUTH_KEY',         ' Xakm<o xQy rw4EMsLKM-?!T+,PFF})H4lzcW57AF0U@N@< >M%G4Yt>f`z]MON');
     *     define('SECURE_AUTH_KEY',  'LzJ}op]mr|6+![P}Ak:uNdJCJZd>(Hx.-Mh#Tz)pCIU#uGEnfFz|f ;;eU%/U^O~');
     *     define('LOGGED_IN_KEY',    '|i|Ux`9<p-h$aFf(qnT:sDO:D1P^wZ$$/Ra@miTJi9G;ddp_<q}6H1)o|a +&JCM');
     *     define('NONCE_KEY',        '%:R{[P|,s.KuMltH5}cI;/k<Gx~j!f0I)m_sIyu+&NJZ)-iO>z7X>QYR0Z_XnZ@|');
     *     define('AUTH_SALT',        'eZyT)-Naw]F8CwA*VaW#q*|.)g@o}||wf~@C-YSt}(dh_r6EbI#A,y|nU2{B#JBW');
     *     define('SECURE_AUTH_SALT', '!=oLUTXh,QW=H `}`L|9/^4-3 STz},T(w}W<I`.JjPi)<Bmf1v,HpGe}T1:Xt7n');
     *     define('LOGGED_IN_SALT',   '+XSqHc;@Q*K_b|Z?NC[3H!!EONbh.n<+=uKR:>*c(u`g~EJBf#8u#R{mUEZrozmm');
     *     define('NONCE_SALT',       'h`GXHhD>SLWVfg1(1(N{;.V!MoE(SfbA_ksP@&`+AycHcAV$+?@3q+rxV{%^VyKT');
     *
     * Salting passwords helps against tools which has stored hashed values of
     * common dictionary strings. The added values makes it harder to crack.
     *
     * @since 2.5.0
     *
     * @link https://api.wordpress.org/secret-key/1.1/salt/ Create secrets for wp-config.php
     *
     * @param string $scheme Authentication scheme (auth, secure_auth, logged_in, nonce)
     * @return string Salt value
     */
    function wp_salt( $scheme = 'auth' ) {
        static $cached_salts = array();
        if ( isset( $cached_salts[ $scheme ] ) ) {
            /**
             * Filters the WordPress salt.
             *
             * @since 2.5.0
             *
             * @param string $cached_salt Cached salt for the given scheme.
             * @param string $scheme      Authentication scheme. Values include 'auth',
             *                            'secure_auth', 'logged_in', and 'nonce'.
             */
            return apply_filters( 'salt', $cached_salts[ $scheme ], $scheme );
        }

        static $duplicated_keys;
        if ( null === $duplicated_keys ) {
            $duplicated_keys = array( 'put your unique phrase here' => true );
            foreach ( array( 'AUTH', 'SECURE_AUTH', 'LOGGED_IN', 'NONCE', 'SECRET' ) as $first ) {
                foreach ( array( 'KEY', 'SALT' ) as $second ) {
                    if ( ! defined( "{$first}_{$second}" ) ) {
                        continue;
                    }
                    $value                     = constant( "{$first}_{$second}" );
                    $duplicated_keys[ $value ] = isset( $duplicated_keys[ $value ] );
                }
            }
        }

        $values = array(
          'key'  => '',
          'salt' => '',
        );
        if ( defined( 'SECRET_KEY' ) && SECRET_KEY && empty( $duplicated_keys[ SECRET_KEY ] ) ) {
            $values['key'] = SECRET_KEY;
        }
        if ( 'auth' === $scheme && defined( 'SECRET_SALT' ) && SECRET_SALT && empty( $duplicated_keys[ SECRET_SALT ] ) ) {
            $values['salt'] = SECRET_SALT;
        }

        if ( in_array( $scheme, array( 'auth', 'secure_auth', 'logged_in', 'nonce' ), true ) ) {
            foreach ( array( 'key', 'salt' ) as $type ) {
                $const = strtoupper( "{$scheme}_{$type}" );
                if ( defined( $const ) && constant( $const ) && empty( $duplicated_keys[ constant( $const ) ] ) ) {
                    $values[ $type ] = constant( $const );
                } elseif ( ! $values[ $type ] ) {
                    $values[ $type ] = get_site_option( "{$scheme}_{$type}" );
                    if ( ! $values[ $type ] ) {
                        $values[ $type ] = wp_generate_password( 64, true, true );
                        update_site_option( "{$scheme}_{$type}", $values[ $type ] );
                    }
                }
            }
        } else {
            if ( ! $values['key'] ) {
                $values['key'] = get_site_option( 'secret_key' );
                if ( ! $values['key'] ) {
                    $values['key'] = wp_generate_password( 64, true, true );
                    update_site_option( 'secret_key', $values['key'] );
                }
            }
            $values['salt'] = hash_hmac( 'md5', $scheme, $values['key'] );
        }

        $cached_salts[ $scheme ] = $values['key'] . $values['salt'];

        /** This filter is documented in wp-includes/pluggable.php */
        return apply_filters( 'salt', $cached_salts[ $scheme ], $scheme );
    }
endif;

if ( ! function_exists( 'wp_hash' ) ) :
    /**
     * Get hash of given string.
     *
     * @since 2.0.3
     *
     * @param string $data   Plain text to hash
     * @param string $scheme Authentication scheme (auth, secure_auth, logged_in, nonce)
     * @return string Hash of $data
     */
    function wp_hash( $data, $scheme = 'auth' ) {
        $salt = wp_salt( $scheme );

        return hash_hmac( 'md5', $data, $salt );
    }
endif;

if ( ! function_exists( 'wp_generate_password' ) ) :
    /**
     * Generates a random password drawn from the defined set of characters.
     *
     * Uses wp_rand() is used to create passwords with far less predictability
     * than similar native PHP functions like `rand()` or `mt_rand()`.
     *
     * @since 2.5.0
     *
     * @param int  $length              Optional. The length of password to generate. Default 12.
     * @param bool $special_chars       Optional. Whether to include standard special characters.
     *                                  Default true.
     * @param bool $extra_special_chars Optional. Whether to include other special characters.
     *                                  Used when generating secret keys and salts. Default false.
     * @return string The random password.
     */
    function wp_generate_password( $length = 12, $special_chars = true, $extra_special_chars = false ) {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        if ( $special_chars ) {
            $chars .= '!@#$%^&*()';
        }
        if ( $extra_special_chars ) {
            $chars .= '-_ []{}<>~`+=,.;:/?|';
        }

        $password = '';
        for ( $i = 0; $i < $length; $i++ ) {
            $password .= substr( $chars, wp_rand( 0, strlen( $chars ) - 1 ), 1 );
        }

        /**
         * Filters the randomly-generated password.
         *
         * @since 3.0.0
         * @since 5.3.0 Added the `$length`, `$special_chars`, and `$extra_special_chars` parameters.
         *
         * @param string $password            The generated password.
         * @param int    $length              The length of password to generate.
         * @param bool   $special_chars       Whether to include standard special characters.
         * @param bool   $extra_special_chars Whether to include other special characters.
         */
        return apply_filters( 'random_password', $password, $length, $special_chars, $extra_special_chars );
    }
endif;

if ( ! function_exists( 'wp_rand' ) ) :
    /**
     * Generates a random number.
     *
     * @since 2.6.2
     * @since 4.4.0 Uses PHP7 random_int() or the random_compat library if available.
     *
     * @global string $rnd_value
     *
     * @param int $min Lower limit for the generated number
     * @param int $max Upper limit for the generated number
     * @return int A random number between min and max
     */
    function wp_rand( $min = 0, $max = 0 ) {
        global $rnd_value;

        // Some misconfigured 32-bit environments (Entropy PHP, for example)
        // truncate integers larger than PHP_INT_MAX to PHP_INT_MAX rather than overflowing them to floats.
        $max_random_number = 3000000000 === 2147483647 ? (float) '4294967295' : 4294967295; // 4294967295 = 0xffffffff

        // We only handle ints, floats are truncated to their integer value.
        $min = (int) $min;
        $max = (int) $max;

        // Use PHP's CSPRNG, or a compatible method.
        static $use_random_int_functionality = true;
        if ( $use_random_int_functionality ) {
            try {
                $_max = ( 0 != $max ) ? $max : $max_random_number;
                // wp_rand() can accept arguments in either order, PHP cannot.
                $_max = max( $min, $_max );
                $_min = min( $min, $_max );
                $val  = random_int( $_min, $_max );
                /** @noinspection PhpStrictComparisonWithOperandsOfDifferentTypesInspection */
                if ( false !== $val ) {
                    return absint( $val );
                } else {
                    $use_random_int_functionality = false;
                }
            } catch ( Error $e ) {
                $use_random_int_functionality = false;
            } catch ( Exception $e ) {
                $use_random_int_functionality = false;
            }
        }

        // Reset $rnd_value after 14 uses.
        // 32 (md5) + 40 (sha1) + 40 (sha1) / 8 = 14 random numbers from $rnd_value.
        if ( strlen( $rnd_value ) < 8 ) {
            if ( defined( 'WP_SETUP_CONFIG' ) ) {
                static $seed = '';
            } else {
                $seed = get_transient( 'random_seed' );
            }
            $rnd_value  = md5( uniqid( microtime() . mt_rand(), true ) . $seed );
            $rnd_value .= sha1( $rnd_value );
            $rnd_value .= sha1( $rnd_value . $seed );
            $seed       = md5( $seed . $rnd_value );
            if ( ! defined( 'WP_SETUP_CONFIG' ) && ! defined( 'WP_INSTALLING' ) ) {
                set_transient( 'random_seed', $seed );
            }
        }

        // Take the first 8 digits for our value.
        $value = substr( $rnd_value, 0, 8 );

        // Strip the first eight, leaving the remainder for the next call to wp_rand().
        $rnd_value = substr( $rnd_value, 8 );

        $value = abs( hexdec( $value ) );

        // Reduce the value to be within the min - max range.
        if ( 0 != $max ) {
            $value = $min + ( $max - $min + 1 ) * $value / ( $max_random_number + 1 );
        }

        return abs( (int) $value );
    }
endif;

if ( ! function_exists( 'wp_parse_auth_cookie' ) ) :
    /**
     * Parses a cookie into its components.
     *
     * @since 2.7.0
     *
     * @param string $cookie Authentication cookie.
     * @param string $scheme Optional. The cookie scheme to use: 'auth', 'secure_auth', or 'logged_in'.
     * @return string[]|false Authentication cookie components.
     */
    function wp_parse_auth_cookie( $cookie = '', $scheme = '' ) {
        if ( empty( $cookie ) ) {
            switch ( $scheme ) {
                case 'auth':
                    $cookie_name = AUTH_COOKIE;
                    break;
                case 'secure_auth':
                    $cookie_name = SECURE_AUTH_COOKIE;
                    break;
                case 'logged_in':
                    $cookie_name = LOGGED_IN_COOKIE;
                    break;
                default:
                    if ( is_ssl() ) {
                        $cookie_name = SECURE_AUTH_COOKIE;
                        $scheme      = 'secure_auth';
                    } else {
                        $cookie_name = AUTH_COOKIE;
                        $scheme      = 'auth';
                    }
            }

            if ( empty( $_COOKIE[ $cookie_name ] ) ) {
                return false;
            }
            $cookie = $_COOKIE[ $cookie_name ];
        }

        $cookie_elements = explode( '|', $cookie );
        if ( count( $cookie_elements ) !== 4 ) {
            return false;
        }

        list( $username, $expiration, $token, $hmac ) = $cookie_elements;

        return compact( 'username', 'expiration', 'token', 'hmac', 'scheme' );
    }
endif;

if ( ! function_exists( 'wp_redirect' ) ) :
    /**
     * Redirects to another page.
     *
     * Note: wp_redirect() does not exit automatically, and should almost always be
     * followed by a call to `exit;`:
     *
     *     wp_redirect( $url );
     *     exit;
     *
     * Exiting can also be selectively manipulated by using wp_redirect() as a conditional
     * in conjunction with the {@see 'wp_redirect'} and {@see 'wp_redirect_location'} filters:
     *
     *     if ( wp_redirect( $url ) ) {
     *         exit;
     *     }
     *
     * @since 1.5.1
     * @since 5.1.0 The `$x_redirect_by` parameter was added.
     * @since 5.4.0 On invalid status codes, wp_die() is called.
     *
     * @global bool $is_IIS
     *
     * @param string $location      The path or URL to redirect to.
     * @param int    $status        Optional. HTTP response status code to use. Default '302' (Moved Temporarily).
     * @param string $x_redirect_by Optional. The application doing the redirect. Default 'WordPress'.
     * @return bool False if the redirect was cancelled, true otherwise.
     */
    function wp_redirect( $location, $status = 302, $x_redirect_by = 'WordPress' ) {
        global $is_IIS;

        /**
         * Filters the redirect location.
         *
         * @since 2.1.0
         *
         * @param string $location The path or URL to redirect to.
         * @param int    $status   The HTTP response status code to use.
         */
        $location = apply_filters( 'wp_redirect', $location, $status );

        /**
         * Filters the redirect HTTP response status code to use.
         *
         * @since 2.3.0
         *
         * @param int    $status   The HTTP response status code to use.
         * @param string $location The path or URL to redirect to.
         */
        $status = apply_filters( 'wp_redirect_status', $status, $location );

        if ( ! $location ) {
            return false;
        }

        if ( $status < 300 || 399 < $status ) {
            wp_die( __( 'HTTP redirect status code must be a redirection code, 3xx.' ) );
        }

        $location = wp_sanitize_redirect( $location );

        if ( ! $is_IIS && 'cgi-fcgi' !== PHP_SAPI ) {
            status_header( $status ); // This causes problems on IIS and some FastCGI setups.
        }

        /**
         * Filters the X-Redirect-By header.
         *
         * Allows applications to identify themselves when they're doing a redirect.
         *
         * @since 5.1.0
         *
         * @param string $x_redirect_by The application doing the redirect.
         * @param int    $status        Status code to use.
         * @param string $location      The path to redirect to.
         */
        $x_redirect_by = apply_filters( 'x_redirect_by', $x_redirect_by, $status, $location );
        if ( is_string( $x_redirect_by ) ) {
            header( "X-Redirect-By: $x_redirect_by" );
        }

        header( "Location: $location", true, $status );

        return true;
    }
endif;

if ( ! function_exists( 'wp_sanitize_redirect' ) ) :
    /**
     * Sanitizes a URL for use in a redirect.
     *
     * @since 2.3.0
     *
     * @param string $location The path to redirect to.
     * @return string Redirect-sanitized URL.
     */
    function wp_sanitize_redirect( $location ) {
        // Encode spaces.
        $location = str_replace( ' ', '%20', $location );

        $regex    = '/
		(
			(?: [\xC2-\xDF][\x80-\xBF]        # double-byte sequences   110xxxxx 10xxxxxx
			|   \xE0[\xA0-\xBF][\x80-\xBF]    # triple-byte sequences   1110xxxx 10xxxxxx * 2
			|   [\xE1-\xEC][\x80-\xBF]{2}
			|   \xED[\x80-\x9F][\x80-\xBF]
			|   [\xEE-\xEF][\x80-\xBF]{2}
			|   \xF0[\x90-\xBF][\x80-\xBF]{2} # four-byte sequences   11110xxx 10xxxxxx * 3
			|   [\xF1-\xF3][\x80-\xBF]{3}
			|   \xF4[\x80-\x8F][\x80-\xBF]{2}
		){1,40}                              # ...one or more times
		)/x';
        $location = preg_replace_callback( $regex, '_wp_sanitize_utf8_in_redirect', $location );
        $location = preg_replace( '|[^a-z0-9-~+_.?#=&;,/:%!*\[\]()@]|i', '', $location );
        /** @noinspection PhpUndefinedFunctionInspection */
        $location = wp_kses_no_null( $location );

        // Remove %0D and %0A from location.
        $strip = array( '%0d', '%0a', '%0D', '%0A' );
        /** @noinspection PhpUndefinedFunctionInspection */
        return _deep_replace( $strip, $location );
    }

    /**
     * URL encode UTF-8 characters in a URL.
     *
     * @ignore
     * @since 4.2.0
     * @access private
     *
     * @see wp_sanitize_redirect()
     *
     * @param array $matches RegEx matches against the redirect location.
     * @return string URL-encoded version of the first RegEx match.
     */
    function _wp_sanitize_utf8_in_redirect( $matches ) {
        return urlencode( $matches[0] );
    }
endif;

if ( ! function_exists( 'wp_safe_redirect' ) ) :
    /**
     * Performs a safe (local) redirect, using wp_redirect().
     *
     * Checks whether the $location is using an allowed host, if it has an absolute
     * path. A plugin can therefore set or remove allowed host(s) to or from the
     * list.
     *
     * If the host is not allowed, then the redirect defaults to wp-admin on the siteurl
     * instead. This prevents malicious redirects which redirect to another host,
     * but only used in a few places.
     *
     * Note: wp_safe_redirect() does not exit automatically, and should almost always be
     * followed by a call to `exit;`:
     *
     *     wp_safe_redirect( $url );
     *     exit;
     *
     * Exiting can also be selectively manipulated by using wp_safe_redirect() as a conditional
     * in conjunction with the {@see 'wp_redirect'} and {@see 'wp_redirect_location'} filters:
     *
     *     if ( wp_safe_redirect( $url ) ) {
     *         exit;
     *     }
     *
     * @since 2.3.0
     * @since 5.1.0 The return value from wp_redirect() is now passed on, and the `$x_redirect_by` parameter was added.
     *
     * @param string $location      The path or URL to redirect to.
     * @param int    $status        Optional. HTTP response status code to use. Default '302' (Moved Temporarily).
     * @param string $x_redirect_by Optional. The application doing the redirect. Default 'WordPress'.
     * @return bool False if the redirect was cancelled, true otherwise.
     */
    function wp_safe_redirect( $location, $status = 302, $x_redirect_by = 'WordPress' ) {

        // Need to look at the URL the way it will end up in wp_redirect().
        $location = wp_sanitize_redirect( $location );

        /**
         * Filters the redirect fallback URL for when the provided redirect is not safe (local).
         *
         * @since 4.3.0
         *
         * @param string $fallback_url The fallback URL to use by default.
         * @param int    $status       The HTTP response status code to use.
         */
        $location = wp_validate_redirect( $location, apply_filters( 'wp_safe_redirect_fallback', admin_url(), $status ) );

        return wp_redirect( $location, $status, $x_redirect_by );
    }
endif;

if ( ! function_exists( 'wp_validate_redirect' ) ) :
    /**
     * Validates a URL for use in a redirect.
     *
     * Checks whether the $location is using an allowed host, if it has an absolute
     * path. A plugin can therefore set or remove allowed host(s) to or from the
     * list.
     *
     * If the host is not allowed, then the redirect is to $default supplied
     *
     * @since 2.8.1
     *
     * @param string $location The redirect to validate
     * @param string $default  The value to return if $location is not allowed
     * @return string redirect-sanitized URL
     */
    function wp_validate_redirect( $location, $default = '' ) {
        $location = wp_sanitize_redirect( trim( $location, " \t\n\r\0\x08\x0B" ) );
        // Browsers will assume 'http' is your protocol, and will obey a redirect to a URL starting with '//'.
        if ( '//' === substr( $location, 0, 2 ) ) {
            $location = 'http:' . $location;
        }

        // In PHP 5 parse_url() may fail if the URL query part contains 'http://'.
        // See https://bugs.php.net/bug.php?id=38143
        $cut  = strpos( $location, '?' );
        $test = $cut ? substr( $location, 0, $cut ) : $location;

        $lp = parse_url( $test );

        // Give up if malformed URL.
        if ( false === $lp ) {
            return $default;
        }

        // Allow only 'http' and 'https' schemes. No 'data:', etc.
        if ( isset( $lp['scheme'] ) && ! ( 'http' === $lp['scheme'] || 'https' === $lp['scheme'] ) ) {
            return $default;
        }

        if ( ! isset( $lp['host'] ) && ! empty( $lp['path'] ) && '/' !== $lp['path'][0] ) {
            $path = '';
            if ( ! empty( $_SERVER['REQUEST_URI'] ) ) {
                $path = dirname( parse_url( 'http://placeholder' . $_SERVER['REQUEST_URI'], PHP_URL_PATH ) . '?' );
                $path = wp_normalize_path( $path );
            }
            $location = '/' . ltrim( $path . '/', '/' ) . $location;
        }

        // Reject if certain components are set but host is not.
        // This catches URLs like https:host.com for which parse_url() does not set the host field.
        if ( ! isset( $lp['host'] ) && ( isset( $lp['scheme'] ) || isset( $lp['user'] ) || isset( $lp['pass'] ) || isset( $lp['port'] ) ) ) {
            return $default;
        }

        // Reject malformed components parse_url() can return on odd inputs.
        foreach ( array( 'user', 'pass', 'host' ) as $component ) {
            if ( isset( $lp[ $component ] ) && strpbrk( $lp[ $component ], ':/?#@' ) ) {
                return $default;
            }
        }

        $wpp = parse_url( home_url() );

        /**
         * Filters the list of allowed hosts to redirect to.
         *
         * @since 2.3.0
         *
         * @param string[] $hosts An array of allowed host names.
         * @param string   $host  The host name of the redirect destination; empty string if not set.
         */
        $allowed_hosts = (array) apply_filters( 'allowed_redirect_hosts', array( $wpp['host'] ), isset( $lp['host'] ) ? $lp['host'] : '' );

        if ( isset( $lp['host'] ) && ( ! in_array( $lp['host'], $allowed_hosts, true ) && strtolower( $wpp['host'] ) !== $lp['host'] ) ) {
            $location = $default;
        }

        return $location;
    }
endif;

if ( ! function_exists( 'wp_mail' ) ) :
    /**
     * Sends an email, similar to PHP's mail function.
     *
     * A true return value does not automatically mean that the user received the
     * email successfully. It just only means that the method used was able to
     * process the request without any errors.
     *
     * The default content type is `text/plain` which does not allow using HTML.
     * However, you can set the content type of the email by using the
     * {@see 'wp_mail_content_type'} filter.
     *
     * The default charset is based on the charset used on the blog. The charset can
     * be set using the {@see 'wp_mail_charset'} filter.
     *
     * @since 1.2.1
     * @since 5.5.0 is_email() is used for email validation,
     *              instead of PHPMailer's default validator.
     *
     * @global PHPMailer\PHPMailer\PHPMailer $phpmailer
     *
     * @param string|string[] $to          Array or comma-separated list of email addresses to send message.
     * @param string          $subject     Email subject.
     * @param string          $message     Message contents.
     * @param string|string[] $headers     Optional. Additional headers.
     * @param string|string[] $attachments Optional. Paths to files to attach.
     * @return bool Whether the email was sent successfully.
     */
    /** @noinspection PhpUndefinedNamespaceInspection */
    function wp_mail( $to, $subject, $message, $headers = '', $attachments = array() ) {
        // Compact the input, apply the filters, and extract them back out.

        /**
         * Filters the wp_mail() arguments.
         *
         * @since 2.2.0
         *
         * @param array $args {
         *     Array of the `wp_mail()` arguments.
         *
         *     @type string|string[] $to          Array or comma-separated list of email addresses to send message.
         *     @type string          $subject     Email subject.
         *     @type string          $message     Message contents.
         *     @type string|string[] $headers     Additional headers.
         *     @type string|string[] $attachments Paths to files to attach.
         * }
         */
        $atts = apply_filters( 'wp_mail', compact( 'to', 'subject', 'message', 'headers', 'attachments' ) );

        /**
         * Filters whether to preempt sending an email.
         *
         * Returning a non-null value will short-circuit {@see wp_mail()}, returning
         * that value instead. A boolean return value should be used to indicate whether
         * the email was successfully sent.
         *
         * @since 5.7.0
         *
         * @param null|bool $return Short-circuit return value.
         * @param array     $atts {
         *     Array of the `wp_mail()` arguments.
         *
         *     @type string|string[] $to          Array or comma-separated list of email addresses to send message.
         *     @type string          $subject     Email subject.
         *     @type string          $message     Message contents.
         *     @type string|string[] $headers     Additional headers.
         *     @type string|string[] $attachments Paths to files to attach.
         * }
         */
        $pre_wp_mail = apply_filters( 'pre_wp_mail', null, $atts );

        if ( null !== $pre_wp_mail ) {
            return $pre_wp_mail;
        }

        if ( isset( $atts['to'] ) ) {
            $to = $atts['to'];
        }

        if ( ! is_array( $to ) ) {
            $to = explode( ',', $to );
        }

        if ( isset( $atts['subject'] ) ) {
            $subject = $atts['subject'];
        }

        if ( isset( $atts['message'] ) ) {
            $message = $atts['message'];
        }

        if ( isset( $atts['headers'] ) ) {
            $headers = $atts['headers'];
        }

        if ( isset( $atts['attachments'] ) ) {
            $attachments = $atts['attachments'];
        }

        if ( ! is_array( $attachments ) ) {
            $attachments = explode( "\n", str_replace( "\r\n", "\n", $attachments ) );
        }
        global $phpmailer;

        // (Re)create it, if it's gone missing.
        if ( ! ( $phpmailer instanceof PHPMailer\PHPMailer\PHPMailer ) ) {
            require_once ABSPATH . WPINC . '/PHPMailer/PHPMailer.php';
            require_once ABSPATH . WPINC . '/PHPMailer/SMTP.php';
            require_once ABSPATH . WPINC . '/PHPMailer/Exception.php';
            $phpmailer = new PHPMailer\PHPMailer\PHPMailer( true );

            $phpmailer::$validator = static function ( $email ) {
                return (bool) is_email( $email );
            };
        }

        // Headers.
        $cc       = array();
        $bcc      = array();
        $reply_to = array();

        if ( empty( $headers ) ) {
            $headers = array();
        } else {
            if ( ! is_array( $headers ) ) {
                // Explode the headers out, so this function can take
                // both string headers and an array of headers.
                $tempheaders = explode( "\n", str_replace( "\r\n", "\n", $headers ) );
            } else {
                $tempheaders = $headers;
            }
            $headers = array();

            // If it's actually got contents.
            if ( ! empty( $tempheaders ) ) {
                // Iterate through the raw headers.
                foreach ( (array) $tempheaders as $header ) {
                    if ( strpos( $header, ':' ) === false ) {
                        if ( false !== stripos( $header, 'boundary=' ) ) {
                            $parts    = preg_split( '/boundary=/i', trim( $header ) );
                            $boundary = trim( str_replace( array( "'", '"' ), '', $parts[1] ) );
                        }
                        continue;
                    }
                    // Explode them out.
                    list( $name, $content ) = explode( ':', trim( $header ), 2 );

                    // Cleanup crew.
                    $name    = trim( $name );
                    $content = trim( $content );

                    switch ( strtolower( $name ) ) {
                        // Mainly for legacy -- process a "From:" header if it's there.
                        case 'from':
                            $bracket_pos = strpos( $content, '<' );
                            if ( false !== $bracket_pos ) {
                                // Text before the bracketed email is the "From" name.
                                if ( $bracket_pos > 0 ) {
                                    $from_name = substr( $content, 0, $bracket_pos - 1 );
                                    $from_name = str_replace( '"', '', $from_name );
                                    $from_name = trim( $from_name );
                                }

                                $from_email = substr( $content, $bracket_pos + 1 );
                                $from_email = str_replace( '>', '', $from_email );
                                $from_email = trim( $from_email );

                                // Avoid setting an empty $from_email.
                            } elseif ( '' !== trim( $content ) ) {
                                $from_email = trim( $content );
                            }
                            break;
                        case 'content-type':
                            if ( strpos( $content, ';' ) !== false ) {
                                list( $type, $charset_content ) = explode( ';', $content );
                                $content_type                   = trim( $type );
                                if ( false !== stripos( $charset_content, 'charset=' ) ) {
                                    $charset = trim( str_replace( array( 'charset=', '"' ), '', $charset_content ) );
                                } elseif ( false !== stripos( $charset_content, 'boundary=' ) ) {
                                    $boundary = trim( str_replace( array( 'BOUNDARY=', 'boundary=', '"' ), '', $charset_content ) );
                                    $charset  = '';
                                }

                                // Avoid setting an empty $content_type.
                            } elseif ( '' !== trim( $content ) ) {
                                $content_type = trim( $content );
                            }
                            break;
                        case 'cc':
                            $cc = array_merge( (array) $cc, explode( ',', $content ) );
                            break;
                        case 'bcc':
                            $bcc = array_merge( (array) $bcc, explode( ',', $content ) );
                            break;
                        case 'reply-to':
                            $reply_to = array_merge( (array) $reply_to, explode( ',', $content ) );
                            break;
                        default:
                            // Add it to our grand headers array.
                            $headers[ trim( $name ) ] = trim( $content );
                            break;
                    }
                }
            }
        }

        // Empty out the values that may be set.
        $phpmailer->clearAllRecipients();
        $phpmailer->clearAttachments();
        $phpmailer->clearCustomHeaders();
        $phpmailer->clearReplyTos();

        // Set "From" name and email.

        // If we don't have a name from the input headers.
        if ( ! isset( $from_name ) ) {
            $from_name = 'WordPress';
        }

        /*
         * If we don't have an email from the input headers, default to wordpress@$sitename
         * Some hosts will block outgoing mail from this address if it doesn't exist,
         * but there's no easy alternative. Defaulting to admin_email might appear to be
         * another option, but some hosts may refuse to relay mail from an unknown domain.
         * See https://core.trac.wordpress.org/ticket/5007.
         */
        if ( ! isset( $from_email ) ) {
            // Get the site domain and get rid of www.
            $sitename = wp_parse_url( network_home_url(), PHP_URL_HOST );
            if ( 'www.' === substr( $sitename, 0, 4 ) ) {
                $sitename = substr( $sitename, 4 );
            }

            $from_email = 'wordpress@' . $sitename;
        }

        /**
         * Filters the email address to send from.
         *
         * @since 2.2.0
         *
         * @param string $from_email Email address to send from.
         */
        $from_email = apply_filters( 'wp_mail_from', $from_email );

        /**
         * Filters the name to associate with the "from" email address.
         *
         * @since 2.3.0
         *
         * @param string $from_name Name associated with the "from" email address.
         */
        $from_name = apply_filters( 'wp_mail_from_name', $from_name );

        try {
            $phpmailer->setFrom( $from_email, $from_name, false );
        } catch ( PHPMailer\PHPMailer\Exception $e ) {
            $mail_error_data                             = compact( 'to', 'subject', 'message', 'headers', 'attachments' );
            $mail_error_data['phpmailer_exception_code'] = $e->getCode();

            /** This filter is documented in wp-includes/pluggable.php */
            do_action( 'wp_mail_failed', new WP_Error( 'wp_mail_failed', $e->getMessage(), $mail_error_data ) );

            return false;
        }

        // Set mail's subject and body.
        $phpmailer->Subject = $subject;
        $phpmailer->Body    = $message;

        // Set destination addresses, using appropriate methods for handling addresses.
        $address_headers = compact( 'to', 'cc', 'bcc', 'reply_to' );

        foreach ( $address_headers as $address_header => $addresses ) {
            if ( empty( $addresses ) ) {
                continue;
            }

            foreach ( (array) $addresses as $address ) {
                try {
                    // Break $recipient into name and address parts if in the format "Foo <bar@baz.com>".
                    $recipient_name = '';

                    if ( preg_match( '/(.*)<(.+)>/', $address, $matches ) ) {
                        if ( count( $matches ) == 3 ) {
                            $recipient_name = $matches[1];
                            $address        = $matches[2];
                        }
                    }

                    switch ( $address_header ) {
                        case 'to':
                            $phpmailer->addAddress( $address, $recipient_name );
                            break;
                        case 'cc':
                            $phpmailer->addCc( $address, $recipient_name );
                            break;
                        case 'bcc':
                            $phpmailer->addBcc( $address, $recipient_name );
                            break;
                        case 'reply_to':
                            $phpmailer->addReplyTo( $address, $recipient_name );
                            break;
                    }
                } catch ( PHPMailer\PHPMailer\Exception $e ) {
                    continue;
                }
            }
        }

        // Set to use PHP's mail().
        $phpmailer->isMail();

        // Set Content-Type and charset.

        // If we don't have a content-type from the input headers.
        if ( ! isset( $content_type ) ) {
            $content_type = 'text/plain';
        }

        /**
         * Filters the wp_mail() content type.
         *
         * @since 2.3.0
         *
         * @param string $content_type Default wp_mail() content type.
         */
        $content_type = apply_filters( 'wp_mail_content_type', $content_type );

        $phpmailer->ContentType = $content_type;

        // Set whether it's plaintext, depending on $content_type.
        if ( 'text/html' === $content_type ) {
            $phpmailer->isHTML( true );
        }

        // If we don't have a charset from the input headers.
        if ( ! isset( $charset ) ) {
            $charset = get_bloginfo( 'charset' );
        }

        /**
         * Filters the default wp_mail() charset.
         *
         * @since 2.3.0
         *
         * @param string $charset Default email charset.
         */
        $phpmailer->CharSet = apply_filters( 'wp_mail_charset', $charset );

        // Set custom headers.
        if ( ! empty( $headers ) ) {
            foreach ( (array) $headers as $name => $content ) {
                // Only add custom headers not added automatically by PHPMailer.
                if ( ! in_array( $name, array( 'MIME-Version', 'X-Mailer' ), true ) ) {
                    try {
                        $phpmailer->addCustomHeader( sprintf( '%1$s: %2$s', $name, $content ) );
                    } catch ( PHPMailer\PHPMailer\Exception $e ) {
                        continue;
                    }
                }
            }

            if ( false !== stripos( $content_type, 'multipart' ) && ! empty( $boundary ) ) {
                $phpmailer->addCustomHeader( sprintf( 'Content-Type: %s; boundary="%s"', $content_type, $boundary ) );
            }
        }

        if ( ! empty( $attachments ) ) {
            foreach ( $attachments as $attachment ) {
                try {
                    $phpmailer->addAttachment( $attachment );
                } catch ( PHPMailer\PHPMailer\Exception $e ) {
                    continue;
                }
            }
        }

        /**
         * Fires after PHPMailer is initialized.
         *
         * @since 2.2.0
         *
         * @param PHPMailer $phpmailer The PHPMailer instance (passed by reference).
         */
        do_action_ref_array( 'phpmailer_init', array( &$phpmailer ) );

        $mail_data = compact( 'to', 'subject', 'message', 'headers', 'attachments' );

        // Send!
        try {
            $send = $phpmailer->send();

            /**
             * Fires after PHPMailer has successfully sent a mail.
             *
             * The firing of this action does not necessarily mean that the recipient received the
             * email successfully. It only means that the `send` method above was able to
             * process the request without any errors.
             *
             * @since 5.9.0
             *
             * @param array $mail_data An array containing the mail recipient, subject, message, headers, and attachments.
             */
            do_action( 'wp_mail_succeeded', $mail_data );

            return $send;
        } catch ( PHPMailer\PHPMailer\Exception $e ) {
            $mail_data['phpmailer_exception_code'] = $e->getCode();

            /**
             * Fires after a PHPMailer\PHPMailer\Exception is caught.
             *
             * @since 4.4.0
             *
             * @param WP_Error $error A WP_Error object with the PHPMailer\PHPMailer\Exception message, and an array
             *                        containing the mail recipient, subject, message, headers, and attachments.
             */
            do_action( 'wp_mail_failed', new WP_Error( 'wp_mail_failed', $e->getMessage(), $mail_data ) );

            return false;
        }
    }
endif;
