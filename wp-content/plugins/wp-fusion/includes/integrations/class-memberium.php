<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


class WPF_Memberium extends WPF_Integrations_Base {

	/**
	 * Gets things started
	 *
	 * @access  public
	 * @since   1.0
	 * @return  void
	 */

	public function init() {

		$this->slug = 'memberium';

		add_filter( 'wpf_bypass_profile_update', array( $this, 'bypass_update' ), 10, 2 );

	}


	/**
	 * We don't want to send data back to the CRM after Memberium has just received an API call
	 *
	 * @access public
	 * @return bool Bypass
	 */

	public function bypass_update( $bypass, $request ) {

		if ( defined( 'MEMBERIUM_SKU' ) && MEMBERIUM_SKU == 'm4ac' && memberium()->getDoingWebHook( $deprecated = null ) ) {
			$bypass = true;
		}

		return $bypass;

	}


}

new WPF_Memberium();
