<?php
namespace TinyAuth\Auth;

use Cake\Core\Configure;
use Cake\Core\Exception\Exception;
use Cake\Datasource\ResultSetInterface;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;
use InvalidArgumentException;
use TinyAuth\Auth\AclAdapter\AclAdapterInterface;
use TinyAuth\Auth\AclAdapter\IniAclAdapter;
use TinyAuth\Utility\Cache;
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
	 * @var \TinyAuth\Auth\AclAdapter\AclAdapterInterface|null
	 */
	protected $_aclAdapter = null;

	/**
	 * @var array|null
	 */
	protected $auth = null;

	/**
	 * @return array
	 */
	protected function _defaultConfig() {
		$defaults = [
			'aclAdapter' => IniAclAdapter::class,
			'idColumn' => 'id', // ID Column in users table
			'roleColumn' => 'role_id', // Foreign key for the Role ID in users table or in pivot table
			'userColumn' => 'user_id', // Foreign key for the User id in pivot table. Only for multi-roles setup
			'aliasColumn' => 'alias', // Name of column in roles table holding role alias/slug
			'rolesTable' => 'Roles', // name of Configure key holding available roles OR class name of roles table
			'usersTable' => 'Users', // name of the Users table
			'pivotTable' => null, // Should be used in multi-roles setups
			'multiRole' => false, // true to enables multirole/HABTM authorization (requires a valid pivot table)
			'superAdminRole' => null, // id of super admin role, which grants access to ALL resources
			'superAdmin' => null, // super admin, which grants access to ALL resources
			'superAdminColumn' => null, // Column of super admin
			'authorizeByPrefix' => false,
			'prefixes' => [], // Whitelisted prefixes (only used when allowAdmin is enabled), leave empty to use all available
			'allowUser' => false, // enable to allow ALL roles access to all actions except prefixed with 'adminPrefix'
			'adminPrefix' => 'admin', // name of the admin prefix route (only used when allowUser is enabled)
			'autoClearCache' => null, // Set to true to delete cache automatically in debug mode, keep null for auto-detect
			'aclFilePath' => null, // Possible to locate INI file at given path e.g. Plugin::configPath('Admin'), filePath is also available for shared config
			'aclFile' => 'tinyauth_acl.ini',
			'includeAuthentication' => false, // Set to true to include public auth access into hasAccess() checks. Note, that this requires Configure configuration.
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

		if ($config['autoClearCache'] === null) {
			$config['autoClearCache'] = Configure::read('debug');
		}
		$this->_aclAdapter = $this->_loadAclAdapter($config['aclAdapter']);

		return $config;
	}

	/**
	 * Finds the authorization adapter to use for this request.
	 *
	 * @param string $adapter Acl adapter to load.
	 * @return \TinyAuth\Auth\AclAdapter\AclAdapterInterface
	 * @throws \Cake\Core\Exception\Exception
	 * @throws \InvalidArgumentException
	 */
	protected function _loadAclAdapter($adapter) {
		if (!class_exists($adapter)) {
			throw new Exception(sprintf('The Acl Adapter class "%s" was not found.', $adapter));
		}

		$adapterInstance = new $adapter();
		if (!($adapterInstance instanceof AclAdapterInterface)) {
			throw new InvalidArgumentException(sprintf(
				'TinyAuth Acl adapters have to implement %s.', AclAdapterInterface::class
			));
		}

		return $adapterInstance;
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
		if ($this->getConfig('includeAuthentication') && $this->_isPublic($params)) {
			return true;
		}

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
			if (in_array($params['prefix'], $this->getConfig('prefixes'), true)) {
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
				if ($userRole === $this->getConfig('superAdminRole')) {
					return true;
				}
			}
		}

		if ($this->_acl === null) {
			$this->_acl = $this->_getAcl($this->getConfig('aclFilePath'));
		}

		$iniKey = $this->_constructIniKey($params);
		if (empty($this->_acl[$iniKey])) {
			return false;
		}

		$action = $params['action'];
		if (!empty($this->_acl[$iniKey]['deny'][$action])) {
			$matchArray = $this->_acl[$iniKey]['deny'][$action];
			foreach ($userRoles as $userRole) {
				if (in_array($userRole, $matchArray, true)) {
					return false;
				}
			}
		}

		// Allow access if user has a role with wildcard access to the resource
		if (isset($this->_acl[$iniKey]['allow']['*'])) {
			$matchArray = $this->_acl[$iniKey]['allow']['*'];
			foreach ($userRoles as $userRole) {
				if (in_array($userRole, $matchArray, true)) {
					return true;
				}
			}
		}

		// Allow access if user has been granted access to the specific resource
		if (array_key_exists($action, $this->_acl[$iniKey]['allow']) && !empty($this->_acl[$iniKey]['allow'][$action])) {
			$matchArray = $this->_acl[$iniKey]['allow'][$action];
			foreach ($userRoles as $userRole) {
				if (in_array($userRole, $matchArray, true)) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * @param array $params
	 *
	 * @return bool
	 */
	protected function _isPublic(array $params) {
		$authentication = $this->_getAuth();

		foreach ($authentication as $rule) {
			if ($params['plugin'] && $params['plugin'] !== $rule['plugin']) {
				continue;
			}
			if (!empty($params['prefix']) && $params['prefix'] !== $rule['prefix']) {
				continue;
			}
			if ($params['controller'] !== $rule['controller']) {
				continue;
			}

			$action = $params['action'];

			if (!empty($rule['deny']) && in_array($action, $rule['deny'], true)) {
				return false;
			}

			if (in_array('*', $rule['allow'], true)) {
				return true;
			}

			return in_array($params['action'], $rule['allow'], true);
		}

		return false;
	}

	/**
	 * Hack to get the auth data here for hasAccess().
	 * We re-use the cached data for performance reasons.
	 *
	 * @return array
	 * @throws \Cake\Core\Exception\Exception
	 */
	protected function _getAuth() {
		if ($this->auth) {
			return $this->auth;
		}

		$authAllow = Cache::read(Cache::KEY_ALLOW);
		if ($authAllow === null) {
			//TOOD make refactor to collect data at runtime? Should not be necessary if AuthComponent is used properly.
			throw new Exception('Cache for Authentication data not found. This is required for `includeAuthentication` as true. Make sure you enabled TinyAuth.AuthComponent.');
		}

		$this->auth = $authAllow;

		return $authAllow;
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
	 * @return array
	 */
	protected function _getAcl($path = null) {
		if ($this->getConfig('autoClearCache') && Configure::read('debug')) {
			Cache::clear(Cache::KEY_ACL);
		}
		$acl = Cache::read(Cache::KEY_ACL);
		if ($acl !== null) {
			return $acl;
		}

		if ($path === null) {
			$path = $this->getConfig('aclFilePath');
		}
		$config = $this->getConfig();
		$config['filePath'] = $path;
		$config['file'] = $config['aclFile'];
		unset($config['aclFilePath']);
		unset($config['aclFile']);

		$acl = $this->_aclAdapter->getAcl($this->_getAvailableRoles(), $config);
		Cache::write(Cache::KEY_ACL, $acl);

		return $acl;
	}

	/**
	 * Returns the found INI file(s) as an array.
	 *
	 * @param array|string|null $paths Paths to look for INI files.
	 * @param string $file INI file name.
	 * @return array List with all found files.
	 */
	protected function _parseFiles($paths, $file) {
		return Utility::parseFiles($paths, $file);
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

		$rolesTableKey = $this->getConfig('rolesTable');
		if (!$rolesTableKey) {
			throw new Exception('Invalid/missing rolesTable config');
		}

		$roles = Configure::read($rolesTableKey);
		if (is_array($roles)) {
			if ($this->getConfig('superAdminRole')) {
				$key = $this->getConfig('superAdmin') ?: 'superadmin';
				$roles[$key] = $this->getConfig('superAdminRole');
			}
			return $roles;
		}

		$rolesTable = TableRegistry::get($rolesTableKey);
		$result = $rolesTable->find()->formatResults(function (ResultSetInterface $results) {
			return $results->combine($this->getConfig('aliasColumn'), 'id');
		});
		$roles = $result->toArray();

		if ($this->getConfig('superAdminRole')) {
			$key = $this->getConfig('superAdmin') ?: 'superadmin';
			$roles[$key] = $this->getConfig('superAdminRole');
		}

		if (count($roles) < 1) {
			throw new Exception('Invalid TinyAuth role setup (roles table `' . $rolesTableKey . '` has no roles)');
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
		// Single-role from session
		if (!$this->getConfig('multiRole')) {
			if (!array_key_exists($this->getConfig('roleColumn'), $user)) {
				throw new Exception(sprintf('Missing TinyAuth role id field (%s) in user session', 'Auth.User.' . $this->getConfig('roleColumn')));
			}
			if (!isset($user[$this->getConfig('roleColumn')])) {
				return [];
			}
			return $this->_mapped([$user[$this->getConfig('roleColumn')]]);
		}

		// Multi-role from session
		if (isset($user[$this->getConfig('rolesTable')])) {
			$userRoles = $user[$this->getConfig('rolesTable')];
			if (isset($userRoles[0]['id'])) {
				$userRoles = Hash::extract($userRoles, '{n}.id');
			}
			return $this->_mapped((array)$userRoles);
		}

		// Multi-role from session via pivot table
		$pivotTableName = $this->getConfig('pivotTable');
		if (!$pivotTableName) {
			list(, $rolesTableName) = pluginSplit($this->getConfig('rolesTable'));
			list(, $usersTableName) = pluginSplit($this->getConfig('usersTable'));
			$tables = [
				$usersTableName,
				$rolesTableName
			];
			asort($tables);
			$pivotTableName = implode('', $tables);
		}
		if (isset($user[$pivotTableName])) {
			$userRoles = $user[$pivotTableName];
			if (isset($userRoles[0][$this->getConfig('roleColumn')])) {
				$userRoles = Hash::extract($userRoles, '{n}.' . $this->getConfig('roleColumn'));
			}
			return $this->_mapped((array)$userRoles);
		}

		// Multi-role from DB: load the pivot table
		$roles = $this->_getRolesFromDb($pivotTableName, $user[$this->getConfig('idColumn')]);
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
