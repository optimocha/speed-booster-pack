<?php

namespace SpeedBoosterPack\Booster;

use SpeedBoosterPack\Common\Helper;

defined('ABSPATH') || exit;

// Just in case. Maybe we will need it in the future
abstract class AbstractModule {

    protected bool $should_sbp_run = true;

	public function __construct() {
		add_action( 'set_current_user', [ $this, 'checkUserRoles' ] );
	}

	public function checkUserRoles() {
		$user               = wp_get_current_user();
		$sbp_disabled_roles = Helper::getOption( 'roles_to_disable_sbp', [] );
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