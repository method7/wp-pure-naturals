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

if ( ! class_exists( 'WpssoJsonFiltersTypeAudiobook' ) ) {

	class WpssoJsonFiltersTypeAudiobook {

		private $p;

		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			$this->p->util->add_plugin_filters( $this, array(
				'json_data_https_schema_org_audiobook' => 5,
			) );
		}

		public function filter_json_data_https_schema_org_audiobook( $json_data, $mod, $mt_og, $page_type_id, $is_main ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			$ret = array();

			if ( ! empty( $mod[ 'obj' ] ) ) {	// Just in case.

				$md_opts = SucomUtil::get_opts_begin( 'schema_book_audio_', 
					array_merge( 
						(array) $mod[ 'obj' ]->get_defaults( $mod[ 'id' ] ), 
						(array) $mod[ 'obj' ]->get_options( $mod[ 'id' ] )	// Returns empty string if no meta found.
					)
				);

			} else {
				$md_opts = array();
			}

			/**
			 * Property:
			 * 	duration
			 */
			WpssoSchema::add_data_time_from_assoc( $ret, $md_opts, array(
				'duration'  => 'schema_book_audio_duration',	// Option prefix for days, hours, mins, secs.
			) );

			return WpssoSchema::return_data_from_filter( $json_data, $ret, $is_main );
		}
	}
}
