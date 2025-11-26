<?php

namespace TinyAuth\Sync;

use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use TinyAuth\Filesystem\Folder;
use TinyAuth\Utility\Utility;

/**
 * Helper class for adding specific ACL entries to the INI configuration file.
 *
 * Used by AddCommand to modify auth_acl.ini file with new or updated
 * controller/action/role mappings.
 *
 * @internal
 */
class Adder {

	/**
	 * @var array<string, mixed>
	 */
	protected array $config;

	public function __construct() {
		$defaults = [
			'aclFile' => 'auth_acl.ini',
			'aclFilePath' => null,
		];
		$this->config = (array)Configure::read('TinyAuth') + $defaults;
	}

	/**
	 * @var array|null
	 */
	protected $authAllow;

	/**
	 * Adds or updates a controller/action entry in the ACL INI file.
	 *
	 * Files modified:
	 * - config/auth_acl.ini (default) or custom path from TinyAuth.aclFilePath
	 *
	 * File format example:
	 * ```ini
	 * [Articles]
	 * index = user, admin
	 * add = admin
	 * * = admin
	 * ```
	 *
	 * @param string $controller Controller name (e.g., 'Articles' or 'MyPlugin.Admin/Articles')
	 * @param string $action Action name (e.g., 'index') or '*' for all actions
	 * @param array<string> $roles Role names to grant access (e.g., ['user', 'admin'])
	 * @param \Cake\Console\Arguments $args
	 * @param \Cake\Console\ConsoleIo $io
	 *
	 * @return void
	 */
	public function addAcl(string $controller, string $action, array $roles, Arguments $args, ConsoleIo $io) {
		$path = $this->config['aclFilePath'] ?: ROOT . DS . 'config' . DS;
		$file = $path . $this->config['aclFile'];
		$content = Utility::parseFile($file);

		if (isset($content[$controller][$action]) || isset($content[$controller]['*'])) {
			$mappedRoles = $content[$controller][$action] ?? $content[$controller]['*'];
			if (strpos($mappedRoles, ',') !== false) {
				$mappedRoles = array_map('trim', explode(',', $mappedRoles));
			}
			$this->checkRoles($roles, (array)$mappedRoles, $io);
		}

		$io->info('Add [' . $controller . '] ' . $action . ' = ' . implode(', ', $roles));
		$content[$controller][$action] = implode(', ', $roles);

		if ($args->getOption('dry-run')) {
			$string = Utility::buildIniString($content);

			if ($args->getOption('verbose')) {
				$io->info('=== ' . $this->config['aclFile'] . ' ===');
				$io->info($string);
				$io->info('=== ' . $this->config['aclFile'] . ' end ===');
			}

			return;
		}

		Utility::generateFile($file, $content);
	}

	/**
	 * @param string|null $plugin
	 * @return array
	 */
	protected function _getControllers($plugin) {
		if ($plugin === 'all') {
			$plugins = Plugin::loaded();

			$controllers = [];
			foreach ($plugins as $plugin) {
				$controllers = array_merge($controllers, $this->_getControllers($plugin));
			}

			return $controllers;
		}

		$folders = App::classPath('Controller', $plugin);

		$controllers = [];
		foreach ($folders as $folder) {
			$controllers = array_merge($controllers, $this->_parseControllers($folder, $plugin));
		}

		return $controllers;
	}

	/**
	 * @param string $folder Path
	 * @param string|null $plugin
	 * @param string|null $prefix
	 *
	 * @return array
	 */
	protected function _parseControllers($folder, $plugin, $prefix = null) {
		$folderContent = (new Folder($folder))->read(Folder::SORT_NAME, true);

		$controllers = [];
		foreach ($folderContent[1] as $file) {
			$className = pathinfo($file, PATHINFO_FILENAME);

			if (!preg_match('#^(.+)Controller$#', $className, $matches)) {
				continue;
			}
			$name = $matches[1];
			if ($matches[1] === 'App') {
				continue;
			}

			if ($this->_noAuthenticationNeeded($name, $plugin, $prefix)) {
				continue;
			}

			$controllers[] = ($plugin ? $plugin . '.' : '') . ($prefix ? $prefix . '/' : '') . $name;
		}

		foreach ($folderContent[0] as $subFolder) {
			$prefixes = (array)Configure::read('TinyAuth.prefixes') ?: null;

			if ($prefixes !== null && !in_array($subFolder, $prefixes, true)) {
				continue;
			}

			$controllers = array_merge($controllers, $this->_parseControllers($folder . $subFolder . DS, $plugin, $subFolder));
		}

		return $controllers;
	}

	/**
	 * @param string $name
	 * @param string|null $plugin
	 * @param string|null $prefix
	 * @return bool
	 */
	protected function _noAuthenticationNeeded($name, $plugin, $prefix) {
		if (!isset($this->authAllow)) {
			$this->authAllow = $this->_parseAuthAllow();
		}

		$key = $name;
		if (!isset($this->authAllow[$key])) {
			return false;
		}

		if ($this->authAllow[$key] === '*') {
			return true;
		}

		//TODO: specific actions?
		return false;
	}

	/**
	 * @return array
	 */
	protected function _parseAuthAllow() {
		$defaults = [
			'allowFilePath' => null,
			'allowFile' => 'auth_allow.ini',
		];
		$config = (array)Configure::read('TinyAuth') + $defaults;

		$path = $config['allowFilePath'] ?: ROOT . DS . 'config' . DS;
		$file = $path . $config['allowFile'];

		return Utility::parseFile($file);
	}

	/**
	 * @param \Cake\Console\Arguments $args
	 *
	 * @return array
	 */
	public function controllers(Arguments $args): array {
		//$path = $this->config['aclFilePath'] ?: ROOT . DS . 'config' . DS;
		//$file = $path . $this->config['aclFile'];
		//$content = Utility::parseFile($file);

		$controllers = $this->_getControllers((string)$args->getOption('plugin') ?: null);

		return $controllers;
	}

	/**
	 * @param array<string> $roles
	 * @param array<string> $mappedRoles
	 * @param \Cake\Console\ConsoleIo $io
	 *
	 * @return void
	 */
	protected function checkRoles(array $roles, array $mappedRoles, ConsoleIo $io): void {
		foreach ($roles as $role) {
			if (!in_array($role, $mappedRoles, true) && !in_array('*', $mappedRoles, true)) {
				return;
			}
		}

		$io->abort('Already present. Aborting');
	}

}
