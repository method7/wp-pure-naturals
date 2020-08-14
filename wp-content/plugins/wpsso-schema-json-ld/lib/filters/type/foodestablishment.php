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

if ( ! class_exists( 'WpssoJsonFiltersTypeFoodEstablishment' ) ) {

	class WpssoJsonFiltersTypeFoodEstablishment {

		private $p;

		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			$this->p->util->add_plugin_filters( $this, array(
				'json_data_https_schema_org_foodestablishment' => 5,
			) );
		}

		/**
		 * https://schema.org/Bakery
		 * https://schema.org/BarOrPub
		 * https://schema.org/Brewery
		 * https://schema.org/CafeOrCoffeeShop
		 * https://schema.org/FastFoodRestaurant
		 * https://schema.org/FoodEstablishment
		 * https://schema.org/IceCreamShop
		 * https://schema.org/Restaurant
		 * https://schema.org/Winery
		 */
		public function filter_json_data_https_schema_org_foodestablishment( $json_data, $mod, $mt_og, $page_type_id, $is_main ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			$ret = array();

			WpssoSchema::add_data_itemprop_from_assoc( $ret, $mt_og, array( 
				'acceptsReservations' => 'place:business:accepts_reservations',	// True or false.
				'hasMenu'             => 'place:business:menu_url',
				'servesCuisine'       => 'place:business:cuisine',
			) );

			return WpssoSchema::return_data_from_filter( $json_data, $ret, $is_main );
		}
	}
}
