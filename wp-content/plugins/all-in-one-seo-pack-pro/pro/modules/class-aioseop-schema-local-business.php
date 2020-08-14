<?php

if ( ! class_exists( 'AIOSEOP_Schema_Local_Business' ) ) {

	/**
	 * Handles Local Business schema.
	 *
	 * @since 3.6.0
	 */
	class AIOSEOP_Schema_Local_Business extends All_in_One_SEO_Pack_Module {

		/**
		 * Class constructor.
		 *
		 * @since 3.6.0
		 */
		public function __construct() {
			if ( ! aioseop_is_addon_allowed( 'schema_local_business' ) ) {
				return;
			}

			$this->name   = __( 'Local Business SEO', 'all-in-one-seo-pack' );
			$this->prefix = 'aiosp_schema_local_business_';
			$this->file   = __FILE__;

			parent::__construct();

			// @TODO: Add support for multiple locations.
			$locations = array(
				// We'll use the abbreviated plugin name as the main slug for now.
				'aioseo' => 'Main',
			);

			// @TODO: Add support for multiple time schedules.
			$time_schedules = array(
				0 => 'Main',
			);

			$this->setDefaultOptions( $locations, $time_schedules );
			$this->setLayout( $locations, $time_schedules );

			$this->hooks();
		}

		/**
		 * Registers our hooks.
		 *
		 * @since 3.6.0
		 *
		 * @return void
		 */
		private function hooks() {
			add_filter( 'admin_init', array( $this, 'registerNotice' ) );
		}

		/**
		 * Registers our notice.
		 *
		 * Needed when the user's schema markup isn't set to Organization in the General Settings menu.
		 *
		 * @since 3.6.0
		 *
		 * @return void
		 */
		public function registerNotice() {
			global $aioseop_options;
			global $aioseop_notices;
			$notice_slug = 'local_business';

			if ( 'organization' !== $aioseop_options['aiosp_schema_site_represents'] ) {
				return $aioseop_notices->activate_notice( $notice_slug );
			}
			return $aioseop_notices->remove_notice( $notice_slug );
		}

		/**
		 * Sets the default options.
		 *
		 * @since 3.6.0
		 * 
		 * @param  array $locations      The locations.
		 * @param  array $time_schedules The time schedules.
		 * @return void
		 */
		private function setDefaultOptions( $locations, $time_schedules ) {
			foreach ( $locations as $location_slug => $location_name ) {
				$this->default_options[ $location_slug . '_business_type' ] = array(
					'name'            => __( 'Business Type', 'all-in-one-seo-pack' ),
					'type'            => 'select',
					'default'         => 'LocalBusiness',
					'initial_options' => array(
						// @TODO: Add support for more specific child types - e.g. https://schema.org/FoodEstablishment#subtypes
						'LocalBusiness'               => __( '-- Select a business type --', 'all-in-one-seo-pack' ), // Acts as a fallback if the user hasn't selected any specific business type yet.
						'AnimalShelter'               => __( 'Animal Shelter', 'all-in-one-seo-pack' ),
						'ArchiveOrganization'         => __( 'Archive Organization', 'all-in-one-seo-pack' ),
						'AutomotiveBusiness'          => __( 'Automotive Business', 'all-in-one-seo-pack' ),
						'ChildCare'                   => __( 'ChildCare', 'all-in-one-seo-pack' ),
						'Dentist'                     => __( 'Dentist', 'all-in-one-seo-pack' ),
						'DryCleaningOrLaundry'        => __( 'Dry Cleaning or Laundry', 'all-in-one-seo-pack' ),
						'EmergencyService'            => __( 'Emergency Service', 'all-in-one-seo-pack' ),
						'EmploymentAgency'            => __( 'Employment Agency', 'all-in-one-seo-pack' ),
						'EntertainmentBusiness'       => __( 'Entertainment Business', 'all-in-one-seo-pack' ),
						'FinancialService'            => __( 'Financial Service', 'all-in-one-seo-pack' ),
						'FoodEstablishment'           => __( 'Food Establishment', 'all-in-one-seo-pack' ),
						'GovernmentOffice'            => __( 'Government Office', 'all-in-one-seo-pack' ),
						'HealthAndBeautyBusiness'     => __( 'Health and Beauty Business', 'all-in-one-seo-pack' ),
						'HomeAndConstructionBusiness' => __( 'Home and Construction Business', 'all-in-one-seo-pack' ),
						'InternetCafe'                => __( 'Internet Cafe', 'all-in-one-seo-pack' ),
						'LegalService'                => __( 'Legal Service', 'all-in-one-seo-pack' ),
						'Library'                     => __( 'Library', 'all-in-one-seo-pack' ),
						'LodgingBusiness'             => __( 'Lodging Business', 'all-in-one-seo-pack' ),
						'MedicalBusiness'             => __( 'Medical Business', 'all-in-one-seo-pack' ),
						'RadioStation'                => __( 'Radio Station', 'all-in-one-seo-pack' ),
						'RealEstateAgent'             => __( 'Real Estate Agent', 'all-in-one-seo-pack' ),
						'RecyclingCenter'             => __( 'Recycling Center', 'all-in-one-seo-pack' ),
						'SelfStorage'                 => __( 'Self Storage', 'all-in-one-seo-pack' ),
						'ShoppingCenter'              => __( 'Shopping Center', 'all-in-one-seo-pack' ),
						'SportsActivityLocation'      => __( 'Sports Activity Location', 'all-in-one-seo-pack' ),
						'Store'                       => __( 'Store', 'all-in-one-seo-pack' ),
						'TelevisionStation'           => __( 'Television Station', 'all-in-one-seo-pack' ),
						'TouristInformationCenter'    => __( 'Tourist Information Center', 'all-in-one-seo-pack' ),
						'TravelAgency'                => __( 'Travel Agency', 'all-in-one-seo-pack' ),
					),
				);

				$this->default_options[ $location_slug . '_business_name' ] = array(
					'name'     => __( 'Business Name', 'all-in-one-seo-pack' ),
					'type'     => 'text',
					'default'  => get_bloginfo( 'name' ),
				);

				$this->default_options[ $location_slug . '_business_image' ] = array(
					'name'     => __( 'Business Image', 'all-in-one-seo-pack' ),
					'type'     => 'image',
					'default'  => aioseop_get_site_logo_url() ? aioseop_get_site_logo_url() : '',
				);

				$this->default_options[ $location_slug . '_postal_address' ] = array(
					'name'     => __( 'Business Address', 'all-in-one-seo-pack' ),
					'type'     => 'address',
					'default'  => array(
						'street_address'   => '',
						'address_locality' => '',
						'address_region'   => '',
						'postal_code'      => '',
						'address_country'  => '',
					),
				);

				$this->default_options[ $location_slug . '_telephone' ] = array(
					/* translators: This is a setting where users can enter a phone number for their organization. This is used for our Schema.org markup. */
					'name'         => __( 'Business Telephone', 'all-in-one-seo-pack' ),
					'type'         => 'tel',
					'autocomplete' => 'off',
				);

				$this->default_options[ $location_slug . '_price_range' ] = array(
					'name'     => __( 'Price Range', 'all-in-one-seo-pack' ),
					'type'     => 'select',
					'default'  => 3,
					'initial_options' => array(
						1 => '$',
						2 => '$$',
						3 => '$$$',
						4 => '$$$$',
						5 => '$$$$$',
					),
				);

				$openingHours = $this->getOpeningHours();
				// @TODO: Add support for multiple time time_schedules.
				foreach ( $time_schedules as $time_index => $time_name ) {
					$this->default_options[ $location_slug . '_time_' . $time_index . '_opening_days' ] = array(
						'name'     => __( "Opening Days", 'all-in-one-seo-pack' ),
						'type'     => 'multicheckbox',
						'default'  => array(),
						'initial_options' => array(
							'Monday'    => __( 'Monday', 'all-in-one-seo-pack' ),
							'Tuesday'   => __( 'Tuesday', 'all-in-one-seo-pack' ),
							'Wednesday' => __( 'Wednesday', 'all-in-one-seo-pack' ),
							'Thursday'  => __( 'Thursday', 'all-in-one-seo-pack' ),
							'Friday'    => __( 'Friday', 'all-in-one-seo-pack' ),
							'Saturday'  => __( 'Saturday', 'all-in-one-seo-pack' ),
							'Sunday'    => __( 'Sunday', 'all-in-one-seo-pack' ),
						),
					);

					// @TODO: Add support for different opening hours per day.
					$this->default_options[ $location_slug . '_time_' . $time_index . '_opens' ] = array(
						'name'     => __( 'Opening Time', 'all-in-one-seo-pack' ),
						'type'     => 'select',
						'default'  => '10:00',
						'initial_options' => $openingHours,
					);

					$this->default_options[ $location_slug . '_time_' . $time_index . '_closes' ] = array(
						'name'     => __( 'Closing Time', 'all-in-one-seo-pack' ),
						'type'     => 'select',
						'default'  => '18:00',
						'initial_options' => $openingHours,
					);
				}
			}
		}

		/**
		 * Sets the metabox layout.
		 *
		 * @since 3.6.0
		 *
		 * @param  array $locations      The locations.
		 * @param  array $time_schedules The time schedules.
		 * @return void
		 */
		private function setLayout( $locations, $time_schedules ) {
			$this->layout = array(
				'schema_local_business' => array(
					'name'    => __( 'Local Business Schema', 'all-in-one-seo-pack' ),
					'options' => array(),
				),
			);

			foreach ( $locations as $location_slug => $location_name ) {
				$this->layout['schema_local_business']['options'] = array_merge(
					$this->layout['schema_local_business']['options'],
					array(
						'aiosp_schema_local_business_' . $location_slug . '_business_type',
						'aiosp_schema_local_business_' . $location_slug . '_business_name',
						'aiosp_schema_local_business_' . $location_slug . '_business_image',
						'aiosp_schema_local_business_' . $location_slug . '_postal_address',
						'aiosp_schema_local_business_' . $location_slug . '_telephone',
						'aiosp_schema_local_business_' . $location_slug . '_price_range',
					)
				);

				// @TODO: Add support for multiple time time_schedules.
				// @TODO: Add support for different opening hours per day.
				foreach ( $time_schedules as $time_index => $time_name ) {
					$this->layout['schema_local_business']['options'] = array_merge(
						$this->layout['schema_local_business']['options'],
						array(
							'aiosp_schema_local_business_' . $location_slug . '_time_' . $time_index . '_opening_days',
							'aiosp_schema_local_business_' . $location_slug . '_time_' . $time_index . '_opens',
							'aiosp_schema_local_business_' . $location_slug . '_time_' . $time_index . '_closes',
						)
					);
				}
			}
		}

		/**
		 * Returns the data for the Opening Hours dropdowns.
		 *
		 * @since 3.6.0
		 *
		 * @return array $openingHours The Opening Hours dropdown data.
		 */
		private function getOpeningHours() {
			$openingHours = array();
			for ( $h = 0; $h < 24; $h++ ) {
				for ( $m = 0; $m < 60; $m += 15 ) {
					$unixTime = strtotime(date('Y-m-d') . " + $h hours + $m minutes");
					$time24   = date('H:i', $unixTime);
					$time12   = date('h:i A', $unixTime);

					$openingHours[ $time24 ] = $time12;
				}
			}
			return $openingHours;
		}
	}
}