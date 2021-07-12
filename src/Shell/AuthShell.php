<?php
namespace TinyAuth\Shell;

use Cake\Console\Shell;
use Cake\Utility\Inflector;
use TinyAuth\Auth\TinyAuthorize as BaseTinyAuthorize;
use Cake\Controller\ComponentRegistry;
use Cake\Core\Configure;
use Cake\Utility\Text;

/**
 * TinyAuth.Auth Shell
 *
 * Modify the acl.ini file via CLI wrapper access
 *
 * @author Mark Scherer
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 */
class AuthShell extends Shell {

	protected $_defaultConfig = [
		'clean' => true, // Auto cleanup (alphabetical order for controllers/actions, config based order for roles, duplicate removal)
	];

	public function startup() {
		parent::startup();

		$aclFile = ACL_FILE;
		if (!empty($this->params['dry-run'])) {
			$aclFile .= '.copy';
			$this->out('DRY RUN Mode enabled, writing to ' . $aclFile . ' instead!', 1, Shell::VERBOSE);
		}
		$this->params['clean'] = $this->_defaultConfig['clean'];
	}

	/**
	 * Add rules
	 *
	 * @return void
	 */
	public function clean() {
		$this->Tiny = new Tiny();
		$acl = $this->Tiny->getAcl();

		$this->Tiny->setAcl($acl, $this->params);

		$this->out('Cleaned acl.ini file');
	}

	/**
	 * Display available roles and their usage stats.
	 *
	 * @return void
	 */
	public function roles() {
		$this->out('Available roles');

		$roles = $this->_roleStats();
		foreach ($roles as $key => $count) {
			$this->out(' - ' . $key . ': ' . $count . 'x in use');
		}
	}

	/**
	 * Add rules
	 *
	 * @return void
	 */
	public function add($controller = null, $actions = null, $roles = null) {
		$controller = $this->_getController($controller);
		$actions = $this->_getActions($actions);
		$roles = $this->_getRoles($roles);

		$this->Tiny = new Tiny();
		$acl = $this->Tiny->getAcl();
		$acl[$controller]['map'][$actions] = $roles;

		$this->Tiny->setAcl($acl, $this->params);

		$this->out('Add: [' . $controller . '] ' . $actions . ' = ' . $roles);
	}

	/**
	 * Add rules
	 *
	 * @return void
	 */
	public function remove($controller = null, $actions = null, $roles = null) {
		$controller = $this->_getController($controller);
		$actions = $this->_getActions($actions);
		$roles = $this->_getRoles($roles);

		$this->Tiny = new Tiny();
		$acl = $this->Tiny->getAcl();

		if (isset($acl[$controller]['map'][$actions])) {
			unset($acl[$controller]['map'][$actions]);
			$this->Tiny->setAcl($acl, $this->params);
		}

		$this->out('Remove: [' . $controller . '] ' . $actions . ' = ' . $roles);
	}

	protected function _getController($controller) {
		while (empty($controller)) {
			$controller = $this->in('Controller? q to quit.');
		}
		if ($controller === 'q') {
			return $this->error('Aborted!');
		}
		return $controller;
	}

	protected function _getActions($actions) {
		while (empty($actions)) {
			$actions = $this->in('Action(s)? * for wildcard, q to quit.');
		}
		if ($actions === 'q') {
			return $this->error('Aborted!');
		}
		return $actions;
	}

	protected function _getRoles($roles, $validate = true) {
		while (empty($roles)) {
			$roles = $this->in('Role(s)? * for wildcard, q to quit.');
		}
		if ($roles === 'q') {
			return $this->error('Aborted!');
		}

		if ($validate) {
			$roleArray = Text::tokenize($roles);
			$availableRoles = $this->_roles();
			foreach ($roleArray as $role) {
				if (!array_key_exists($role, $availableRoles)) {
					return $this->error('Invalid role: ' . $role);
				}
			}
		}
		return $roles;
	}

	protected function _roles() {
		$this->Tiny = new Tiny();
		$roles = $this->Tiny->getAvailableRoles();

		return $roles;
	}

	/**
	 * @return array
	 */
	protected function _roleStats() {
		$this->Tiny = new Tiny();
		$roles = $this->Tiny->getAvailableRoles();

		// Add stats
		$roleStats = [];
		$acl = $this->Tiny->getAcl();
		foreach ($acl as $array) {
			foreach ($array['actions'] as $action => $actionRoles) {
				foreach ($actionRoles as $key => $actionRole) {
					$roleName = array_keys($roles, $actionRole);
					$roleName = array_shift($roleName);
					$roleStats[$roleName] = isset($roleStats[$roleName]) ? ($roleStats[$roleName] + 1) : 1;
				}
			}
		}

		foreach ($roles as $key => $role) {
			$count = 0;
			if (!empty($roleStats['*'])) {
				$count += $roleStats['*'];
			}
			if (!empty($roleStats[$key])) {
				$count += $roleStats[$key];
			}
			$roles[$key] = $count;
		}
		return $roles;
	}

