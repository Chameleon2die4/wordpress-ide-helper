<?php /** @noinspection SpellCheckingInspection */
/** @noinspection PhpUnused */
/** @noinspection PhpUnnecessaryCurlyVarSyntaxInspection */

/**
 * Retrieve the translation of $text.
 *
 * If there is no translation, or the text domain isn't loaded, the original text is returned.
 *
 * *Note:* Don't use translate() directly, use __() or related functions.
 *
 * @since 2.2.0
 * @since 5.5.0 Introduced gettext-{$domain} filter.
 *
 * @param string $text   Text to translate.
 * @param string $domain Optional. Text domain. Unique identifier for retrieving translated strings.
 *                       Default 'default'.
 * @return string Translated text.
 */
function translate( $text, $domain = 'default' ) {
    /** @noinspection PhpUndefinedFunctionInspection */
    $translations = get_translations_for_domain( $domain );
    $translation  = $translations->translate( $text );

    /**
     * Filters text with its translation.
     *
     * @since 2.0.11
     *
     * @param string $translation Translated text.
     * @param string $text        Text to translate.
     * @param string $domain      Text domain. Unique identifier for retrieving translated strings.
     */
    $translation = apply_filters( 'gettext', $translation, $text, $domain );

    /**
     * Filters text with its translation for a domain.
     *
     * The dynamic portion of the hook name, `$domain`, refers to the text domain.
     *
     * @since 5.5.0
     *
     * @param string $translation Translated text.
     * @param string $text        Text to translate.
     * @param string $domain      Text domain. Unique identifier for retrieving translated strings.
     */
    return apply_filters( "gettext_{$domain}", $translation, $text, $domain );
}

/**
 * Retrieve the translation of $text in the context defined in $context.
 *
 * If there is no translation, or the text domain isn't loaded, the original text is returned.
 *
 * *Note:* Don't use translate_with_gettext_context() directly, use _x() or related functions.
 *
 * @since 2.8.0
 * @since 5.5.0 Introduced gettext_with_context-{$domain} filter.
 *
 * @param string $text    Text to translate.
 * @param string $context Context information for the translators.
 * @param string $domain  Optional. Text domain. Unique identifier for retrieving translated strings.
 *                        Default 'default'.
 * @return string Translated text on success, original text on failure.
 */
function translate_with_gettext_context( $text, $context, $domain = 'default' ) {
    /** @noinspection PhpUndefinedFunctionInspection */
    $translations = get_translations_for_domain( $domain );
    $translation  = $translations->translate( $text, $context );

    /**
     * Filters text with its translation based on context information.
     *
     * @since 2.8.0
     *
     * @param string $translation Translated text.
     * @param string $text        Text to translate.
     * @param string $context     Context information for the translators.
     * @param string $domain      Text domain. Unique identifier for retrieving translated strings.
     */
    $translation = apply_filters( 'gettext_with_context', $translation, $text, $context, $domain );

    /**
     * Filters text with its translation based on context information for a domain.
     *
     * The dynamic portion of the hook name, `$domain`, refers to the text domain.
     *
     * @since 5.5.0
     *
     * @param string $translation Translated text.
     * @param string $text        Text to translate.
     * @param string $context     Context information for the translators.
     * @param string $domain      Text domain. Unique identifier for retrieving translated strings.
     */
    return apply_filters( "gettext_with_context_{$domain}", $translation, $text, $context, $domain );
}

/**
 * Retrieve the translation of $text.
 *
 * If there is no translation, or the text domain isn't loaded, the original text is returned.
 *
 * @since 2.1.0
 *
 * @param string $text   Text to translate.
 * @param string $domain Optional. Text domain. Unique identifier for retrieving translated strings.
 *                       Default 'default'.
 * @return string Translated text.
 */
function __( $text, $domain = 'default' ) {
    return translate( $text, $domain );
}

/**
 * Retrieve the translation of $text and escapes it for safe use in an attribute.
 *
 * If there is no translation, or the text domain isn't loaded, the original text is returned.
 *
 * @since 2.8.0
 *
 * @param string $text   Text to translate.
 * @param string $domain Optional. Text domain. Unique identifier for retrieving translated strings.
 *                       Default 'default'.
 * @return string Translated text on success, original text on failure.
 */
