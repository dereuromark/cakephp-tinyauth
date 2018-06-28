<?php

namespace TinyAuth\Sync;

use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Core\App;
use Cake\Core\Configure;
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
			'file' => 'acl.ini',
		];
		$config = (array)Configure::read('TinyAuth') + $defaults;

		$file = ROOT . DS . 'config' . DS . $config['file'];
		$content = Utility::parseFile($file);

		$controllers = $this->getControllers((string)$args->getOption('plugin'));
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
	protected function getControllers($plugin) {
		$folders = App::path('Controller', $plugin);

		$controllers = [];
		foreach ($folders as $folder) {
			$controllers = array_merge($controllers, $this->parseControllers($folder, $plugin));
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
	protected function parseControllers($folder, $plugin, $prefix = null) {
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

			if ($this->noAuthenticationNeeded($name, $plugin, $prefix)) {
				continue;
			}

			$controllers[] = ($plugin ? strtolower($plugin) . '.' : '') . ($prefix ? strtolower($prefix) . '/' : '') . $name;
		}

		foreach ($folderContent[0] as $subFolder) {
			$prefixes = (array)Configure::read('TinyAuth.prefixes') ?: null;

			if ($prefixes !== null && !in_array($subFolder, $prefixes, true)) {
				continue;
			}

			$controllers = array_merge($controllers, $this->parseControllers($folder . $subFolder . DS, $plugin, $subFolder));
		}

		return $controllers;
	}

	/**
	 * @param string $name
	 * @param string $plugin
	 * @param string $prefix
	 * @return bool
	 */
	protected function noAuthenticationNeeded($name, $plugin, $prefix) {
		if (!isset($this->authAllow)) {
			$this->authAllow = $this->parseAuthAllow();
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
	protected function parseAuthAllow() {
		$defaults = [
			'file' => 'auth_allow.ini',
		];
		$config = (array)Configure::read('TinyAuth') + $defaults;

		$file = ROOT . DS . 'config' . DS . $config['file'];

		return Utility::parseFile($file);
	}

}
