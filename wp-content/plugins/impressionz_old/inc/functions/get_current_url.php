<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! function_exists( 'get_current_url' ) ) :
	/**
	 * Get current url
	 *
	 *
	 * @package WordPress
	 * @subpackage GOX
	 *
	 *
	 * @since 1.0
	 *
	 **/
	function get_current_url( $queries = TRUE ) {
		$current_url = 	( is_ssl() || force_ssl_admin() ) ? 'https://'.$_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"] : 'http://'.$_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"] ;
		if($queries) :
			$the_url = 	$current_url;
		else:
			$parts = parse_url("$current_url");
			$the_url =	( is_ssl() || force_ssl_admin() ) ? 'https://'.$parts["host"] . $parts["path"] : 'http://'.$parts["host"] . $parts["path"] ;
		endif;
		return $the_url;
	}
endif;