function esc_attr__( $text, $domain = 'default' ) {
    return esc_attr( translate( $text, $domain ) );
}

/**
 * Retrieve the translation of $text and escapes it for safe use in HTML output.
 *
 * If there is no translation, or the text domain isn't loaded, the original text
 * is escaped and returned.
 *
 * @since 2.8.0
 *
 * @param string $text   Text to translate.
 * @param string $domain Optional. Text domain. Unique identifier for retrieving translated strings.
 *                       Default 'default'.
 * @return string Translated text.
 */
function esc_html__( $text, $domain = 'default' ) {
    return esc_html( translate( $text, $domain ) );
}

/**
 * Display translated text.
 *
 * @since 1.2.0
 *
 * @param string $text   Text to translate.
 * @param string $domain Optional. Text domain. Unique identifier for retrieving translated strings.
 *                       Default 'default'.
 */
function _e( $text, $domain = 'default' ) {
    echo translate( $text, $domain );
}

/**
 * Display translated text that has been escaped for safe use in an attribute.
 *
 * Encodes `< > & " '` (less than, greater than, ampersand, double quote, single quote).
 * Will never double encode entities.
 *
 * If you need the value for use in PHP, use esc_attr__().
 *
 * @since 2.8.0
 *
 * @param string $text   Text to translate.
 * @param string $domain Optional. Text domain. Unique identifier for retrieving translated strings.
 *                       Default 'default'.
 */
function esc_attr_e( $text, $domain = 'default' ) {
    echo esc_attr( translate( $text, $domain ) );
}

/**
 * Display translated text that has been escaped for safe use in HTML output.
 *
 * If there is no translation, or the text domain isn't loaded, the original text
 * is escaped and displayed.
 *
 * If you need the value for use in PHP, use esc_html__().
 *
 * @since 2.8.0
 *
 * @param string $text   Text to translate.
 * @param string $domain Optional. Text domain. Unique identifier for retrieving translated strings.
 *                       Default 'default'.
 */
function esc_html_e( $text, $domain = 'default' ) {
    echo esc_html( translate( $text, $domain ) );
}

/**
 * Retrieve translated string with gettext context.
 *
 * Quite a few times, there will be collisions with similar translatable text
 * found in more than two places, but with different translated context.
 *
 * By including the context in the pot file, translators can translate the two
 * strings differently.
 *
 * @since 2.8.0
 *
 * @param string $text    Text to translate.
 * @param string $context Context information for the translators.
 * @param string $domain  Optional. Text domain. Unique identifier for retrieving translated strings.
 *                        Default 'default'.
 * @return string Translated context string without pipe.
 */
function _x( $text, $context, $domain = 'default' ) {
    return translate_with_gettext_context( $text, $context, $domain );
}

/**
 * Display translated string with gettext context.
 *
 * @since 3.0.0
 *
 * @param string $text    Text to translate.
 * @param string $context Context information for the translators.
 * @param string $domain  Optional. Text domain. Unique identifier for retrieving translated strings.
 *                        Default 'default'.
 */
function _ex( $text, $context, $domain = 'default' ) {
    echo _x( $text, $context, $domain );
}

/**
 * Translate string with gettext context, and escapes it for safe use in an attribute.
 *
 * If there is no translation, or the text domain isn't loaded, the original text
 * is escaped and returned.
 *
 * @since 2.8.0
 *
 * @param string $text    Text to translate.
 * @param string $context Context information for the translators.
 * @param string $domain  Optional. Text domain. Unique identifier for retrieving translated strings.
 *                        Default 'default'.
 * @return string Translated text.
 */
function esc_attr_x( $text, $context, $domain = 'default' ) {
    return esc_attr( translate_with_gettext_context( $text, $context, $domain ) );
}

