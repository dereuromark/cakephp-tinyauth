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
	 * @param array $params
	 * @return array
	 */
	protected function _getAllowRule(array $params) {
		$rules = $this->_getAllow($this->getConfig('allowFilePath'));

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

			return $rule;
		}

		return [];
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
	 * @return \TinyAuth\Auth\AllowAdapter\AllowAdapterInterface
	 * @throws \Cake\Core\Exception\Exception
	 * @throws \InvalidArgumentException
	 */
	protected function _loadAllowAdapter($adapter) {
		if (!class_exists($adapter)) {
			throw new Exception(sprintf('The Acl Adapter class "%s" was not found.', $adapter));
		}

		$adapterInstance = new $adapter();
		if (!($adapterInstance instanceof AllowAdapterInterface)) {
			throw new InvalidArgumentException(sprintf(
				'TinyAuth Acl adapters have to implement %s.', AllowAdapterInterface::class
			));
		}

		return $adapterInstance;
	}

}
