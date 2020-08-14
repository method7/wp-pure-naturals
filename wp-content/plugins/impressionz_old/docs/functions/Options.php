<?php

	namespace IMP;

	if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly


	/**
	* Register Impressionz Options
	*
	*/
	register_options([
		'impressionz'=>[
				'add'											=> 'menu_page',
				'page' 									=> 'impressionz',
				'title'									=> 'Impressionz',
				'desc'										=> '',
				'menu'										=> 'main',
				'menu_title'				=> 'Content',
				'compatibility'	=> 'manage_options',
				'menu_parent'   => 'impressionz',
				'parent_page'			=> 0,
				'sections'						=>
				[
						//Content section
						[ 'slug'			=> 'imp_content',
								'title'		=> 'Content',
								'desc'			=> '<small><stong>M</stong> - Mentions in the page
																					<br><span style="color:#4285f4;"><stong>C</stong> - Clicks</span>
																					<br><span style="color:#5e35b1;"><stong>I</stong> - Impressions</span>
																					<br><span style="color:#00897b;"><stong>CTR</stong> - Click Through Rate</span>
																					<br><span style="color:#e8710a;"><stong>P</stong> - Position</span></small>',
								'fileds'	=>
								[
											[
												'title'		=> 'Google Search Console',
												'desc'			=> '',
												'name'			=> 'impressionz',
												'type'			=> 'impressionz',
												'value'		=> '',
												'attr'			=> [],
												],
									], //fields
							] //Content section
					] //Sections
				] //impressionz
		]);

		/**
		* Register impressionz_cannibalization Options
		*
		*/

		register_options([
			'impressionz_cannibalization'=>
				//general options
				[
					'add'						=> 'menu_page',
					'page' 					=> 'impressionz_cannibalization',
					'title'					=> 'Impressionz',
					'desc'						=> '', //<a href="">Help</a> |  <a href="">What is keywords cannibalization?</a>
					'menu'					=> 'main',
					'menu_title'		=> 'Cannibalization',
					'menu_parent'   => 'impressionz',
					'compatibility'	=> 'manage_options',
					'parent_page'		=> 'impressionz',

					'sections'=> [
						[
  						'slug'		=> 'general',
  						'title'		=> 'Cannibalization',
  						'desc'		=> '<small><span style="color:#4285f4;"><stong>C</stong> - Clicks</span>
						<br><span style="color:#5e35b1;"><stong>I</stong> - Impressions</span>
						<br><span style="color:#00897b;"><stong>CTR</stong> - Click Through Rate</span>
						<br><span style="color:#e8710a;"><stong>P</stong> - Position</span></small>', //<a href="">Help</a> |  <a href="">What is keywords cannibalization?</a>

  						'fileds'	=> [

									[
											'title'			=> 'Google Seach Console',
											'desc'				=> 'Google Webmaster results',
											'name'				=> 'impressionz_cannibalization',
											'value'			=> '',
											'type'				=> 'impressionz_cannibalization',
											'choices' => []
										],




							],//fileds
						],//section



					]//sections


		]]);

		/**
		* Register impressionz_settings
		*
		*/
		register_options([
				'impressionz_settings'=>[
						'add'											=> 'menu_page',
						'page' 									=> 'impressionz_settings',
						'title'									=> 'Impressionz',
						'desc'										=> '',
						'menu'										=> 'main',
						'menu_title'				=> 'Settings',
						'compatibility'	=> 'manage_options',
						'menu_parent'   => 'impressionz',
						'parent_page'			=> 0,
						'sections'						=> [
							[
								'slug'				=> 'imp_gsc',
								'title'			=> 'Google Seach Console',
								'desc'				=> '<a href="https://search.google.com/search-console/performance/search-analytics?resource_id='.site_url().'&hl=en" target="_blank"/>Site Performance</a>',
								'fileds'		=> [
									[
										'title'		=> 'Authorization',
										'desc'			=> 'You need to create a Google Search Console account before proceeding to authorization.
																						<br />Use this link to get your one-time-use access code:
																						<a href="https://accounts.google.com/o/oauth2/auth?response_type=code&access_type=offline&client_id=211755221734-qmv8fthb6vmnpkv78donuk6g6l8s7vs3.apps.googleusercontent.com&redirect_uri=urn%3Aietf%3Awg%3Aoauth%3A2.0%3Aoob&state&scope=https%3A%2F%2Fwww.googleapis.com%2Fauth%2Fwebmasters.readonly&approval_prompt=force&include_granted_scopes=true" target="_blank">Get Access Code.</a>',
										'name'			=> 'imp_authorization_code',
										'type'			=> 'impressionz_auth_code',
										'value'		=> '',
										'attr'			=> ['readonly'=>'readonly', 'placeholder'=>'Access Code'],
										'choices' => []
									],

									[
										'title'		=> 'Date',
										'desc'			=> 'Range results',
										'name'			=> prefix().'gsc_date',
										'type'			=> 'select',
										'value'		=> '28',
										'choices' => [
												'7'				=>	'Last 7 days',
												'28'			=>	'Last 28 days',
												'90'			=>	'Last 3 months',
												'180'		=>	'Last 6 months',
												'480'		=>	'Last 16 months',
											]
									],


									[
										'title'				=> 'Impressions',
										'desc'					=> 'Min impressions',
										'name'					=> prefix().'gsc_impression',
										'type'					=> 'number',
										'value'				=> '100',
										'choices' 	=> []
									],

									[
										'title'				=> 'Property',
										'desc'					=> 'Select Property',
										'name'					=> prefix().'gsc_property',
										'type'					=> 'select',
										'value'				=> get_option( prefix().'gsc_property',  get_site_url().'/' ),
										'choices' 	=> (get_option('imp_gsc_sites')) ? get_option('imp_gsc_sites') : [ get_site_url().'/' => get_site_url() ],
									],


									[
										'title'				=> 'Display',
										'desc'					=> 'Display data',
										'name'					=> prefix().'display',
										'type'					=> 'impressionz_display',
										'value'				=> '',
										'choices' 	=> [],
									],

									[
										'title'				=> 'Status',
										'desc'					=> '',
										'name'					=> 'impressionz_status',
										'type'					=> 'impressionz_status',
										'value'				=> '',
										'choices' 	=> []
									],

								], //fileds
						]//one section
					]//sections
				] //impressionz_settings
			]);


			/**
			* Register impressionz_license Options
			*
			*/
			register_options([
						'impressionz_license'=>
								[
									'add'											=> 'menu_page',
									'page' 									=> 'impressionz_license',
									'title'									=> 'Impressionz',
									'desc'										=> '',
									'menu'										=> 'main',
									'menu_title'				=> 'License',
									'compatibility'	=> 'manage_options',
									'menu_parent'   => 'impressionz',
									'parent_page'			=> 0,
									'sections'						=> [
										[
											'slug'			=> 'impressionz_license',
											'title'		=> 'Impressionz License',
											'desc'			=> '<a href="https://impressionz.io/?site='.site_url().'" target="_blank"/>Impressionz.io</a>
																							| <a href="https://impressionz.io/my-account/manage-license/?site='.site_url().'" target="_blank">Manage License</a>',
											'fileds'	=> [

													[
														'title'		=> 'License Key',
														'desc'		=> '',
														'name'		=> 'impressionz_license_key',
														'type'		=> 'impressionz_license_key',
														'value'		=> '',
														'attr'	=> ['readonly'=>'readonly'],
														'choices' => []
													],

													[
															'title'			=> 'License Message',
															'desc'				=> '',
															'name'				=> 'impressionz_license_message',
															'type'					=> 'impressionz_license_message',
															'value'				=> '100',
															'choices' => []
													],
												], //fields
											] //the section
								] //sections
							]// id
					]);