/**
 * Translate string with gettext context, and escapes it for safe use in HTML output.
 *
 * If there is no translation, or the text domain isn't loaded, the original text
 * is escaped and returned.
 *
 * @since 2.9.0
 *
 * @param string $text    Text to translate.
 * @param string $context Context information for the translators.
 * @param string $domain  Optional. Text domain. Unique identifier for retrieving translated strings.
 *                        Default 'default'.
 * @return string Translated text.
 */
function esc_html_x( $text, $context, $domain = 'default' ) {
    return esc_html( translate_with_gettext_context( $text, $context, $domain ) );
}

/**
 * Translates and retrieves the singular or plural form based on the supplied number.
 *
 * Used when you want to use the appropriate form of a string based on whether a
 * number is singular or plural.
 *
 * Example:
 *
 *     printf( _n( '%s person', '%s people', $count, 'text-domain' ), number_format_i18n( $count ) );
 *
 * @since 2.8.0
 * @since 5.5.0 Introduced ngettext-{$domain} filter.
 *
 * @param string $single The text to be used if the number is singular.
 * @param string $plural The text to be used if the number is plural.
 * @param int    $number The number to compare against to use either the singular or plural form.
 * @param string $domain Optional. Text domain. Unique identifier for retrieving translated strings.
 *                       Default 'default'.
 * @return string The translated singular or plural form.
 */
function _n( $single, $plural, $number, $domain = 'default' ) {
    /** @noinspection PhpUndefinedFunctionInspection */
    $translations = get_translations_for_domain( $domain );
    $translation  = $translations->translate_plural( $single, $plural, $number );

    /**
     * Filters the singular or plural form of a string.
     *
     * @since 2.2.0
     *
     * @param string $translation Translated text.
     * @param string $single      The text to be used if the number is singular.
     * @param string $plural      The text to be used if the number is plural.
     * @param string $number      The number to compare against to use either the singular or plural form.
     * @param string $domain      Text domain. Unique identifier for retrieving translated strings.
     */
    $translation = apply_filters( 'ngettext', $translation, $single, $plural, $number, $domain );

    /**
     * Filters the singular or plural form of a string for a domain.
     *
     * The dynamic portion of the hook name, `$domain`, refers to the text domain.
     *
     * @since 5.5.0
     *
     * @param string $translation Translated text.
     * @param string $single      The text to be used if the number is singular.
     * @param string $plural      The text to be used if the number is plural.
     * @param string $number      The number to compare against to use either the singular or plural form.
     * @param string $domain      Text domain. Unique identifier for retrieving translated strings.
     */
    return apply_filters( "ngettext_{$domain}", $translation, $single, $plural, $number, $domain );
}

/**
 * Retrieves the current locale.
 *
 * If the locale is set, then it will filter the locale in the {@see 'locale'}
 * filter hook and return the value.
 *
 * If the locale is not set already, then the WPLANG constant is used if it is
 * defined. Then it is filtered through the {@see 'locale'} filter hook and
 * the value for the locale global set and the locale is returned.
 *
 * The process to get the locale should only be done once, but the locale will
 * always be filtered using the {@see 'locale'} hook.
 *
 * @since 1.5.0
 *
 * @global string $locale           The current locale.
 * @global string $wp_local_package Locale code of the package.
 *
 * @return string The locale of the blog or from the {@see 'locale'} hook.
 */
function get_locale() {
    global $locale, $wp_local_package;

    if ( isset( $locale ) ) {
        /** This filter is documented in wp-includes/l10n.php */
        return apply_filters( 'locale', $locale );
    }

    if ( isset( $wp_local_package ) ) {
        $locale = $wp_local_package;
    }

    // WPLANG was defined in wp-config.
    if ( defined( 'WPLANG' ) ) {
        $locale = WPLANG;
    }

    // If multisite, check options.
    if ( is_multisite() ) {
        // Don't check blog option when installing.
        if ( wp_installing() ) {
            $ms_locale = get_site_option( 'WPLANG' );
        } else {
            $ms_locale = get_option( 'WPLANG' );
            if ( false === $ms_locale ) {
                $ms_locale = get_site_option( 'WPLANG' );
            }
        }

        if ( false !== $ms_locale ) {
            $locale = $ms_locale;
        }
    } else {
        $db_locale = get_option( 'WPLANG' );
        if ( false !== $db_locale ) {
            $locale = $db_locale;
        }
    }

    if ( empty( $locale ) ) {
        $locale = 'en_US';
    }

    /**
     * Filters the locale ID of the WordPress installation.
     *
     * @since 1.5.0
     *
     * @param string $locale The locale ID.
     */
    return apply_filters( 'locale', $locale );
}

