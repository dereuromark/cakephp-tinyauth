<?php

namespace TinyAuth\Utility;

use ArrayAccess;
use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\Core\Exception\CakeException;
use Cake\Core\StaticConfigTrait;

/**
 * TinyAuth cache wrapper around session cache engine(s).
 */
class SessionCache {

	use StaticConfigTrait;

	protected static array $_defaultConfig = [
		'cache' => 'default',
		'prefix' => 'auth_user_',
	];

	/**
	 * Clears all session user info based on prefix
	 *
	 * @return void
	 */
	public static function clear(): void {
		$config = static::prepareConfig();
		static::assertValidCacheSetup($config);

		if (!empty($config['groups'])) {
			foreach ((array)$config['groups'] as $group) {
				Cache::clearGroup($group, $config['cache']);
			}

			return;
		}

		Cache::clear($config['cache']);
	}

	/**
	 * @param string|int $userId
	 * @param \ArrayAccess|array $data
	 *
	 * @return void
	 */
	public static function write(int|string $userId, ArrayAccess|array $data): void {
		$config = static::prepareConfig();

		Cache::write(static::key($userId), $data, $config['cache']);
	}

	/**
	 * @param string|int $userId
	 *
	 * @return \ArrayAccess|array|null
	 */
	public static function read(int|string $userId): ArrayAccess|array|null {
		$config = static::prepareConfig();

		return Cache::read(static::key($userId), $config['cache']) ?: null;
	}

	/**
	 * @param string|int $userId
	 *
	 * @return bool
	 */
	public static function delete(int|string $userId): bool {
		$config = static::prepareConfig();

		return Cache::delete(static::key($userId), $config['cache']);
	}

	/**
	 * @param string|int $userId
	 * @return string
	 */
	public static function key(int|string $userId): string {
		$config = static::prepareConfig();

		static::assertValidCacheSetup($config);

		return $config['prefix'] . $userId;
	}

	/**
	 * @param array<string, mixed> $config
	 * @throws \Cake\Core\Exception\CakeException
	 * @return void
	 */
	protected static function assertValidCacheSetup(array $config): void {
		if (!in_array($config['cache'], Cache::configured(), true)) {
			throw new CakeException(sprintf('Invalid or not configured TinyAuth cache `%s`', $config['cache']));
		}
	}

	/**
	 * @return array<string, mixed>
	 */
	protected static function prepareConfig(): array {
		$defaultConfig = static::$_defaultConfig;

		return (array)Configure::read('TinyAuth') + $defaultConfig;
	}

}
