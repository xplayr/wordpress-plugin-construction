<?php
/*
Plugin Name: Contact Form 7 Robot Trap
Plugin URI: https://github.com/szepeviktor/wordpress-plugin-construction
Description: Put this in the contact form <code>[robottrap email-verify id:email-verify tabindex:2]</code> and hide it by CSS
Version: 0.1
License: The MIT License (MIT)
Author: Viktor Szépe
Author URI: http://www.online1.hu/webdesign/
*/

/**
 * A module for the following tag type:
 *  [robottrap]  # Hidden input field for stopping robots
 *
 * Test #1 Is the hidden text filed filled in?
 * Test #2 Does the submitted email address' domain name accept email?
 *
 * use the `wpcf7_spam` filter to do something with the spammer
 *
 */

add_action( 'wpcf7_init', 'wpcf7_add_shortcode_robottrap' );
add_filter( 'wpcf7_validate_robottrap', 'wpcf7_robottrap_validation_filter', 1, 2 );
add_filter( 'wpcf7_validate_email', 'wpcf7_robottrap_domain_validation_filter', 1, 2 );
add_filter( 'wpcf7_validate_email*', 'wpcf7_robottrap_domain_validation_filter', 1, 2 );

function wpcf7_add_shortcode_robottrap() {
    wpcf7_add_shortcode(
        array( 'robottrap' ),
        'wpcf7_robottrap_shortcode_handler',
        true
    );
}

function wpcf7_robottrap_shortcode_handler( $tag ) {
    $tag = new WPCF7_Shortcode( $tag );

    // default field name
    if ( empty( $tag->name ) )
        $tag->name = 'email-verify';

    // per field errors
    $validation_error = wpcf7_get_validation_error( $tag->name );

    // any wpcf7 specific class to add
    $class = wpcf7_form_controls_class( 'text' );

    if ( $validation_error )
        $class .= ' wpcf7-not-valid';

    $atts = array();

    $atts['size'] = $tag->get_size_option( '40' );
    $atts['maxlength'] = $tag->get_maxlength_option();
    $atts['class'] = $tag->get_class_option( $class );
    $atts['id'] = $tag->get_id_option();
    $atts['tabindex'] = $tag->get_option( 'tabindex', 'int', true );

    // robots may look for the word "hidden"
    //$atts['aria-hidden'] = 'true';

    $value = (string) reset( $tag->values );

    if ( $tag->has_option( 'placeholder' ) || $tag->has_option( 'watermark' ) ) {
        $atts['placeholder'] = $value;
        $value = '';
    } elseif ( '' === $value ) {
        $value = $tag->get_default_option();
    }

    $value = wpcf7_get_hangover( $tag->name, $value );

    $atts['value'] = $value;
    $atts['type'] = 'text';
    $atts['name'] = $tag->name;

    $atts = wpcf7_format_atts( $atts );

    $html = sprintf( '<span class="wpcf7-form-control-wrap %s"><input %s />%s</span>',
        sanitize_html_class( $tag->name ),
        $atts,
        $validation_error
    );

    return $html;
}

function wpcf7_robottrap_validation_filter($result, $tag) {
    $tag = new WPCF7_Shortcode( $tag );

    $name = $tag->name;

    if ( ! empty( $_POST[$name] ) ) {
        $result['valid'] = false;
        $result['spam']  = true;
        $result['reason'][$name] = wpcf7_get_message('spam');
    }

    return $result;
}

function wpcf7_robottrap_domain_validation_filter($result, $tag) {
    $tag = new WPCF7_Shortcode( $tag );

    $name = $tag->name;

    $value = isset( $_POST[$name] )
        ? trim( wp_unslash( strtr( (string) $_POST[$name], "\n", " " ) ) )
        : '';
    $domain = substr( strrchr( $value, '@' ), 1 );

    //WARNING if the nameserver is down it will generate false positives !!!
    if ( ! empty( $domain ) && ! checkdnsrr( $domain, 'MX' ) ) {
        $result['valid'] = false;
        $result['spam']  = true;
        $result['reason'][$name] = wpcf7_get_message('spam');
    }

    return $result;
}

