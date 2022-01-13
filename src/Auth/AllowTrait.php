<?php

namespace TinyAuth\Auth;

use Cake\Core\Configure;
use Cake\Core\Exception\Exception;
use InvalidArgumentException;
use TinyAuth\Auth\AllowAdapter\AllowAdapterInterface;
use TinyAuth\Utility\Cache;

trait AllowTrait {

	/**
	 * @var \TinyAuth\Auth\AllowAdapter\AllowAdapterInterface|null
	 */
	protected $_allowAdapter;

	/**
	 * Get the rules for a specific controller.
	 *
	 * Each consumer has to check the allow and deny rules inside,
	 * a deny always trumps an allow, specific actions always the wildcard (*).
	 *
	 * @param array $params
	 * @return array
	 */
	protected function _getAllowRule(array $params) {
		$rules = $this->_getAllow($this->getConfig('allowFilePath'));

		$allowDefaults = $this->_getAllowDefaultsForCurrentParams($params);

		foreach ($rules as $rule) {
			if ($params['plugin'] && $params['plugin'] !== $rule['plugin']) {
				continue;
			}
			if (!empty($params['prefix']) && $params['prefix'] !== $rule['prefix']) {
				continue;
			}
			if ($params['controller'] !== $rule['controller']) {
				continue;
			}

			if ($allowDefaults) {
				$rule['allow'] = array_merge($rule['allow'], $allowDefaults);
			}

			return $rule;
		}

		return [
			'allow' => $allowDefaults,
			'deny' => [],
		];
	}

	/**
	 * @param array $rule
	 * @param string $action
	 *
	 * @return bool
	 */
	protected function _isActionAllowed(array $rule, $action) {
		$rule += [
			'deny' => [],
			'allow' => [],
		];

		if (in_array($action, $rule['deny'], true) || in_array('*', $rule['deny'], true)) {
			return false;
		}

		if (!in_array($action, $rule['allow'], true) && !in_array('*', $rule['allow'], true)) {
			return false;
		}

		return true;
	}

	/**
	 * @param array $params
	 * @return array<string>
	 */
	protected function _getAllowDefaultsForCurrentParams(array $params) {
		if ($this->getConfig('allowNonPrefixed') && empty($params['prefix'])) {
			return ['*'];
		}

		if (empty($params['prefix'])) {
			return [];
		}

		/** @var array<string> $allowedPrefixes */
		$allowedPrefixes = (array)$this->getConfig('allowPrefixes');

		$result = [];
		if ($allowedPrefixes) {
			foreach ($allowedPrefixes as $allowedPrefix) {
				if ($params['prefix'] === $allowedPrefix || strpos($params['prefix'], $allowedPrefix . '/') === 0) {
					return ['*'];
				}
			}
		}

		return $result;
	}

	/**
	 * @param string|null $path
	 * @return array
	 */
	protected function _getAllow($path = null) {
		if ($this->getConfig('autoClearCache') && Configure::read('debug')) {
			Cache::clear(Cache::KEY_ALLOW);
		}
		$auth = Cache::read(Cache::KEY_ALLOW);
		if ($auth !== null) {
			return $auth;
		}

		if ($path === null) {
			$path = $this->getConfig('allowFilePath');
		}

		$config = $this->getConfig();
		$config['filePath'] = $path;
		$config['file'] = $config['allowFile'];
		unset($config['allowFilePath']);
		unset($config['allowFile']);

		$auth = $this->_loadAllowAdapter($config['allowAdapter'])->getAllow($config);

		Cache::write(Cache::KEY_ALLOW, $auth);

		return $auth;
	}

	/**
	 * Finds the authentication adapter to use for this request.
	 *
	 * @param string $adapter Acl adapter to load.
	 * @throws \Cake\Core\Exception\Exception
	 * @throws \InvalidArgumentException
	 * @return \TinyAuth\Auth\AllowAdapter\AllowAdapterInterface
	 */
	protected function _loadAllowAdapter($adapter) {
		if ($this->_allowAdapter !== null) {
			return $this->_allowAdapter;
		}

		if (!class_exists($adapter)) {
			throw new Exception(sprintf('The Acl Adapter class "%s" was not found.', $adapter));
		}

		$adapterInstance = new $adapter();
		if (!($adapterInstance instanceof AllowAdapterInterface)) {
			throw new InvalidArgumentException(sprintf(
				'TinyAuth Acl adapters have to implement %s.',
				AllowAdapterInterface::class,
			));
		}
		$this->_allowAdapter = $adapterInstance;

		return $adapterInstance;
	}

}
