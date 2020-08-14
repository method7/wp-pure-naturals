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

if ( ! class_exists( 'WpssoJsonFiltersTypeMovie' ) ) {

	class WpssoJsonFiltersTypeMovie {

		private $p;

		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			$this->p->util->add_plugin_filters( $this, array(
				'json_data_https_schema_org_movie' => 5,
			) );
		}

		public function filter_json_data_https_schema_org_movie( $json_data, $mod, $mt_og, $page_type_id, $is_main ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			$ret = array();

			if ( ! empty( $mod[ 'obj' ] ) ) {	// Just in case.

				/**
				 * Merge defaults to get a complete meta options array.
				 */
				$md_opts = SucomUtil::get_opts_begin( 'schema_movie_', 
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
				'duration' => 'schema_movie_duration',	// Option prefix for days, hours, mins, secs.
			) );

			/**
			 * Property:
			 * 	actor (supersedes actors)
			 */
			WpssoSchema::add_person_names_data( $ret, 'actor', $md_opts, 'schema_movie_actor_person_name' );

			/**
			 * Property:
			 * 	director
			 */
			WpssoSchema::add_person_names_data( $ret, 'director', $md_opts, 'schema_movie_director_person_name' );

			/**
			 * Property:
			 * 	productionCompany
			 */
			if ( isset( $md_opts[ 'schema_movie_prodco_org_id' ] ) ) {

				$md_val = $md_opts[ 'schema_movie_prodco_org_id' ]; 
				
				if ( null !== $md_val && '' !== $md_val && 'none' !== $md_val ) {
					WpssoSchemaSingle::add_organization_data( $ret[ 'productionCompany' ], $mod, $md_val, 'org_logo_url', $list_element = true );
				}
			}

			return WpssoSchema::return_data_from_filter( $json_data, $ret, $is_main );
		}
	}
}
