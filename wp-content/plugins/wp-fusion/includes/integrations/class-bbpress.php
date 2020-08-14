<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


class WPF_bbPress extends WPF_Integrations_Base {

	/**
	 * Gets things started
	 *
	 * @access  public
	 * @return  void
	 */

	public function init() {

		$this->slug = 'bbpress';

		// Settings
		add_filter( 'wpf_configure_settings', array( $this, 'register_settings' ), 15, 2 );
		add_filter( 'wpf_configure_sections', array( $this, 'configure_sections' ), 10, 2 );
		add_action( 'show_field_bbp_allow_tags', array( $this, 'show_field_bbp_allow_tags' ), 10, 2 );

		add_filter( 'bbp_get_forum_class', array( $this, 'get_forum_class' ), 10, 2 );

		add_action( 'wpf_filtering_page_content', array( $this, 'prepare_content_filter' ) );
		add_action( 'wpf_begin_redirect', array( $this, 'begin_redirect' ) );
		add_filter( 'wpf_redirect_post_id', array( $this, 'redirect_post_id' ) );
		add_filter( 'wpf_user_can_access_post_id', array( $this, 'user_can_access_post_id' ) );
		add_filter( 'wpf_post_access_meta', array( $this, 'inherit_permissions_from_forum' ), 10, 2 );

	}

	/**
	 * Registers bbPress settings
	 *
	 * @access  public
	 * @return  array Settings
	 */

	public function register_settings( $settings, $options ) {

		$settings['bbp_header'] = array(
			'title'   => __( 'bbPress Integration', 'wp-fusion' ),
			'std'     => 0,
			'type'    => 'heading',
			'section' => 'integrations'
		);

		$settings['bbp_lock'] = array(
			'title'   => __( 'Restrict Access', 'wp-fusion' ),
			'desc'    => sprintf( __( 'Restrict access to forums archive (%s/forums/)', 'wp-fusion' ), home_url() ),
			'std'     => 0,
			'type'    => 'checkbox',
			'section' => 'integrations',
			'unlock'  => array( 'bbp_lock_all', 'bbp_allow_tags', 'bbp_redirect' )
		);

		$settings['bbp_lock_all'] = array(
			'title'   => __( 'Restrict Forums', 'wp-fusion' ),
			'desc'    => __( 'Restrict access to all forums in addition to the archive', 'wp-fusion' ),
			'std'     => 0,
			'type'    => 'checkbox',
			'section' => 'integrations',
		);

		if( empty( $options['available_tags'] ) ) {
			$options['available_tags'] = array();
		}

		$settings['bbp_allow_tags'] = array(
			'title'   => __( 'Required tags (any)', 'wp-fusion' ),
			'desc'    => __( 'If the user doesn\'t have any of the tags specified, they will be redirected to the URL below. You must specify a redirect for forum restriction to work.', 'wp-fusion' ),
			'type'    => 'multi_select',
			'choices' => $options['available_tags'],
			'section' => 'integrations'
		);

		$settings['bbp_redirect'] = array(
			'title'   => __( 'Redirect URL', 'wp-fusion' ),
			'type'    => 'text',
			'section' => 'integrations'
		);

		if ( ! isset( $options['bbp_lock'] ) || $options['bbp_lock'] != 1 ) {
			$settings['bbp_lock_all']['disabled'] = true;
			$settings['bbp_allow_tags']['disabled'] = true;
			$settings['bbp_redirect']['disabled']   = true;
		}

		return $settings;

	}

	/**
	 * Shows assign tags field
	 *
	 * @access public
	 * @return mixed
	 */

	public function show_field_bbp_allow_tags( $id, $field ) {

		$settings = wp_fusion()->settings->get( $id );

		if ( empty( $settings ) ) {
			$settings = array();
		}

		$args = array(
			'setting' 		=> $settings,
			'meta_name'		=> 'wpf_options',
			'field_id'		=> $id,
			'disabled'		=> $field['disabled'],
		);

		wpf_render_tag_multiselect( $args );

	}

	/**
	 * Adds Integrations tab if not already present
	 *
	 * @access public
	 * @return void
	 */

	public function configure_sections( $page, $options ) {

		if ( ! isset( $page['sections']['integrations'] ) ) {
			$page['sections'] = wp_fusion()->settings->insert_setting_after( 'contact-fields', $page['sections'], array( 'integrations' => __( 'Integrations', 'wp-fusion' ) ) );
		}

		return $page;

	}


