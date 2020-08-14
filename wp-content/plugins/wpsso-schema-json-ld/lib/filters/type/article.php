<?php
/**
 * IMPORTANT: READ THE LICENSE AGREEMENT CAREFULLY. BY INSTALLING, COPYING, RUNNING, OR OTHERWISE USING THE WPSSO SCHEMA JSON-LD
 * MARKUP (WPSSO JSON) PREMIUM APPLICATION, YOU AGREE TO BE BOUND BY THE TERMS OF ITS LICENSE AGREEMENT. IF YOU DO NOT AGREE TO THE
 * TERMS OF ITS LICENSE AGREEMENT, DO NOT INSTALL, RUN, COPY, OR OTHERWISE USE THE WPSSO SCHEMA JSON-LD MARKUP (WPSSO JSON) PREMIUM
 * APPLICATION.
 * 
 * License URI: https://wpsso.com/wp-content/plugins/wpsso-schema-json-ld/license/premium.txt
 * 
 * Copyright 2016-2020 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'WpssoJsonFiltersTypeArticle' ) ) {

	class WpssoJsonFiltersTypeArticle {

		private $p;

		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			$this->p->util->add_plugin_filters( $this, array(
				'json_data_https_schema_org_article' => 5,
			) );
		}

		public function filter_json_data_https_schema_org_article( $json_data, $mod, $mt_og, $page_type_id, $is_main ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			$ret = array();

			/**
			 * Property:
			 *	articleSection
			 */
			WpssoSchema::add_data_itemprop_from_assoc( $ret, $mt_og, array(
				'articleSection' => 'article:section',
			) );

			$amp_size_names = array(
				$this->p->lca . '-schema-article-1-1',
				$this->p->lca . '-schema-article-4-3',
				$this->p->lca . '-schema-article-16-9',
			);

			if ( SucomUtil::is_amp() ) {	// Returns null, true, or false.

				$size_names     = $amp_size_names;
				$alt_size_names = null;
				$org_logo_key   = 'org_banner_url';

			} else {

				$size_names     = array( $this->p->lca . '-schema-article' );
				$alt_size_names = empty( $this->p->avail[ 'amp' ][ 'any' ] ) ? null : $amp_size_names;
				$org_logo_key   = 'org_banner_url';
			}

			/**
			 * Property:
			 *      articleBody
			 */
			if ( ! empty( $this->p->options[ 'schema_add_text_prop' ] ) ) {

				$text_max_len = $this->p->options[ 'schema_text_max_len' ];

				$ret[ 'articleBody' ] = $this->p->page->get_text( $text_max_len, $dots = '...', $mod );
			}

			/**
			 * Property:
			 *      image as https://schema.org/ImageObject
			 *      video as https://schema.org/VideoObject
			 */
			WpssoSchema::add_media_data( $ret, $mod, $mt_og, $size_names, $add_video = true, $alt_size_names );

			WpssoSchema::check_required( $ret, $mod, array( 'image' ) );

			return WpssoSchema::return_data_from_filter( $json_data, $ret, $is_main );
		}
	}
}
