<?php
namespace TinyAuth\Auth;

use Cake\Auth\BaseAuthorize;
use Cake\Cache\Cache;
use Cake\Controller\ComponentRegistry;
use Cake\Core\Configure;
use Cake\Core\Exception\Exception;
use Cake\Network\Request;
use Cake\ORM\TableRegistry;

if (!defined('AUTH_CACHE')) {
	define('AUTH_CACHE', '_cake_core_'); // use the most persistent cache by default
}
if (!defined('ACL_FILE')) {
	define('ACL_FILE', 'acl.ini'); // stored in /app/Config/ by default
}

/**
 * Probably the most simple and fastest ACL out there.
 * Only one config file `acl.ini` necessary,
 * doesn't even need a Roles Table / roles table.
 * Uses most persistent _cake_core_ cache by default.
 *
 * @link http://www.dereuromark.de/2011/12/18/tinyauth-the-fastest-and-easiest-authorization-for-cake2
 *
 * Usage:
 * Include it in your beforeFilter() method of the AppController with the following config:
 * 'authorize' => ['Tools.Tiny']
 *
 * Or with admin prefix protection only
 * 'authorize' => ['Tools.Tiny' => ['allowUser' => true]];
 *
 * @author Mark Scherer
 * @license MIT
 */
class TinyAuthorize extends BaseAuthorize {

	/**
	 * @var array|null
	 */
	protected $_acl = null;

	/**
	 * @var array
	 */
	protected $_defaultConfig = [
		'idColumn' => 'id', // ID Column in users table
		'roleColumn' => 'role_id', // Foreign key for the Role ID in users table or in pivot table
		'userColumn' => 'user_id', // Foreign key for the User id in pivot table. Only for multi-roles setup
		'aliasColumn' => 'alias', // Name of column in roles table holding role alias/slug
		'rolesTable' => 'Roles', // name of Configure key holding available roles OR class name of roles table
		'usersTable' => 'Users', // name of the Users table
		'pivotTable' => null, // Should be used in multi-roles setups
		'multiRole' => false, // true to enables multirole/HABTM authorization (requires a valid pivot table)
		'superAdminRole' => null, // id of super admin role, which grants access to ALL resources
		'superAdmin'=>null, // super admin, which grants access to ALL resourc
		'superAdminColumn'=>null, // Column of super admin
		'authorizeByPrefix' => false,
		'prefixes' => [], // Whitelisted prefixes (only used when allowAdmin is enabled), leave empty to use all available
		'allowUser' => false, // enable to allow ALL roles access to all actions except prefixed with 'adminPrefix'
		'adminPrefix' => 'admin', // name of the admin prefix route (only used when allowUser is enabled)
		'cache' => AUTH_CACHE,
		'cacheKey' => 'tiny_auth_acl',
		'autoClearCache' => false, // usually done by Cache automatically in debug mode,
	];

	/**
	 * TinyAuthorize::__construct()
	 *
	 * @param \Cake\Controller\ComponentRegistry $registry
	 * @param array $config
	 * @throws \Cake\Core\Exception\Exception
	 */
	public function __construct(ComponentRegistry $registry, array $config = []) {
		$config += (array)Configure::read('TinyAuth');
		$config += $this->_defaultConfig;
		if (!$config['prefixes'] && !empty($config['authorizeByPrefix'])) {
			throw new Exception('Invalid TinyAuthorization setup for `authorizeByPrefix`. Please declare `prefixes`.');
		}

		parent::__construct($registry, $config);

		if (!in_array($config['cache'], Cache::configured())) {
			throw new Exception(sprintf('Invalid TinyAuthorization cache `%s`', $config['cache']));
		}
	}

	/**
	 * Authorizes a user using the AclComponent.
	 *
	 * Allows single or multi role based authorization
	 *
	 * Examples:
	 * - User HABTM Roles (Role array in User array)
	 * - User belongsTo Roles (role_id in User array)
	 *
	 * @param array $user The user to authorize
	 * @param \Cake\Network\Request $request The request needing authorization.
	 * @return bool Success
	 */
	public function authorize($user, Request $request) {
		if(!empty($this->_config['superAdmin'])){
			if(empty($this->_config['superAdminColumn'])){
				$this->_config['superAdminColumn']=$this->_config['idColumn'];
			}
			if(!isset($user[$this->_config['superAdminColumn']])){
				throw new Exception('Missing super Admin Column in user table');
			}
			if($user[$this->_config['superAdminColumn']]===$this->_config['superAdmin']){
				return true;
			}
		}
		
		return $this->validate($this->_getUserRoles($user), $request);
	}

