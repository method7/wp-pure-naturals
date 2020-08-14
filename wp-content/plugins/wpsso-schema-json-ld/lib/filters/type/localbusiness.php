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

if ( ! class_exists( 'WpssoJsonFiltersTypeLocalBusiness' ) ) {

	class WpssoJsonFiltersTypeLocalBusiness {

		private $p;

		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			$this->p->util->add_plugin_filters( $this, array(
				'json_data_https_schema_org_localbusiness' => 5,
			) );
		}

		public function filter_json_data_https_schema_org_localbusiness( $json_data, $mod, $mt_og, $page_type_id, $is_main ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			$ret = array();

			/**
			 * Skip reading place meta tags if not main schema type or if there are no place meta tags.
			 */
			$read_mt_place = false;

			if ( $is_main && preg_grep( '/^place:/', array_keys( $mt_og ) ) ) {
				$read_mt_place = true;
			}

			/**
			 * Property:
			 * 	currenciesAccepted
			 * 	paymentAccepted
			 * 	priceRange
			 */
			if ( $read_mt_place ) {

				WpssoSchema::add_data_itemprop_from_assoc( $ret, $mt_og, array(
					'currenciesAccepted' => 'place:business:currencies_accepted',	// Example: USD, CAD.
					'paymentAccepted'    => 'place:business:payment_accepted',	// Example: Cash, Credit Card.
					'priceRange'         => 'place:business:price_range',		// Example: $$.
				) );
			}

			/**
			 * Property:
			 *	areaServerd as https://schema.org/GeoShape
			 */
			if ( $read_mt_place ) {

				if ( ! empty( $mt_og[ 'place:location:latitude' ] ) &&
					! empty( $mt_og[ 'place:location:longitude' ] ) &&
						! empty( $mt_og[ 'place:business:service_radius' ] ) ) {
	
					$ret[ 'areaServed' ] = WpssoSchema::get_schema_type_context( 'https://schema.org/GeoShape',
						array(
							'circle' => $mt_og[ 'place:location:latitude' ] . ' ' . 
								$mt_og[ 'place:location:longitude' ] . ' ' . 
								$mt_og[ 'place:business:service_radius' ]
						)
					);

				} elseif ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'no place:location meta tags found for area served' );
				}
			}

			return WpssoSchema::return_data_from_filter( $json_data, $ret, $is_main );
		}
	}
}
