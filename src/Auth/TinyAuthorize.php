<?php
namespace TinyAuth\Auth;

use Cake\Auth\BaseAuthorize;
use Cake\Cache\Cache;
use Cake\Controller\ComponentRegistry;
use Cake\Core\Configure;
use Cake\Core\Exception\Exception;
use Cake\Database\Schema\Collection;
use Cake\Datasource\ConnectionManager;
use Cake\Network\Request;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;
use Cake\Utility\Inflector;

if (!defined('CLASS_USERS')) {
	define('CLASS_USERS', 'Users'); // override if you have it in a plugin: PluginName.Users etc
}
if (!defined('AUTH_CACHE')) {
	define('AUTH_CACHE', '_cake_core_'); // use the most persistent cache by default
}
if (!defined('ACL_FILE')) {
	define('ACL_FILE', 'acl.ini'); // stored in /app/Config/ by default
}

/**
 * Probably the most simple and fastest Acl out there.
 * Only one config file `acl.ini` necessary
 * Doesn't even need a Role Model / roles table
 * Uses most persistent _cake_core_ cache by default
 * @link http://www.dereuromark.de/2011/12/18/tinyauth-the-fastest-and-easiest-authorization-for-cake2
 *
 * Usage:
 * Include it in your beforeFilter() method of the AppController
 * $this->Auth->authorize = array('Tools.Tiny');
 *
 * Or with admin prefix protection only
 * $this->Auth->authorize = array('Tools.Tiny' => array('allowUser' => true));
 *
 * @author Mark Scherer
 * @license MIT
 */
class TinyAuthorize extends BaseAuthorize {

	protected $_acl = null;

