<?php

namespace IMP;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

	/**
	 * 	Register meta boxes in plugin global config
	 *
	 *
	 * @author 		  	Goran Petrovic <support@goxpress.com>
	 * @package     WordPress
	 * @subpackage  Gox
	 * @since 						Gox 1.0.0
	 *	@see 								https://developer.wordpress.org/reference/functions/add_meta_box/
	 * @version 				1.0.0
	 *
	 **/

	register_custom_fields([
				[
					'post_types' => ['post', 'page', 'product', 'lost', 'found'],
					'id'		 		    =>	'impressionz',
					'title'		 	  => __('Impressionz','the-chameleon'),
					'desc'		   		=> __('<stong>M</stong> - Mentions in the page
																				<br /><span style="color:#4285f4;"><stong>C</stong> - Clicks</span>
																				<br /><span style="color:#5e35b1;"><stong>I</stong> - Impressions</span>
																				<br /><span style="color:#e8710a;"><stong>P</stong> - Position</span>','gox'),
					'context'	   => 'side', //position
					'fields' 	   => [ //custom fileds
								//impressionz
								[
										'name'	  	=> 'impressionz',
										'type'	  	=> 'impressionz',
										'title'	  => __('Example Text','the-chameleon'),
										'desc'	  	=> __('Example text filed description', 'the-chameleon'),
										'col'	  		=> 'col-12 col-lg-12',
										'default' => '',
										'attr'	  	=> [
													'class'							=> "form-control",
													'placeholder'	=> 'Placeholder',
													'title'							=> 'Title',
											]
									],

        ]//fields
   				]// meta box
   	]);
