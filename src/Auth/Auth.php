<?php
namespace TinyAuth\Auth;

if (!defined('USER_ROLE_KEY')) {
	define('USER_ROLE_KEY', 'Role');
}
if (!defined('USER_RIGHT_KEY')) {
	define('USER_RIGHT_KEY', 'Right');
}

use App\Controller\Component\AuthComponent;
use Cake\Utility\Hash;

/**
 * Convenience wrapper to access Auth data and check on roles.
 *
 *   use TinyAuth\Auth\Auth;
 *
 * Expects the Role session infos to be either
 * 	- `Auth.User.role_id` (single) or
 * 	- `Auth.User.Role` (multi - flat array of roles, or array role data)
 * and can be adjusted via constants and defined().
 *
 * @author Mark Scherer
 * @license MIT
 * @deprecated Static access not possible in 3.x in a clean way, Use Tools.AuthUser component and helper instead.
 */
class Auth {

	/**
	 * Normalizes roles.
	 *
	 * It will return the single role for single role setup, and a flat
	 * list of roles for multi role setup.
	 *
	 * @param string|array Roles as simple or complex (Role.id) array.
	 * @return array Roles
	 */
	public static function roles($roles) {
		if (!is_array($roles)) {
			return (array)$roles;
		}
		if (isset($roles[0]['id'])) {
			$roles = Hash::extract($roles, '{n}.id');
		}
		return $roles;
	}

	/**
	 * Check if the current session has this role.
	 *
	 * @param mixed $expectedRole
	 * @param array $currentRoles
	 * @return bool Success
	 */
	public static function hasRole($expectedRole, $currentRoles) {
		$currentRoles = static::roles($currentRoles);
		return in_array($expectedRole, $currentRoles);
	}

	/**
	 * Check if the current session has one of these roles.
	 *
	 * You can either require one of the roles (default), or you can require all
	 * roles to match.
	 *
	 * @param mixed $expectedRoles
	 * @param bool $oneRoleIsEnough (if all $roles have to match instead of just one)
	 * @param array $currentRoles
	 * @return bool Success
	 */
	public static function hasRoles($expectedRoles, $currentRoles, $oneRoleIsEnough = true) {
		$expectedRoles = (array)$expectedRoles;
		if (empty($expectedRoles)) {
			return false;
		}
		$count = 0;
		foreach ($expectedRoles as $role) {
			if (static::hasRole($role, $currentRoles)) {
				if ($oneRoleIsEnough) {
					return true;
				}
				$count++;
			} else {
				if (!$oneRoleIsEnough) {
					return false;
				}
			}
		}

		if ($count === count($expectedRoles)) {
			return true;
		}
		return false;
	}

}
