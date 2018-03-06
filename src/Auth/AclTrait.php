<?php
namespace TinyAuth\Auth;

use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\Core\Exception\Exception;
use Cake\Datasource\ResultSetInterface;
use Cake\ORM\TableRegistry;
use Cake\Utility\Inflector;
use Cake\Utility\Hash;
use TinyAuth\Utility\Utility;

trait AclTrait {

	/**
	 * @var array|null
	 */
	protected $_acl = null;

	/**
	 * @var array|null
	 */
	protected $_roles = null;

	/**
	 * @var array|null
	 */
	protected $_userRoles = null;

	/**
	 * @return array
	 */
	protected function _defaultConfig() {
		$defaults = [
			'idColumn' => 'id', // ID Column in users table
			'accountsIdColumn' => 'id', //ID Column in accounts table if accounts table is not null
			'roleColumn' => 'role_id', // Foreign key for the Role ID in users table or in pivot table
			'userColumn' => 'user_id', // Foreign key for the User id in pivot table. Only for multi-roles setup
			'aliasColumn' => 'alias', // Name of column in roles table holding role alias/slug
			'rolesTable' => 'Roles', // name of Configure key holding available roles OR class name of roles table
			'usersTable' => 'Users', // name of the Users table
			'accountsTable' => null, // name of the Accounts table, used for authorization. If null, usersTable is used.
			'multipleAccounts' => true, // if true, tiny expects accountsTable to be a indexed array.
			'pivotTable' => null, // Should be used in multi-roles setups
			'multiRole' => false, // true to enables multirole/HABTM authorization (requires a valid pivot table)
			'superAdminRole' => null, // id of super admin role, which grants access to ALL resources
			'superAdmin' => null, // super admin, which grants access to ALL resources
			'superAdminColumn' => null, // Column of super admin
			'authorizeByPrefix' => false,
			'prefixes' => [], // Whitelisted prefixes (only used when allowAdmin is enabled), leave empty to use all available
			'allowUser' => false, // enable to allow ALL roles access to all actions except prefixed with 'adminPrefix'
			'adminPrefix' => 'admin', // name of the admin prefix route (only used when allowUser is enabled)
			'cache' => '_cake_core_',
			'cacheKey' => 'tiny_auth_acl',
			'autoClearCache' => null, // Set to true to delete cache automatically in debug mode, keep null for auto-detect
			'filePath' => null, // Possible to locate INI file at given path e.g. Plugin::configPath('Admin')
			'file' => 'acl.ini',
		];
		$config = (array)Configure::read('TinyAuth') + $defaults;

		return $config;
	}

	/**
	 * @param array $config
	 * @throws \Cake\Core\Exception\Exception
	 * @return array
	 */
	protected function _prepareConfig(array $config) {
		$config += $this->_defaultConfig();
		if (!$config['prefixes'] && !empty($config['authorizeByPrefix'])) {
			throw new Exception('Invalid TinyAuthorization setup for `authorizeByPrefix`. Please declare `prefixes`.');
		}

		if (!in_array($config['cache'], Cache::configured())) {
			throw new Exception(sprintf('Invalid TinyAuth cache `%s`', $config['cache']));
		}

		if ($config['autoClearCache'] === null) {
			$config['autoClearCache'] = Configure::read('debug');
		}

		return $config;
	}

