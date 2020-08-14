<?php
/**
 * Graph for the Local Business schema.
 *
 * @since 3.6.0
 *
 * @link https://schema.org/LocalBusiness
 */
class AIOSEOP_Graph_LocalBusiness extends AIOSEOP_Graph {

	/**
	 * The plugin options.
	 *
	 * @since 3.6.0
	 *
	 * @var array
	 */
	private $options;

	public function __construct() {
		parent::__construct();
		global $aioseop_options;
		$this->options = $aioseop_options;
	}

	/**
	 * Registers our hooks.
	 *
	 * @since 3.6.0
	 *
	 * @return void
	 */
	protected function add_hooks() {
		parent::add_hooks();
		add_filter( 'aioseop_schema_class_data_AIOSEOP_Graph_Organization', array( $this, 'addLocation' ) );
	}

	/**
	 * Adds the location.
	 *
	 * @since 3.6.0
	 *
	 * @param  $graph_data
	 * @return mixed
	 */
	public function addLocation( $graph_data ) {
		global $aioseop_modules;
		$loaded_modules = $aioseop_modules->get_loaded_module_list();

		if ( isset( $loaded_modules['schema_local_business'] ) ) {
			$homeUrl = home_url() . '/';
			$graph_data['location'] = array(
				'@id' => $this->getId()
			);
		}


		return $graph_data;
	}

	/**
	 * Returns the slug of the graph.
	 *
	 * Used as a fallback in case the Business Type is empty.
	 *
	 * @since 3.6.0
	 *
	 * @return string
	 */
	protected function get_slug() {
		return 'LocalBusiness';
	}

	/**
	 * Returns the name of the graph.
	 *
	 * @since 3.6.0
	 *
	 * @return string
	 */
	protected function get_name() {
		return 'Local Business';
	}

	/**
	 * Prepares the data.
	 *
	 * Limited support for Restaurants (Food Establishment). Requires contact Google via form.
	 *
	 * @since 3.6.0
	 *
	 * @return array {
	 *     @type string @type
	 *     @type string @id
	 *     @type string name
	 *     @type array address {
	 *         Thing > Intangible > StructuredValue > ContactPoint > PostalAddress.
	 *
	 *         @type string @type
	 *         @type string streetAddress
	 *         @type string addressLocality
	 *         @type string addressRegion
	 *         @type string postalCode
	 *         @type string addressCountry
	 *     }
	 *     @type array openingHoursSpecification {
	 *         Thing > Intangible > StructuredValue > OpeningHoursSpecification
	 *
	 *         @tyoe array dayOfWeek     An array of names associated to days of the week.
	 *         @type string opens        hh:mm:ss format.
	 *         @type string closes       hh:mm:ss format.
	 *     }
	 *     @type string telephone
	 *     @type string url
	 *     @type array  image
	 *     @type string priceRange A numerical range (for example, "$10-15"), or a normalized number of currency signs (for example, "$$$")
	 * }
	 */
	protected function prepare() {
		$data = array(
			'@type'      => $this->getType(),
			'@id'        => $this->getId(),
			'name'       => $this->getName(),
			'url'        => home_url() . '/',
			'image'      => $this->getImage(),
			'address'    => $this->getAddress(),
			'telephone'  => $this->getTelephone(),
			'priceRange' => $this->getPriceRange()
		);

		$address      = $this->getAddress();
		$openingHours = $this->getOpeningHours();

		if ( ! empty ( $address ) ) {
			$data['address'] = $address;
		}
		if ( ! empty ( $openingHours ) ) {
			$data['openingHoursSpecification'] = $openingHours;
		}

		return $data;
	}

	/**
	 * Returns the business/schema type.
	 *
	 * @since 3.6.0
	 *
	 * @return string $type The business/schema type.
	 */
	private function getType() {
		$type = $this->slug;
		if ( ! empty( $this->options['modules']['aiosp_schema_local_business_options']['aiosp_schema_local_business_aioseo_business_type'] ) ) {
			$type = $this->options['modules']['aiosp_schema_local_business_options']['aiosp_schema_local_business_aioseo_business_type'];
		}
		return $type;

	}

	/**
	 * Returns the schema ID.
	 *
	 * @since 3.6.0
	 *
	 * @return string The schema ID.
	 */
	private function getId() {
		return home_url() . '/#' . strtolower( $this->slug );
	}

