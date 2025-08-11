<?php

namespace TinyAuth\Sync;

use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use TinyAuth\Filesystem\Folder;
use TinyAuth\Utility\Utility;

class Syncer {

	/**
	 * @var array|null
	 */
	protected $authAllow;

	/**
	 * Synchronizes all discovered controllers with the ACL INI file.
	 *
	 * Files modified:
	 * - config/auth_acl.ini (default) or custom path from TinyAuth.aclFilePath
	 *
	 * Process:
	 * 1. Scans src/Controller/ (and plugin controllers if specified)
	 * 2. Finds all controllers that don't have ACL entries
	 * 3. Adds missing controllers with wildcard (*) action and specified roles
	 *
	 * Example result in auth_acl.ini:
	 * ```ini
	 * [NewController]
	 * * = user, admin
	 * ```
	 *
	 * Note: Existing controller entries are preserved and not modified.
	 *
	 * @param \Cake\Console\Arguments $args Command arguments including roles
	 * @param \Cake\Console\ConsoleIo $io Console I/O for output
	 * @return void
	 */
	public function syncAcl(Arguments $args, ConsoleIo $io) {
		$defaults = [
			'aclFile' => 'auth_acl.ini',
			'aclFilePath' => null,
		];
		$config = (array)Configure::read('TinyAuth') + $defaults;

		$path = $config['aclFilePath'] ?: ROOT . DS . 'config' . DS;
		$file = $path . $config['aclFile'];
		$content = Utility::parseFile($file);

		$controllers = $this->_getControllers((string)$args->getOption('plugin') ?: null);
		foreach ($controllers as $controller) {
			if (isset($content[$controller])) {
				continue;
			}

			$io->info('Add ' . $controller);
			$roles = (string)$args->getArgument('roles');
			$roles = array_map('trim', explode(',', $roles));
			$map = [
				'*' => implode(', ', $roles),
			];
			$content[$controller] = $map;
		}

		if ($args->getOption('dry-run')) {
			$string = Utility::buildIniString($content);

			if ($args->getOption('verbose')) {
				$io->info('=== ' . $config['aclFile'] . ' ===');
				$io->info($string);
				$io->info('=== ' . $config['aclFile'] . ' end ===');
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

}
