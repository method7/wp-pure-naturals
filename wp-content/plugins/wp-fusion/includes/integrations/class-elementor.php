<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WPF_Elementor extends WPF_Integrations_Base {


	/**
	 * Gets things started
	 *
	 * @access  public
	 * @since   1.0
	 * @return  void
	 */

	public function init() {

		$this->slug = 'elementor';

		add_filter( 'wpf_meta_box_post_types', array( $this, 'unset_wpf_meta_boxes' ) );

		// Controls
		add_action( 'elementor/element/after_section_end', array( $this, 'render_controls' ), 10, 3 );

		// Display
		add_action( 'elementor/frontend/widget/before_render', array( $this, 'before_render_widget' ) );
		add_filter( 'elementor/widget/render_content', array( $this, 'render_widget' ), 10, 2 );
		add_action( 'elementor/frontend/section/before_render', array( $this, 'render_section' ) );
		add_action( 'elementor/frontend/column/before_render', array( $this, 'render_section' ) );

		// Filter queries
		add_action( 'elementor/element/before_section_end', array( $this, 'add_filter_queries_control' ), 10, 3 );
		add_filter( 'elementor/query/query_args', array( $this, 'query_args' ), 10, 2 );

	}

	/**
	 * Removes standard WPF meta boxes from Elementor template library items
	 *
	 * @access  public
	 * @return  array Post Types
	 */

	public function unset_wpf_meta_boxes( $post_types ) {

		unset( $post_types['elementor_library'] );

		return $post_types;

	}

	/**
	 * Render widget controls
	 *
	 * @access public
	 * @return void
	 */

	public function render_controls( $element, $section_id, $args ) {

		if ( $section_id != 'section_custom_css_pro' || is_a( $element, 'Elementor\Core\DocumentTypes\Post' ) ) {
			return;
		}

		$available_tags = wp_fusion()->settings->get( 'available_tags', array() );

		$data = array();

		foreach ( $available_tags as $id => $label ) {

			if ( is_array( $label ) ) {
				$label = $label['label'];
			}

			$data[ $id ] = $label;

		}

		$element->start_controls_section(
			'wpf_tags_section',
			[
				'label' => __( 'WP Fusion', 'wp-fusion' ),
				'tab'   => \Elementor\Controls_Manager::TAB_ADVANCED,
			]
		);

		$element->add_control(
			'wpf_tags',
			[
				'label'       => sprintf( __( 'Required %s Tags (Any)', 'wp-fusion' ), wp_fusion()->crm->name ),
				'type'        => \Elementor\Controls_Manager::SELECT2,
				'options'     => $data,
				'multiple'    => true,
				'label_block' => true,
			]
		);

		$element->add_control(
			'wpf_tags_not',
			[
				'label'       => sprintf( __( 'Required %s Tags (Not)', 'wp-fusion' ), wp_fusion()->crm->name ),
				'type'        => \Elementor\Controls_Manager::SELECT2,
				'options'     => $data,
				'multiple'    => true,
				'label_block' => true,
			]
		);

		$element->add_control(
			'wpf_loggedout',
			[
				'label'       => 'Logged Out Behavior',
				'type'        => \Elementor\Controls_Manager::SELECT,
				'default'     => 'default',
				'options'     => array(
					'default' => __( 'Default (hidden)', 'wp-fusion' ),
					'display' => __( 'Display', 'wp-fusion' ),
				),
				'multiple'    => false,
				'label_block' => true,
				'description' => 'This setting only applies when using "Not" tags. By default content will be hidden when logged out. Set to Display to show to visitors.',
			]
		);

		do_action( 'wpf_elementor_controls_section', $element );

		$element->end_controls_section();

	}

	/**
	 * Determines if a user has access to an element
	 *
	 * @access public
	 * @return bool Access
	 */

	private function can_access( $element ) {

		if ( is_admin() ) {
			return true;
		}

		$widget_tags = $element->get_settings( 'wpf_tags' );

		$widget_tags_not = $element->get_settings( 'wpf_tags_not' );

		if ( empty( $widget_tags ) && empty( $widget_tags_not ) ) {

			$can_access = apply_filters( 'wpf_elementor_can_access', true, $element );

			return apply_filters( 'wpf_user_can_access', $can_access, wpf_get_current_user_id(), false );

		}

		if ( wp_fusion()->settings->get( 'exclude_admins' ) == true && current_user_can( 'manage_options' ) ) {
			return true;
		}

		$user_tags = array();

		$can_access = true;

		if ( wpf_is_user_logged_in() ) {

			// See if user has required tags
			$user_tags = wp_fusion()->user->get_tags();

			if ( ! empty( $widget_tags ) ) {

				$result = array_intersect( $widget_tags, $user_tags );

				if ( empty( $result ) ) {
					$can_access = false;
				}
			}

			if ( $can_access == true && ! empty( $widget_tags_not ) ) {

				$result = array_intersect( $widget_tags_not, $user_tags );

				if ( ! empty( $result ) ) {
					$can_access = false;
				}
			}
		} else {

			// Not logged in
			$can_access = false;

			if ( $element->get_settings( 'wpf_loggedout' ) == 'display' && empty( $widget_tags ) ) {
				$can_access = true;
			}
		}

		$can_access = apply_filters( 'wpf_elementor_can_access', $can_access, $element );

		global $post;

		$post_id = 0;

		if ( ! empty( $post ) ) {
			$post_id = $post->ID;
		}

		$can_access = apply_filters( 'wpf_user_can_access', $can_access, wpf_get_current_user_id(), $post_id );

		if ( $can_access ) {
			return true;
		} else {
			return false;
		}

	}

	/**
	 * Hide the widget wrapper if access denied
	 *
	 * @access public
	 * @return void
	 */

	public function before_render_widget( $widget ) {

		if ( ! $this->can_access( $widget ) ) {

			$widget->add_render_attribute( '_wrapper', 'style', 'display:none' );

		}

	}

	/**
	 * Conditionall show / hide widget based on tags
	 *
	 * @access public
	 * @return mixed / bool
	 */

	public function render_widget( $content, $widget ) {

		if ( $this->can_access( $widget ) ) {
			return $content;
		} else {
			return false;
		}

	}

	/**
	 * Conditionally show / hide section based on tags
	 *
	 * @access public
	 * @return void
	 */

	public function render_section( $element ) {

		if ( $this->can_access( $element ) ) {
			return;
		} else {
			$element->add_render_attribute( '_wrapper', 'style', 'display:none' );
		}

	}

	/**
	 * Render widget controls
	 *
	 * @access public
	 * @return void
	 */

	public function add_filter_queries_control( $element, $section_id, $args ) {

		if ( $section_id !== 'section_query' ) {
			return;
		}

		$element->add_control(
			'wpf_filter_queries',
			[
				'label'       => __( 'Filter Queries', 'wp-fusion' ),
				'description' => __( 'Filter results based on WP Fusion access rules', 'wp-fusion' ),
				'type'        => \Elementor\Controls_Manager::SWITCHER,
				'label_block' => false,
				'show_label'  => true,
				'separator'   => 'before',
			]
		);

	}

	/**
	 * Filter queries if enabled
	 *
	 * @access public
	 * @return array Query Args
	 */

	public function query_args( $query_args, $widget ) {

		$settings = $widget->get_settings_for_display();

		if ( ! isset( $settings['wpf_filter_queries'] ) || 'yes' !== $settings['wpf_filter_queries'] ) {
			return $query_args;
		}

		// No need to do this again if WPF is already doing it globally

		if ( 'advanced' == wp_fusion()->settings->get( 'hide_archives' ) ) {
			return $query_args;
		}

		$args = array(
			'post_type'  => $query_args['post_type'],
			'nopaging'   => true,
			'fields'     => 'ids',
			'meta_query' => array(
				array(
					'key'     => 'wpf-settings',
					'compare' => 'EXISTS',
				),
			),
		);

		$post_ids = get_posts( $args );

		if ( ! empty( $post_ids ) ) {

			if ( ! isset( $query_args['post__not_in'] ) ) {
				$query_args['post__not_in'] = array();
			}

			foreach ( $post_ids as $post_id ) {

				if ( ! wp_fusion()->access->user_can_access( $post_id ) ) {

					$query_args['post__not_in'][] = $post_id;

				}
			}
		}

		return $query_args;

	}

}

new WPF_Elementor();