	/**
	 * Returns the business name.
	 *
	 * @since 3.6.0
	 *
	 * @return string $name The image name.
	 */
	private function getName() {
		$name = '';
		if ( ! empty( $this->options['modules']['aiosp_schema_local_business_options']['aiosp_schema_local_business_aioseo_business_name'] ) ) {
			$name = $this->options['modules']['aiosp_schema_local_business_options']['aiosp_schema_local_business_aioseo_business_name'];
			// Fallback to Organization Name or Site Title.
		} elseif ( ! empty( $this->options['aiosp_schema_organization_name'] ) ) {
			$name = $this->options['aiosp_schema_organization_name'];
		} else {
			$name = get_bloginfo( 'name' );
		}
		return $name;
	}

	/**
	 * Returns the business image URL.
	 *
	 * @since 3.6.0
	 *
	 * @return string $image The image URL.
	 */
	private function getImage() {
		$image = '';
		if ( ! empty( $this->options['modules']['aiosp_schema_local_business_options']['aiosp_schema_local_business_aioseo_business_image'] ) ) {
			$image = $this->options['modules']['aiosp_schema_local_business_options']['aiosp_schema_local_business_aioseo_business_image'];
			// Fallback to Organization Logo or Site Logo.
		} elseif ( ! empty( $this->options['aiosp_schema_organization_logo'] ) ) {
			$image = $this->options['aiosp_schema_organization_logo'];
		} elseif ( aioseop_get_site_logo_url() ) {
			$image = aioseop_get_site_logo_url();
		}
		return $image;
	}

	/**
	 * Returns the business address.
	 *
	 * @since 3.6.0
	 *
	 * @return array $address The business address.
	 */
	private function getAddress() {
		$address         = array();
		$addressOption   = ! empty( $this->options['modules']['aiosp_schema_local_business_options']['aiosp_schema_local_business_aioseo_postal_address'] ) ? $this->options['modules']['aiosp_schema_local_business_options']['aiosp_schema_local_business_aioseo_postal_address'] : array();
		$filteredAddress = array_filter( $addressOption );
		if ( ! empty( $filteredAddress ) ) {
			$address = array(
				'streetAddress'   => $addressOption['street_address'],
				'addressLocality' => $addressOption['address_locality'],
				'addressRegion'   => $addressOption['address_region'],
				'postalCode'      => $addressOption['postal_code'],
				'addressCountry'  => $addressOption['address_country'],
			);
		}
		if ( empty( $address ) ) {
			return array();
		}
		$address['@type'] = 'PostalAddress';
		return $address;
	}

	/**
	 * Returns the business telephone number.
	 *
	 * @since 3.6.0
	 *
	 * @return string $telephone The telephone number.
	 */
	private function getTelephone() {
		$telephone = '';
		if ( ! empty( $this->options['modules']['aiosp_schema_local_business_options']['aiosp_schema_local_business_aioseo_telephone'] ) ) {
			$telephone = $this->options['modules']['aiosp_schema_local_business_options']['aiosp_schema_local_business_aioseo_telephone'];
			// Fallback to Organization Phone Number.
		} elseif ( $this->options['aiosp_schema_phone_number'] ) {
			$telephone = $this->options['aiosp_schema_phone_number'];
		}
		return $telephone;
	}

	/**
	 * Returns the price range the business falls in.
	 *
	 * @since 3.6.0
	 *
	 * @return string $priceRange The price range (formatted as a series of $ symbols).
	 */
	private function getPriceRange() {
		$priceRange = '$$$';
		if ( ! empty( $this->options['modules']['aiosp_schema_local_business_options']['aiosp_schema_local_business_aioseo_price_range'] ) ) {
			$priceRange = '';
			for ( $i = 1; $i <= $this->options['modules']['aiosp_schema_local_business_options']['aiosp_schema_local_business_aioseo_price_range']; $i++ ) {
				$priceRange .= '$';
			}
		}
		return $priceRange;
	}

	/**
	 * Returns the opening days/hours of the business.
	 *
	 * @since 3.6.0
	 *
	 * @return array $openingHours The opening days/hours.
	 */
	private function getOpeningHours() {
		$openingHours  = array();
		if ( ! empty( $this->options['modules']['aiosp_schema_local_business_options']['aiosp_schema_local_business_aioseo_time_0_opening_days'] ) ) {
			$openingHours = array(
				'dayOfWeek' => $this->options['modules']['aiosp_schema_local_business_options']['aiosp_schema_local_business_aioseo_time_0_opening_days'],
				'opens'     => $this->options['modules']['aiosp_schema_local_business_options']['aiosp_schema_local_business_aioseo_time_0_opens'],
				'closes'    => $this->options['modules']['aiosp_schema_local_business_options']['aiosp_schema_local_business_aioseo_time_0_closes'],
			);
		}
		if ( empty( $openingHours ) ) {
			return array();
		}
		$openingHours['@type'] = 'OpeningHoursSpecification';
		return $openingHours;
	}
}