/**
 * Registers plural strings in POT file, but does not translate them.
 *
 * Used when you want to keep structures with translatable plural
 * strings and use them later when the number is known.
 *
 * Example:
 *
 *     $message = _n_noop( '%s post', '%s posts', 'text-domain' );
 *     ...
 *     printf( translate_nooped_plural( $message, $count, 'text-domain' ), number_format_i18n( $count ) );
 *
 * @since 2.5.0
 *
 * @param string $singular Singular form to be localized.
 * @param string $plural   Plural form to be localized.
 * @param string $domain   Optional. Text domain. Unique identifier for retrieving translated strings.
 *                         Default null.
 * @return array {
 *     Array of translation information for the strings.
 *
 *     @type string $0        Singular form to be localized. No longer used.
 *     @type string $1        Plural form to be localized. No longer used.
 *     @type string $singular Singular form to be localized.
 *     @type string $plural   Plural form to be localized.
 *     @type null   $context  Context information for the translators.
 *     @type string $domain   Text domain.
 * }
 */
function _n_noop( $singular, $plural, $domain = null ) {
    return array(
      0          => $singular,
      1          => $plural,
      'singular' => $singular,
      'plural'   => $plural,
      'context'  => null,
      'domain'   => $domain,
    );
}

/**
 * Load a .mo file into the text domain $domain.
 *
 * If the text domain already exists, the translations will be merged. If both
 * sets have the same string, the translation from the original value will be taken.
 *
 * On success, the .mo file will be placed in the $l10n global by $domain
 * and will be a MO object.
 *
 * @since 1.5.0
 *
 * @global MO[] $l10n          An array of all currently loaded text domains.
 * @global MO[] $l10n_unloaded An array of all text domains that have been unloaded again.
 *
 * @param string $domain Text domain. Unique identifier for retrieving translated strings.
 * @param string $mofile Path to the .mo file.
 * @return bool True on success, false on failure.
 */
function load_textdomain( $domain, $mofile ) {
    global $l10n, $l10n_unloaded;

    $l10n_unloaded = (array) $l10n_unloaded;

    /**
     * Filters whether to override the .mo file loading.
     *
     * @since 2.9.0
     *
     * @param bool   $override Whether to override the .mo file loading. Default false.
     * @param string $domain   Text domain. Unique identifier for retrieving translated strings.
     * @param string $mofile   Path to the MO file.
     */
    $plugin_override = apply_filters( 'override_load_textdomain', false, $domain, $mofile );

    if ( true === (bool) $plugin_override ) {
        unset( $l10n_unloaded[ $domain ] );

        return true;
    }

    /**
     * Fires before the MO translation file is loaded.
     *
     * @since 2.9.0
     *
     * @param string $domain Text domain. Unique identifier for retrieving translated strings.
     * @param string $mofile Path to the .mo file.
     */
    do_action( 'load_textdomain', $domain, $mofile );

    /**
     * Filters MO file path for loading translations for a specific text domain.
     *
     * @since 2.9.0
     *
     * @param string $mofile Path to the MO file.
     * @param string $domain Text domain. Unique identifier for retrieving translated strings.
     */
    $mofile = apply_filters( 'load_textdomain_mofile', $mofile, $domain );

    if ( ! is_readable( $mofile ) ) {
        return false;
    }

    $mo = new MO();
    if ( ! $mo->import_from_file( $mofile ) ) {
        return false;
    }

    if ( isset( $l10n[ $domain ] ) ) {
        $mo->merge_with( $l10n[ $domain ] );
    }

    unset( $l10n_unloaded[ $domain ] );

    $l10n[ $domain ] = &$mo;

    return true;
}
