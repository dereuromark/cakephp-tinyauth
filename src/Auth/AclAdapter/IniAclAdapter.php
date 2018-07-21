<?php

namespace TinyAuth\Auth\AclAdapter;

use TinyAuth\Utility\Utility;

class IniAclAdapter implements AclAdapterInterface {
	/**
	 * {@inheritdoc}
	 *
	 * @return array
	 */
	public function getAcl($availableRoles, $tinyConfig) {
		$iniArray = Utility::parseFiles($tinyConfig['filePath'], $tinyConfig['file']);

		$res = [];
		foreach ($iniArray as $key => $array) {
			$res[$key] = Utility::deconstructIniKey($key);
			$res[$key]['map'] = $array;

			foreach ($array as $actions => $roles) {
				// Get all roles used in the current INI section
				$roles = explode(',', $roles);
				$actions = explode(',', $actions);

				foreach ($roles as $roleId => $role) {
					$role = trim($role);
					if (!$role) {
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
						if (!$role || $role === '*') {
							continue;
						}

						// Lookup role id by name in roles array
						$newRole = $availableRoles[strtolower($role)];
						$res[$key]['actions'][$action][] = $newRole;
					}
				}
			}
		}

		return $res;
	}

}
