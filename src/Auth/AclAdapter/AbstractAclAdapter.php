<?php

namespace TinyAuth\Auth\AclAdapter;

use Cake\Core\Configure;
use TinyAuth\Utility\Utility;

abstract class AbstractAclAdapter implements AclAdapterInterface {

	/**
	 * Loads the raw section => data array from the underlying config source.
	 *
	 * The returned shape matches `parse_ini_file($path, true)` — top-level keys are
	 * section identifiers (e.g. `Tags`, `Plugin.Admin/Tags`) and each value is a
	 * `actions => roles` map.
	 *
	 * @param array<string, mixed> $config Current TinyAuth configuration values.
	 * @return array<string, array<string, string>>
	 */
	abstract protected function parseConfig(array $config): array;

	/**
	 * @param array $availableRoles A list of available user roles.
	 * @param array<string, mixed> $config Current TinyAuth configuration values.
	 * @return array
	 */
	public function getAcl(array $availableRoles, array $config): array {
		$sections = $this->parseConfig($config);

		$acl = [];
		foreach ($sections as $key => $array) {
			$acl[$key] = Utility::deconstructIniKey($key);
			if (Configure::read('debug')) {
				$acl[$key]['map'] = $array;
			}
			$acl[$key]['deny'] = [];
			$acl[$key]['allow'] = [];

			foreach ($array as $actions => $roles) {
				$roles = explode(',', $roles);
				$actions = explode(',', $actions);

				$deniedRoles = [];
				foreach ($roles as $roleId => $role) {
					$role = trim($role);
					if (!$role) {
						continue;
					}
					$denied = mb_substr($role, 0, 1) === '!';
					if ($denied) {
						$role = mb_substr($role, 1);
						if (!array_key_exists($role, $availableRoles)) {
							unset($roles[$roleId]);

							continue;
						}

						unset($roles[$roleId]);
						$deniedRoles[] = $role;

						continue;
					}

					if (!array_key_exists($role, $availableRoles) && $role !== '*') {
						unset($roles[$roleId]);

						continue;
					}
					if ($role === '*') {
						unset($roles[$roleId]);
						$roles = array_merge($roles, array_keys($availableRoles));
					}
				}

				foreach ($actions as $action) {
					$action = trim($action);
					if (!$action) {
						continue;
					}

					foreach ($roles as $role) {
						$role = trim($role);
						if (!$role) {
							continue;
						}
						$roleName = strtolower($role);

						$newRole = $availableRoles[$roleName];
						$acl[$key]['allow'][$action][$roleName] = $newRole;
					}
					foreach ($deniedRoles as $role) {
						$role = trim($role);
						if (!$role) {
							continue;
						}
						$roleName = strtolower($role);

						$newRole = $availableRoles[$roleName];
						$acl[$key]['deny'][$action][$roleName] = $newRole;
					}
				}
			}
		}

		return $acl;
	}

}
