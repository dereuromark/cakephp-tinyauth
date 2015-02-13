<?php
namespace TinyAuth\Auth;

use Cake\Core\Configure;
use Cake\Cache\Cache;
use Cake\Utility\Inflector;
use Cake\Utility\Hash;
use Cake\Auth\BaseAuthorize;
use Cake\Network\Request;
use Cake\Controller\ComponentRegistry;
use Cake\ORM\TableRegistry;

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
		'adminRole' => null, // needed together with adminPrefix if allowAdmin is enabled
		'superAdminRole' => null, // id of the role to grant access to ALL resources
		'allowUser' => false, // quick way to allow user access to non prefixed urls
		'allowAdmin' => false, // quick way to allow admin access to admin prefixed urls
		'adminPrefix' => 'admin',
		'cache' => AUTH_CACHE,
		'cacheKey' => 'tiny_auth_acl',
		'autoClearCache' => false, // usually done by Cache automatically in debug mode,
		'aclTable' => 'Roles', // only for multiple roles per user (HABTM)
		'aclKey' => 'role_id', // only for single roles per user (BT)
	];

	/**
	 * TinyAuthorize::__construct()
	 *
	 * @param ComponentRegistry $registry
	 * @param array $config
	 */
	public function __construct(ComponentRegistry $registry, array $config = array()) {
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
		if (isset($user[$this->_config['aclTable']])) {
			if (isset($user[$this->_config['aclTable']][0]['id'])) {
				$roles = Hash::extract($user[$this->_config['aclTable']], '{n}.id');
			} elseif (isset($user[$this->_config['aclTable']]['id'])) {
				$roles = array($user[$this->_config['aclTable']]['id']);
			} else {
				$roles = (array)$user[$this->_config['aclTable']];
			}
		} elseif (isset($user[$this->_config['aclKey']])) {
			$roles = array($user[$this->_config['aclKey']]);
		} else {
			$acl = $this->_config['aclTable'] . '/' . $this->_config['aclKey'];
			trigger_error(sprintf('Missing acl information (%s) in user session', $acl));
			$roles = array();
		}
		return $this->validate($roles, $request);
	}

	/**
	 * Validate the url to the role(s)
	 * allows single or multi role based authorization
	 *
	 * @param array $roles
	 * @param string $plugin
	 * @param string $controller
	 * @param string $action
	 * @return bool Success
	 */
	//public function validate($roles, $plugin, $controller, $action) {
	public function validate($roles, Request $request) {
		// construct the iniKey and iniMap for easy lookups
		$iniKey = $this->constructIniKey($request);
		$availableRoles = Configure::read($this->_config['aclTable']);

		// allow logged in users access to all actions except prefixed
		// @todo: this logic is based on the config description above, could
		// possibly be changed to allow all prefixes as well except /admin
		if (!empty($this->_config['allowUser'])) {
			if (empty($request->params['prefix'])) {
				return true;
			}
		}

		// allow access to all /admin prefixed actions for users belonging to
		// the specified adminRole.
		if (!empty($this->_config['allowAdmin']) && !empty($this->_config['adminRole'])) {
			if (!empty($request->params['prefix']) && $request->params['prefix'] === $this->_config['adminPrefix']) {
				$adminRoleId = $availableRoles[$this->_config['adminRole']];
				if (in_array($adminRoleId, $roles)) {
					return true;
				}
			}
		}

		// allow logged in super admins access to all resources
		if (!empty($this->_config['superAdminRole'])) {
			foreach ($roles as $role) {
				if ($role == $this->_config['superAdminRole']) {
					return true;
				}
			}
		}

		// generate ACL if not already set
		if ($this->_acl === null) {
			$this->_acl = $this->_getAcl();
		}

		// allow access if user has a role with wildcard access to the resource
		if (isset($this->_acl[$iniKey]['actions']['*'])) {
			$matchArray = $this->_acl[$iniKey]['actions']['*'];
			if (in_array('-1', $matchArray)) {
				return true;
			}
			foreach ($roles as $role) {
				if (in_array((string)$role, $matchArray)) {
					return true;
				}
			}
		}

		// allow access if user has been granted access to the specific resource
		if(array_key_exists($request->action, $this->_acl[$iniKey]['actions']) && !empty($this->_acl[$iniKey]['actions'][$request->action])) {
			$matchArray = $this->_acl[$iniKey]['actions'][$request->action];

			// direct access? (even if he has no roles = GUEST)
			if (in_array('-1', $matchArray)) {
				return true;
			}

			// normal access (rolebased)
			foreach ($roles as $role) {
				if (in_array((string)$role, $matchArray)) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * @return Cake\ORM\Table The User table
	 */
	public function getTable() {
		return TableRegistry::get(CLASS_USER);
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

		$availableRoles = Configure::read($this->_config['aclTable']);
		if (!is_array($availableRoles)) {
			$Table = $this->getTable();
			if (!isset($Table->{$this->_config['aclTable']})) {
				throw new \Exception('Missing relationship between Users and Roles.');
			}

			$availableRoles = $Table->{$this->_config['aclTable']}->find('all')->formatResults(function ($results) {
				return $results->combine('alias', 'id');
			})->toArray();
			Configure::write($this->_config['aclTable'], $availableRoles);
		}
		if (!is_array($availableRoles) || !is_array($iniArray)) {
			trigger_error('Invalid Role Setup for TinyAuthorize (no roles found)');
			return array();
		}

		$res = [];
		foreach ($iniArray as $key => $array) {
			$key = $this->normalizeIniKey($key);
			$res[$key] = $this->deconstructIniKey($key);

			foreach ($array as $actions => $roles) {
				// get all roles used in the current ini section
				$roles = explode(',', $roles);
				$actions = explode(',', $actions);

				foreach ($roles as $roleId => $role) {
					if (!($role = trim($role))) {
						continue;
					}
					// prevent undefined roles appearing in the iniMap
					if (!array_key_exists($role, $availableRoles) && $role != '*') {
						unset($roles[$roleId]);
						continue;
					}
					if ($role === '*') {
						unset($roles[$roleId]);
						$roles = array_merge($roles, array_keys(Configure::read($this->_config['aclTable'])));
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
						$newRole = Configure::read($this->_config['aclTable'] . '.' . strtolower($role));
						$res[$key]['actions'][$action][] = $newRole;

					}
				}
			}
		}
		Cache::write($this->_config['cacheKey'], $res, $this->_config['cache']);
		return $res;
	}

	/**
	 * Conforms a user specified ACL ini section key to CakePHP conventions.
	 * This way internal $_acl has correct naming for controllers etc + this
	 * prevents possible casing problems.
	 *
	 * @todo: not changing prefix yet, is the casing user definable?
	 *
	 * @param string INI section key as found in acl.ini
	 * @return string String converted to use cake conventions
	 */
	protected function normalizeIniKey($key) {
		$iniMap = $this->deconstructIniKey($key);
		$res = Inflector::camelize($iniMap['controller']);
		if (!empty($iniMap['prefix'])) {
			$res = strtolower($iniMap['prefix']) . "/$res";
		}
		if (!empty($iniMap['plugin'])) {
			$res = Inflector::camelize($iniMap['plugin']) . ".$res";
		}
		return $res;
	}

	/**
	 * Deconstructs an ACL ini section key into a named array with ACL parts
	 *
	 * @param string INI section key as found in acl.ini
	 * @return array Hash with named keys for controller, plugin and prefix
	 */
	protected function deconstructIniKey($key) {
		$res = [
			'plugin' => null,
			'prefix' => null
		];

		if (strpos($key, '.') !== false) {
			list($plugin, $key) = explode('.', $key);
			$res['plugin'] = Inflector::camelize($plugin);
		}
		if (strpos($key, '/') !== false) {
			list($res['prefix'], $key) = explode('/', $key);
			$res['prefix'] = strtolower($res['prefix']);
		}
		$res['controller'] = Inflector::camelize($key);
		return $res;
	}

	/**
	 * Constructs an ACL ini section key from a given CakeRequest
	 *
	 * @param Cake\Network\Request $request The request needing authorization.
	 * @return array Hash with named keys for controller, plugin and prefix
	 */
	protected function constructIniKey(Request $request) {
		$res = Inflector::camelize($request->params['controller']);
		if (!empty($request->params['prefix'])) {
			$res = strtolower($request->params['prefix']) . "/$res";
		}
		if (!empty($request->params['plugin'])) {
			$res = Inflector::camelize($request->params['plugin']) . ".$res";
		}
		return $res;
	}

}