	/**
	 * Validates the URL to the role(s).
	 *
	 * Allows single or multi role based authorization
	 *
	 * @param array $userRoles
	 * @param \Cake\Network\Request $request Request instance
	 * @return bool Success
	 */
	public function validate($userRoles, Request $request) {
		// Give any logged in user access to ALL actions when `allowUser` is
		// enabled except when the `adminPrefix` is being used.
		if (!empty($this->_config['allowUser'])) {
			if (empty($request->params['prefix'])) {
				return true;
			}
			if ($request->params['prefix'] !== $this->_config['adminPrefix']) {
				return true;
			}
		}

		// Allow access to all prefixed actions for users belonging to
		// the specified role that matches the prefix.
		if (!empty($this->_config['authorizeByPrefix']) && !empty($request->params['prefix'])) {
			if (in_array($request->params['prefix'], $this->_config['prefixes'])) {
				$roles = $this->_getAvailableRoles();
				$role = isset($roles[$request->params['prefix']]) ? $roles[$request->params['prefix']] : null;
				if ($role && in_array($role, $userRoles)) {
					return true;
				}
			}
		}

		// Allow logged in super admins access to all resources
		if (!empty($this->_config['superAdminRole'])) {
			foreach ($userRoles as $userRole) {
				if ($userRole === $this->_config['superAdminRole']) {
					return true;
				}
			}
		}

		if ($this->_acl === null) {
			$this->_acl = $this->_getAcl();
		}

		// Allow access if user has a role with wildcard access to the resource
		$iniKey = $this->_constructIniKey($request);
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
			if (array_key_exists($request->action, $this->_acl[$iniKey]['actions']) && !empty($this->_acl[$iniKey]['actions'][$request->action])) {
				$matchArray = $this->_acl[$iniKey]['actions'][$request->action];
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
	 * Parse ini file and returns the allowed roles per action.
	 *
	 * Uses cache for maximum performance.
	 * Improved speed by several actions before caching:
	 * - Resolves role slugs to their primary key / identifier
	 * - Resolves wildcards to their verbose translation
	 *
	 * @param string|null $path
	 * @return array Roles
	 */
	protected function _getAcl($path = null) {
		if ($path === null) {
			$path = ROOT . DS . 'config' . DS;
		}

		if ($this->_config['autoClearCache'] && Configure::read('debug')) {
			Cache::delete($this->_config['cacheKey'], $this->_config['cache']);
		}
		$roles = Cache::read($this->_config['cacheKey'], $this->_config['cache']);
		if ($roles !== false) {
			return $roles;
		}

		$iniArray = $this->_parseAclIni($path . ACL_FILE);
		$availableRoles = $this->_getAvailableRoles();

		$res = [];
		foreach ($iniArray as $key => $array) {
			$res[$key] = $this->_deconstructIniKey($key);
			$res[$key]['map'] = $array;

			foreach ($array as $actions => $roles) {
				// Get all roles used in the current ini section
				$roles = explode(',', $roles);
				$actions = explode(',', $actions);

				foreach ($roles as $roleId => $role) {
					if (!($role = trim($role))) {
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

		Cache::write($this->_config['cacheKey'], $res, $this->_config['cache']);
		return $res;
	}

	/**
	 * Returns the acl.ini file as an array.
	 *
	 * @param string $ini Full path to the acl.ini file
	 * @return array List with all available roles
	 * @throws \Cake\Core\Exception\Exception
	 */
	protected function _parseAclIni($ini) {
		if (!file_exists($ini)) {
			throw new Exception(sprintf('Missing TinyAuthorize ACL file (%s)', $ini));
		}

		if (function_exists('parse_ini_file')) {
			$iniArray = parse_ini_file($ini, true);
		} else {
			$iniArray = parse_ini_string(file_get_contents($ini), true);
		}

		if (!is_array($iniArray)) {
			throw new Exception('Invalid TinyAuthorize ACL file');
		}
		return $iniArray;
	}

	/**
	 * Deconstructs an ACL ini section key into a named array with ACL parts.
	 *
	 * @param string $key INI section key as found in acl.ini
	 * @return array Array with named keys for controller, plugin and prefix
	 */
	protected function _deconstructIniKey($key) {
		$res = [
			'plugin' => null,
			'prefix' => null
		];

		if (strpos($key, '.') !== false) {
			list($res['plugin'], $key) = explode('.', $key);
		}
		if (strpos($key, '/') !== false) {
			list($res['prefix'], $key) = explode('/', $key);
		}
		$res['controller'] = $key;
		return $res;
	}

	/**
	 * Constructs an ACL ini section key from a given Request.
	 *
	 * @param \Cake\Network\Request $request The request needing authorization.
	 * @return string Hash with named keys for controller, plugin and prefix
	 */
	protected function _constructIniKey(Request $request) {
		$res = $request->params['controller'];
		if (!empty($request->params['prefix'])) {
			$res = $request->params['prefix'] . "/$res";
		}
		if (!empty($request->params['plugin'])) {
			$res = $request->params['plugin'] . ".$res";
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
		$roles = Configure::read($this->_config['rolesTable']);
		if (is_array($roles)) {
			return $roles;
		}

		$rolesTable = TableRegistry::get($this->_config['rolesTable']);
		$roles = $rolesTable->find()->formatResults(function ($results) {
			return $results->combine($this->_config['aliasColumn'], 'id');
		})->toArray();

		if (count($roles) < 1) {
			throw new Exception('Invalid TinyAuthorize role setup (roles table `' . $this->_config['rolesTable'] . '` has no roles)');
		}
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
		// Single-role
		if (!$this->_config['multiRole']) {
			if (isset($user[$this->_config['roleColumn']])) {
				return [$user[$this->_config['roleColumn']]];
			}
			throw new Exception(sprintf('Missing TinyAuthorize role id (%s) in user session', $this->_config['roleColumn']));
		}

		// Multi-role case : load the pivot table
		$pivotTableName = $this->_config['pivotTable'];
		if (!$pivotTableName) {
			list(, $rolesTableName) = pluginSplit($this->_config['rolesTable']);
			list(, $usersTableName) = pluginSplit($this->_config['usersTable']);
			$tables = [
				$usersTableName,
				$rolesTableName
			];
			asort($tables);
			$pivotTableName = implode('', $tables);
		}
		$pivotTable = TableRegistry::get($pivotTableName);
		$roleColumn = $this->_config['roleColumn'];
		$roles = $pivotTable->find()
			->select($roleColumn)
			->where([$this->_config['userColumn'] => $user[$this->_config['idColumn']]])
			->all()
			->extract($roleColumn)
			->toArray();

		if (!count($roles)) {
			throw new Exception('Missing TinyAuthorize roles for user in pivot table');
		}
		return $roles;
	}

}