	/**
	 * Checks the URL to the role(s).
	 *
	 * Allows single or multi role based authorization
	 *
	 * @param array $user User data
	 * @param array $params Request params
	 * @return bool Success
	 * @throws \Cake\Core\Exception\Exception
	 */
	protected function _check(array $user, array $params) {
		if (!$user) {
			return false;
		}

		if ($this->getConfig('superAdmin')) {
			$superAdminColumn = $this->getConfig('superAdminColumn');
			if (!$superAdminColumn) {
				$superAdminColumn = $this->getConfig('idColumn');
			}
			if (!isset($user[$superAdminColumn])) {
				throw new Exception('Missing super Admin Column in user table');
			}
			if ($user[$superAdminColumn] === $this->getConfig('superAdmin')) {
				return true;
			}
		}

		// Give any logged in user access to ALL actions when `allowUser` is
		// enabled except when the `adminPrefix` is being used.
		if ($this->getConfig('allowUser')) {
			if (empty($params['prefix'])) {
				return true;
			}
			if ($params['prefix'] !== $this->getConfig('adminPrefix')) {
				return true;
			}
		}

		$userRoles = $this->_getUserRoles($user);

		// Allow access to all prefixed actions for users belonging to
		// the specified role that matches the prefix.
		if ($this->getConfig('authorizeByPrefix') && !empty($params['prefix'])) {
			if (in_array($params['prefix'], $this->getConfig('prefixes'))) {
				$roles = $this->_getAvailableRoles();
				$role = isset($roles[$params['prefix']]) ? $roles[$params['prefix']] : null;
				if ($role && in_array($role, $userRoles)) {
					return true;
				}
			}
		}

		// Allow logged in super admins access to all resources
		if ($this->getConfig('superAdminRole')) {
			foreach ($userRoles as $userRole) {
				if ((string)$userRole === (string)$this->getConfig('superAdminRole')) {
					return true;
				}
			}
		}

		if ($this->_acl === null) {
			$this->_acl = $this->_getAcl($this->getConfig('filePath'));
		}

		// Allow access if user has a role with wildcard access to the resource
		$iniKey = $this->_constructIniKey($params);
		if (isset($this->_acl[$iniKey]['actions']['*'])) {
			$matchArray = $this->_acl[$iniKey]['actions']['*'];
			foreach ($userRoles as $userRole) {
				if (in_array((string)$userRole, $matchArray)) {
					return true;
				}
			}
		}

		// Allow access if user has been granted access to the specific resource
		if (isset($this->_acl[$iniKey]['actions'])) {
			if (array_key_exists($params['action'], $this->_acl[$iniKey]['actions']) && !empty($this->_acl[$iniKey]['actions'][$params['action']])) {
				$matchArray = $this->_acl[$iniKey]['actions'][$params['action']];
				foreach ($userRoles as $userRole) {
					if (in_array((string)$userRole, $matchArray)) {
						return true;
					}
				}
			}
		}
		return false;
	}

	/**
	 * Parses INI file and returns the allowed roles per action.
	 *
	 * Uses cache for maximum performance.
	 * Improved speed by several actions before caching:
	 * - Resolves role slugs to their primary key / identifier
	 * - Resolves wildcards to their verbose translation
	 *
	 * @param string|array|null $path
	 * @return array Roles
	 */
	protected function _getAcl($path = null) {
		if ($this->getConfig('autoClearCache') && Configure::read('debug')) {
			Cache::delete($this->getConfig('cacheKey'), $this->getConfig('cache'));
		}
		$roles = Cache::read($this->getConfig('cacheKey'), $this->getConfig('cache'));
		if ($roles !== false) {
			return $roles;
		}

		$iniArray = $this->_parseFiles($path, $this->getConfig('file'));
		$availableRoles = $this->_getAvailableRoles();

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

		Cache::write($this->getConfig('cacheKey'), $res, $this->getConfig('cache'));
		return $res;
	}

	/**
	 * Returns the found INI file(s) as an array.
	 *
	 * @param array|string|null $paths Paths to look for INI files.
	 * @param string $file INI file name.
	 * @return array List with all found files.
	 */
	protected function _parseFiles($paths, $file) {
		if ($paths === null) {
			$paths = ROOT . DS . 'config' . DS;
		}

		$list = [];
		foreach ((array)$paths as $path) {
			$list += Utility::parseFile($path . $file);
		}

		return $list;
	}

	/**
	 * Deconstructs an ACL INI section key into a named array with ACL parts.
	 *
	 * @param string $key INI section key as found in acl.ini
	 * @return array Array with named keys for controller, plugin and prefix
	 */
	protected function _deconstructIniKey($key) {
		return Utility::deconstructIniKey($key);
	}

	/**
	 * Constructs an ACL INI section key from a given Request.
	 *
	 * @param array $params The request params
	 * @return string Hash with named keys for controller, plugin and prefix
	 */
	protected function _constructIniKey($params) {
		$res = $params['controller'];
		if (!empty($params['prefix'])) {
			$res = $params['prefix'] . "/$res";
		}
		if (!empty($params['plugin'])) {
			$res = $params['plugin'] . ".$res";
		}
		return $res;
	}

	/**
	 * Returns a list of all available roles.
	 *
	 * Will look for a roles array in
	 * Configure first, tries database roles table next.
	 *
	 * @return array List with all available roles
	 * @throws \Cake\Core\Exception\Exception
	 */
	protected function _getAvailableRoles() {
		if ($this->_roles !== null) {
			return $this->_roles;
		}

		$roles = Configure::read($this->getConfig('rolesTable'));
		if (is_array($roles)) {
			if ($this->getConfig('superAdminRole')) {
				$key = $this->getConfig('superAdmin') ?: 'superadmin';
				$roles[$key] = $this->getConfig('superAdminRole');
			}
			return $roles;
		}

		$rolesTable = TableRegistry::get($this->getConfig('rolesTable'));
		$roles = $rolesTable->find()->formatResults(function (ResultSetInterface $results) {
			return $results->combine($this->getConfig('aliasColumn'), 'id');
		})->toArray();

		if ($this->getConfig('superAdminRole')) {
			$key = $this->getConfig('superAdmin') ?: 'superadmin';
			$roles[$key] = $this->getConfig('superAdminRole');
		}

		if (count($roles) < 1) {
			throw new Exception('Invalid TinyAuth role setup (roles table `' . $this->getConfig('rolesTable') . '` has no roles)');
		}

		$this->_roles = $roles;

		return $roles;
	}