	/**
	 * Sets topics to inherit permissions from their forums
	 *
	 * @access  public
	 * @return  int Post ID
	 */

	public function redirect_post_id( $post_id ) {

		if ( 'topic' == get_post_type( $post_id ) ) {

			$settings = get_post_meta( $post_id, 'wpf-settings', true );

			if ( empty( $settings ) || empty( $settings['lock_content'] ) ) {

				// If the discussion is open then inherit permissions from the parent forum

				$forum_id = get_post_meta( $post_id, '_bbp_forum_id', true );

				if ( ! empty( $forum_id ) ) {
					$post_id = $forum_id;
				}
			}
		}

		return $post_id;

	}

	/**
	 * Inherit protections for replies from the topic
	 *
	 * @access  public
	 * @return  int Post ID
	 */

	public function user_can_access_post_id( $post_id ) {

		if ( 'reply' == get_post_type( $post_id ) ) {

			$post_id = get_post_meta( $post_id, '_bbp_topic_id', true );

		}

		return $post_id;

	}

	/**
	 * Inherit protections for replies from the topic
	 *
	 * @access  public
	 * @return  array Access Meta
	 */

	public function inherit_permissions_from_forum( $access_meta, $post_id ) {

		if ( empty( $access_meta ) || empty( $access_meta['lock_content'] ) ) {

			if ( 'topic' == get_post_type( $post_id ) ) {

				$forum_id = get_post_meta( $post_id, '_bbp_forum_id', true );

				$access_meta = get_post_meta( $forum_id, 'wpf-settings', true );

			}

		}

		return $access_meta;

	}

	/**
	 * Re-add the content filter after bbPress has removed it for theme compatibility
	 *
	 * @access public
	 * @return void
	 */

	public function prepare_content_filter( $post_id ) {

		add_action( 'bbp_head', array( $this, 'add_content_filter' ) );

	}


	/**
	 * Re-add the content filter after bbPress has removed it for theme compatibility
	 *
	 * @access public
	 * @return void
	 */

	public function add_content_filter( $post_id ) {

		add_filter( 'the_content', array( wp_fusion()->access, 'restricted_content_filter' ) );

	}

	/**
	 * Enables redirects for bbP forum archives
	 *
	 * @access public
	 * @return bool
	 */

	public function begin_redirect() {

		global $post;

		if(!is_object($post)) {
			return false;
		}

		// Check if forum archive is locked
		if ( ( bbp_is_forum_archive() && wp_fusion()->settings->get( 'bbp_lock' ) == true ) || ( bbp_is_forum( $post->ID ) && wp_fusion()->settings->get( 'bbp_lock_all' ) == true ) )  {

			$redirect = wp_fusion()->settings->get( 'bbp_redirect' );

			$redirect = apply_filters( 'wpf_redirect_url', $redirect, $post_id = false );

			if ( ! wpf_is_user_logged_in() && ! empty( $redirect ) ) {
				return $redirect;
			}

			$user_id = wpf_get_current_user_id();

			// If admins are excluded from restrictions
			if ( wp_fusion()->settings->get( 'exclude_admins' ) == true && user_can($user_id, 'manage_options') ) {
				return false;
			}

			$user_tags = wp_fusion()->user->get_tags( $user_id );

			// If user has no valid tags
			if ( empty( $user_tags ) && !empty($redirect) ) {

				wp_redirect( $redirect );
				exit();

			}

			$allow_tags = wp_fusion()->settings->get( 'bbp_allow_tags' );

			foreach ( (array) $allow_tags as $tag ) {

				if ( in_array( $tag, $user_tags ) ) {
					return;
				}
			}


			if ( ! empty( $redirect ) ) {

				wp_redirect( $redirect );
				exit();

			}

		}

		return;

	}


	/**
	 * Applies a class to bbPress forums if they're locked
	 *
	 * @access  public
	 * @return  array Classes
	 */

	public function get_forum_class( $classes, $forum_id ) {

		if ( ! wp_fusion()->access->user_can_access( $forum_id ) ) {
			$classes[] = 'wpf-locked';
		}

		return $classes;

	}


}

new WPF_bbPress;
