<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


class WPF_LifterLMS extends WPF_Integrations_Base {

	/**
	 * Gets things started
	 *
	 * @access  public
	 * @since   1.0
	 * @return  void
	 */

	public function init() {

		$this->slug = 'lifterlms';

		// Course stuff
		add_action( 'llms_user_enrolled_in_course', array( $this, 'course_begin' ), 10, 2 );
		add_action( 'lifterlms_course_completed', array( $this, 'course_lesson_complete' ), 10, 2 );
		add_action( 'lifterlms_lesson_completed', array( $this, 'course_lesson_complete' ), 10, 2 );
		add_action( 'lifterlms_quiz_completed', array( $this, 'quiz_complete' ), 10, 3 );

		// Membership
		add_action( 'llms_user_added_to_membership_level', array( $this, 'added_to_membership' ), 10, 2 );
		add_action( 'llms_user_removed_from_membership_level', array( $this, 'removed_from_membership' ), 10, 2 );
		add_action( 'wpf_tags_modified', array( $this, 'tags_modified' ), 10, 2 );

		// Access plans
		add_action( 'lifterlms_access_plan_purchased', array( $this, 'access_plan_purchased' ), 10, 2 );
		add_action( 'llms_access_plan_mb_after_row_five', array( $this, 'access_plan_settings' ), 10, 3 );
		add_action( 'llms_access_plan_saved', array( $this, 'save_plan' ), 10, 3 );

		// Voucher
		add_action( 'llms_voucher_used', array( $this, 'voucher_used' ), 10, 3 );

		// Engagements (Work in progress)
		add_filter( 'lifterlms_engagement_triggers', array( $this, 'engagement_triggers' ) );
		add_filter( 'llms_metabox_fields_lifterlms_engagement', array( $this, 'engagement_fields' ) );
		add_action( 'save_post', array( $this, 'save_engagement_data' ) );
		add_action( 'wpf_tags_modified', array( $this, 'update_engagements' ), 10, 2 );

		// Groups (beta)
		add_action( 'llms_user_group_enrollment_created', array( $this, 'group_enrollment_created' ), 10, 2 );
		add_action( 'llms_user_group_enrollment_updated', array( $this, 'group_enrollment_created' ), 10, 2 );
		add_action( 'llms_user_enrollment_deleted', array( $this, 'group_unenrollment' ), 10, 4 );
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );

		// Settings
		add_filter( 'llms_metabox_fields_lifterlms_membership', array( $this, 'membership_metabox' ) );
		add_filter( 'llms_metabox_fields_lifterlms_course_options', array( $this, 'course_lesson_metabox' ) );
		add_filter( 'llms_metabox_fields_lifterlms_lesson', array( $this, 'course_lesson_metabox' ) );
		add_filter( 'llms_metabox_fields_lifterlms_voucher', array( $this, 'voucher_metabox' ) );
		add_action( 'llms_builder_register_custom_fields', array( $this, 'quiz_settings' ), 100 );
		add_action( 'save_post', array( $this, 'save_meta_box_data' ), 20 );

		// Registration / profile / checkout stuff
		add_filter( 'wpf_meta_field_groups', array( $this, 'add_meta_field_group' ), 20 );
		add_filter( 'wpf_meta_fields', array( $this, 'prepare_meta_fields' ) );
		add_filter( 'wpf_watched_meta_fields', array( $this, 'watch_meta_fields' ) );
		add_filter( 'wpf_user_register', array( $this, 'user_register' ), 10, 2 );
		add_action( 'lifterlms_user_updated', array( $this, 'user_updated' ), 10, 3 );

