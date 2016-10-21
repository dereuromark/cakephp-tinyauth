<?php

namespace TinyAuth\Auth;

use Cake\Utility\Hash;

/**
 * Convenience wrapper to access Auth data and check on rights/roles.
 *
 * Simply add it at the class file:
 *
 *   trait AuthUserTrait;
 *
 * But needs
 *
 *   protected function _getUser() {}
 *
 * to be implemented in the using class.
 *
 * Expects the Role session infos to be either
 *     - `Auth.User.role_id` (single) or
 *     - `Auth.User.Role` (multi - flat array of roles, or array role data)
 * and can be adjusted via constants and defined().
 * Same goes for Right data.
 *
 * If roles are defined in configuration file (non-db roles setup) the constant
 * `USER_ROLE_KEY` has to be defined in `bootstrap.php`.
 * ```
 * // if role key in User model is role_id
 * define('USER_ROLE_KEY', 'role_id');
 * ```
 *
 * Note: This uses AuthComponent internally to work with both stateful and stateless auth.
 *
 * @author Mark Scherer
 * @license MIT
 */
trait AuthUserTrait {

	/**
	 * Get the user id of the current session.
	 *
	 * This can be used anywhere to check if a user is logged in.
	 *
	 * @return mixed User id if existent, null otherwise.
	 */
	public function id() {
		$field = $this->config('idColumn');

		return $this->user($field);
	}

	/**
	 * This check can be used to tell if a record that belongs to some user is the
	 * current logged in user
	 *
	 * @param string|int $userId
	 * @return bool
	 */
	public function isMe($userId) {
		$field = $this->config('idColumn');
		return $userId && (string)$userId === (string)$this->user($field);
	}

	/**
	 * Get the user data of the current session.
	 *
	 * @param string|null $key Key in dot syntax.
	 * @return mixed Data
	 */
	public function user($key = null) {
		$user = $this->_getUser();
		if ($key === null) {
			return $user;
		}
		return Hash::get($user, $key);
	}

	/**
	 * Get the role(s) of the current session.
	 *
	 * It will return the single role for single role setup, and a flat
	 * list of roles for multi role setup.
	 *
	 * @return array Array of roles
	 */
	public function roles() {
		$user = $this->user();
		if (!$user) {
			return [];
		}

		$roles = $this->_getUserRoles($user);

		return $roles;
	}

	/**
	 * Check if the current session has this role.
	 *
	 * @param mixed $expectedRole
	 * @param mixed|null $providedRoles
	 * @return bool Success
	 */
	public function hasRole($expectedRole, $providedRoles = null) {
		if ($providedRoles !== null) {
			$roles = (array)$providedRoles;
		} else {
			$roles = $this->roles();
		}

		if (!$roles) {
			return false;
		}

		if (array_key_exists($expectedRole, $roles) || in_array($expectedRole, $roles)) {
			return true;
		}
		return false;
	}

	/**
	 * Check if the current session has one of these roles.
	 *
	 * You can either require one of the roles (default), or you can require all
	 * roles to match.
	 *
	 * @param mixed $expectedRoles
	 * @param bool $oneRoleIsEnough (if all $roles have to match instead of just one)
	 * @param mixed|null $providedRoles
	 * @return bool Success
	 */
	public function hasRoles($expectedRoles, $oneRoleIsEnough = true, $providedRoles = null) {
		if ($providedRoles !== null) {
			$roles = $providedRoles;
		} else {
			$roles = $this->roles();
		}

		$expectedRoles = (array)$expectedRoles;
		if (!$expectedRoles) {
			return false;
		}

		$count = 0;
		foreach ($expectedRoles as $expectedRole) {
			if ($this->hasRole($expectedRole, $roles)) {
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
