<?php

namespace TinyAuth\Auth;

use Cake\Core\Configure;
use Cake\Core\Exception\CakeException;
use Cake\Datasource\ResultSetInterface;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;
use Cake\Utility\Inflector;
use InvalidArgumentException;
use RuntimeException;
use TinyAuth\Auth\AclAdapter\AclAdapterInterface;
use TinyAuth\Utility\Cache;
use TinyAuth\Utility\Utility;

trait AclTrait {

	/**
	 * @var array|null
	 */
	protected $_acl;

	/**
	 * @var array<int>|null
	 */
	protected $_roles;

	/**
	 * @var array<string>|null
	 */
	protected $_prefixMap;

	/**
	 * @var array|null
	 */
	protected $_userRoles;

	/**
	 * @var \TinyAuth\Auth\AclAdapter\AclAdapterInterface|null
	 */
	protected $_aclAdapter;

	/**
	 * @var array|null
	 */
	protected $auth;

	/**
	 * Finds the authorization adapter to use for this request.
	 *
	 * @param string $adapter Acl adapter to load.
	 * @throws \Cake\Core\Exception\CakeException
	 * @throws \InvalidArgumentException
	 * @return \TinyAuth\Auth\AclAdapter\AclAdapterInterface
	 */
	protected function _loadAclAdapter($adapter) {
		if ($this->_aclAdapter !== null) {
			return $this->_aclAdapter;
		}

		if (!class_exists($adapter)) {
			throw new CakeException(sprintf('The Acl Adapter class "%s" was not found.', $adapter));
		}

		$adapterInstance = new $adapter();
		if (!($adapterInstance instanceof AclAdapterInterface)) {
			throw new InvalidArgumentException(sprintf(
				'TinyAuth Acl adapters have to implement %s.',
				AclAdapterInterface::class,
			));
		}

		$this->_aclAdapter = $adapterInstance;

		return $adapterInstance;
	}

