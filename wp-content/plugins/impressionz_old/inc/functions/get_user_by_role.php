<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

	/**
	* User roles helper functions
	*
	* @package WordPress
	* @subpackage GOX 
	* 
	* @since 1.0
	*/

if ( ! function_exists( 'get_user_roles' ) ) :
	/**
	* Get User roles
	*
	* Debug function, display all user roles
	*
	* @return array
	*/
	function get_user_roles( $user_id = null ) {
    $user = get_userdata( $user_id );
    return empty( $user ) ? array() : $user->roles;
	}
endif;


if ( ! function_exists( 'is_user_role' ) ) :
	/**
	* Check if user in the role 
	*
	* if ( is_user_role( $user_id ) == "administrator" )
	*
	* @return false/true
	*/
	function is_user_role( $user_id, $role ) {
		    return in_array( $role, get_user_roles( $user_id ) );
	}
endif;