	/**
	 * Returns a list of all roles belonging to the authenticated user.
	 *
	 * Lookup in the following order:
	 * - single role id using the roleColumn in single-role mode
	 * - direct lookup in the pivot table (to support both Configure and Model
	 *   in multi-role mode)
	 *
	 * @param array $user The user to get the roles for
	 * @return array List with all role ids belonging to the user
	 * @throws \Cake\Core\Exception\Exception
	 */
	protected function _getUserRoles($user) {
		$autorizeData = $user;

		if($this->getConfig('accountsTable') !== null) {
			// If multipleAccounts === true, 
			$autorizeData = $this->getConfig('multipleAccounts') === true ? $user[mb_strtolower($this->getConfig('accountsTable'))][0] 
				: $user[mb_strtolower(Inflector::singularize($this->getConfig('accountsTable')))];
		}

		// Single-role from session
		if (!$this->getConfig('multiRole')) {
			if (!array_key_exists($this->getConfig('roleColumn'), $autorizeData)) {
				throw new Exception(sprintf('Missing TinyAuth role id field (%s) in user session', 'Auth.User.' . $this->getConfig('roleColumn')));
			}
			if (!isset($autorizeData[$this->getConfig('roleColumn')])) {
				return [];
			}
			return $this->_mapped([$autorizeData[$this->getConfig('roleColumn')]]);
		}

		// Multi-role from session
		if (isset($autorizeData[$this->getConfig('rolesTable')])) {
			$userRoles = $autorizeData[$this->getConfig('rolesTable')];
			if (isset($userRoles[0]['id'])) {
				$userRoles = Hash::extract($userRoles, '{n}.id');
			}
			return $this->_mapped((array)$userRoles);
		}

		// Multi-role from session via pivot table
		$pivotTableName = $this->getConfig('pivotTable');
		if (!$pivotTableName) {
			list(, $rolesTableName) = pluginSplit($this->getConfig('rolesTable'));
			list(, $usersTableName) = $this->getConfig('accountsTable') === null ? pluginSplit($this->getConfig('usersTable')) : pluginSplit($this->getConfig('accountsTable'));
			$tables = [
				$usersTableName,
				$rolesTableName
			];
			asort($tables);
			$pivotTableName = implode('', $tables);
		}
		if (isset($autorizeData[$pivotTableName])) {
			$userRoles = $autorizeData[$pivotTableName];
			if (isset($userRoles[0][$this->getConfig('roleColumn')])) {
				$userRoles = Hash::extract($userRoles, '{n}.' . $this->getConfig('roleColumn'));
			}
			return $this->_mapped((array)$userRoles);
		}

		// Multi-role from DB: load the pivot table
		$idColumn = $this->getConfig('accountsTable') === null ? $this->getConfig('idColumn') : $this->getConfig('accountsIdColumn');
		$roles = $this->_getRolesFromDb($pivotTableName, $autorizeData[$idColumn]);
		if (!$roles) {
			return [];
		}

		return $this->_mapped($roles);
	}

	/**
	 * @param string $pivotTableName
	 * @param int $id User ID
	 * @return array
	 */
	protected function _getRolesFromDb($pivotTableName, $id) {
		if (isset($this->_userRoles[$id])) {
			return $this->_userRoles[$id];
		}

		$pivotTable = TableRegistry::get($pivotTableName);
		$roleColumn = $this->getConfig('roleColumn');
		$roles = $pivotTable->find()
			->select($roleColumn)
			->where([$this->getConfig('userColumn') => $id])
			->all()
			->extract($roleColumn)
			->toArray();

		$this->_userRoles[$id] = $roles;

		return $roles;
	}

	/**
	 * @param array $roles
	 * @return array
	 */
	protected function _mapped($roles) {
		$availableRoles = $this->_getAvailableRoles();

		$array = [];
		foreach ($roles as $role) {
			$alias = array_keys($availableRoles, $role);
			$alias = array_shift($alias);
			if (!$alias || !is_string($alias)) {
				continue;
			}

			$array[$alias] = $role;
		}

		return $array;
	}

}
