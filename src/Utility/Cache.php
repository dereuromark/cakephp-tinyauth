<?php

namespace TinyAuth\Utility;

use Cake\Cache\Cache as CoreCache;
use Cake\Core\Configure;
use Cake\Core\Exception\CakeException;
use Cake\Core\StaticConfigTrait;

/**
 * TinyAuth cache wrapper around core cache engine(s).
 */
class Cache {

	use StaticConfigTrait;

	/**
	 * @var string
	 */
	public const KEY_ALLOW = 'allow';

	/**
	 * @var string
	 */
	public const KEY_ACL = 'acl';

	/**
	 * @var array
	 */
	protected static $_defaultConfig = [
		'cache' => '_cake_translations_',
		'cachePrefix' => 'tiny_auth_',
		'allowCacheKey' => self::KEY_ALLOW,
		'aclCacheKey' => self::KEY_ACL,
	];

	/**
	 * Clears specific cache or all caches.
	 *
	 * @param string|null $type
	 *
	 * @return void
	 */
	public static function clear($type = null) {
		$config = static::prepareConfig();
		static::assertValidCacheSetup($config);

		if ($type) {
			$key = static::key($type);
			CoreCache::delete($key, $config['cache']);

			return;
		}

		CoreCache::delete(static::key(static::KEY_ALLOW), $config['cache']);
		CoreCache::delete(static::key(static::KEY_ACL), $config['cache']);
	}

	/**
	 * @param string $type
	 * @param array $data
	 *
	 * @return void
	 */
	public static function write($type, $data) {
		$config = static::prepareConfig();

		CoreCache::write(static::key($type), $data, $config['cache']);
	}

	/**
	 * @param string $type
	 *
	 * @return array|null
	 */
	public static function read($type) {
		$config = static::prepareConfig();

		return CoreCache::read(static::key($type), $config['cache']) ?: null;
	}

	/**
	 * @param string $type
	 * @throws \Cake\Core\Exception\CakeException
	 * @return string
	 */
	public static function key($type) {
		$config = static::prepareConfig();

		static::assertValidCacheSetup($config);

		$key = $type . 'CacheKey';
		if (empty($config[$key])) {
			throw new CakeException(sprintf('Invalid TinyAuth cache key `%s`', $key));
		}

		return $config[$key];
	}

	/**
	 * @param array $config
	 * @throws \Cake\Core\Exception\CakeException
	 * @return void
	 */
	protected static function assertValidCacheSetup(array $config) {
		if (!in_array($config['cache'], CoreCache::configured(), true)) {
			throw new CakeException(sprintf('Invalid or not configured TinyAuth cache `%s`', $config['cache']));
		}
	}

	/**
	 * @return array
	 */
	protected static function prepareConfig() {
		$config = (array)Configure::read('TinyAuth') + static::$_defaultConfig;

		return $config;
	}

}
