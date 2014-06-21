<?php
/*
Plugin Name: Arabic Numbers
Plugin URI: http://www.kadimi.com
Description: Converts Arabic numerals into Eastern Arabic, e.g.: 3 becomes ۳, the original content is retained, on numbers are converted on the fly, before being displayed
Version: 0.1
Author: Nabil Kadimi
*/

/**
 * The plugin settings placeholder
 */
$k_an_settings = array();

/**
 * The direction
 * @todo Un-hardcode
 */
$k_an_settings[ 'direction' ] = 'w2e';

/**
 * Hook the converter function
 */
if( ! is_admin() ) {
	add_filter( 'get_the_time', 'k_arabic_convert_' . $k_an_settings[ 'direction' ] );
	add_filter( 'get_the_date', 'k_arabic_convert_' . $k_an_settings[ 'direction' ] );
	add_filter( 'the_content', 'k_arabic_convert_' . $k_an_settings[ 'direction' ] );
	add_filter( 'the_title', 'k_arabic_convert_' . $k_an_settings[ 'direction' ] );
}

/**
 * Converts Arabic numerals from western Arabic (123) to eastern Arabic (١٢٣) and vice versa
 * 
 * @param string $str The string to convert
 * @param string $direction The direction, w2e or e2w, case insensitive
 * @return string The string with numerals converted
 */
function k_arabic_convert( $str, $direction ) {

	// The conversion array
	$w2e = array(
		'0' => '٠',
		'1' => '١',
		'2' => '٢',
		'3' => '٣',
		'4' => '٤',
		'5' => '٥',
		'6' => '٦',
		'7' => '٧',
		'8' => '٨',
		'9' => '٩',
	);

	// Vaidate direction
	$direction = trim( strtolower( $direction ) );
	switch ( $direction ) {
	case 'w2e':
		$replacements = $w2e;
		break;
	case 'e2w':
		$replacements = array_flip( $w2e );
		break;
	default:
		return $str;
	}

	return k_wp_html_str_replace( $str, $replacements );
}

/**
 * Converts Arabic numerals from western Arabic (123) to eastern Arabic (١٢٣)
 * 
 * This is a helper function for k_arabic_convert
 * 
 * @param string $str The string to convert
 * @return string The string with numerals converted from western Arabic to eastern Arabic
 */
function k_arabic_convert_w2e( $str ) {

	return k_arabic_convert( $str, 'w2e' );
}

/**
 * Converts Arabic numerals from eastern Arabic (١٢٣) to western Arabic (123)
 * 
 * This is a helper function for k_arabic_convert
 * 
 * @param string $str The string to convert
 * @return string The string with numerals converted from eastern Arabic to western Arabic
 */
function k_arabic_convert_e2w( $str ) {

	return k_arabic_convert( $str, 'e2w' );
}

/**
 * Replaces text while preserving HTML tags
 * 
 * @param string $html HTML to search
 * @param array $replacements Associative array searh=>replace
 * @return string The new HTML code after replacements have been made
 * @todo Improve function documentation
 */
function k_html_str_replace( $html, $replacements ) {

	if( ! strlen( $html ) ) {
		return $html;
	}

	if( ! function_exists( 'k_html_str_replace_inner' ) ) {
		function k_html_str_replace_inner( $node, $replacements ) {
			if( $node->hasChildNodes() ) {
				$nodes = array();
				foreach ( $node->childNodes as $childNode ) {
					$nodes[] = $childNode;
				}
				foreach ( $nodes as $childNode ) {
					if ( $childNode instanceof DOMText ) {
						$text = str_replace(
							array_keys( $replacements ),
							array_values( $replacements ),
							$childNode->wholeText
						);
						$node->replaceChild( new DOMText( $text ), $childNode );
					}
					else {
						k_html_str_replace_inner( $childNode, $replacements );
					}
				}
			}
		} // function: k_html_str_replace_inner
	}

	// Make sure our HTML is in UTF-8
	$html = mb_convert_encoding( $html, 'utf-8', mb_detect_encoding( $html ) );
	$html = mb_convert_encoding( $html, 'html-entities', 'utf-8' );

	// Create a DOMDocument from HTML code
	$node = new DOMDocument();
	$node->loadHtml( $html );

	// Run the HTML safe string replacement
	k_html_str_replace_inner( $node->documentElement, $replacements );

	// Save as HTML
	$html = $node->saveHTML();
	
	// Remove DTD, <html> and <body>
	$html = preg_replace(
		'/^<!DOCTYPE.+?>/',
		'',
		str_replace(
			array( '<html>', '</html>', '<body>', '</body>' ),
			array( '', '', '', '' ),
			$html
		)
	);

	return $html;
}

/**
 * Replaces text while preserving HTML tags and shortcodes
 * 
 * @param string $html HTML to search
 * @param array $replacements Associative array searh=>replace
 * @return string The new HTML code after replacements have been made
 */
function k_wp_html_str_replace( $html, $replacements ) {

	preg_match_all(
		'/' . get_shortcode_regex() . '/s',
		$html,
		$shortcodes,
		PREG_PATTERN_ORDER
	);
	
	// Due to the flag we are using, raw shortcodes are in the array $shortcodes[0]
	$shortcodes = $shortcodes[0];

	// Leave here if no shortcodes were found
 	if( empty( $shortcodes )) {
		$html = k_html_str_replace( $html, $replacements );
		return $html;
	}

	// Build the shortcode hashes array
	foreach ( $shortcodes as $shortcode ) {
		$shortcode_hashes [ $shortcode ] = k_random_letters();
	}

	// Repalce shortcodes with hashes
	$html = str_replace(
		array_keys( $shortcode_hashes ),
		array_values( $shortcode_hashes ),
		$html
	);

	// Replace HTML
	$html = k_html_str_replace( $html, $replacements );
	
	// Restore shortcodes
	$html = str_replace(
		array_values( $shortcode_hashes ),
		array_keys( $shortcode_hashes ),
		$html
	);

	return $html;
}

/**
 * Generates a random [a-z] string
 * 
 * @param $length The length of the string
 * @return string A random string
 */
function k_random_letters( $length = 7 ) {

	$letters = 'abcdefghijklmnopqrstuvwxyz';
	$random_letters = '';
	for ( $i = 0; $i < $length; $i++ ) {
		$random_letters .= $letters[ mt_rand( 0, strlen( $letters ) - 1 ) ];
	}
	return $random_letters;
}
