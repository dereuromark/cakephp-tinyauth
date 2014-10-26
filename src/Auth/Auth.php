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
 * Convenience wrapper to access Auth data and check on rights/roles.
 *
 * It can be used anywhere in the application due to static access.
 * So in the view we can use this shortcut to check if a user is logged in:
 *
 *   if (Auth::id()) {
 *     // Display element
 *   }
 *
 * Simply add it at the end of your bootstrap file (after the plugin is loaded):
 *
 *   use Tools\Auth;
 *
 * Expects the Role session infos to be either
 * 	- `Auth.User.role_id` (single) or
 * 	- `Auth.User.Role` (multi - flat array of roles, or array role data)
 * and can be adjusted via constants and defined().
 * Same goes for Right data.
 *
 * Note: This uses AuthComponent internally to work with both stateful and stateless auth.
 *
 * @author Mark Scherer
 * @license MIT
 * @php 5
 */
class Auth {

	/**
	 * Get the user id of the current session.
	 *
	 * This can be used anywhere to check if a user is logged in.
	 *
	 * @return mixed User id if existent, null otherwise.
	 * @deprecated Not usable in CakePHP 3.
	 */
	public static function id() {
		return AuthComponent::user('id');
	}

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
	 * Get the user data of the current session.
	 *
	 * @param string $key Key in dot syntax.
	 * @return mixed Data
	 * @deprecated Not usable in CakePHP 3.
	 */
	public static function user($key = null) {
		return AuthComponent::user($key);
	}

	/**
	 * Check if the current session has this role.
	 *
	 * @param mixed $role
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
	 * @param mixed $roles
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

	/**
	 * Check if the current session has this right.
	 *
	 * Rights can be an additional element to give permissions, e.g.
	 * the right to send messages/emails, to friend request other users,...
	 * This can be set via Right model and stored in the Auth array upon login
	 * the same way the roles are.
	 *
	 * @param mixed $role
	 * @param array $currentRights
	 * @return bool Success
	 */
	public static function hasRight($expectedRight, $currentRights) {
		$currentRights = (array)$currentRights;
		if (array_key_exists($expectedRight, $currentRights) && !empty($currentRights[$expectedRight])) {
			return true;
		}
		return false;
	}

}
