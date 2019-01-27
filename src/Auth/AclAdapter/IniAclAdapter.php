<?php

namespace TinyAuth\Auth\AclAdapter;

use TinyAuth\Utility\Utility;

class IniAclAdapter implements AclAdapterInterface {

	/**
	 * {@inheritdoc}
	 *
	 * @return array
	 */
	public function getAcl(array $availableRoles, array $config) {
		$iniArray = Utility::parseFiles($config['filePath'], $config['file']);

		$res = [];
		foreach ($iniArray as $key => $array) {
			$res[$key] = Utility::deconstructIniKey($key);
			$res[$key]['map'] = $array;
			$res[$key]['actions'] = [];

			foreach ($array as $actions => $roles) {
				// Get all roles used in the current INI section
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

						$deniedRoles[] = $role;

						continue;
					}

					// Prevent undefined roles appearing in the iniMap
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

						// Lookup role id by name in roles array
						$newRole = $availableRoles[strtolower($role)];
						$res[$key]['actions'][$action][] = $newRole;
					}
					foreach ($deniedRoles as $role) {
						$role = trim($role);
						if (!$role) {
							continue;
						}

						// Lookup role id by name in roles array
						$newRole = $availableRoles[strtolower($role)];
						$res[$key]['deny'][$action][] = $newRole;
					}
				}
			}
		}

		return $res;
	}

}
