<?php

 if (!defined('ABSPATH')) exit; // Exit if accessed directly
	/**
		* Add plugin action links.
		*
		* Add a link to the settings page on the plugins.php page.
		*
		* @since 1.0.0
		*
		* @param  array  $links List of existing plugin action links.
		* @return array         List of modified plugin action links.
		*/
	function impressionz_plugin_action_links( $links ) {
		// $links = array_merge( array(
		// 	'<a href="' . esc_url( 'https://impressionz.io/support?site='.get_site_url().'' ) . '">' . __( 'Support', 'gox' ) . '</a>'
		// ), $links );
		//
		// $links = array_merge( array(
		// 	'<a href="' . esc_url( 'https://impressionz.io/documentation?site='.get_site_url().'' ) . '">' . __( 'Documentation', 'gox' ) . '</a>'
		// ), $links );

		$links = array_merge( array(
			'<a href="' . esc_url( admin_url( '/admin.php?page=impressionz_settings' ) ) . '">' . __( 'Settings', 'gox' ) . '</a>'
		), $links );


		$links = array_merge( array(
			'<a href="' . esc_url( admin_url( '/admin.php?page=impressionz_license' ) ) . '">' . __( '<b style="color:red;">License</b>', 'gox' ) . '</a>'
		), $links );

		return $links;
	}
	add_action( 'plugin_action_links_' .IMP\basename(), 'impressionz_plugin_action_links' );