	/**
	 * @return ConsoleOptionParser
	 */
	public function getOptionParser() {
		$subcommandParser = array(
			'options' => array(
				'dry-run' => array(
					'short' => 'd',
					'help' => 'Dry run the command. A .copy file will be created instead.',
					'boolean' => true
				),
			),
			'arguments' => [
				'controller' => [
					'help' => '[Plugin.prefix/Controller]',
					'required' => false
				],
				'actions' => [
					'help' => '[action(s)|*]',
					'required' => false
				],
				'roles' => [
					'help' => '[roles(s)|*]',
					'required' => false
				]
			]
		);

		$parser = parent::getOptionParser();
		return $parser
			->description('The TinyAuth authorization shell can modify your acl.ini file. Note that case sensitive is very important for auth to function correctly.')
			->addSubcommand('add', array(
				'help' => 'Add rules.',
				'parser' => $subcommandParser
			))
			->addSubcommand('remove', array(
				'help' => 'Remove rules.',
				'parser' => $subcommandParser,
			))
			->addSubcommand('clean', array(
				'help' => 'Cleanup.',
				'parser' => [
					'options' => array(
						'dry-run' => array(
							'short' => 'd',
							'help' => 'Dry run the command. A .copy file will be created instead.',
							'boolean' => true
						),
					),
				]
			))
			->addSubcommand('roles', array(
				'help' => 'Display available roles.'
			));
	}

}

/**
 * Class Tiny
 *
 * Convenience wrapper
 * //TODO: abstract it to make DB driven modification possible, as well.
 */
class Tiny {

	public $Auth;

	public function __construct() {
		$this->Auth = new TinyAuthorize(new ComponentRegistry(), ['autoClearCache' => true]);
	}

	public function getAcl() {
		return $this->Auth->getAcl();
	}

	public function getAvailableRoles() {
		return $this->Auth->getAvailableRoles();
	}

	public function setAcl(array $data, array $config) {
		$aclFile = ACL_FILE;
		if (!empty($config['dry-run'])) {
			$aclFile .= '.copy';
		}

		if (!empty($config['clean'])) {
			ksort($data);
			foreach ($data as $key => &$array) {
				ksort($array['map']);
			}
			$res = [];
			foreach ($data as $key => $array) {
				$map = $array['map'];
				foreach ($map as $actions => $roles) {
					$newActions = $actions;
					$newRoles = $roles;
					// If containing the wildcard, all other actions are irrelevant
					if (strpos($newActions, '*') !== false) {
						$newActions = '*';
					}
					if (strpos($newRoles, '*') !== false) {
						$newRoles = '*';
					}
					$newActions = implode(',', Text::tokenize($newActions));
					$newRoles = implode(',', Text::tokenize($newRoles));
					// Try to combine multiple rows with the same key before overwriting
					if (isset($map[$newActions]) && $actions !== $newActions) {
						$newRoles = $map[$newActions] . ',' . $newRoles;
					}

					$map[$newActions] = $newRoles;
					if ($actions !== $newActions) {
						unset($map[$actions]);
					}
				}
				$res[$key] = $map;
			}
			$data = $res;
		}

		return $this->Auth->setAcl($data, null, $aclFile);
	}

}

/**
 * Class TinyAuthorize
 *
 * Extend base class to support INI write manipulation
 */
class TinyAuthorize extends BaseTinyAuthorize {

	public function getAcl() {
		return $this->_getAcl();
	}

	public function getAvailableRoles() {
		return $this->_getAvailableRoles();
	}

	public function setAcl($data, $path = null, $aclFile = ACL_FILE) {
		if ($path === null) {
			$path = ROOT . DS . 'config' . DS;
		}

		if (!file_exists($path . $aclFile)) {
			touch($path . $aclFile);
		}

		return $this->_writeIniFile($data, $path . $aclFile, true);
	}

	/**
	 * @param $data
	 * @param $path
	 * @param bool $hasSections
	 * @return bool Success
	 */
	protected function _writeIniFile($data, $path, $hasSections = FALSE) {
		$content = "";
		if ($hasSections) {
			foreach ($data as $key => $elem) {
				$content .= "[" . $key . "]\n";
				foreach ($elem as $key2 => $elem2) {
					if (is_array($elem2)) {
						for ($i = 0; $i < count($elem2); $i++) {
							$content .= $key2 . "[] = " . $elem2[$i] . "\n";
						}
					} elseif ($elem2 === "") $content .= $key2 . " = \n";
					else $content .= $key2 . " = " . $elem2 . "\n";
				}
				$content .= "\n";
			}
		} else {
			foreach ($data as $key => $elem) {
				if (is_array($elem)) {
					for ($i = 0; $i < count($elem); $i++) {
						$content .= $key . "[] = " . $elem[$i] . "\n";
					}
				} elseif ($elem === "") $content .= $key . " = \n";
				else $content .= $key . " = \"" . $elem . "\"\n";
			}
		}

		if (!$handle = fopen($path, 'w')) {
			return false;
		}

		$success = (bool)fwrite($handle, $content);
		fclose($handle);

		return $success;
	}

}
