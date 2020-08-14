<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WPF_Advanced_Ads extends WPF_Integrations_Base {

	/**
	 * Gets things started
	 *
	 * @access  public
	 * @since   1.0
	 * @return  void
	 */

	public function init() {

		$this->slug = 'advanced-ads';;

		//Add conditions
		add_filter( 'advanced-ads-visitor-conditions', array( $this, 'add_conditions' ), 100, 1 );

	}

	/**
	 * Meta box output
	 *
	 * @access public
	 * @return mixed
	 */

	public function add_conditions( $conditions ) {

		$conditions['wpf_tags'] = array(
			'label' => 'WP Fusion',
			'description' => sprintf( __('Show and hide ads based on a logged in user’s %s tags.', 'advanced-ads'), wp_fusion()->crm->name ),
			'metabox' => array( $this, 'metabox'), // callback to generate the metabox
			'check' => array( $this, 'check'), // callback for frontend check
		);

		return $conditions;

	}

	/**
	 * Meta box output
	 *
	 * @access public
	 * @return mixed
	 */

	public function metabox( $options, $index = 0 ) {

		if (!isset($options['type']) || '' === $options['type']) {
			return;
		}

		if( empty( $options['operator'] ) ) {
			$options['operator'] = 'has_tags';
		}

		if( empty( $options['value'] ) ) {
			$options['value'] = array();
		}

		$name = 'advanced_ad[visitors][' . $index . ']';

		?>

		<input type="hidden" class="wp-fusion" name="<?php echo $name; ?>[type]" value="<?php echo $options['type']; ?>"/>

		<select style="margin-bottom: 8px;" name="<?php echo $name; ?>[operator]">
		    <option value="has_tags" <?php selected('has_tags', $options['operator']); ?>><?php echo sprintf( __('User is logged in and has at least one of the %s tags', 'wp-fusion'), wp_fusion()->crm->name ); ?></option>
		    <option value="not_tags" <?php selected('not_tags', $options['operator']); ?>><?php echo sprintf( __('User is logged in and has none of the %s tags', 'wp-fusion'), wp_fusion()->crm->name ); ?></option>
		</select>

		<?php

		wpf_render_tag_multiselect( array( 'setting' => $options, 'meta_name' => 'advanced_ad[visitors]', 'field_id' => $index, 'field_sub_id' => 'value' ) );

	}

	/**
	 * Meta box output
	 *
	 * @access public
	 * @return bool Can Access or Not
	 */

	public function check( $options = array(), Advanced_Ads_Ad $ad ) {

		if( ! wpf_is_user_logged_in() ) {
			return false;
		}

		$can_access = true;

		$user_tags = wp_fusion()->user->get_tags();

		if( isset( $options['operator'] ) && $options['operator'] == 'has_tags' ) {

			$result = array_intersect( (array) $options['value'], $user_tags );

			if( ! empty( $result ) ) {
				$can_access = true;
			} else {
				$can_access = false;
			}

		} elseif( isset( $options['operator'] ) && $options['operator'] == 'not_tags' ) {

			$result = array_intersect( (array) $options['value'], $user_tags );

			if( empty( $result ) ) {
				$can_access = true;
			} else {
				$can_access = false;
			}

		}

		if ( wp_fusion()->settings->get( 'exclude_admins' ) == true && current_user_can( 'manage_options' ) ) {
			$can_access = true;
		}

		return apply_filters( 'wpf_user_can_access', $can_access, wpf_get_current_user_id(), $options['value'] );

	}

}

new WPF_Advanced_Ads;
