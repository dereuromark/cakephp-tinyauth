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
	protected static array $_defaultConfig = [
		'cache' => '_cake_model_',
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
	public static function clear(?string $type = null): void {
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
	public static function write(string $type, array $data): void {
		$config = static::prepareConfig();

		CoreCache::write(static::key($type), $data, $config['cache']);
	}

	/**
	 * @param string $type
	 *
	 * @return array|null
	 */
	public static function read(string $type): ?array {
		$config = static::prepareConfig();

		return CoreCache::read(static::key($type), $config['cache']) ?: null;
	}

	/**
	 * Resolve the cache key for a given type (acl/allow), prepending the configured prefix.
	 *
	 * Two TinyAuth installs that share a Cake cache pool would previously collide on the
	 * bare `acl` / `allow` keys because the `cachePrefix` option was declared but never
	 * applied. Multi-tenant or test environments where Cache pools survive across plugin
	 * boots could read another install's permissions data. The prefix is now applied so
	 * the on-disk key is e.g. `tiny_auth_acl` / `tiny_auth_allow` by default.
	 *
	 * @param string $type
	 * @throws \Cake\Core\Exception\CakeException
	 * @return string
	 */
	public static function key(string $type): string {
		$config = static::prepareConfig();

		static::assertValidCacheSetup($config);

		$keyConfigName = $type . 'CacheKey';
		if (empty($config[$keyConfigName])) {
			throw new CakeException(sprintf('Invalid TinyAuth cache key `%s`', $keyConfigName));
		}

		$prefix = isset($config['cachePrefix']) ? (string)$config['cachePrefix'] : '';

		return $prefix . $config[$keyConfigName];
	}

	/**
	 * @param array<string, mixed> $config
	 * @throws \Cake\Core\Exception\CakeException
	 * @return void
	 */
	protected static function assertValidCacheSetup(array $config): void {
		if (!in_array($config['cache'], CoreCache::configured(), true)) {
			throw new CakeException(sprintf('Invalid or not configured TinyAuth cache `%s`', $config['cache']));
		}
	}

	/**
	 * @return array<string, mixed>
	 */
	protected static function prepareConfig(): array {
		$defaultConfig = static::$_defaultConfig;

		//BC with 5.0.x
		$configured = CoreCache::getRegistry();
		if ($configured->has('_cake_core_')) {
			$defaultConfig['cache'] = '_cake_core_';
		}

		return (array)Configure::read('TinyAuth') + $defaultConfig;
	}

}
