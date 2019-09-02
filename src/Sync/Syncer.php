<?php

namespace TinyAuth\Sync;

use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Filesystem\Folder;
use TinyAuth\Utility\Utility;

class Syncer {

	/**
	 * @var array|null
	 */
	protected $authAllow;

	/**
	 * @param \Cake\Console\Arguments $args
	 * @param \Cake\Console\ConsoleIo $io
	 * @return void
	 */
	public function syncAcl(Arguments $args, ConsoleIo $io) {
		$defaults = [
			'file' => 'tinyauth_acl.ini',
		];
		$config = (array)Configure::read('TinyAuth') + $defaults;

		$file = ROOT . DS . 'config' . DS . $config['file'];
		$content = Utility::parseFile($file);

		$controllers = $this->_getControllers((string)$args->getOption('plugin'));
		foreach ($controllers as $controller) {
			if (isset($content[$controller])) {
				continue;
			}

			$io->info('Add ' . $controller);
			$map = [
				'*' => $args->getArgument('roles'),
			];
			$content[$controller] = $map;
		}

		if ($args->getOption('dry-run')) {
			$string = Utility::buildIniString($content);

			if ($args->getOption('verbose')) {
				$io->info($string);
			}
			return;
		}

		Utility::generateFile($file, $content);
	}

	/**
	 * @param string $plugin
	 * @return array
	 */
	protected function _getControllers($plugin) {
		if ($plugin === 'all') {
			$plugins = (array)Plugin::loaded();

			$controllers = [];
			foreach ($plugins as $plugin) {
				$controllers = array_merge($controllers, $this->_getControllers($plugin));
			}

			return $controllers;
		}

		$folders = App::path('Controller', $plugin);

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

			$controllers[] = ($plugin ? $plugin . '.' : '') . ($prefix ? strtolower($prefix) . '/' : '') . $name;
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
	 * @param string $plugin
	 * @param string $prefix
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
			'file' => 'tinyauth_allow.ini',
		];
		$config = (array)Configure::read('TinyAuth') + $defaults;

		$file = ROOT . DS . 'config' . DS . $config['file'];

		return Utility::parseFile($file);
	}

}
