<?php
namespace TinyAuth\Auth;

use Cake\Auth\BaseAuthorize;
use Cake\Cache\Cache;
use Cake\Controller\ComponentRegistry;
use Cake\Core\Configure;
use Cake\Database\Schema\Collection;
use Cake\Datasource\ConnectionManager;
use Cake\Network\Request;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;
use Cake\Utility\Inflector;

if (!defined('CLASS_USER')) {
	define('CLASS_USER', 'Users'); // override if you have it in a plugin: PluginName.Users etc
}
if (!defined('AUTH_CACHE')) {
	define('AUTH_CACHE', '_cake_core_'); // use the most persistent cache by default
}
if (!defined('ACL_FILE')) {
	define('ACL_FILE', 'acl.ini'); // stored in /app/Config/
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
		'adminRole' => null, // id of the admin role used by allowAdmin
		'adminPrefix' => 'admin', // admin prefix used by  allowAdmin
		'allowUser' => false, // enable to allow ALL roles access to all actions except prefixed with 'adminPrefix'
		'allowAdmin' => false, // enable to allow admin role access to all 'adminPrefix' prefixed urls
		'allowAll' => null, // id of super admin role granted access to ALL resources
		'cache' => AUTH_CACHE,
		'cacheKey' => 'tiny_auth_acl',
		'autoClearCache' => false, // usually done by Cache automatically in debug mode,
		'roleColumn' => 'role_id', // name of column in user table holding role id (used for single role/BT only)
		'rolesTable' => 'Roles', // name of table class OR Configure key holding all available roles
		'multiRole' => false // enables multirole (HABTM) authorization (requires valid rolesTable and join table)
	];

	/**
	 * TinyAuthorize::__construct()
	 *
	 * @param ComponentRegistry $registry
	 * @param array $config
	 */
	public function __construct(ComponentRegistry $registry, array $config = []) {
		$config += $this->_defaultConfig;
		parent::__construct($registry, $config);

		if (Cache::config($config['cache']) === false) {
			throw new \Exception(sprintf('TinyAuth could not find `%s` cache - expects at least a `default` cache', $config['cache']));
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
			if ($request->params['prefix'] != $this->_config['adminPrefix']) {
				return true;
			}
		}

		// allow access to all /admin prefixed actions for users belonging to
		// the specified adminRole id.
		if (!empty($this->_config['allowAdmin']) && !empty($this->_config['adminRole'])) {
			if (!empty($request->params['prefix']) && $request->params['prefix'] === $this->_config['adminPrefix']) {
				if (in_array($this->_config['adminRole'], $userRoles)) {
					return true;
				}
			}
		}

		// allow logged in super admins access to all resources
		if (!empty($this->_config['allowAll'])) {
			foreach ($userRoles as $userRole) {
				if ($userRole === $this->_config['allowAll']) {
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
	 * @return Cake\ORM\Table The User table
	 */
	public function getUserTable() {
		$table = TableRegistry::get(CLASS_USER);
		if (!$table->associations()->has($this->_config['rolesTable'])) {
			throw new \Exception('Missing relationship between Users and ' .
				$this->_config['rolesTable'] . '.');
		}
		return $table;
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

		if (!file_exists($path . ACL_FILE)) {
			touch($path . ACL_FILE);
		}

		if (function_exists('parse_ini_file')) {
			$iniArray = parse_ini_file($path . ACL_FILE, true);
		} else {
			$iniArray = parse_ini_string(file_get_contents($path . ACL_FILE), true);
		}

		$availableRoles = $this->_getAvailableRoles();

		if (!is_array($availableRoles) || !is_array($iniArray)) {
			trigger_error('Invalid Role Setup for TinyAuthorize (no roles found)');
			return [];
		}

		$res = [];
		foreach ($iniArray as $key => $array) {
			$res[$key] = $this->_deconstructIniKey($key);

			foreach ($array as $actions => $roles) {
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
						$newRole = Configure::read($this->_config['rolesTable'] . '.' . strtolower($role));
						$res[$key]['actions'][$action][] = $newRole;

					}
				}
			}
		}
		Cache::write($this->_config['cacheKey'], $res, $this->_config['cache']);
		return $res;
	}

	/**
	 * Returns a list of all roles belonging to the authenticated user
	 *
	 * @todo discuss trigger_error + caching (?)
	 *
	 * @param array $user The user to get the roles for
	 * @return array List with all role ids belonging to the user
	 */
	protected function _getUserRoles($user) {
		if (!$this->_config['multiRole']) {
			if (isset($user[$this->_config['roleColumn']])) {
				return [$user[$this->_config['roleColumn']]];
			}
			trigger_error(sprintf('Missing role id (%s) in user session', $this->_config['roleColumn']));
			return [];
		}

		// multi-role: fetch user data and associated roles from database
		$usersTable = $this->getUserTable();
		$userData = $usersTable->get($user['id'], [
			'contain' => [$this->_config['rolesTable']]
		]);
		return Hash::extract($userData->toArray(), Inflector::tableize($this->_config['rolesTable']) . '.{n}.id');
	}

	/**
	 * Returns a list of all available roles from the database if the roles
	 * table exists, otherwise returns the roles array from Configure.
	 *
	 * @return array List with all available roles
	 */
	protected function _getAvailableRoles() {
		// if no roles table exists return the roles array from Configure
		$tables = ConnectionManager::get('default')->schemaCollection()->listTables();
		if (!in_array(Inflector::tableize($this->_config['rolesTable']), $tables)) {
			return Configure::read($this->_config['rolesTable']);
		}

		// table exists so return all roles found in the database
		$userTable = $this->getUserTable();
		$roles = $userTable->{$this->_config['rolesTable']}->find('all')->formatResults(function ($results) {
			return $results->combine('alias', 'id');
		})->toArray();
		Configure::write($this->_config['rolesTable'], $roles);
		return $roles;
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

}