	/**
	 * Checks the URL to the role(s).
	 *
	 * Allows single or multi role based authorization
	 *
	 * @param array $user User data
	 * @param array $params Request params
	 * @throws \Cake\Core\Exception\CakeException
	 * @return bool Success
	 */
	protected function _checkUser(array $user, array $params) {
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
				throw new CakeException('Missing super Admin Column in user table');
			}
			if ($user[$superAdminColumn] === $this->getConfig('superAdmin')) {
				return true;
			}
		}

		// Give any logged in user access to ALL actions when `allowLoggedIn` is
		// enabled except when the `protectedPrefix` is being used.
		if ($this->getConfig('allowLoggedIn')) {
			if (empty($params['prefix'])) {
				return true;
			}
			$protectedPrefixes = (array)$this->getConfig('protectedPrefix');
			if (!$this->_isProtectedPrefix($params['prefix'], $protectedPrefixes)) {
				return true;
			}
		}

		$userRoles = $this->_getUserRoles($user);

		return $this->_check($userRoles, $params);
	}

	/**
	 * @param string $prefix
	 * @param array<string> $protectedPrefixes
	 *
	 * @return bool
	 */
	protected function _isProtectedPrefix($prefix, array $protectedPrefixes) {
		foreach ($protectedPrefixes as $protectedPrefix) {
			if ($prefix === $protectedPrefix || strpos($prefix, $protectedPrefix . '/') === 0) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @param array<int> $userRoles
	 * @param array $params
	 *
	 * @return bool
	 */
	protected function _check(array $userRoles, array $params) {
		// Allow access to all prefixed actions for users belonging to
		// the specified role that matches the prefix.
		$prefixMap = $this->getConfig('authorizeByPrefix');
		if (!empty($params['prefix']) && $prefixMap) {
			$roles = $this->_getAvailableRoles();
			$prefixMap = $this->_prefixMap($roles);
			if ($prefixMap && $this->_isAuthorizedByPrefix($params['prefix'], $prefixMap, $userRoles, $roles)) {
				return true;
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
	 * @param array $roles
	 * @return array<string>
	 */
	protected function _prefixMap(array $roles): array {
		if ($this->_prefixMap !== null) {
			return $this->_prefixMap;
		}

		/** @var array<string>|bool $prefixMap */
		$prefixMap = $this->getConfig('authorizeByPrefix');
		if (!$prefixMap) {
			return [];
		}

		if ($prefixMap === true) {
			$prefixMap = $this->_prefixesFromRoles($roles);
		} else {
			$prefixMap = $this->_normalizePrefixes($prefixMap);
		}

		$this->_prefixMap = $prefixMap;

		return $this->_prefixMap;
	}

	/**
	 * Gets the [PrefixName => roleName] pairs from existing roles.
	 *
	 * @param array $roles
	 *
	 * @return array<string>
	 */
	protected function _prefixesFromRoles(array $roles) {
		$names = array_keys($roles);
		$prefixMap = [];
		foreach ($names as $name) {
			$prefix = Inflector::camelize(Inflector::underscore($name));
			$prefixMap[$prefix] = $name;
		}

		return $prefixMap;
	}

	/**
	 * @param string $prefix
	 * @param array<string> $prefixMap
	 * @param array<int> $userRoles
	 * @param array<int> $availableRoles
	 *
	 * @return bool
	 */
	protected function _isAuthorizedByPrefix($prefix, array $prefixMap, array $userRoles, array $availableRoles) {
		if (!$userRoles || !$prefixMap || !$availableRoles) {
			return false;
		}

		if (empty($prefixMap[$prefix])) {
			return false;
		}

		$prefixRoleSlugs = (array)$prefixMap[$prefix];
		foreach ($prefixRoleSlugs as $prefixRoleSlug) {
			$role = $availableRoles[$prefixRoleSlug] ?? null;
			if ($role && in_array($role, $userRoles, true)) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Can be [PrefixOne, PrefixTwo => roleOne, PrefixTwo => [roleOne, roleTwo]]
	 *
	 * @param array<string> $prefixes
	 *
	 * @return array
	 */
	protected function _normalizePrefixes(array $prefixes) {
		$normalized = [];
		foreach ($prefixes as $prefix => $role) {
			if (is_int($prefix)) {
				$prefix = $role;
				$role = Inflector::dasherize(Inflector::underscore($role));
			}

			$normalized[$prefix] = $role;
		}

		return $normalized;
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
	 * @throws \Cake\Core\Exception\CakeException
	 * @return array
	 */
	protected function _getAuth() {
		if ($this->auth) {
			return $this->auth;
		}

		$authAllow = $this->_getAllow();

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
	 * @param array|string|null $path
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

		$acl = $this->_loadAclAdapter($config['aclAdapter'])->getAcl($this->_getAvailableRoles(), $config);
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
	 * @throws \Cake\Core\Exception\CakeException
	 * @return array<int> List with all available roles
	 */
	protected function _getAvailableRoles() {
		if ($this->_roles !== null) {
			return $this->_roles;
		}

		$rolesTableKey = $this->getConfig('rolesTable');
		if (!$rolesTableKey) {
			throw new CakeException('Invalid/missing rolesTable config');
		}

		$roles = Configure::read($rolesTableKey);
		if (is_array($roles)) {
			if ($this->getConfig('superAdminRole')) {
				$key = $this->getConfig('superAdmin') ?: 'superadmin';
				$roles[$key] = $this->getConfig('superAdminRole');
			}

			if (!$roles) {
				throw new CakeException('Invalid roles config: No roles found in config.');
			}

			return $roles;
		}

		$roles = $this->_getRolesFromDb($rolesTableKey);
		if ($this->getConfig('superAdminRole')) {
			$key = $this->getConfig('superAdmin') ?: 'superadmin';
			/** @var int $value */
			$value = $this->getConfig('superAdminRole');
			$roles[$key] = $value;
		}

		if (count($roles) < 1) {
			throw new CakeException('Invalid TinyAuth role setup (roles table `' . $rolesTableKey . '` has no roles)');
		}

		$this->_roles = $roles;

		return $roles;
	}

	/**
	 * @param string $rolesTableKey
	 *
	 * @throws \Cake\Core\Exception\CakeException
	 *
	 * @return array<int>
	 */
	protected function _getRolesFromDb(string $rolesTableKey): array {
		try {
			$rolesTable = TableRegistry::getTableLocator()->get($rolesTableKey);
			$result = $rolesTable->find()->formatResults(function (ResultSetInterface $results) {
				return $results->combine($this->getConfig('aliasColumn'), 'id');
			});
		} catch (RuntimeException $e) {
			throw new CakeException('Invalid roles config: DB table `' . $rolesTableKey . '` cannot be found/accessed (`' . $e->getMessage() . '`).', null, $e);
		}

		/** @var array<int> */
		return $result->toArray();
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
	 * @throws \Cake\Core\Exception\CakeException
	 * @return array<int> List with all role ids belonging to the user
	 */
	protected function _getUserRoles($user) {
		// Single-role from session
		if (!$this->getConfig('multiRole')) {
			$roleColumn = $this->getConfig('roleColumn');
			if (!$roleColumn) {
				throw new CakeException('Invalid TinyAuth config, `roleColumn` config missing.');
			}

			if (!array_key_exists($roleColumn, $user)) {
				throw new CakeException(sprintf('Missing TinyAuth role id field (%s) in user session', 'Auth.User.' . $this->getConfig('roleColumn')));
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
		$pivotTableName = $this->_pivotTableName();
		if (isset($user[$pivotTableName])) {
			$userRoles = $user[$pivotTableName];
			if (isset($userRoles[0][$this->getConfig('roleColumn')])) {
				$userRoles = Hash::extract($userRoles, '{n}.' . $this->getConfig('roleColumn'));
			}

			return $this->_mapped((array)$userRoles);
		}

		// Multi-role from DB: load the pivot table
		$roles = $this->_getRolesFromJunction($pivotTableName, $user[$this->getConfig('idColumn')]);
		if (!$roles) {
			return [];
		}

		return $this->_mapped($roles);
	}

	/**
	 * @return string
	 */
	protected function _pivotTableName() {
		$pivotTableName = $this->getConfig('pivotTable');
		if (!$pivotTableName) {
			[, $rolesTableName] = pluginSplit($this->getConfig('rolesTable'));
			[, $usersTableName] = pluginSplit($this->getConfig('usersTable'));
			$tables = [
				$usersTableName,
				$rolesTableName,
			];
			asort($tables);
			$pivotTableName = implode('', $tables);
		}

		return $pivotTableName;
	}

	/**
	 * @param string $pivotTableName
	 * @param int $id User ID
	 * @return array
	 */
	protected function _getRolesFromJunction($pivotTableName, $id) {
		if (isset($this->_userRoles[$id])) {
			return $this->_userRoles[$id];
		}

		$pivotTable = TableRegistry::getTableLocator()->get($pivotTableName);
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
	 * Returns current roles as [alias => id] pairs.
	 *
	 * @param array<int> $roles
	 * @return array<int>
	 */
	protected function _mapped(array $roles) {
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
