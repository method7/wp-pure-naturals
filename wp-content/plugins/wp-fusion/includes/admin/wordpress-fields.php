<?php

// Contains default names and types for standard WordPress fields. Can be filtered with wpf_meta_fields
$wp_fields['first_name'] = array(
	'type'  => 'text',
	'label' => __( 'First Name', 'wp-fusion' ),
);

$wp_fields['last_name'] = array(
	'type'  => 'text',
	'label' => __( 'Last Name', 'wp-fusion' ),
);

$wp_fields['user_email'] = array(
	'type'  => 'text',
	'label' => __( 'E-mail Address', 'wp-fusion' ),
);

$wp_fields['display_name'] = array(
	'type'  => 'text',
	'label' => __( 'Profile Display Name', 'wp-fusion' ),
);

$wp_fields['nickname'] = array(
	'type'  => 'text',
	'label' => __( 'Nickname', 'wp-fusion' ),
);

$wp_fields['user_login'] = array(
	'type'  => 'text',
	'label' => __( 'Username', 'wp-fusion' ),
);

$wp_fields['user_id'] = array(
	'type'  => 'integer',
	'label' => __( 'User ID', 'wp-fusion' ),
);

$wp_fields['locale'] = array(
	'type'  => 'text',
	'label' => __( 'Language', 'wp-fusion' ),
);

$wp_fields['role'] = array(
	'type'  => 'text',
	'label' => __( 'User Role', 'wp-fusion' ),
);

$wp_fields['wp_capabilities'] = array(
	'type'  => 'multiselect',
	'label' => __( 'User Capabilities', 'wp-fusion' ),
);

$wp_fields['user_pass'] = array(
	'type'  => 'text',
	'label' => __( 'Password', 'wp-fusion' ),
);

$wp_fields['user_registered'] = array(
	'type'  => 'date',
	'label' => __( 'User Registered', 'wp-fusion' ),
);

$wp_fields['description'] = array(
	'type'  => 'textarea',
	'label' => __( 'Biography', 'wp-fusion' ),
);

$wp_fields['user_url'] = array(
	'type'  => 'text',
	'label' => __( 'Website (URL)', 'wp-fusion' ),
);

$wp_fields['leadsource'] = array(
	'type'  => 'text',
	'label' => __( 'Lead Source', 'wp-fusion' ),
	'group' => 'leadsource',
);

$wp_fields['utm_campaign'] = array(
	'type'  => 'text',
	'label' => __( 'Google Analytics Campaign', 'wp-fusion' ),
	'group' => 'leadsource',
);

$wp_fields['utm_source'] = array(
	'type'  => 'text',
	'label' => __( 'Google Analytics Source', 'wp-fusion' ),
	'group' => 'leadsource',
);

$wp_fields['utm_medium'] = array(
	'type'  => 'text',
	'label' => __( 'Google Analytics Medium', 'wp-fusion' ),
	'group' => 'leadsource',
);

$wp_fields['utm_term'] = array(
	'type'  => 'text',
	'label' => __( 'Google Analytics Term', 'wp-fusion' ),
	'group' => 'leadsource',
);

$wp_fields['utm_content'] = array(
	'type'  => 'text',
	'label' => __( 'Google Analytics Content', 'wp-fusion' ),
	'group' => 'leadsource',
);

$wp_fields['gclid'] = array(
	'type'  => 'text',
	'label' => __( 'Google Click Identifier', 'wp-fusion' ),
	'group' => 'leadsource',
);

$wp_fields['original_ref'] = array(
	'type'  => 'text',
	'label' => __( 'Original Referrer', 'wp-fusion' ),
	'group' => 'leadsource',
);

$wp_fields['landing_page'] = array(
	'type'  => 'text',
	'label' => __( 'Landing Page', 'wp-fusion' ),
	'group' => 'leadsource',
);
