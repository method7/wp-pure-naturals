<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2014-2020 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'WpssoJsonStdAdminEdit' ) ) {

	class WpssoJsonStdAdminEdit {

		private $p;

		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			$this->p->util->add_plugin_filters( $this, array( 
				'metabox_sso_edit_rows'  => 4,
				'metabox_sso_media_rows' => 4,
			) );
		}

		public function filter_metabox_sso_edit_rows( $table_rows, $form, $head_info, $mod ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			/**
			 * Move Schema options to the end of the table, just in case.
			 */
			foreach ( SucomUtil::preg_grep_keys( '/^(subsection_schema|schema_)/', $table_rows ) as $key => $row ) {
				SucomUtil::move_to_end( $table_rows, $key );
			}

			$dots               = '...';
			$read_cache         = true;
			$no_hashtags        = false;
			$maybe_hashtags     = true;
			$do_encode          = true;
			$schema_desc_md_key = array( 'seo_desc', 'og_desc' );

			/**
			 * Select option arrays.
			 */
			$schema_exp_secs = $this->p->util->get_cache_exp_secs( $this->p->lca . '_t_' );	// Default is month in seconds.
			$schema_types    = $this->p->schema->get_schema_types_select( $context = 'meta' );
			$currencies      = SucomUtil::get_currency_abbrev();

			/**
			 * Maximum option lengths.
			 */
			$og_title_max_len        = $this->p->options[ 'og_title_max_len' ];
			$schema_headline_max_len = $this->p->cf[ 'head' ][ 'limit_max' ][ 'schema_headline_len' ];
			$schema_desc_max_len     = $this->p->options[ 'schema_desc_max_len' ];		// Max. Schema Description Length.
			$schema_text_max_len     = $this->p->options[ 'schema_text_max_len' ];

			/**
			 * Default option values.
			 */
			$def_copyright_year   = $mod[ 'is_post' ] ? trim( get_post_time( 'Y', $gmt = true, $mod[ 'id' ] ) ) : '';
			$def_schema_title     = $this->p->page->get_title( $max_len = 0, '', $mod, $read_cache, $no_hashtags, $do_encode, 'og_title' );
			$def_schema_title_alt = $this->p->page->get_title( $og_title_max_len, $dots, $mod, $read_cache, $no_hashtags, $do_encode, 'og_title' );
			$def_schema_headline  = $this->p->page->get_title( $schema_headline_max_len, '', $mod, $read_cache, $no_hashtags, $do_encode, 'og_title' );
			$def_schema_desc      = $this->p->page->get_description( $schema_desc_max_len, $dots, $mod, $read_cache, $no_hashtags, $do_encode, $schema_desc_md_key );
			$def_schema_text      = $this->p->page->get_text( $schema_text_max_len, '', $mod, $read_cache, $no_hashtags, $do_encode, $md_key = 'none' );
			$def_schema_keywords  = $this->p->page->get_keywords( $mod, $read_cache, $md_key = 'none' );

			/**
			 * Organization variables.
			 */
			$org_req_msg    = $this->p->msgs->maybe_ext_required( 'wpssoorg' );
			$org_disable    = empty( $org_req_msg ) ? false : true;
			$org_site_names = $this->p->util->get_form_cache( 'org_site_names', $add_none = true );

			/**
			 * Person variables.
			 */
			$person_names = $this->p->util->get_form_cache( 'person_names', $add_none = true );

			/**
			 * Place variables.
			 */
			$plm_req_msg     = $this->p->msgs->maybe_ext_required( 'wpssoplm' );
			$plm_disable     = empty( $plm_req_msg ) ? false : true;
			$plm_place_names = $this->p->util->get_form_cache( 'place_names', $add_none = true );

			/**
			 * Javascript classes to hide/show rows by selected schema type.
			 */
			$schema_type_row_class             = WpssoSchema::get_schema_type_row_class( 'schema_type' );
			$schema_review_item_type_row_class = WpssoSchema::get_schema_type_row_class( 'schema_review_item_type' );

			/**
			 * Metabox form rows.
			 */
			$form_rows = array(
				'wpssojson_pro_feature_msg' => array(
					'table_row' => '<td colspan="2">' . $this->p->msgs->pro_feature( 'wpssojson' ) . '</td>',
				),

				/**
				 * All Schema Types.
				 */
				'schema_title' => array(
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Name / Title', 'option label', 'wpsso-schema-json-ld' ),
					'tooltip'  => 'meta-schema_title',
					'content'  => $form->get_no_input_value( $def_schema_title, $css_class = 'wide' ),
				),
				'schema_title_alt' => array(
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Alternate Name', 'option label', 'wpsso-schema-json-ld' ),
					'tooltip'  => 'meta-schema_title_alt',
					'content'  => $form->get_no_input_value( $def_schema_title_alt, $css_class = 'wide' ),
				),
				'schema_desc' => array(
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Description', 'option label', 'wpsso-schema-json-ld' ),
					'tooltip'  => 'meta-schema_desc',
					'content'  => $form->get_no_textarea_value( $def_schema_desc, $css_class = '', $css_id = '',
						$schema_desc_max_len ),
				),
				'schema_addl_type_url' => array(
					'tr_class' => $form->get_css_class_hide_prefix( 'basic', 'schema_addl_type_url' ),
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Microdata Type URLs', 'option label', 'wpsso-schema-json-ld' ),
					'tooltip'  => 'meta-schema_addl_type_url',
					'content'  => $form->get_no_input_value( '', $css_class = 'wide', $css_id = '', '', $repeat = 2 ),
				),
				'schema_sameas_url' => array(
					'tr_class' => $form->get_css_class_hide_prefix( 'basic', 'schema_sameas_url' ),
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Same-As URLs', 'option label', 'wpsso-schema-json-ld' ),
					'tooltip'  => 'meta-schema_sameas_url',
					'content'  => $form->get_no_input_value( '', $css_class = 'wide', $css_id = '', '', $repeat = 2 ),
				),

				/**
				 * Schema Creative Work.
				 */
				'subsection_creative_work' => array(
					'tr_class' => $schema_type_row_class[ 'creative_work' ],
					'td_class' => 'subsection',
					'header'   => 'h5',
					'label'    => _x( 'Creative Work Information', 'metabox title', 'wpsso-schema-json-ld' ),
				),
				'schema_ispartof_url' => array(
					'tr_class' => $schema_type_row_class[ 'creative_work' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Is Part of URL', 'option label', 'wpsso-schema-json-ld' ),
					'tooltip'  => 'meta-schema_ispartof_url',
					'content'  => $form->get_no_input_value( '', $css_class = 'wide', $css_id = '', '', $repeat = 2 ),
				),
				'schema_headline' => array(
					'tr_class' => $schema_type_row_class[ 'creative_work' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Headline', 'option label', 'wpsso-schema-json-ld' ),
					'tooltip'  => 'meta-schema_headline',
					'content'  => $form->get_no_input_value( $def_schema_headline, $css_class = 'wide' ),
				),
				'schema_text' => array(
					'tr_class' => $schema_type_row_class[ 'creative_work' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Full Text', 'option label', 'wpsso-schema-json-ld' ),
					'tooltip'  => 'meta-schema_text',
					'content'  => $form->get_no_textarea_value( $def_schema_text, $css_class = 'full_text' ),
				),
				'schema_keywords' => array(
					'tr_class' => $schema_type_row_class[ 'creative_work' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Keywords', 'option label', 'wpsso-schema-json-ld' ),
					'tooltip'  => 'meta-schema_keywords',
					'content'  => $form->get_no_input_value( $def_schema_keywords, $css_class = 'wide' ),
				),
				'schema_lang' => array(
					'tr_class' => $schema_type_row_class[ 'creative_work' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Language', 'option label', 'wpsso-schema-json-ld' ),
					'tooltip'  => 'meta-schema_lang',
					'content'  => $form->get_no_select( 'schema_lang', SucomUtil::get_available_locales(), 'locale' ),
				),
				'schema_family_friendly' => array(
					'tr_class' => $schema_type_row_class[ 'creative_work' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Family Friendly', 'option label', 'wpsso-schema-json-ld' ),
					'tooltip'  => 'meta-schema_family_friendly',
					'content'  => $form->get_no_select_none( 'schema_family_friendly',
						$this->p->cf[ 'form' ][ 'yes_no' ], $css_class = 'yes-no', $css_id = '', $is_assoc = true ),
				),
				'schema_copyright_year' => array(
					'tr_class' => $schema_type_row_class[ 'creative_work' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Copyright Year', 'option label', 'wpsso-schema-json-ld' ),
					'tooltip'  => 'meta-schema_copyright_year',
					'content'  => $form->get_no_input_value( $def_copyright_year, $css_class = 'year' ),
				),
				'schema_license_url' => array(
					'tr_class' => $schema_type_row_class[ 'creative_work' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'License URL', 'option label', 'wpsso-schema-json-ld' ),
					'tooltip'  => 'meta-schema_license_url',
					'content'  => $form->get_no_input_value( '', $css_class = 'wide' ),
				),
				'schema_pub_org_id' => array(
					'tr_class' => $schema_type_row_class[ 'creative_work' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Publisher (Org)', 'option label', 'wpsso-schema-json-ld' ),
					'tooltip'  => 'meta-schema_pub_org_id',
					'content'  => $form->get_no_select( 'schema_pub_org_id', $org_site_names,
						$css_class = 'long_name', $css_id = '', $is_assoc = true ) . $org_req_msg,
				),
				'schema_pub_person_id' => array(
					'tr_class' => $schema_type_row_class[ 'creative_work' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Publisher (Person)', 'option label', 'wpsso-schema-json-ld' ),
					'tooltip'  => 'meta-schema_pub_person_id',
					'content'  => $form->get_no_select( 'schema_pub_person_id', $person_names,
						$css_class = 'long_name', $css_id = '', $is_assoc = true ),
				),
				'schema_prov_org_id' => array(
					'tr_class' => $schema_type_row_class[ 'creative_work' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Service Prov. (Org)', 'option label', 'wpsso-schema-json-ld' ),
					'tooltip'  => 'meta-schema_prov_org_id',
					'content'  => $form->get_no_select( 'schema_prov_org_id', $org_site_names,
						$css_class = 'long_name', $css_id = '', $is_assoc = true ) . $org_req_msg,
				),
				'schema_prov_person_id' => array(
					'tr_class' => $schema_type_row_class[ 'creative_work' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Service Prov. (Person)', 'option label', 'wpsso-schema-json-ld' ),
					'tooltip'  => 'meta-schema_prov_person_id',
					'content'  => $form->get_no_select( 'schema_prov_person_id', $person_names,
						$css_class = 'long_name', $css_id = '', $is_assoc = true ),
				),

				/**
				 * Schema Creative Work / Book / Audiobook.
				 */
				'subsection_book_audio' => array(
					'tr_class' => $schema_type_row_class[ 'book_audio' ],
					'td_class' => 'subsection',
					'header'   => 'h5',
					'label'    => _x( 'Audiobook Information', 'metabox title', 'wpsso-schema-json-ld' ),
				),
				'schema_book_audio_duration_time' => array(
					'tr_class' => $schema_type_row_class[ 'book_audio' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Duration', 'option label', 'wpsso-schema-json-ld' ),
					'tooltip'  => 'meta-schema_book_audio_duration_time',
					'content'  => $this->get_time_dhms( $form ),
				),

				/**
				 * Schema Creative Work / How-To.
				 */
				'subsection_howto' => array(
					'tr_class' => $schema_type_row_class[ 'how_to' ],
					'td_class' => 'subsection',
					'header'   => 'h5',
					'label'    => _x( 'How-To Information', 'metabox title', 'wpsso-schema-json-ld' ),
				),
				'schema_howto_yield' => array(
					'tr_class' => $schema_type_row_class[ 'how_to' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'How-To Makes', 'option label', 'wpsso-schema-json-ld' ),
					'tooltip'  => 'meta-schema_howto_yield',
					'content'  => $form->get_no_input_value( '', 'long_name' ),
				),
				'schema_howto_prep_time' => array(
					'tr_class' => $schema_type_row_class[ 'how_to' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Preparation Time', 'option label', 'wpsso-schema-json-ld' ),
					'tooltip'  => 'meta-schema_howto_prep_time',
					'content'  => $this->get_time_dhms( $form ),
				),
				'schema_howto_total_time' => array(
					'tr_class' => $schema_type_row_class[ 'how_to' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Total Time', 'option label', 'wpsso-schema-json-ld' ),
					'tooltip'  => 'meta-schema_howto_total_time',
					'content'  => $this->get_time_dhms( $form ),
				),
				'schema_howto_supplies' => array(
					'tr_class' => $schema_type_row_class[ 'how_to' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'How-To Supplies', 'option label', 'wpsso-schema-json-ld' ),
					'tooltip'  => 'meta-schema_howto_supplies',
					'content'  => $form->get_no_input_value( '', 'long_name', $css_id = '', '', $repeat = 5 ),
				),
				'schema_howto_tools' => array(
					'tr_class' => $schema_type_row_class[ 'how_to' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'How-To Tools', 'option label', 'wpsso-schema-json-ld' ),
					'tooltip'  => 'meta-schema_howto_tools',
					'content'  => $form->get_no_input_value( '', 'long_name', $css_id = '', '', $repeat = 5 ),
				),
				'schema_howto_steps' => array(
					'tr_class' => $schema_type_row_class[ 'how_to' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'How-To Steps', 'option label', 'wpsso-schema-json-ld' ),
					'tooltip'  => 'meta-schema_howto_steps',
					'content'  => $form->get_no_mixed_multi( array(
						'schema_howto_step_section' => array(
							'input_type'    => 'radio',
							'input_class'   => 'howto_step_section',
							'input_content' => _x( '%1$s Step Details %2$s or New Section Details', 'option label', 'wpsso-schema-json-ld' ),
							'input_values'  => array( 0, 1 ),
							'input_default' => 0,
						),
						'schema_howto_step' => array(
							'input_label' => _x( 'Name', 'option label', 'wpsso-schema-json-ld' ),
							'input_type'  => 'text',
							'input_class' => 'wide howto_step_name is_required',
						),
						'schema_howto_step_text' => array(
							'input_label' => _x( 'Description', 'option label', 'wpsso-schema-json-ld' ),
							'input_type'  => 'textarea',
							'input_class' => 'wide howto_step_text',
						),
						'schema_howto_step_img' => array(
							'input_label' => _x( 'Image ID', 'option label', 'wpsso-schema-json-ld' ),
							'input_type'  => 'image',
							'input_class' => 'howto_step_img',
						),
					), $css_class = '', $css_id = 'schema_howto_step',
						$start_num = 0, $max_input = 3, $show_first = 3 ),
				),

				/**
				 * Schema Creative Work / How-To / Recipe.
				 */
				'subsection_recipe' => array(
					'tr_class' => $schema_type_row_class[ 'recipe' ],
					'td_class' => 'subsection',
					'header'   => 'h5',
					'label'    => _x( 'Recipe Information', 'metabox title', 'wpsso-schema-json-ld' ),
				),
				'schema_recipe_cuisine' => array(
					'tr_class' => $schema_type_row_class[ 'recipe' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Recipe Cuisine', 'option label', 'wpsso-schema-json-ld' ),
					'tooltip'  => 'meta-schema_recipe_cuisine',
					'content'  => $form->get_no_input_value( '', 'long_name' ),
				),
				'schema_recipe_course' => array(
					'tr_class' => $schema_type_row_class[ 'recipe' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Recipe Course', 'option label', 'wpsso-schema-json-ld' ),
					'tooltip'  => 'meta-schema_recipe_course',
					'content'  => $form->get_no_input_value( '', 'long_name' ),
				),
				'schema_recipe_yield' => array(
					'tr_class' => $schema_type_row_class[ 'recipe' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Recipe Makes', 'option label', 'wpsso-schema-json-ld' ),
					'tooltip'  => 'meta-schema_recipe_yield',
					'content'  => $form->get_no_input_value( '', 'long_name' ),
				),
				'schema_recipe_cook_method' => array(
					'tr_class' => $schema_type_row_class[ 'recipe' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Cooking Method', 'option label', 'wpsso-schema-json-ld' ),
					'tooltip'  => 'meta-schema_recipe_cook_method',
					'content'  => $form->get_no_input_value( '', 'long_name' ),
				),
				'schema_recipe_prep_time' => array(
					'tr_class' => $schema_type_row_class[ 'recipe' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Preparation Time', 'option label', 'wpsso-schema-json-ld' ),
					'tooltip'  => 'meta-schema_recipe_prep_time',
					'content'  => $this->get_time_dhms( $form ),
				),
				'schema_recipe_cook_time' => array(
					'tr_class' => $schema_type_row_class[ 'recipe' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Cooking Time', 'option label', 'wpsso-schema-json-ld' ),
					'tooltip'  => 'meta-schema_recipe_cook_time',
					'content'  => $this->get_time_dhms( $form ),
				),
				'schema_recipe_total_time' => array(
					'tr_class' => $schema_type_row_class[ 'recipe' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Total Time', 'option label', 'wpsso-schema-json-ld' ),
					'tooltip'  => 'meta-schema_recipe_total_time',
					'content'  => $this->get_time_dhms( $form ),
				),
				'schema_recipe_ingredients' => array(
					'tr_class' => $schema_type_row_class[ 'recipe' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Recipe Ingredients', 'option label', 'wpsso-schema-json-ld' ),
					'tooltip'  => 'meta-schema_recipe_ingredients',
					'content'  => $form->get_no_input_value( '', 'long_name', $css_id = '', '', $repeat = 5 ),
				),
				'schema_recipe_instructions' => array(
					'tr_class' => $schema_type_row_class[ 'recipe' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Recipe Instructions', 'option label', 'wpsso-schema-json-ld' ),
					'tooltip'  => 'meta-schema_recipe_instructions',
					'content'  => $form->get_no_input_value( '', $css_class = 'wide', $css_id = '', '', $repeat = 5 ),
				),

				/**
				 * Schema Creative Work / How-To / Recipe - Nutrition Information.
				 */
				'subsection_recipe_nutrition' => array(
					'tr_class' => $schema_type_row_class[ 'recipe' ],
					'td_class' => 'subsection',
					'header'   => 'h5',
					'label'    => _x( 'Nutrition Information per Serving', 'metabox title', 'wpsso-schema-json-ld' ),
				),
				'schema_recipe_nutri_serv' => array(
					'tr_class' => $schema_type_row_class[ 'recipe' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Serving Size', 'option label', 'wpsso-schema-json-ld' ),
					'tooltip'  => 'meta-schema_recipe_nutri_serv',
					'content'  => $form->get_no_input_value( '', 'long_name is_required' ),
				),
				'schema_recipe_nutri_cal' => array(
					'tr_class' => $schema_type_row_class[ 'recipe' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Calories', 'option label', 'wpsso-schema-json-ld' ),
					'tooltip'  => 'meta-schema_recipe_nutri_cal',
					'content'  => $form->get_no_input_value( '', 'medium' ) . ' ' . 
						_x( 'calories', 'option comment', 'wpsso-schema-json-ld' ),
				),
				'schema_recipe_nutri_prot' => array(
					'tr_class' => $schema_type_row_class[ 'recipe' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Protein', 'option label', 'wpsso-schema-json-ld' ),
					'tooltip'  => 'meta-schema_recipe_nutri_prot',
					'content'  => $form->get_no_input_value( '', 'medium' ) . ' ' . 
						_x( 'grams of protein', 'option comment', 'wpsso-schema-json-ld' ),
				),
				'schema_recipe_nutri_fib' => array(
					'tr_class' => $schema_type_row_class[ 'recipe' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Fiber', 'option label', 'wpsso-schema-json-ld' ),
					'tooltip'  => 'meta-schema_recipe_nutri_fib',
					'content'  => $form->get_no_input_value( '', 'medium' ) . ' ' . 
						_x( 'grams of fiber', 'option comment', 'wpsso-schema-json-ld' ),
				),
				'schema_recipe_nutri_carb' => array(
					'tr_class' => $schema_type_row_class[ 'recipe' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Carbohydrates', 'option label', 'wpsso-schema-json-ld' ),
					'tooltip'  => 'meta-schema_recipe_nutri_carb',
					'content'  => $form->get_no_input_value( '', 'medium' ) . ' ' . 
						_x( 'grams of carbohydrates', 'option comment', 'wpsso-schema-json-ld' ),
				),
				'schema_recipe_nutri_sugar' => array(
					'tr_class' => $schema_type_row_class[ 'recipe' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Sugar', 'option label', 'wpsso-schema-json-ld' ),
					'tooltip'  => 'meta-schema_recipe_nutri_sugar',
					'content'  => $form->get_no_input_value( '', 'medium' ) . ' ' . 
						_x( 'grams of sugar', 'option comment', 'wpsso-schema-json-ld' ),
				),
				'schema_recipe_nutri_sod' => array(
					'tr_class' => $schema_type_row_class[ 'recipe' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Sodium', 'option label', 'wpsso-schema-json-ld' ),
					'tooltip'  => 'meta-schema_recipe_nutri_sod',
					'content'  => $form->get_no_input_value( '', 'medium' ) . ' ' . 
						_x( 'milligrams of sodium', 'option comment', 'wpsso-schema-json-ld' ),
				),
				'schema_recipe_nutri_fat' => array(
					'tr_class' => $schema_type_row_class[ 'recipe' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Fat', 'option label', 'wpsso-schema-json-ld' ),
					'tooltip'  => 'meta-schema_recipe_nutri_fat',
					'content'  => $form->get_no_input_value( '', 'medium' ) . ' ' . 
						_x( 'grams of fat', 'option comment', 'wpsso-schema-json-ld' ),
				),
				'schema_recipe_nutri_sat_fat' => array(
					'tr_class' => $schema_type_row_class[ 'recipe' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Saturated Fat', 'option label', 'wpsso-schema-json-ld' ),
					'tooltip'  => 'meta-schema_recipe_nutri_sat_fat',
					'content'  => $form->get_no_input_value( '', 'medium' ) . ' ' . 
						_x( 'grams of saturated fat', 'option comment', 'wpsso-schema-json-ld' ),
				),
				'schema_recipe_nutri_unsat_fat' => array(
					'tr_class' => $schema_type_row_class[ 'recipe' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Unsaturated Fat', 'option label', 'wpsso-schema-json-ld' ),
					'tooltip'  => 'meta-schema_recipe_nutri_unsat_fat',
					'content'  => $form->get_no_input_value( '', 'medium' ) . ' ' . 
						_x( 'grams of unsaturated fat', 'option comment', 'wpsso-schema-json-ld' ),
				),
				'schema_recipe_nutri_trans_fat' => array(
					'tr_class' => $schema_type_row_class[ 'recipe' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Trans Fat', 'option label', 'wpsso-schema-json-ld' ),
					'tooltip'  => 'meta-schema_recipe_nutri_trans_fat',
					'content'  => $form->get_no_input_value( '', 'medium' ) . ' ' . 
						_x( 'grams of trans fat', 'option comment', 'wpsso-schema-json-ld' ),
				),
				'schema_recipe_nutri_chol' => array(
					'tr_class' => $schema_type_row_class[ 'recipe' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Cholesterol', 'option label', 'wpsso-schema-json-ld' ),
					'tooltip'  => 'meta-schema_recipe_nutri_chol',
					'content'  => $form->get_no_input_value( '', 'medium' ) . ' ' . 
						_x( 'milligrams of cholesterol', 'option comment', 'wpsso-schema-json-ld' ),
				),

				/**
				 * Schema Creative Work / Movie.
				 */
				'subsection_movie' => array(
					'tr_class' => $schema_type_row_class[ 'movie' ],
					'td_class' => 'subsection',
					'header'   => 'h5',
					'label'    => _x( 'Movie Information', 'metabox title', 'wpsso-schema-json-ld' ),
				),
				'schema_movie_actor_person_names' => array(
					'tr_class' => $schema_type_row_class[ 'movie' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Cast Names', 'option label', 'wpsso-schema-json-ld' ),
					'tooltip'  => 'meta-schema_movie_actor_person_names',
					'content'  => $form->get_no_input_value( '', $css_class = 'long_name', $css_id = '', '', $repeat = 5 ),
				),
				'schema_movie_director_person_names' => array(
					'tr_class' => $schema_type_row_class[ 'movie' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Director Names', 'option label', 'wpsso-schema-json-ld' ),
					'tooltip'  => 'meta-schema_movie_director_person_names',
					'content'  => $form->get_no_input_value( '', $css_class = 'long_name', $css_id = '', '', $repeat = 2 ),
				),
				'schema_movie_prodco_org_id' => array(
					'tr_class' => $schema_type_row_class[ 'movie' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Production Company', 'option label', 'wpsso-schema-json-ld' ),
					'tooltip'  => 'meta-schema_movie_prodco_org_id',
					'content'  => $form->get_no_select( 'schema_movie_prodco_org_id', $org_site_names,
						$css_class = 'long_name', $css_id = '', $is_assoc = true ) . $org_req_msg,
				),
				'schema_movie_duration_time' => array(
					'tr_class' => $schema_type_row_class[ 'movie' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Movie Runtime', 'option label', 'wpsso-schema-json-ld' ),
					'tooltip'  => 'meta-schema_movie_duration_time',
					'content'  => $this->get_time_dhms( $form ),
				),

				/**
				 * Schema Creative Work / Review.
				 */
				'subsection_review' => array(
					'tr_class' => $schema_type_row_class[ 'review' ],
					'td_class' => 'subsection',
					'header'   => 'h5',
					'label'    => _x( 'Review Information', 'metabox title', 'wpsso-schema-json-ld' ),
				),
				'schema_review_rating' => array(	// Included as schema.org/Rating, not schema.org/aggregateRating.
					'tr_class' => $schema_type_row_class[ 'review' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Review Rating', 'option label', 'wpsso-schema-json-ld' ),
					'tooltip'  => 'meta-schema_review_rating',
					'content'  => $form->get_no_input_value( $form->defaults[ 'schema_review_rating' ], 'short is_required' ) . 
						' ' . _x( 'from', 'option comment', 'wpsso-schema-json-ld' ) . ' ' . 
						$form->get_no_input_value( $form->defaults[ 'schema_review_rating_from' ], 'short' ) . 
						' ' . _x( 'to', 'option comment', 'wpsso-schema-json-ld' ) . ' ' . 
						$form->get_no_input_value( $form->defaults[ 'schema_review_rating_to' ], 'short' ),
				),
				'schema_review_rating_alt_name' => array(
					'tr_class' => $schema_type_row_class[ 'review' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Rating Value Name', 'option label', 'wpsso-schema-json-ld' ),
					'tooltip'  => 'meta-schema_review_rating_alt_name',
					'content'  => $form->get_no_input_value(),
				),

				/**
				 * Schema Creative Work / Review - Subject.
				 */
				'subsection_review_item' => array(
					'tr_class' => $schema_type_row_class[ 'review' ],
					'td_class' => 'subsection',
					'header'   => 'h4',
					'label'    => _x( 'Subject of the Review', 'metabox title', 'wpsso-schema-json-ld' ),
				),
				'schema_review_item_type' => array(
					'tr_class' => $schema_type_row_class[ 'review' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Subject Webpage Type', 'option label', 'wpsso-schema-json-ld' ),
					'tooltip'  => 'meta-schema_review_item_type',
					'content'  => $form->get_no_select( 'schema_review_item_type', $schema_types, $css_class = 'schema_type', $css_id = '',
						$is_assoc = true, $selected = false, $event_names = array( 'on_focus_load_json', 'on_show_unhide_rows' ),
							$event_args = array(
								'json_var'  => 'schema_types',
								'exp_secs'  => $schema_exp_secs,	// Create and read from a javascript URL.
								'is_transl' => true,			// No label translation required.
								'is_sorted' => true,			// No label sorting required.
							)
						),
				),
				'schema_review_item_url' => array(
					'tr_class' => $schema_type_row_class[ 'review' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Subject Webpage URL', 'option label', 'wpsso-schema-json-ld' ),
					'tooltip'  => 'meta-schema_review_item_url',
					'content'  => $form->get_no_input_value( '', $css_class = 'wide is_required' ),
				),
				'schema_review_item_sameas_url' => array(
					'tr_class' => $form->get_css_class_hide_prefix( 'basic', 'schema_review_item_sameas_url' ),
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Subject Same-As URLs', 'option label', 'wpsso-schema-json-ld' ),
					'tooltip'  => 'meta-schema_review_item_sameas_url',
					'content'  => $form->get_no_input_value( '', $css_class = 'wide', $css_id = '', '', $repeat = 2 ),
				),
				'schema_review_item_name' => array(
					'tr_class' => $schema_type_row_class[ 'review' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Subject Name', 'option label', 'wpsso-schema-json-ld' ),
					'tooltip'  => 'meta-schema_review_item_name',
					'content'  => $form->get_no_input_value( '', $css_class = 'wide is_required' ),
				),
				'schema_review_item_desc' => array(
					'tr_class' => $schema_type_row_class[ 'review' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Subject Description', 'option label', 'wpsso-schema-json-ld' ),
					'tooltip'  => 'meta-schema_review_item_desc',
					'content'  => $form->get_no_textarea_value( '' ),
				),
				'schema_review_item_img_id' => array(
					'tr_class' => $schema_type_row_class[ 'review' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Subject Image ID', 'option label', 'wpsso-schema-json-ld' ),
					'tooltip'  => 'meta-schema_review_item_img_id',
					'content'  => $form->get_no_input_image_upload( 'schema_review_item_img' ),
				),
				'schema_review_item_img_url' => array(
					'tr_class' => $schema_type_row_class[ 'review' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'or an Image URL', 'option label', 'wpsso-schema-json-ld' ),
					'tooltip'  => 'meta-schema_review_item_img_url',
					'content'  => $form->get_no_input_value( '' ),
				),

				/**
				 * Schema Creative Work / Review - Subject: Creative Work.
				 */
				'subsection_review_item_cw' => array(
					'tr_class' => 'hide_schema_type ' . $schema_review_item_type_row_class[ 'creative_work' ],
					'td_class' => 'subsection',
					'header'   => 'h5',
					'label'    => _x( 'Creative Work Subject Information', 'metabox title', 'wpsso-schema-json-ld' ),
				),
				'schema_review_item_cw_author_type' => array(
					'tr_class' => 'hide_schema_type ' . $schema_review_item_type_row_class[ 'creative_work' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'C.W. Author Type', 'option label', 'wpsso-schema-json-ld' ),
					'tooltip'  => 'meta-schema_review_item_cw_author_type',
					'content'  => $form->get_no_select( 'schema_review_item_cw_author_type', $this->p->cf[ 'form' ][ 'author_types' ] ),
				),
				'schema_review_item_cw_author_name' => array(
					'tr_class' => 'hide_schema_type ' . $schema_review_item_type_row_class[ 'creative_work' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'C.W. Author Name', 'option label', 'wpsso-schema-json-ld' ),
					'tooltip'  => 'meta-schema_review_item_cw_author_name',
					'content'  => $form->get_no_input_value( '', $css_class = 'wide' ),
				),
				'schema_review_item_cw_author_url' => array(
					'tr_class' => 'hide_schema_type ' . $schema_review_item_type_row_class[ 'creative_work' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'C.W. Author URL', 'option label', 'wpsso-schema-json-ld' ),
					'tooltip'  => 'meta-schema_review_item_cw_author_url',
					'content'  => $form->get_no_input_value( '', $css_class = 'wide' ),
				),
				'schema_review_item_cw_pub' => array(
					'tr_class' => 'hide_schema_type ' . $schema_review_item_type_row_class[ 'creative_work' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'C.W. Published Date', 'option label', 'wpsso-schema-json-ld' ),
					'tooltip'  => 'meta-schema_review_item_cw_pub',
					'content'  => $form->get_no_date_time_tz( 'schema_review_item_cw_pub' ),
				),
				'schema_review_item_cw_created' => array(
					'tr_class' => 'hide_schema_type ' . $schema_review_item_type_row_class[ 'creative_work' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'C.W. Created Date', 'option label', 'wpsso-schema-json-ld' ),
					'tooltip'  => 'meta-schema_review_item_cw_created',
					'content'  => $form->get_no_date_time_tz( 'schema_review_item_cw_created' ),
				),

				/**
				 * Schema Creative Work / Review / Claim Review.
				 */
				'subsection_review_claim' => array(
					'tr_class' => $schema_type_row_class[ 'review_claim' ],
					'td_class' => 'subsection',
					'header'   => 'h5',
					'label'    => _x( 'Claim Subject Information', 'metabox title', 'wpsso-schema-json-ld' ),
				),
				'schema_review_claim_reviewed' => array(
					'tr_class' => $schema_type_row_class[ 'review_claim' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Short Summary of Claim', 'option label', 'wpsso-schema-json-ld' ),
					'tooltip'  => 'meta-schema_review_claim_reviewed',
					'content'  => $form->get_no_input_value( '', $css_class = 'wide' ),
				),
				'schema_review_claim_first_url' => array(
					'tr_class' => $schema_type_row_class[ 'review_claim' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'First Appearance URL', 'option label', 'wpsso-schema-json-ld' ),
					'tooltip'  => 'meta-schema_review_claim_first_url',
					'content'  => $form->get_no_input_value( '', $css_class = 'wide' ),
				),

				/**
				 * Schema Creative Work / Software Application.
				 */
				'subsection_software_app' => array(
					'tr_class' => $schema_type_row_class[ 'software_app' ],
					'td_class' => 'subsection',
					'header'   => 'h5',
					'label'    => _x( 'Software Application Information', 'metabox title', 'wpsso-schema-json-ld' ),
				),
				'schema_software_app_os' => array(
					'tr_class' => $schema_type_row_class[ 'software_app' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Operating System', 'option label', 'wpsso-schema-json-ld' ),
					'tooltip'  => 'meta-schema_software_app_os',
					'content'  => $form->get_no_input_value( '', $css_class = 'wide' ),
				),
				'schema_software_app_cat' => array(
					'tr_class' => $schema_type_row_class[ 'software_app' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Application Category', 'option label', 'wpsso-schema-json-ld' ),
					'tooltip'  => 'meta-schema_software_app_cat',
					'content'  => $form->get_no_input_value( '', $css_class = 'wide' ),
				),

				/**
				 * Schema Creative Work / Web Page / QA Page.
				 */
				'subsection_qa' => array(
					'tr_class' => $schema_type_row_class[ 'qa' ],
					'td_class' => 'subsection',
					'header'   => 'h5',
					'label'    => _x( 'QA Page Information', 'metabox title', 'wpsso-schema-json-ld' ),
				),
				'schema_qa_desc' => array(
					'tr_class' => $schema_type_row_class[ 'qa' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'QA Heading', 'option label', 'wpsso-schema-json-ld' ),
					'tooltip'  => 'meta-schema_qa_desc',
					'content'  => $form->get_no_input_value( '', $css_class = 'wide' ),
				),

				/**
				 * Schema Event.
				 */
				'subsection_event' => array(
					'tr_class' => $schema_type_row_class[ 'event' ],
					'td_class' => 'subsection',
					'header'   => 'h5',
					'label'    => _x( 'Event Information', 'metabox title', 'wpsso-schema-json-ld' ),
				),
				'schema_event_lang' => array(
					'tr_class' => $schema_type_row_class[ 'event' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Event Language', 'option label', 'wpsso-schema-json-ld' ),
					'tooltip'  => 'meta-schema_event_lang',
					'content'  => $form->get_no_select( 'schema_event_lang', SucomUtil::get_available_locales(), 'locale' ),
				),
				'schema_event_attendance' => array(
					'tr_class' => $schema_type_row_class[ 'event' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Event Attendance', 'option label', 'wpsso-schema-json-ld' ),
					'tooltip'  => 'meta-schema_event_attendance',
					'content'  => $form->get_no_select( 'schema_event_attendance', $this->p->cf[ 'form' ][ 'event_attendance' ],
						$css_class = '', $css_id = '', $is_assoc = true ),
				),
				'schema_event_online_url' => array(
					'tr_class' => $schema_type_row_class[ 'event' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Event Online URL', 'option label', 'wpsso-schema-json-ld' ),
					'tooltip'  => 'meta-schema_event_online_url',
					'content'  => $form->get_no_input_value( '', $css_class = 'wide' ),
				),
				'schema_event_location_id' => array(
					'tr_class' => $schema_type_row_class[ 'event' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Event Physical Venue', 'option label', 'wpsso-schema-json-ld' ),
					'tooltip'  => 'meta-schema_event_location_id',
					'content'  => $form->get_no_select( 'schema_event_location_id', $plm_place_names,
						$css_class = 'long_name', $css_id = '', $is_assoc = true ) . $plm_req_msg,
				),
				'schema_event_organizer_org_id' => array(
					'tr_class' => $schema_type_row_class[ 'event' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Organizer (Org)', 'option label', 'wpsso-schema-json-ld' ),
					'tooltip'  => 'meta-schema_event_organizer_org_id',
					'content'  => $form->get_no_select( 'schema_event_organizer_org_id', $org_site_names,
						$css_class = 'long_name', $css_id = '', $is_assoc = true ) . $org_req_msg,
				),
				'schema_event_organizer_person_id' => array(
					'tr_class' => $schema_type_row_class[ 'event' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Organizer (Person)', 'option label', 'wpsso-schema-json-ld' ),
					'tooltip'  => 'meta-schema_event_organizer_person_id',
					'content'  => $form->get_no_select( 'schema_event_organizer_person_id', $person_names,
						$css_class = 'long_name' ),
				),
				'schema_event_performer_org_id' => array(
					'tr_class' => $schema_type_row_class[ 'event' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Performer (Org)', 'option label', 'wpsso-schema-json-ld' ),
					'tooltip'  => 'meta-schema_event_performer_org_id',
					'content'  => $form->get_no_select( 'schema_event_performer_org_id', $org_site_names,
						$css_class = 'long_name', $css_id = '', $is_assoc = true ) . $org_req_msg,
				),
				'schema_event_performer_person_id' => array(
					'tr_class' => $schema_type_row_class[ 'event' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Performer (Person)', 'option label', 'wpsso-schema-json-ld' ),
					'tooltip'  => 'meta-schema_event_performer_person_id',
					'content'  => $form->get_no_select( 'schema_event_performer_person_id', $person_names,
						$css_class = 'long_name' ),
				),
				'schema_event_status' => array(
					'tr_class' => $schema_type_row_class[ 'event' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Event Status', 'option label', 'wpsso-schema-json-ld' ),
					'tooltip'  => 'meta-schema_event_status',
					'content'  => $form->get_no_select( 'schema_event_status', $this->p->cf[ 'form' ][ 'event_status' ],
						$css_class = '', $css_id = '', $is_assoc = true ),
				),
				'schema_event_start' => array(
					'tr_class' => $schema_type_row_class[ 'event' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Event Start', 'option label', 'wpsso-schema-json-ld' ),
					'tooltip'  => 'meta-schema_event_start',
					'content'  => $form->get_no_date_time_tz( 'schema_event_start' ),
				),
				'schema_event_end' => array(
					'tr_class' => $schema_type_row_class[ 'event' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Event End', 'option label', 'wpsso-schema-json-ld' ),
					'tooltip'  => 'meta-schema_event_end',
					'content'  => $form->get_no_date_time_tz( 'schema_event_end' ),
				),
				'schema_event_previous' => array(
					'tr_class' => $schema_type_row_class[ 'event' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Event Previous Start', 'option label', 'wpsso-schema-json-ld' ),
					'tooltip'  => 'meta-schema_event_previous',
					'content'  => $form->get_no_date_time_tz( 'schema_event_previous' ),
				),
				'schema_event_offers_start' => array(
					'tr_class' => $schema_type_row_class[ 'event' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Event Offers Start', 'option label', 'wpsso-schema-json-ld' ),
					'tooltip'  => 'meta-schema_event_offers_start',
					'content'  => $form->get_no_date_time_tz( 'schema_event_offers_start' ),
				),
				'schema_event_offers_end' => array(
					'tr_class' => $schema_type_row_class[ 'event' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Event Offers End', 'option label', 'wpsso-schema-json-ld' ),
					'tooltip'  => 'meta-schema_event_offers_end',
					'content'  => $form->get_no_date_time_tz( 'schema_event_offers_end' ),
				),
				'schema_event_offers' => array(
					'tr_class' => $schema_type_row_class[ 'event' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Event Offers', 'option label', 'wpsso-schema-json-ld' ),
					'tooltip'  => 'meta-schema_event_offers',
					'content'  => $form->get_no_mixed_multi( array(
						'schema_event_offer_name' => array(
							'input_title' => _x( 'Event Offer Name', 'option label', 'wpsso-schema-json-ld' ),
							'input_type'  => 'text',
							'input_class' => 'offer_name',
						),
						'schema_event_offer_price' => array(
							'input_title' => _x( 'Event Offer Price', 'option label', 'wpsso-schema-json-ld' ),
							'input_type'  => 'text',
							'input_class' => 'price',
						),
						'schema_event_offer_currency' => array(
							'input_title'    => _x( 'Event Offer Currency', 'option label', 'wpsso-schema-json-ld' ),
							'input_type'     => 'select',
							'input_class'    => 'currency',
							'select_options' => $currencies,
							'select_default' => $this->p->options[ 'plugin_def_currency' ],
						),
						'schema_event_offer_avail' => array(
							'input_title'    => _x( 'Event Offer Availability', 'option label', 'wpsso-schema-json-ld' ),
							'input_type'     => 'select',
							'input_class'    => 'stock',
							'select_options' => $this->p->cf[ 'form' ][ 'item_availability' ],
							'select_default' => 'InStock',
						),
					), $css_class = 'single_line', $css_id = 'schema_event_offer',
						$start_num = 0, $max_input = 2, $show_first = 2 ),
				),

				/**
				 * Schema Intangible / Job Posting.
				 */
				'subsection_job' => array(
					'tr_class' => $schema_type_row_class[ 'job_posting' ],
					'td_class' => 'subsection',
					'header'   => 'h5',
					'label'    => _x( 'Job Posting Information', 'metabox title', 'wpsso-schema-json-ld' ),
				),
				'schema_job_title' => array(
					'tr_class' => $schema_type_row_class[ 'job_posting' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Job Title', 'option label', 'wpsso-schema-json-ld' ),
					'tooltip'  => 'meta-schema_job_title',
					'content'  => $form->get_no_input_value( $def_schema_title, $css_class = 'wide' ),
				),
				'schema_job_hiring_org_id' => array(
					'tr_class' => $schema_type_row_class[ 'job_posting' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Hiring Organization', 'option label', 'wpsso-schema-json-ld' ),
					'tooltip'  => 'meta-schema_job_hiring_org_id',
					'content'  => $form->get_no_select( 'schema_job_hiring_org_id', $org_site_names,
						$css_class = 'long_name', $css_id = '', $is_assoc = true ) . $org_req_msg,
				),
				'schema_job_location_id' => array(
					'tr_class' => $schema_type_row_class[ 'job_posting' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Job Location', 'option label', 'wpsso-schema-json-ld' ),
					'tooltip'  => 'meta-schema_job_location_id',
					'content'  => $form->get_no_select( 'schema_job_location_id', $plm_place_names,
						$css_class = 'long_name', $css_id = '', $is_assoc = true ) . $plm_req_msg,
				),
				'schema_job_salary' => array(
					'tr_class' => $schema_type_row_class[ 'job_posting' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Base Salary', 'option label', 'wpsso-schema-json-ld' ),
					'tooltip'  => 'meta-schema_job_salary',
					'content'  => $form->get_no_input_value( '', $css_class = 'medium' ) . ' ' . 
						$form->get_no_select( 'schema_job_salary_currency', $currencies, $css_class = 'currency' ) . ' ' . 
						_x( 'per', 'option comment', 'wpsso-schema-json-ld' ) . ' ' . 
						$form->get_no_select( 'schema_job_salary_period', $this->p->cf[ 'form' ][ 'time_text' ], 'short' ),
				),
				'schema_job_empl_type' => array(
					'tr_class' => $schema_type_row_class[ 'job_posting' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Employment Type', 'option label', 'wpsso-schema-json-ld' ),
					'tooltip'  => 'meta-schema_job_empl_type',
					'content'  => $form->get_no_checklist( 'schema_job_empl_type', $this->p->cf[ 'form' ][ 'employment_type' ] ),
				),
				'schema_job_expire' => array(
					'tr_class' => $schema_type_row_class[ 'job_posting' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Job Posting Expires', 'option label', 'wpsso-schema-json-ld' ),
					'tooltip'  => 'meta-schema_job_expire',
					'content'  => $form->get_no_date_time_tz( 'schema_job_expire' ),
				),

				/**
				 * Schema Organization.
				 */
				'subsection_organization' => array(
					'tr_class' => $schema_type_row_class[ 'organization' ],
					'td_class' => 'subsection',
					'header'   => 'h5',
					'label'    => _x( 'Organization Information', 'metabox title', 'wpsso-schema-json-ld' ),
				),
				'schema_organization_org_id' => array(
					'tr_class' => $schema_type_row_class[ 'organization' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Select an Organization', 'option label', 'wpsso-schema-json-ld' ),
					'tooltip'  => 'meta-schema_organization_org_id',
					'content'  => $form->get_no_select( 'schema_organization_org_id', $org_site_names,
						$css_class = 'long_name', $css_id = '', $is_assoc = true ) .
					( empty( $form->options[ 'schema_organization_org_id:is' ] ) ? $org_req_msg : '' ),
				),

				/**
				 * Schema Person.
				 */
				'subsection_person' => array(
					'tr_class' => $schema_type_row_class[ 'person' ],
					'td_class' => 'subsection',
					'header'   => 'h5',
					'label'    => _x( 'Person Information', 'metabox title', 'wpsso-schema-json-ld' ),
				),
				'schema_person_id' => array(
					'tr_class' => $schema_type_row_class[ 'person' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Select a Person', 'option label', 'wpsso-schema-json-ld' ),
					'tooltip'  => 'meta-schema_person_id',
					'content'  => $form->get_no_select( 'schema_person_id', $person_names,
						$css_class = 'long_name' ),
				),

				/**
				 * Schema Product.
				 *
				 * Note that unlike most schema option names, product options start with 'product_' and not 'schema_'.
				 */
				'subsection_product' => array(
					'tr_class' => $schema_type_row_class[ 'product' ],
					'td_class' => 'subsection',
					'header'   => 'h5',
					'label'    => _x( 'Additional Product Information', 'metabox title', 'wpsso-schema-json-ld' ),
				),
				'schema_product_ecom_msg' => array(
					'tr_class' => $schema_type_row_class[ 'product' ],
					'table_row' => ( empty( $this->p->avail[ 'ecom' ][ 'any' ] ) ? '' :
						'<td colspan="2">' . $this->p->msgs->get( 'pro-ecom-product-msg' ) . '</td>' ),
				),
				'schema_product_length_value' => array(
					'tr_class' => $schema_type_row_class[ 'product' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Product Length', 'option label', 'wpsso-schema-json-ld' ),
					'tooltip'  => 'meta-product_length_value',
					'content'  => $form->get_no_input( 'product_length_value', '', $css_id = '', $placeholder = true ) .
						WpssoAdmin::get_option_unit_comment( 'product_length_value' ),
				),
				'schema_product_width_value' => array(
					'tr_class' => $schema_type_row_class[ 'product' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Product Width', 'option label', 'wpsso-schema-json-ld' ),
					'tooltip'  => 'meta-product_width_value',
					'content'  => $form->get_no_input( 'product_width_value', '', $css_id = '', $placeholder = true ) .
						WpssoAdmin::get_option_unit_comment( 'product_width_value' ),
				),
				'schema_product_height_value' => array(
					'tr_class' => $schema_type_row_class[ 'product' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Product Height', 'option label', 'wpsso-schema-json-ld' ),
					'tooltip'  => 'meta-product_height_value',
					'content'  => $form->get_no_input( 'product_height_value', '', $css_id = '', $placeholder = true ) .
						WpssoAdmin::get_option_unit_comment( 'product_height_value' ),
				),
				'schema_product_depth_value' => array(
					'tr_class' => $schema_type_row_class[ 'product' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Product Depth', 'option label', 'wpsso-schema-json-ld' ),
					'tooltip'  => 'meta-product_depth_value',
					'content'  => $form->get_no_input( 'product_depth_value', '', $css_id = '', $placeholder = true ) .
						WpssoAdmin::get_option_unit_comment( 'product_depth_value' ),
				),
				'schema_product_fluid_volume_value' => array(
					'tr_class' => $schema_type_row_class[ 'product' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Product Fluid Volume', 'option label', 'wpsso-schema-json-ld' ),
					'tooltip'  => 'meta-product_fluid_volume_value',
					'content'  => $form->get_no_input( 'product_fluid_volume_value', '', $css_id = '', $placeholder = true ) .
						WpssoAdmin::get_option_unit_comment( 'product_fluid_volume_value' ),
				),
				'schema_product_gtin14' => array(
					'tr_class' => $schema_type_row_class[ 'product' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Product GTIN-14', 'option label', 'wpsso-schema-json-ld' ),
					'tooltip'  => 'meta-product_gtin14',
					'content'  => $form->get_no_input( 'product_gtin14', '', $css_id = '', $placeholder = true ),
				),
				'schema_product_gtin13' => array(
					'tr_class' => $schema_type_row_class[ 'product' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Product GTIN-13 (EAN)', 'option label', 'wpsso-schema-json-ld' ),	// aka Product EAN.
					'tooltip'  => 'meta-product_gtin13',
					'content'  => $form->get_no_input( 'product_gtin13', '', $css_id = '', $placeholder = true ),
				),
				'schema_product_gtin12' => array(
					'tr_class' => $schema_type_row_class[ 'product' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Product GTIN-12 (UPC)', 'option label', 'wpsso-schema-json-ld' ),	// aka Product UPC.
					'tooltip'  => 'meta-product_gtin12',
					'content'  => $form->get_no_input( 'product_gtin12', '', $css_id = '', $placeholder = true ),
				),
				'schema_product_gtin8' => array(
					'tr_class' => $schema_type_row_class[ 'product' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Product GTIN-8', 'option label', 'wpsso-schema-json-ld' ),
					'tooltip'  => 'meta-product_gtin8',
					'content'  => $form->get_no_input( 'product_gtin8', '', $css_id = '', $placeholder = true ),
				),
				'schema_product_gtin' => array(
					'tr_class' => $schema_type_row_class[ 'product' ],
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Product GTIN', 'option label', 'wpsso-schema-json-ld' ),
					'tooltip'  => 'meta-product_gtin',
					'content'  => $form->get_no_input( 'product_gtin', '', $css_id = '', $placeholder = true ),
				),
			);

			return $form->get_md_form_rows( $table_rows, $form_rows, $head_info, $mod );
		}

		public function filter_metabox_sso_media_rows( $table_rows, $form, $head_info, $mod ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			/**
			 * Move Schema options to the end of the table, just in case.
			 */
			foreach ( SucomUtil::preg_grep_keys( '/^(subsection_schema|schema_)/', $table_rows ) as $key => $row ) {
				SucomUtil::move_to_end( $table_rows, $key );
			}

			$max_media_items = $this->p->cf[ 'form' ][ 'max_media_items' ];

			$media_info = $this->p->og->get_media_info( $this->p->lca . '-schema',
				array( 'pid', 'img_url' ), $mod, $md_pre = 'og', $mt_pre = 'og' );
	
			$row_class = $form->in_options( '/^schema_img_/', $is_preg = true ) ? '' : 'hide_in_basic';

			$form_rows = array(
				'wpssojson_pro_feature_msg' => array(
					'tr_class'  => $row_class,
					'table_row' => '<td colspan="2">' . $this->p->msgs->pro_feature( 'wpssojson' ) . '</td>',
				),
			);

			if ( $mod[ 'is_post' ] ) {
				$form_rows[ 'schema_img_max' ] = array(
					'tr_class' => $row_class,
					'th_class' => 'medium',
					'td_class' => 'blank',
					'label'    => _x( 'Maximum Images', 'option label', 'wpsso' ),
					'tooltip'  => 'schema_img_max',	// Use tooltip message from settings.
					'content'  => $form->get_no_select( 'schema_img_max', range( 0, $max_media_items ), $css_class = 'medium' ),
				);
			}

			$form_rows[ 'schema_img_id' ] = array(
				'tr_class' => $row_class,
				'th_class' => 'medium',
				'td_class' => 'blank',
				'label'    => _x( 'Image ID', 'option label', 'wpsso' ),
				'tooltip'  => 'meta-schema_img_id',
				'content'  => $form->get_no_input_image_upload( 'schema_img', $media_info[ 'pid' ], true ),
			);

			$form_rows[ 'schema_img_url' ] = array(
				'tr_class' => $row_class,
				'th_class' => 'medium',
				'td_class' => 'blank',
				'label'    => _x( 'or an Image URL', 'option label', 'wpsso' ),
				'tooltip'  => 'meta-schema_img_url',
				'content'  => $form->get_no_input_value( $media_info[ 'img_url' ], $css_class = 'wide' ),
			);

			return $form->get_md_form_rows( $table_rows, $form_rows, $head_info, $mod );
		}

		private function get_time_dhms( $form ) {

			static $days_sep = null;
			static $hours_sep = null;
			static $mins_sep = null;
			static $secs_sep = null;

			/**
			 * Translated text strings.
			 */
			if ( null === $days_sep ) {
				$days_sep  = ' ' . _x( 'days', 'option comment', 'wpsso-schema-json-ld' ) . ', ';
				$hours_sep = ' ' . _x( 'hours', 'option comment', 'wpsso-schema-json-ld' ) . ', ';
				$mins_sep  = ' ' . _x( 'mins', 'option comment', 'wpsso-schema-json-ld' ) . ', ';
				$secs_sep  = ' ' . _x( 'secs', 'option comment', 'wpsso-schema-json-ld' );
			}

			return $form->get_no_input_value( '0', 'xshort' ) . $days_sep . 
				$form->get_no_input_value( '0', 'xshort' ) . $hours_sep . 
				$form->get_no_input_value( '0', 'xshort' ) . $mins_sep . 
				$form->get_no_input_value( '0', 'xshort' ) . $secs_sep;
		}
	}
}
