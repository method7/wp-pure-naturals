<?php

namespace IMP;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly


if (!defined('IPM_DISPLAY')) {

		//only post and pages
		$display = json_encode( array('post', 'page') );

		//all
		//$display = json_encode( array('post', 'page', 'taxonomy', 'author', 'other') );
		define('IPM_DISPLAY', 	$display );

}


if (!defined('IPM_KW_LIMIT')) {
		define('IPM_KW_LIMIT', 	30 );
}