	protected $_defaultConfig = [
		'roleColumn' => 'role_id', // name of column in user table holding role id (used for single role/BT only)
		'rolesTable' => 'Roles', // name of Configure key holding available roles OR class name of roles table
		'multiRole' => false, // true to enables multirole/HABTM authorization (requires a valid join table)
		'pivotTable' => null, // Use instead of auto-detect for the multi-role pivot table holding the user's roles
		'superAdminRole' => null, // id of super admin role granted access to ALL resources
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
	 * @param ComponentRegistry $registry
	 * @param array $config
	 * @throws Cake\Core\Exception\Exception
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
	 * Authorize a user using the AclComponent.
	 * allows single or multi role based authorization
	 *
	 * Examples:
	 * - User HABTM Roles (Role array in User array)
	 * - User belongsTo Roles (role_id in User array)
	 *
	 * @param array $user The user to authorize
	 * @param Cake\Network\Request $request The request needing authorization.
	 * @return bool Success
	 */
	public function authorize($user, Request $request) {
		return $this->validate($this->_getUserRoles($user), $request);
	}

	/**
	 * Validate the url to the role(s)
	 * allows single or multi role based authorization
	 *
	 * @param array $userRoles
	 * @param string $plugin
	 * @param string $controller
	 * @param string $action
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

		// allow access to all  prefixed actions for users belonging to
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

		// allow logged in super admins access to all resources
		if (!empty($this->_config['superAdminRole'])) {
			foreach ($userRoles as $userRole) {
				if ($userRole === $this->_config['superAdminRole']) {
					return true;
				}
			}
		}

		// generate ACL if not already set
		if ($this->_acl === null) {
			$this->_acl = $this->_getAcl();
		}

		// allow access if user has a role with wildcard access to the resource
		$iniKey = $this->_constructIniKey($request);
		if (isset($this->_acl[$iniKey]['actions']['*'])) {
			$matchArray = $this->_acl[$iniKey]['actions']['*'];
			foreach ($userRoles as $userRole) {
				if (in_array((string)$userRole, $matchArray)) {
					return true;
				}
			}
		}

		// allow access if user has been granted access to the specific resource
		if (isset($this->_acl[$iniKey]['actions'])) {
			if(array_key_exists($request->action, $this->_acl[$iniKey]['actions']) && !empty($this->_acl[$iniKey]['actions'][$request->action])) {
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
	 * Parse ini file and returns the allowed roles per action
	 * - uses cache for maximum performance
	 * improved speed by several actions before caching:
	 * - resolves role slugs to their primary key / identifier
	 * - resolves wildcards to their verbose translation
	 *
	 * @param string $path
	 * @return array Roles
	 */
	protected function _getAcl($path = null) {
		if ($path === null) {
			$path = ROOT . DS . 'config' . DS;
		}

		if ($this->_config['autoClearCache'] && Configure::read('debug') > 0) {
			Cache::delete($this->_config['cacheKey'], $this->_config['cache']);
		}
		if (($roles = Cache::read($this->_config['cacheKey'], $this->_config['cache'])) !== false) {
			return $roles;
		}

		$iniArray = $this->_parseAclIni($path . ACL_FILE);
		$availableRoles = $this->_getAvailableRoles();

		$res = [];
		foreach ($iniArray as $key => $array) {
			$res[$key] = $this->_deconstructIniKey($key);

			foreach ($array as $actions => $roles) {
				//$res[$key]['actions_string'] = $actions;

				// get all roles used in the current ini section
				$roles = explode(',', $roles);
				$actions = explode(',', $actions);

				foreach ($roles as $roleId => $role) {
					if (!($role = trim($role))) {
						continue;
					}
					// prevent undefined roles appearing in the iniMap
					if (!array_key_exists($role, $availableRoles) && $role !== '*') {
						unset($roles[$roleId]);
						continue;
					}
					if ($role === '*') {
						unset($roles[$roleId]);
						$roles = array_merge($roles, array_keys($availableRoles));
					}
				}

				// process actions
				foreach ($actions as $action) {
					if (!($action = trim($action))) {
						continue;
					}
					foreach ($roles as $role) {
						if (!($role = trim($role)) || $role === '*') {
							continue;
						}
						// lookup role id by name in roles array
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
	 * @return array List with all available roles
	 * @throws Cake\Core\Exception\Exception
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
		if (!count($iniArray)) {
			throw new Exception('Invalid TinyAuthorize ACL file');
		}
		return $iniArray;
	}

	/**
	 * Deconstructs an ACL ini section key into a named array with ACL parts
	 *
	 * @param string INI section key as found in acl.ini
	 * @return array Hash with named keys for controller, plugin and prefix
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
	 * Constructs an ACL ini section key from a given CakeRequest
	 *
	 * @param Cake\Network\Request $request The request needing authorization.
	 * @return array Hash with named keys for controller, plugin and prefix
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
	 * Returns a list of all available roles. Will look for a roles array in
	 * Configure first, tries database roles table next.
	 *
	 * @return array List with all available roles
	 * @throws Cake\Core\Exception\Exception
	 */
	protected function _getAvailableRoles() {
		$roles = Configure::read($this->_config['rolesTable']);
		if (is_array($roles)) {
			return $roles;
		}

		// no roles in Configure AND rolesTable does not exist
		$tables = ConnectionManager::get('default')->schemaCollection()->listTables();
		if (!in_array(Inflector::tableize($this->_config['rolesTable']), $tables)) {
			throw new Exception('Invalid TinyAuthorize Role Setup (no roles found in Configure or database)');
		}

		// fetch roles from database
		$rolesTable = TableRegistry::get($this->_config['rolesTable']);
		$roles = $rolesTable->find('all')->formatResults(function ($results) {
			return $results->combine('alias', 'id');
		})->toArray();

		if (!count($roles)) {
			throw new Exception('Invalid TinyAuthorize Role Setup (rolesTable has no roles)');
		}
		return $roles;
	}

	/**
	 * Returns a list of all roles belonging to the authenticated user in the
	 * following order:
	 * - single role id using the roleColumn in single-role mode
	 * - direct lookup in the pivot table (to support both Configure and Model
	 *   in multi-role mode)
	 *
	 * @param array $user The user to get the roles for
	 * @return array List with all role ids belonging to the user
	 * @throws Cake\Core\Exception\Exception
	 */
	protected function _getUserRoles($user) {
		// single-role
		if (!$this->_config['multiRole']) {
			if (isset($user[$this->_config['roleColumn']])) {
				return [$user[$this->_config['roleColumn']]];
			}
			throw new Exception(sprintf('Missing TinyAuthorize role id (%s) in user session', $this->_config['roleColumn']));
		}

		// multi-role: reverse engineer name of the pivot table
		$rolesTableName = $this->_config['rolesTable'];
		$pivotTableName = $this->_config['pivotTable'];
		if (!$pivotTableName) {
			$tables = [
				CLASS_USERS,
				$rolesTableName
			];
			asort($tables);
			$pivotTableName = implode('', $tables);
		}

		// fetch roles directly from the pivot table
		$pivotTable = TableRegistry::get($pivotTableName);
		$roleColumn = $this->_config['roleColumn'];
		$roles = $pivotTable->find('all', [
			'conditions' => ['user_id' => $user['id']],
			'fields' => $roleColumn
		])->extract($roleColumn)->toArray();

		if (!count($roles)) {
			throw new Exception('Missing TinyAuthorize roles for user in pivot table');
		}
		return $roles;
	}

}
