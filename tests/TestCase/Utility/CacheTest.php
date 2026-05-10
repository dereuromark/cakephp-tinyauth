<?php

namespace TinyAuth\Test\TestCase\Utility;

use Cake\Core\Configure;
use Cake\Core\Exception\CakeException;
use Cake\TestSuite\TestCase;
use TinyAuth\Utility\Cache;

class CacheTest extends TestCase {

	/**
	 * Tests exception thrown when Cache is unavailable.
	 *
	 * @return void
	 */
	public function testCacheInvalid() {
		Configure::write('TinyAuth.cache', 'foo');

		$this->expectException(CakeException::class);

		Cache::read(Cache::KEY_ALLOW);
	}

	/**
	 * @return void
	 */
	public function testCache() {
		Cache::clear();

		$result = Cache::read(Cache::KEY_ALLOW);
		$this->assertNull($result);

		$result = Cache::read(Cache::KEY_ACL);
		$this->assertNull($result);
	}

	/**
	 * Two installs sharing a Cache pool must not collide on `acl` / `allow` because
	 * the configured `cachePrefix` is now applied. The default prefix is `tiny_auth_`.
	 *
	 * @return void
	 */
	public function testKeyAppliesConfiguredPrefix() {
		Configure::delete('TinyAuth.cachePrefix');

		$this->assertSame('tiny_auth_acl', Cache::key(Cache::KEY_ACL));
		$this->assertSame('tiny_auth_allow', Cache::key(Cache::KEY_ALLOW));
	}

	/**
	 * @return void
	 */
	public function testKeyCustomPrefix() {
		Configure::write('TinyAuth.cachePrefix', 'tenant_42_');

		$this->assertSame('tenant_42_acl', Cache::key(Cache::KEY_ACL));
		$this->assertSame('tenant_42_allow', Cache::key(Cache::KEY_ALLOW));
	}

	/**
	 * @return void
	 */
	public function testKeyEmptyPrefixYieldsBareKey() {
		Configure::write('TinyAuth.cachePrefix', '');

		$this->assertSame('acl', Cache::key(Cache::KEY_ACL));
		$this->assertSame('allow', Cache::key(Cache::KEY_ALLOW));
	}

}