		// Batch operations
		add_filter( 'wpf_export_options', array( $this, 'export_options' ) );
		add_action( 'wpf_batch_lifter_memberships_init', array( $this, 'batch_init' ) );
		add_action( 'wpf_batch_lifter_memberships', array( $this, 'batch_step' ) );

	}


	/**
	 * Adds WPF settings to LLMS Membership meta box
	 *
	 * @access  public
	 * @return  array Fields
	 */

	public function membership_metabox( $fields ) {

		global $post;

		$wpf_settings = array(
			'link_tag'              => array(),
			'apply_tags_membership' => array(),
			'remove_tags'           => false,
		);

		if ( get_post_meta( $post->ID, 'wpf-settings', true ) ) {
			$wpf_settings = array_merge( $wpf_settings, get_post_meta( $post->ID, 'wpf-settings', true ) );
		}

		$values = $this->get_tag_select_values( $wpf_settings );

		$fields[] = array(
			'title'  => 'WP Fusion',
			'fields' => array(
				array(
					'class'           => 'select4-wpf-tags',
					'data_attributes' => array(
						'placeholder' => 'Select tags',
						'no-dupes'    => 'wpf-settings[link_tag]',
					),
					'desc'            => __( 'These tags will be applied when a member purchases or registers for this membership level.', 'wp-fusion' ),
					'id'              => 'wpf-settings[apply_tags_membership]',
					'label'           => __( 'Apply Tags', 'wp-fusion' ),
					'multi'           => '1',
					'type'            => 'select',
					'value'           => $values,
					'selected'        => $wpf_settings['apply_tags_membership'],
				),
				array(
					'class'           => 'select4-wpf-tags',
					'data_attributes' => array(
						'placeholder' => 'Select tags',
						'limit'       => '1',
						'no-dupes'    => 'wpf-settings[apply_tags_membership]',
					),
					'desc'            => sprintf( __( 'This tag will be applied in %1$s when a user is enrolled, and will be removed when a user is unenrolled. Likewise, if this tag is applied to a user from within %2$s, they will be automatically enrolled in this membership. If this tag is removed, the user will be removed from the membership.', 'wp-fusion' ), wp_fusion()->crm->name, wp_fusion()->crm->name ),
					'id'              => 'wpf-settings[link_tag]',
					'label'           => __( 'Link with Tag / Auto-Enrollment Tag', 'wp-fusion' ),
					'multi'           => '1',
					'type'            => 'select',
					'value'           => $values,
					'selected'        => $wpf_settings['link_tag'],
				),
				array(
					'type'       => 'checkbox',
					'label'      => __( 'Remove Tags', 'wp-fusion' ),
					'desc'       => __( 'Remove tags specified in "Apply Tags" if membership is cancelled.', 'wp-fusion' ),
					'id'         => 'wpf-settings[remove_tags]',
					'class'      => '',
					'value'      => $wpf_settings['remove_tags'],
					'desc_class' => 'd-3of4 t-3of4 m-1of2',
				),
			),
		);

		return $fields;

	}

	/**
	 * Adds WPF settings to LLMS Membership meta box
	 *
	 * @access  public
	 * @return  array Fields
	 */

	public function course_lesson_metabox( $fields ) {

		global $post;

		$wpf_settings = array(
			'apply_tags_start'    => array(),
			'apply_tags_complete' => array(),
			'link_tag'            => array(),
		);

		if ( get_post_meta( $post->ID, 'wpf-settings', true ) ) {
			$wpf_settings = array_merge( $wpf_settings, get_post_meta( $post->ID, 'wpf-settings', true ) );
		}

		$values = $this->get_tag_select_values( $wpf_settings );

		$fields['wpf'] = array(
			'title'  => 'WP Fusion',
			'fields' => array(),
		);

		if ( $post->post_type == 'course' ) {

			$fields['wpf']['fields'][] = array(
				'class'           => 'select4-wpf-tags',
				'data_attributes' => array(
					'placeholder' => 'Select tags',
				),
				'desc'            => __( 'Apply these tags when user enrolled in course.', 'wp-fusion' ),
				'id'              => 'wpf-settings[apply_tags_start]',
				'label'           => __( 'Apply tags when enrolled', 'wp-fusion' ),
				'multi'           => '1',
				'type'            => 'select',
				'value'           => $values,
				'selected'        => $wpf_settings['apply_tags_start'],
			);

			$fields['wpf']['fields'][] = array(
				'class'           => 'select4-wpf-tags',
				'data_attributes' => array(
					'placeholder' => 'Select tags',
					'limit'       => '1',
					'no-dupes'    => 'wpf-settings[apply_tags_start]',
				),
				'desc'            => sprintf( __( 'This tag will be applied in %1$s when a user is enrolled, and will be removed when a user is unenrolled. Likewise, if this tag is applied to a user from within %2$s, they will be automatically enrolled in this course. If this tag is removed, the user will be removed from the course.', 'wp-fusion' ), wp_fusion()->crm->name, wp_fusion()->crm->name ),
				'id'              => 'wpf-settings[link_tag]',
				'label'           => __( 'Link with Tag / Auto-Enrollment Tag', 'wp-fusion' ),
				'multi'           => '1',
				'type'            => 'select',
				'value'           => $values,
				'selected'        => $wpf_settings['link_tag'],
			);

		}

		$fields['wpf']['fields'][] = array(
			'class'           => 'select4-wpf-tags',
			'data_attributes' => array(
				'placeholder' => __( 'Select tags', 'wp-fusion' ),
				'data-limit'  => '1',
			),
			'desc'            => sprintf( __( 'Apply these tags when %s marked complete.', 'wp-fusion' ), $post->post_type ),
			'id'              => 'wpf-settings[apply_tags_complete]',
			'label'           => __( 'Apply tags - Completed', 'wp-fusion' ),
			'multi'           => '1',
			'type'            => 'select',
			'value'           => $values,
			'selected'        => $wpf_settings['apply_tags_complete'],
		);

		return $fields;

	}

	/**
	 * Adds WPF settings to LLMS Voucher meta box
	 *
	 * @access  public
	 * @return  array Fields
	 */

	public function voucher_metabox( $fields ) {

		global $post;

		$settings = array(
			'apply_tags_voucher' => array(),
		);

		if ( get_post_meta( $post->ID, 'wpf_settings_llms_voucher', true ) ) {
			$settings = array_merge( $settings, get_post_meta( $post->ID, 'wpf_settings_llms_voucher', true ) );
		}

		$values = $this->get_tag_select_values( $settings );

		$fields[] = array(
			'title'  => 'WP Fusion',
			'fields' => array(
				array(
					'class'           => 'select4-wpf-tags',
					'data_attributes' => array(
						'placeholder' => 'Select tags',
					),
					'desc'            => sprintf( __( 'These tags will be applied in %s when the voucher is used.', 'wp-fusion' ), wp_fusion()->crm->name ),
					'id'              => 'wpf_settings_llms_voucher[apply_tags_voucher]',
					'label'           => __( 'Apply Tags', 'wp-fusion' ),
					'multi'           => '1',
					'type'            => 'select',
					'value'           => $values,
					'selected'        => $settings['apply_tags_voucher'],
				),
			),
		);

		return $fields;

	}

	/**
	 * Adds WPF settings to quiz settings
	 *
	 * @access  public
	 * @return  array Fields
	 */

	public function quiz_settings( $fields ) {

		$available_tags = wp_fusion()->settings->get( 'available_tags', array() );

		$data = array();

		foreach ( $available_tags as $id => $label ) {

			if ( is_array( $label ) ) {
				$label = $label['label'];
			}

			$data[ $id ] = $label;

		}

		asort( $data );

		$fields['quiz']['wp_fusion'] = array(
			'title'      => 'WP Fusion',
			'toggleable' => true,
			'fields'     => array(
				array(
					array(
						'attribute' => 'apply_tags_attempted',
						'label'     => __( 'Apply Tags - Quiz Attempted', 'wp-fusion' ),
						'type'      => 'select',
						'class'     => 'FOO',
						'multiple'  => true,
						'options'   => $data,
					),
					array(
						'attribute' => 'apply_tags_passed',
						'label'     => __( 'Apply Tags - Quiz Passed', 'wp-fusion' ),
						'type'      => 'select',
						'multiple'  => true,
						'options'   => $data,
					),
				),
			),
		);

		return $fields;

	}


	/*
	 * Adds WPF settings to LLMS access plan meta box
	 *
	 * @access  public
	 * @return  mixed Access Plan Settings
	 */

	public function access_plan_settings( $plan, $id, $order ) {

		?>
		<div class="llms-metabox-field d-1of3">

			<?php if ( empty( $plan ) ) : ?>
				<label>Save this access plan to configure WP Fusion tags.</label></div>
				<?php return; ?>
			<?php endif; ?>

			<label>Apply Tags</label>
			<?php

			$settings = get_post_meta( $plan->id, 'wpf-settings-llms-plan', true );

			if ( empty( $settings ) ) {
				$settings = array( 'apply_tags' => array() );
			}

			$args = array(
				'setting'      => $settings,
				'meta_name'    => '_llms_plans',
				'field_id'     => $order,
				'field_sub_id' => 'apply_tags',
			);

			wpf_render_tag_multiselect( $args );

			?>
		</div>

		<?php

	}

	/**
	 * Save access plan
	 *
	 * @access  public
	 * @return  void
	 */

	public function save_plan( $plan, $raw_plan_data, $metabox ) {

		if ( ! empty( $raw_plan_data['apply_tags'] ) ) {

			update_post_meta( $raw_plan_data['id'], 'wpf-settings-llms-plan', array( 'apply_tags' => $raw_plan_data['apply_tags'] ) );

		} else {

			delete_post_meta( $raw_plan_data['id'], 'wpf-settings-llms-plan' );

		}

	}

	/**
	 * Sanitize meta box data on saving
	 *
	 * @access  public
	 * @return  void
	 */

	public function save_meta_box_data( $post_id ) {

		// If this is an autosave, our form has not been submitted, so we don't want to do anything.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Don't update on revisions
		if ( ! isset( $_POST['post_type'] ) || $_POST['post_type'] == 'revision' ) {
			return;
		}

		if ( $_POST['post_type'] == 'llms_membership' || $_POST['post_type'] == 'course' && isset( $_POST['wpf-settings'] ) ) {

			// Memberships and courses

			$settings = $_POST['wpf-settings'];

			if ( ! empty( $settings['link_tag'] ) && is_array( $settings['link_tag'] ) && count( $settings['link_tag'] ) > 1 ) {

				foreach ( $settings['link_tag'] as $i => $tag ) {

					if ( empty( $tag ) ) {
						unset( $settings['link_tag'][ $i ] );
					}
				}

				update_post_meta( $post_id, 'wpf-settings', $settings );

			}
		} elseif ( 'llms_voucher' == $_POST['post_type'] ) {

			// Vouchers

			if ( ! empty( $_POST['wpf_settings_llms_voucher'] ) ) {
				update_post_meta( $post_id, 'wpf_settings_llms_voucher', $_POST['wpf_settings_llms_voucher'] );
			} else {
				delete_post_meta( $post_id, 'wpf_settings_llms_voucher' );
			}
		}

		// Save access plan settings
		if ( ! empty( $_POST['wpf-settings-llms-plan'] ) ) {

			foreach ( $_POST['wpf-settings-llms-plan'] as $plan_id => $setting ) {

				update_post_meta( $plan_id, 'wpf-settings-llms-plan', $setting );

			}
		}

	}

	/**
	 * Apply tags when access plan purchased
	 *
	 * @access  public
	 * @return  void
	 */

	public function access_plan_purchased( $user_id, $plan_id ) {

		$settings = get_post_meta( $plan_id, 'wpf-settings-llms-plan', true );

		if ( ! empty( $settings ) && ! empty( $settings['apply_tags'] ) ) {
			wp_fusion()->user->apply_tags( $settings['apply_tags'], $user_id );
		}

	}

	/**
	 * Add WPF engagement trigger
	 *
	 * @access  public
	 * @return  array Triggers
	 */

	public function engagement_triggers( $triggers ) {

		$triggers['tag_applied'] = __( 'A tag is applied to a student (WP Fusion)', 'wp-fusion' );

		return $triggers;

	}

	/**
	 * Add WPF engagement fields
	 *
	 * @access  public
	 * @return  array Fields
	 */

	public function engagement_fields( $fields ) {

		$available_tags = wp_fusion()->settings->get( 'available_tags', array() );

		if ( is_array( reset( $available_tags ) ) ) {

			foreach ( $available_tags as $id => $data ) {
				$values[] = array(
					'key'   => $id,
					'title' => $data['label'],
				);
			}
		} else {
			foreach ( $available_tags as $id => $label ) {

				// Fix for LLMS auto-selecting "0" if available in $values
				if ( empty( $label ) ) {
					continue;
				}

				$values[] = array(
					'key'   => $id,
					'title' => $label,
				);

			}
		}

		global $post;

		$new_field = array(
			'allow_null'       => false,
			'class'            => 'llms-select2',
			'controller'       => '#_llms_trigger_type',
			'controller_value' => 'tag_applied',
			'data_attributes'  => array(
				'allow_clear' => true,
				'placeholder' => __( 'Select a tag', 'wp-fusion' ),
			),
			'id'               => '_llms_engagement_trigger_tag',
			'label'            => __( 'Select a tag', 'wp-fusion' ),
			'type'             => 'select',
			'value'            => $available_tags,
			'selected'         => get_post_meta( $post->ID, '_llms_engagement_trigger_tag', true ),
		);

		array_splice( $fields[0]['fields'], 2, 0, array( $new_field ) );

		return $fields;

	}

	/**
	 * Sanitize meta box data on saving
	 *
	 * @access  public
	 * @return  void
	 */

	public function save_engagement_data( $post_id ) {

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( isset( $_POST['post_type'] ) && $_POST['post_type'] == 'llms_engagement' && isset( $_POST['_llms_engagement_trigger_tag'] ) ) {

			update_post_meta( $post_id, '_llms_engagement_trigger_tag', $_POST['_llms_engagement_trigger_tag'] );

		}

	}


	/**
	 * Updates user's engagements if a trigger tag is present
	 *
	 * @access public
	 * @return void
	 */

	public function update_engagements( $user_id, $user_tags ) {

		$engagements = get_posts(
			array(
				'post_type'  => 'llms_engagement',
				'nopaging'   => true,
				'fields'     => 'ids',
				'meta_query' => array(
					array(
						'key'   => '_llms_trigger_type',
						'value' => 'tag_applied',
					),
				),
			)
		);

		if ( empty( $engagements ) ) {
			return;
		}

		$student = new LLMS_Student( $user_id );

		$student_achievements = $student->get_achievements();

		$student_achievement_ids = array();

		if ( ! empty( $student_achievements ) ) {
			foreach ( $student_achievements as $student_achievement ) {
				$student_achievement_ids[] = $student_achievement->post_id;
			}
		}

		$student_certificates = $student->get_certificates();

		$student_certificate_ids = array();

		if ( ! empty( $student_certificates ) ) {
			foreach ( $student_certificates as $student_certificate ) {
				$student_certificate_ids[] = $student_certificate->post_id;
			}
		}

		// Update role based on user tags
		foreach ( $engagements as $engagement_id ) {

			$tag      = get_post_meta( $engagement_id, '_llms_engagement_trigger_tag', true );
			$type     = get_post_meta( $engagement_id, '_llms_engagement_type', true );
			$award_id = get_post_meta( $engagement_id, '_llms_engagement', true );

			if ( in_array( $tag, $user_tags ) ) {

				if ( $type == 'achievement' && ! in_array( $award_id, $student_achievement_ids ) ) {

					wpf_log( 'info', $user_id, 'User granted LifterLMS achievement <a href="' . get_edit_post_link( $award_id ) . '" target="_blank">' . get_the_title( $award_id ) . '</a> by tag <strong>' . wp_fusion()->user->get_tag_label( $tag ) . '</strong>', array( 'source' => 'lifterlms' ) );

					$achievements = LLMS()->achievements();
					$achievements->trigger_engagement( $user_id, $award_id, $engagement_id );

				} elseif( $type == 'certificate' && ! in_array( $award_id, $student_certificate_ids ) ) {

					wpf_log( 'info', $user_id, 'User granted LifterLMS certificate <a href="' . get_edit_post_link( $award_id ) . '" target="_blank">' . get_the_title( $award_id ) . '</a> by tag <strong>' . wp_fusion()->user->get_tag_label( $tag ) . '</strong>', array( 'source' => 'lifterlms' ) );

					$certificates = LLMS()->certificates();
					$certificates->trigger_engagement( $user_id, $award_id, $engagement_id );

				}

			}

		}

	}

	/**
	 * Apply tags when member added to group
	 *
	 * @access public
	 * @return void
	 */

	public function group_enrollment_created( $student_id, $group_id ) {

		$settings = get_post_meta( $group_id, 'wpf_settings_llms_group', true );

		if ( ! empty( $settings ) && ! empty( $settings['apply_tags'] ) ) {

			wp_fusion()->user->apply_tags( $settings['apply_tags'], $student_id );

		}

	}

	/**
	 * Maybe remove tags when member removed from group
	 *
	 * @access public
	 * @return void
	 */

	public function group_unenrollment( $student_id, $post_id ) {

		if ( 'llms_group' !== get_post_type( $post_id ) ) {
			return;
		}

		$settings = get_post_meta( $post_id, 'wpf_settings_llms_group', true );

		if ( ! empty( $settings ) && ! empty( $settings['remove_tags'] ) ) {

			wp_fusion()->user->remove_tags( $settings['apply_tags'], $student_id );

		}

	}

	/**
	 * Creates Groups submenu item
	 *
	 * @access public
	 * @return void
	 */

	public function admin_menu() {

		$id = add_submenu_page(
			'edit.php?post_type=llms_group',
			sprintf( __( '%s Integration', 'wp-fusion' ), wp_fusion()->crm->name ),
			__( 'WP Fusion', 'wp-fusion' ),
			'manage_options',
			'wpf-settings',
			array( $this, 'render_groups_settings_page' )
		);

		add_action( 'load-' . $id, array( $this, 'enqueue_scripts' ) );

	}

	/**
	 * Enqueues WPF scripts and styles on Groups options page
	 *
	 * @access public
	 * @return void
	 */

	public function enqueue_scripts() {

		wp_enqueue_style( 'bootstrap', WPF_DIR_URL . 'includes/admin/options/css/bootstrap.min.css' );
		wp_enqueue_style( 'options-css', WPF_DIR_URL . 'includes/admin/options/css/options.css' );
		wp_enqueue_style( 'wpf-options', WPF_DIR_URL . 'assets/css/wpf-options.css' );

	}


	/**
	 * Renders Groups submenu item
	 *
	 * @access public
	 * @return mixed
	 */

	public function render_groups_settings_page() {

		if ( isset( $_POST['wpf_llms_groups_nonce'] ) && wp_verify_nonce( $_POST['wpf_llms_groups_nonce'], 'wpf_llms_groups' ) ) {

			foreach ( $_POST['wpf_settings_llms_groups'] as $group_id => $settings ) {

				if ( ! empty( $settings ) ) {
					update_post_meta( $group_id, 'wpf_settings_llms_group', $settings );
				} else {
					delete_post_meta( $group_id, 'wpf_settings_llms_group' );
				}
			}

			echo '<div id="message" class="updated fade"><p><strong>' . __( 'Settings saved', 'wp-fusion' ) . '</strong></p></div>';

		}

		$args = array(
			'nopaging'  => true,
			'post_type' => 'llms_group',
			'orderby'   => 'title',
		);

		$groups = get_posts( $args );

		?>

		<div class="wrap">
			<h2><?php printf( __( '%s Integration', 'wp-fusion' ), wp_fusion()->crm->name ); ?></h2>

			<form id="wpf-llms-groups-settings" action="" method="post">
				<?php wp_nonce_field( 'wpf_llms_groups', 'wpf_llms_groups_nonce' ); ?>
				<input type="hidden" name="action" value="update">

				<h4><?php _e( 'Group Tags', 'wp-fusion' ); ?></h4>
				<p class="description"><?php printf( __( 'For each LifterLMS group below, specify tags to be applied in %s when a member is enrolled in the group. You can also optionally select <strong>Remove Tags</strong> to have the tags removed when the member is removed from the group.', 'wp-fusion' ), wp_fusion()->crm->name ); ?></p>
				<br/>

				<?php if ( empty( $groups ) ) : ?>

					<strong><?php _e( 'No groups found.', 'wp-fusion' ); ?></strong>

				<?php else : ?>

					<table class="table table-hover wpf-settings-table" id="wpf-wishlist-levels-table">
						<thead>
						<tr>
							<th><?php _e( 'Group Name', 'wp-fusion' ); ?></th>
							<th><?php _e( 'Apply Tags', 'wp-fusion' ); ?></th>
							<th><?php _e( 'Remove Tags', 'wp-fusion' ); ?></th>
						</tr>
						</thead>
						<tbody>

						<?php

						foreach ( $groups as $group ) :

							$defaults = array(
								'apply_tags'  => array(),
								'remove_tags' => false,
							);

							$settings = get_post_meta( $group->ID, 'wpf_settings_llms_group', true );

							$settings = wp_parse_args( $settings, $defaults );

							?>

							<tr>
								<td><?php echo $group->post_title; ?></td>
								<td>
									<?php

									$args = array(
										'setting'      => $settings,
										'meta_name'    => 'wpf_settings_llms_groups',
										'field_id'     => $group->ID,
										'field_sub_id' => 'apply_tags',
									);

									wpf_render_tag_multiselect( $args );

									?>

								</td>
								<td>
									<input name="wpf_settings_llms_groups[<?php echo $group->ID; ?>][remove_tags]" type="checkbox" <?php checked( $settings['remove_tags'], true, true ); ?> value="1" />
								</td>
							</tr>

						<?php endforeach; ?>

						</tbody>

					</table>

				<?php endif; ?>

				<p class="submit"><input name="Submit" type="submit" class="button-primary" value="<?php _e( 'Save Changes', 'wp-fusion' ); ?>"/>
				</p>

			</form>

		</div>

		<?php

	}


	/**
	 * Triggered when user is added to a membership level
	 *
	 * @access public
	 * @return void
	 */

	public function added_to_membership( $user_id, $membership_id ) {

		// Prevent looping
		remove_action( 'wpf_tags_modified', array( $this, 'tags_modified' ), 10, 2 );

		$wpf_settings = get_post_meta( $membership_id, 'wpf-settings', true );

		if ( ! empty( $wpf_settings ) && ! empty( $wpf_settings['apply_tags_membership'] ) ) {
			wp_fusion()->user->apply_tags( $wpf_settings['apply_tags_membership'], $user_id );
		}

		if ( ! empty( $wpf_settings ) && ! empty( $wpf_settings['link_tag'] ) ) {
			wp_fusion()->user->apply_tags( $wpf_settings['link_tag'], $user_id );
		}

		wp_fusion()->user->push_user_meta( $user_id, array( 'llms_last_membership_start_date' => time() ) );

		add_action( 'wpf_tags_modified', array( $this, 'tags_modified' ), 10, 2 );

	}

	/**
	 * Triggered when user is removed from a membership level
	 *
	 * @access public
	 * @return void
	 */

	public function removed_from_membership( $user_id, $membership_id ) {

		$wpf_settings = get_post_meta( $membership_id, 'wpf-settings', true );

		// Prevent looping
		remove_action( 'wpf_tags_modified', array( $this, 'tags_modified' ), 10, 2 );

		if ( ! empty( $wpf_settings ) && ! empty( $wpf_settings['link_tag'] ) ) {
			wp_fusion()->user->remove_tags( $wpf_settings['link_tag'], $user_id );
		}

		if ( ! empty( $wpf_settings ) && isset( $wpf_settings['remove_tags'] ) && ! empty( $wpf_settings['apply_tags_membership'] ) ) {
			wp_fusion()->user->remove_tags( $wpf_settings['apply_tags_membership'], $user_id );
		}

		add_action( 'wpf_tags_modified', array( $this, 'tags_modified' ), 10, 2 );

	}

	/**
	 * Apply tags when a voucher is used
	 *
	 * @access  public
	 * @return  void
	 */

	public function voucher_used( $voucher_id, $user_id, $voucher_code ) {

		$voucher_class = new LLMS_Voucher();
		$voucher       = $voucher_class->get_voucher_by_code( $voucher_code );

		$settings = get_post_meta( $voucher->voucher_id, 'wpf_settings_llms_voucher', true );

		if ( ! empty( $settings ) && ! empty( $settings['apply_tags_voucher'] ) ) {

			wp_fusion()->user->apply_tags( $settings['apply_tags_voucher'], $user_id );

		}

	}

	/**
	 * Updates user's memberships and/or courses if a linked tag is added/removed
	 *
	 * @access public
	 * @return void
	 */

	public function tags_modified( $user_id, $user_tags ) {

		$membership_levels = get_posts(
			array(
				'post_type'   => 'llms_membership',
				'nopaging'    => true,
				'fields'      => 'ids',
				'post_status' => array( 'publish', 'private' ),
				'meta_query'  => array(
					array(
						'key'     => 'wpf-settings',
						'compare' => 'EXISTS',
					),
				),
			)
		);

		// Update role based on user tags
		foreach ( $membership_levels as $level_id ) {

			$settings = get_post_meta( $level_id, 'wpf-settings', true );

			if ( empty( $settings ) || empty( $settings['link_tag'] ) ) {
				continue;
			}

			// Fix for 0 tags
			if ( empty( $settings['link_tag'][0] ) && isset( $settings['link_tag'][1] ) ) {
				$settings['link_tag'][0] = $settings['link_tag'][1];
			}

			$tag_id = $settings['link_tag'][0];

			$student = new LLMS_Student( $user_id );

			if ( in_array( $tag_id, $user_tags ) && ! llms_is_user_enrolled( $user_id, $level_id ) ) {

				// Logger
				wpf_log( 'info', $user_id, 'User auto-enrolled in LifterLMS membership <a href="' . get_edit_post_link( $level_id ) . '" target="_blank">' . get_the_title( $level_id ) . '</a> by tag <strong>' . wp_fusion()->user->get_tag_label( $tag_id ) . '</strong>', array( 'source' => 'lifterlms' ) );

				// Prevent looping
				remove_action( 'llms_user_added_to_membership_level', array( $this, 'added_to_membership' ), 10, 2 );

				$student->enroll( $level_id, 'wpf_tag_' . sanitize_title( wp_fusion()->user->get_tag_label( $tag_id ) ) );

				add_action( 'llms_user_added_to_membership_level', array( $this, 'added_to_membership' ), 10, 2 );

			} elseif ( ! in_array( $tag_id, $user_tags ) && llms_is_user_enrolled( $user_id, $level_id ) ) {

				// Prevent looping
				remove_action( 'llms_user_removed_from_membership_level', array( $this, 'removed_from_membership' ), 10, 2 );

				$success = $student->unenroll( $level_id, 'wpf_tag_' . sanitize_title( wp_fusion()->user->get_tag_label( $tag_id ) ) );

				if ( $success ) {

					// Logger
					wpf_log( 'info', $user_id, 'User un-enrolled from LifterLMS membership <a href="' . get_edit_post_link( $level_id ) . '" target="_blank">' . get_the_title( $level_id ) . '</a> by tag <strong>' . wp_fusion()->user->get_tag_label( $tag_id ) . '</strong>', array( 'source' => 'lifterlms' ) );

				}

				add_action( 'llms_user_removed_from_membership_level', array( $this, 'removed_from_membership' ), 10, 2 );

			}
		}

		$courses = get_posts(
			array(
				'post_type'   => 'course',
				'nopaging'    => true,
				'fields'      => 'ids',
				'post_status' => array( 'publish', 'private' ),
				'meta_query'  => array(
					array(
						'key'     => 'wpf-settings',
						'compare' => 'EXISTS',
					),
				),
			)
		);

		// Update role based on user tags
		foreach ( $courses as $course_id ) {

			$settings = get_post_meta( $course_id, 'wpf-settings', true );

			if ( empty( $settings ) || empty( $settings['link_tag'] ) || empty( $settings['link_tag'][0] ) ) {
				continue;
			}

			// Fix for 0 tags
			if ( empty( $settings['link_tag'][0] ) && isset( $settings['link_tag'][1] ) ) {
				$settings['link_tag'][0] = $settings['link_tag'][1];
			}

			$tag_id = $settings['link_tag'][0];

			$student = new LLMS_Student( $user_id );

			if ( in_array( $tag_id, $user_tags ) && ! llms_is_user_enrolled( $user_id, $course_id ) ) {

				// Logger
				wpf_log( 'info', $user_id, 'User auto-enrolled in LifterLMS course <a href="' . get_edit_post_link( $course_id ) . '" target="_blank">' . get_the_title( $course_id ) . '</a> by tag <strong>' . wp_fusion()->user->get_tag_label( $settings['link_tag'][0] ) . '</strong>', array( 'source' => 'lifterlms' ) );

				$enrollment_trigger = 'wpf_tag_' . sanitize_title( wp_fusion()->user->get_tag_label( $settings['link_tag'][0] ) );

				$enrollment_trigger = apply_filters( 'wpf_llms_course_enrollment_trigger', $enrollment_trigger );

				// Prevent looping
				remove_action( 'llms_user_enrolled_in_course', array( $this, 'course_begin' ), 10, 2 );

				$student->enroll( $course_id, $enrollment_trigger );

				add_action( 'llms_user_enrolled_in_course', array( $this, 'course_begin' ), 10, 2 );

			} elseif ( ! in_array( $tag_id, $user_tags ) && llms_is_user_enrolled( $user_id, $course_id ) ) {

				$enrollment_trigger = 'wpf_tag_' . sanitize_title( wp_fusion()->user->get_tag_label( $settings['link_tag'][0] ) );

				$enrollment_trigger = apply_filters( 'wpf_llms_course_unenrollment_trigger', $enrollment_trigger );

				$success = $student->unenroll( $course_id, $enrollment_trigger );

				if ( $success ) {

					// Logger
					wpf_log( 'info', $user_id, 'User un-enrolled from LifterLMS course <a href="' . get_edit_post_link( $course_id ) . '" target="_blank">' . get_the_title( $course_id ) . '</a> by tag <strong>' . wp_fusion()->user->get_tag_label( $settings['link_tag'][0] ) . '</strong>', array( 'source' => 'lifterlms' ) );

				}
			}
		}

	}

	/**
	 * Triggered when user is enrolled in / begins course
	 *
	 * @access public
	 * @return void
	 */

	public function course_begin( $user_id, $course_id ) {

		$wpf_settings = get_post_meta( $course_id, 'wpf-settings', true );

		if ( ! empty( $wpf_settings ) ) {

			// Prevent looping
			remove_action( 'wpf_tags_modified', array( $this, 'tags_modified' ), 10, 2 );

			if ( ! empty( $wpf_settings['apply_tags_start'] ) ) {
				wp_fusion()->user->apply_tags( $wpf_settings['apply_tags_start'], $user_id );
			}

			if ( ! empty( $wpf_settings['link_tag'] ) ) {
				wp_fusion()->user->apply_tags( $wpf_settings['link_tag'], $user_id );
			}

			add_action( 'wpf_tags_modified', array( $this, 'tags_modified' ), 10, 2 );

		}

	}

	/**
	 * Triggered when course / lesson marked complete
	 *
	 * @access public
	 * @return void
	 */

	public function course_lesson_complete( $user_id, $post_id ) {

		$wpf_settings = get_post_meta( $post_id, 'wpf-settings', true );

		if ( ! empty( $wpf_settings ) && ! empty( $wpf_settings['apply_tags_complete'] ) ) {
			wp_fusion()->user->apply_tags( $wpf_settings['apply_tags_complete'], $user_id );
		}

		if ( get_post_type( $post_id ) == 'course' ) {

			update_user_meta( $user_id, 'llms_last_course_completed', get_the_title( $post_id ) );

		} elseif ( get_post_type( $post_id ) == 'lesson' ) {

			update_user_meta( $user_id, 'llms_last_lesson_completed', get_the_title( $post_id ) );

		}

	}


	/**
	 * Triggered when quiz completed
	 *
	 * @access public
	 * @return void
	 */

	public function quiz_complete( $user_id, $quiz_id, $quiz ) {

		$apply_tags_attempted = get_post_meta( $quiz_id, 'apply_tags_attempted', true );

		if ( ! empty( $apply_tags_attempted ) ) {

			wp_fusion()->user->apply_tags( $apply_tags_attempted, $user_id );

		}

		$apply_tags_passed = get_post_meta( $quiz_id, 'apply_tags_passed', true );

		if ( ! empty( $apply_tags_passed ) && $quiz->get( 'status' ) == 'pass' ) {

			wp_fusion()->user->apply_tags( $apply_tags_passed, $user_id );

		}

	}

	/**
	 * Adds LLMS field group to meta fields list
	 *
	 * @access  public
	 * @return  array Field groups
	 */

	public function add_meta_field_group( $field_groups ) {

		$field_groups['lifterlms'] = array(
			'title'  => 'LifterLMS',
			'fields' => array(),
		);

		$field_groups['lifterlms_progress'] = array(
			'title'  => 'LifterLMS Progress',
			'fields' => array(),
		);

		return $field_groups;

	}


	/**
	 * Adds LifterLMS meta fields to WPF contact fields list
	 *
	 * @access  public
	 * @return  array Meta Fields
	 */

	public function prepare_meta_fields( $meta_fields ) {

		$meta_fields['billing_address_1'] = array(
			'label' => 'Billing Address 1',
			'type'  => 'text',
			'group' => 'lifterlms',
		);

		$meta_fields['billing_address_2'] = array(
			'label' => 'Billing Address 2',
			'type'  => 'text',
			'group' => 'lifterlms',
		);

		$meta_fields['billing_city'] = array(
			'label' => 'Billing City',
			'type'  => 'text',
			'group' => 'lifterlms',
		);

		$meta_fields['billing_state'] = array(
			'label' => 'Billing State',
			'type'  => 'text',
			'group' => 'lifterlms',
		);

		$meta_fields['billing_country'] = array(
			'label' => 'Billing Country',
			'type'  => 'text',
			'group' => 'lifterlms',
		);

		$meta_fields['billing_postcode'] = array(
			'label' => 'Billing Postcode',
			'type'  => 'text',
			'group' => 'lifterlms',
		);

		$meta_fields['phone_number'] = array(
			'label' => 'Phone Number',
			'type'  => 'text',
			'group' => 'lifterlms',
		);

		$meta_fields['llms_last_membership_start_date'] = array(
			'label' => 'Membership Start Date',
			'type'  => 'date',
			'group' => 'lifterlms',
		);

		$meta_fields['llms_last_lesson_completed'] = array(
			'label' => 'Last Lesson Completed',
			'type'  => 'text',
			'group' => 'lifterlms_progress',
		);

		$meta_fields['llms_last_course_completed'] = array(
			'label' => 'Last Course Completed',
			'type'  => 'text',
			'group' => 'lifterlms_progress',
		);

		return $meta_fields;

	}

	/**
	 * Sets up last lesson / last course fields for automatic sync
	 *
	 * @access  public
	 * @return  array Meta Fields
	 */

	public function watch_meta_fields( $meta_fields ) {

		$meta_fields[] = 'llms_last_lesson_completed';
		$meta_fields[] = 'llms_last_course_completed';

		return $meta_fields;

	}


	/**
	 * Filters user meta on registration
	 *
	 * @access  public
	 * @return  array Post Data
	 */

	public function user_register( $post_data, $user_id ) {

		$field_map = array(
			'email_address'          => 'user_email',
			'password'               => 'user_pass',
			'llms_billing_address_1' => 'billing_address_1',
			'llms_billing_address_2' => 'billing_address_2',
			'llms_billing_city'      => 'billing_city',
			'llms_billing_state'     => 'billing_state',
			'llms_billing_zip'       => 'billing_postcode',
			'llms_billing_country'   => 'billing_country',
			'llms_phone'             => 'phone_number',
		);

		$post_data = $this->map_meta_fields( $post_data, $field_map );

		return $post_data;

	}


	/**
	 * Filters user meta on account update
	 *
	 * @access  public
	 * @return  void
	 */

	public function user_updated( $person_id, $post_data, $screen ) {

		$field_map = array(
			'email_address'          => 'user_email',
			'password'               => 'user_pass',
			'llms_billing_address_1' => 'billing_address_1',
			'llms_billing_address_2' => 'billing_address_2',
			'llms_billing_city'      => 'billing_city',
			'llms_billing_state'     => 'billing_state',
			'llms_billing_zip'       => 'billing_postcode',
			'llms_billing_country'   => 'billing_country',
			'llms_phone'             => 'phone_number',
		);

		$post_data = $this->map_meta_fields( $post_data, $field_map );

		wp_fusion()->user->push_user_meta( $post_data['user_id'], $post_data );

	}


	/**
	 * Gets LLMS formatted array of tag options for multiselect box
	 *
	 * @access  public
	 * @return  array Values
	 */

	public function get_tag_select_values( $settings ) {

		$available_tags = wp_fusion()->settings->get( 'available_tags', array() );

		// Handling for user created tags (like with ActiveCampaign)
		if ( is_array( wp_fusion()->crm->supports ) && in_array( 'add_tags', wp_fusion()->crm->supports ) ) {

			$tags_added         = false;
			$selected_tags_temp = array();

			foreach ( $settings as $setting ) {

				if ( is_array( $setting ) ) {
					$selected_tags_temp = array_merge( $selected_tags_temp, $setting );
				}
			}

			foreach ( $selected_tags_temp as $tag ) {

				if ( ! in_array( $tag, $available_tags ) ) {
					$available_tags[ $tag ] = $tag;
					$tags_added             = true;
				}
			}

			if ( $tags_added ) {
				wp_fusion()->settings->set( 'available_tags', $available_tags );
			}
		}

		$values = array();

		if ( is_array( reset( $available_tags ) ) ) {

			foreach ( $available_tags as $id => $data ) {
				$values[] = array(
					'key'   => $id,
					'title' => $data['label'],
				);
			}
		} else {
			foreach ( $available_tags as $id => $label ) {

				// Fix for LLMS auto-selecting "0" if available in $values
				if ( empty( $label ) ) {
					continue;
				}

				$values[] = array(
					'key'   => $id,
					'title' => $label,
				);

			}
		}

		return $values;

	}



	/**
	 * //
	 * // BATCH TOOLS
	 * //
	 **/

	/**
	 * Adds Woo Subscriptions checkbox to available export options
	 *
	 * @access public
	 * @return array Options
	 */

	public function export_options( $options ) {

		$options['lifter_memberships'] = array(
			'label'   => 'LifterLMS membership statuses',
			'title'   => 'Members',
			'tooltip' => 'Applies tags for all LifterLMS members based on the tags configured for their membership level. If memberships have been cancelled, and you\'ve selected \'Remove tags if membership is cancelled\', the tags will be removed.',
		);

		return $options;

	}

	/**
	 * Counts total number of subscriptions to be processed
	 *
	 * @access public
	 * @return array Subscriptions
	 */

	public function batch_init() {

		$membership_levels = get_posts(
			array(
				'post_type'  => 'llms_membership',
				'nopaging'   => true,
				'fields'     => 'ids',
				'meta_query' => array(
					array(
						'key'     => 'wpf-settings',
						'compare' => 'EXISTS',
					),
				),
			)
		);

		$users = array();

		foreach ( $membership_levels as $level_id ) {

			$students = llms_get_enrolled_students( $level_id, array( 'enrolled', 'cancelled' ), 5000 );
			$users    = array_merge( $users, $students );

		}

		wpf_log( 'info', 0, 'Beginning <strong>LifterLMS Memberships Statuses</strong> batch operation on ' . count( $users ) . ' members', array( 'source' => 'batch-process' ) );

		return $users;

	}

	/**
	 * Processes subscription actions in batches
	 *
	 * @access public
	 * @return void
	 */

	public function batch_step( $user_id ) {

		$member      = new LLMS_Student( $user_id );
		$enrollments = $member->get_enrollments( 'membership' );

		if ( ! empty( $enrollments['results'] ) ) {

			foreach ( $enrollments['results'] as $membership_id ) {

				$status = $member->get_enrollment_status( $membership_id );

				if ( $status == 'cancelled' ) {

					$this->removed_from_membership( $user_id, $membership_id );

				} elseif ( $status == 'enrolled' ) {

					$this->added_to_membership( $user_id, $membership_id );

				}
			}
		}

	}

}

new WPF_LifterLMS();
