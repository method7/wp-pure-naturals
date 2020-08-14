<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WPF_Events_Manager extends WPF_Integrations_Base {

	/**
	 * Gets things started
	 *
	 * @access  public
	 * @since   3.33
	 * @return  void
	 */

	public function init() {

		$this->name = 'Events Manager';
		$this->slug = 'events-manager';

		add_action( 'em_booking_add_registration_result', array( $this, 'add_registration' ), 10, 3 );

		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ), 10, 2 );
		add_action( 'save_post_event', array( $this, 'save_meta_box_data' ) );

	}

	/**
	 * Apply tags after event registration
	 *
	 * @access public
	 * @return void
	 */

	public function add_registration( $registration, $booking, $notices ) {

		$settings = get_post_meta( $booking->event->post_id, 'wpf_settings_event', true );

		if ( ! empty( $settings ) && ! empty( $settings['apply_tags'] ) ) {
			wp_fusion()->user->apply_tags( $settings['apply_tags'], $booking->person_id );
		}

	}


	/**
	 * Adds meta box
	 *
	 * @access public
	 * @return void
	 */

	public function add_meta_box( $post_id, $data ) {

		add_meta_box( 'wpf-event-meta', 'WP Fusion - Event Settings', array( $this, 'meta_box_callback' ), 'event' );

	}

	/**
	 * Displays meta box content
	 *
	 * @access public
	 * @return mixed
	 */

	public function meta_box_callback( $post ) {

		$settings = array(
			'apply_tags' => array(),
		);

		if ( get_post_meta( $post->ID, 'wpf_settings_event', true ) ) {
			$settings = array_merge( $settings, get_post_meta( $post->ID, 'wpf_settings_event', true ) );
		}

		echo '<table class="form-table"><tbody>';

		echo '<tr>';

		echo '<th scope="row"><label for="apply_tags">' . __( 'Apply tags', 'wp-fusion' ) . ':</label></th>';
		echo '<td>';

		$args = array(
			'setting'   => $settings['apply_tags'],
			'meta_name' => 'wpf_settings_event',
			'field_id'  => 'apply_tags',
		);

		wpf_render_tag_multiselect( $args );

		echo '<span class="description">' . sprintf( __( 'The selected tags will be applied in %s when someone registers for this event.', 'wp-fusion' ), wp_fusion()->crm->name ) . '</span>';
		echo '</td>';

		echo '</tr>';

		echo '</tbody></table>';

	}

	/**
	 * Runs when WPF meta box is saved
	 *
	 * @access public
	 * @return void
	 */

	public function save_meta_box_data( $post_id ) {

		// Update the meta field in the database.

		if ( ! empty( $_POST['wpf_settings_event'] ) ) {
			update_post_meta( $post_id, 'wpf_settings_event', $_POST['wpf_settings_event'] );
		} else {
			delete_post_meta( $post_id, 'wpf_settings_event' );
		}

	}

}

new WPF_Events_Manager();
