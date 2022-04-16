<?php

/**
 * Retrieve post title from XMLRPC XML.
 *
 * If the title element is not part of the XML, then the default post title from
 * the $post_default_title will be used instead.
 *
 * @since 0.71
 *
 * @global string $post_default_title Default XML-RPC post title.
 *
 * @param string $content XMLRPC XML Request content
 * @return string Post title
 */
function xmlrpc_getposttitle( $content ) {
    global $post_default_title;
    if ( preg_match( '/<title>(.+?)<\/title>/is', $content, $matchtitle ) ) {
        $post_title = $matchtitle[1];
    } else {
        $post_title = $post_default_title;
    }
    return $post_title;
}

/**
 * Retrieve the post category or categories from XMLRPC XML.
 *
 * If the category element is not found, then the default post category will be
 * used. The return type then would be what $post_default_category. If the
 * category is found, then it will always be an array.
 *
 * @since 0.71
 *
 * @global string $post_default_category Default XML-RPC post category.
 *
 * @param string $content XMLRPC XML Request content
 * @return string|array List of categories or category name.
 */
function xmlrpc_getpostcategory( $content ) {
    global $post_default_category;
    if ( preg_match( '/<category>(.+?)<\/category>/is', $content, $matchcat ) ) {
        $post_category = trim( $matchcat[1], ',' );
        $post_category = explode( ',', $post_category );
    } else {
        $post_category = $post_default_category;
    }
    return $post_category;
}

/**
 * XMLRPC XML content without title and category elements.
 *
 * @since 0.71
 *
 * @param string $content XML-RPC XML Request content.
 * @return string XMLRPC XML Request content without title and category elements.
 */
function xmlrpc_removepostdata( $content ) {
    $content = preg_replace( '/<title>(.+?)<\/title>/si', '', $content );
    $content = preg_replace( '/<category>(.+?)<\/category>/si', '', $content );
    $content = trim( $content );
    return $content;
}