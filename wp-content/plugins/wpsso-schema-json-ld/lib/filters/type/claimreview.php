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

if ( ! class_exists( 'WpssoJsonFiltersTypeClaimReview' ) ) {

	class WpssoJsonFiltersTypeClaimReview {

		private $p;

		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			$this->p->util->add_plugin_filters( $this, array(
				'json_data_https_schema_org_claimreview' => 5,
			) );
		}

		public function filter_json_data_https_schema_org_claimreview( $json_data, $mod, $mt_og, $page_type_id, $is_main ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			$ret = array();

			if ( ! empty( $mod[ 'obj' ] ) ) {	// Just in case.

				$md_opts = SucomUtil::get_opts_begin( 'schema_review_', array_merge( 
					(array) $mod[ 'obj' ]->get_defaults( $mod[ 'id' ] ), 
					(array) $mod[ 'obj' ]->get_options( $mod[ 'id' ] )	// Returns empty string if no meta found.
				) );

			} else {
				$md_opts = array();
			}

			/**
			 * Create the 'appearance' property value.
			 *
			 * Inherit the 'itemReviewed' property value from https://schema.org/Review.
			 */
			if ( ! empty( $json_data[ 'itemReviewed' ] ) ) {

				$appearance_type_obj = $json_data[ 'itemReviewed' ];
				$appearance_type_url = $this->p->schema->get_data_type_url( $appearance_type_obj );
				$claim_review_url    = $this->p->schema->get_schema_type_url( 'review.claim' );

				/**
				 * The subject of a claim review cannot be another claim review.
				 */
				if ( $claim_review_url === $appearance_type_url ) {

					/**
					 * Add notice only if the admin notices have not already been shown.
					 */
					if ( $this->p->notice->is_admin_pre_notices() ) {
						
						$notice_msg = __( 'A claim review cannot be the subject of another claim review.', 'wpsso-schema-json-ld' ) . ' ';

						$notice_msg .= __( 'CreativeWork will be used instead as the Schema type for the subject of the webpage (ie. the content) being reviewed.', 'wpsso-schema-json-ld' );

						$this->p->notice->err( $notice_msg );
					}

					$appearance_type_url = $this->p->schema->get_schema_type_url( 'creative.work' );
					$appearance_type_obj = $this->p->schema->get_schema_type_context( $appearance_type_url, $appearance_type_obj );
				}

			} else {

				$appearance_type_url = $this->p->schema->get_schema_type_url( 'creative.work' );
				$appearance_type_obj = $this->p->schema->get_schema_type_context( $appearance_type_url );
			}

			/**
			 * Re-define the 'itemReviewed' property as a https://schema.org/Claim and set the 'appearance' property.
			 */
			$claim_type_url = $this->p->schema->get_schema_type_url( 'claim' );

			$ret[ 'itemReviewed' ] = WpssoSchema::get_schema_type_context( $claim_type_url );

			/**
			 * Google suggests adding the 'author' and 'datePublished' properties to the Schema Claim type, if
			 * available.
			 */
			foreach ( array( 'author', 'datePublished' ) as $prop_name ) {
				if ( ! empty( $appearance_type_obj[ $prop_name ] ) ) {
					$ret[ 'itemReviewed' ][ $prop_name ] = $appearance_type_obj[ $prop_name ];
				}
			}

			$ret[ 'itemReviewed' ][ 'appearance' ] = $appearance_type_obj;

			/**
			 * https://schema.org/claimReviewed
			 *
			 * A short summary of the specific claims reviewed in a ClaimReview.
			 *
			 * Used on these types:
			 *
			 *	ClaimReview 
			 */
			if ( ! empty( $md_opts[ 'schema_review_claim_reviewed' ] ) ) {
				$ret[ 'claimReviewed' ] = $md_opts[ 'schema_review_claim_reviewed' ];
			} 

			/**
			 * If there's a first appearance URL, add the URL using a CreativeWork object as well.
			 */
			if ( ! empty( $md_opts[ 'schema_review_claim_first_url' ] ) ) {

				$ret[ 'itemReviewed' ][ 'firstAppearance' ] = WpssoSchema::get_schema_type_context( $appearance_type_url );

				WpssoSchema::add_data_itemprop_from_assoc( $ret[ 'itemReviewed' ][ 'firstAppearance' ], $md_opts, array(
					'url' => 'schema_review_claim_first_url',
				) );
			}

			return WpssoSchema::return_data_from_filter( $json_data, $ret, $is_main );
		}
	}
}
