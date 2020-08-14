<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


class WPF_Simple_Pay extends WPF_Integrations_Base {

	/**
	 * Gets things started
	 *
	 * @access  public
	 * @since   3.30.4
	 * @return  void
	 */

	public function init() {

		$this->name = 'Simple Pay';
		$this->slug = 'simple-pay';

		add_action( 'simpay_after_customer_created', array( $this, 'customer_created' ) );

		add_filter( 'simpay_form_settings_meta_tabs_li', array( $this, 'settings_tabs' ), 10, 2 );
		add_action( 'simpay_form_settings_meta_options_panel', array( $this, 'settings_options_panel' ) );
		add_action( 'simpay_save_form_settings', array( $this, 'save_settings' ), 10, 2 );

	}


	/**
	 * Sync the customer to the CRM
	 *
	 * @access public
	 * @return void
	 */

	public function customer_created( $customer ) {

		$form_id = $customer->metadata->simpay_form_id;

		$settings = get_post_meta( $form_id, 'wpf_settings_simple_pay', true );

		if ( empty( $settings ) || false == $settings['enable'] ) {
			return;
		}

		// Build the name

		$name = explode( ' ', $customer->name );

		$first_name = $name[0];

		unset( $name[0] );

		$last_name = implode( ' ', $name );

		$update_data = array(
			'first_name' => $first_name,
			'last_name'  => $last_name,
			'user_email' => $customer->email,
		);

		if ( is_user_logged_in() ) {

			wp_fusion()->user->push_user_meta( wpf_get_current_user_id(), $update_data );

			if ( ! empty( $settings['apply_tags'] ) ) {

				wp_fusion()->user->apply_tags( $settings['apply_tags'] );

			}
		} else {

			$contact_id = $this->guest_registration( $customer->email, $update_data );

			if ( $contact_id && ! empty( $settings['apply_tags'] ) ) {

				wpf_log( 'info', 0, 'Simple Pay guest payment applying tag(s): ', array( 'tag_array' => $settings['apply_tags'] ) );

				wp_fusion()->crm->apply_tags( $settings['apply_tags'], $contact_id );

			}
		}

	}

	/**
	 * Register settings tab on payment form
	 *
	 * @access public
	 * @return array Tabs
	 */

	public function settings_tabs( $tabs, $post_id ) {

		$tabs['wp_fusion'] = array(
			'label'  => 'WP Fusion',
			'target' => 'wp-fusion-settings-panel',
			'icon'   => '',
		);

		return $tabs;

	}

	/**
	 * Output settings panel
	 *
	 * @access public
	 * @return mixed HTML COntent
	 */

	public function settings_options_panel( $post_id ) {

		$defaults = array(
			'enable'     => false,
			'apply_tags' => array(),
		);

		$settings = get_post_meta( $post_id, 'wpf_settings_simple_pay', true );

		$settings = wp_parse_args( $settings, $defaults );

		?>

		<div id="wp-fusion-settings-panel" class="simpay-panel simpay-panel-hidden">

			<table>
				<thead>
				<tr>
					<th colspan="2">WP Fusion</th>
				</tr>
				</thead>
				<tbody class="simpay-panel-section">

				<tr class="simpay-panel-field">
					<th>
						<label for="wpf-enable"><?php esc_html_e( 'Enable', 'wp-fusion' ); ?></label>
					</th>
					<td>

						<input class="checkbox" type="checkbox" id="wpf-enable" name="wpf_settings_simple_pay[enable]" value="1" <?php checked( $settings['enable'], 1 ); ?> />
						<label for="wpf-enable"><?php echo sprintf( __( 'Sync customers with %s', 'wp-fusion' ), wp_fusion()->crm->name ); ?></label>

						<?php /* <br /><br />
						<p class="description"><?php _e( 'Field mapping can be configured on the Custom Form Fields tab.', 'wp-fusion' ); ?></p> */ ?>

					</td>
				</tr>

				<tr class="simpay-panel-field">
					<th>
						<label for="apply_tags"><?php esc_html_e( 'Apply Tags', 'wp-fusion' ); ?></label>
					</th>
					<td>

						<?php

						wpf_render_tag_multiselect(
							array(
								'setting'   => $settings['apply_tags'],
								'meta_name' => 'wpf_settings_simple_pay',
								'field_id'  => 'apply_tags',
							)
						);

						?>

						<p class="description"><?php echo sprintf( __( 'Select tags to apply in %s when a payment is received.', 'wp-fusion' ), wp_fusion()->crm->name ); ?></p>

					</td>
				</tr>
				</tbody>
			</table>

			<div class="simpay-docs-link-wrap">
				<a href="http://wpfusion.com/documentation/ecommerce/wp-simple-pay/" target="_blank" rel="noopener noreferrer">Help docs for WP Fusion<span class="dashicons dashicons-editor-help"></span></a>
			</div>

		</div>

		<?php

	}

	/**
	 * Save settings
	 *
	 * @access public
	 * @return void
	 */

	public function save_settings( $post_id, $post ) {

		if ( isset( $_POST['wpf_settings_simple_pay'] ) ) {
			update_post_meta( $post_id, 'wpf_settings_simple_pay', $_POST['wpf_settings_simple_pay'] );
		} else {
			delete_post_meta( $post_id, 'wpf_settings_simple_pay' );
		}

	}

}

new WPF_Simple_Pay();
