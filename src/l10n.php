<?php

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
    $translation = apply_filters( "gettext_{$domain}", $translation, $text, $domain );

    return $translation;
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
    $translation = apply_filters( "gettext_with_context_{$domain}", $translation, $text, $context, $domain );

    return $translation;
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
    $translation = apply_filters( "ngettext_{$domain}", $translation, $single, $plural, $number, $domain );

    return $translation;
}