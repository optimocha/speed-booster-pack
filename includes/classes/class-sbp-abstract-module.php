<?php

namespace SpeedBooster;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Just in case. Maybe we will need it in the future
abstract class SBP_Abstract_Module {
	protected $should_sbp_run = true;

	public function __construct() {
		add_action( 'set_current_user', [ $this, 'check_user_roles' ] );
	}

	public function check_user_roles() {
		$user               = wp_get_current_user();
		$sbp_disabled_roles = sbp_get_option( 'roles_to_disable_sbp', [] );
		$roles              = $user->roles;
		if ( $roles && $sbp_disabled_roles ) {
			foreach ( $roles as $role ) {
				if ( in_array( $role, $sbp_disabled_roles ) ) {
					$this->should_sbp_run = false;
					break;
				}
			}
		}
	}
}