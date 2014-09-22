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

	protected $_defaultConfig = array(
		'superadminRole' => null, // quick way to allow access to every action
		'allowUser' => false, // quick way to allow user access to non prefixed urls
		'allowAdmin' => false, // quick way to allow admin access to admin prefixed urls
		'adminPrefix' => 'admin_',
		'adminRole' => null, // needed together with adminPrefix if allowAdmin is enabled
		'cache' => AUTH_CACHE,
		'cacheKey' => 'tiny_auth_acl',
		'autoClearCache' => false, // usually done by Cache automatically in debug mode,
		'aclTable' => 'Roles', // only for multiple roles per user (HABTM)
		'aclKey' => 'role_id', // only for single roles per user (BT)
	);

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

		return $this->validate($roles, $request->params['plugin'], $request->params['controller'], $request->params['action']);
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
	public function validate($roles, $plugin, $controller, $action) {
		$action = Inflector::underscore($action);
		$controller = Inflector::underscore($controller);
		$plugin = Inflector::underscore($plugin);

		if (!empty($this->_config['allowUser'])) {
			// all user actions are accessable for logged in users
			if (mb_strpos($action, $this->_config['adminPrefix']) !== 0) {
				return true;
			}
		}
		if (!empty($this->_config['allowAdmin']) && !empty($this->_config['adminRole'])) {
			// all admin actions are accessable for logged in admins
			if (mb_strpos($action, $this->_config['adminPrefix']) === 0) {
				if (in_array((string)$this->_config['adminRole'], $roles)) {
					return true;
				}
			}
		}

		if ($this->_acl === null) {
			$this->_acl = $this->_getAcl();
		}

		// allow_all check
		if (!empty($this->_config['superadminRole'])) {
			foreach ($roles as $role) {
				if ($role == $this->_config['superadminRole']) {
					return true;
				}
			}
		}

		// controller wildcard
		if (isset($this->_acl[$controller]['*'])) {
			$matchArray = $this->_acl[$controller]['*'];
			if (in_array('-1', $matchArray)) {
				return true;
			}
			foreach ($roles as $role) {
				if (in_array((string)$role, $matchArray)) {
					return true;
				}
			}
		}

		// specific controller/action
		if (!empty($controller) && !empty($action)) {
			if (array_key_exists($controller, $this->_acl) && !empty($this->_acl[$controller][$action])) {
				$matchArray = $this->_acl[$controller][$action];

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
			$path = APP . 'Config' . DS;
		}

		$res = array();
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

		foreach ($iniArray as $key => $array) {
			list($plugin, $controllerName) = pluginSplit($key);
			$controllerName = Inflector::underscore($controllerName);

			foreach ($array as $actions => $roles) {
				$actions = explode(',', $actions);
				$roles = explode(',', $roles);

				foreach ($roles as $key => $role) {
					if (!($role = trim($role))) {
						continue;
					}
					if ($role === '*') {
						unset($roles[$key]);
						$roles = array_merge($roles, array_keys(Configure::read($this->_config['aclTable'])));
					}
				}

				foreach ($actions as $action) {
					if (!($action = trim($action))) {
						continue;
					}
					$actionName = Inflector::underscore($action);

					foreach ($roles as $role) {
						if (!($role = trim($role)) || $role === '*') {
							continue;
						}
						$newRole = Configure::read($this->_config['aclTable'] . '.' . strtolower($role));
						if (!empty($res[$controllerName][$actionName]) && in_array((string)$newRole, $res[$controllerName][$actionName])) {
							continue;
						}
						$res[$controllerName][$actionName][] = $newRole;
					}
				}
			}
		}
		Cache::write($this->_config['cacheKey'], $res, $this->_config['cache']);
		return $res;
	}

}
