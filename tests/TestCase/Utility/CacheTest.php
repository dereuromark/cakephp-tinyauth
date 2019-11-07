<?php

namespace TinyAuth\Test\TestCase\Utility;

use Cake\Core\Configure;
use Cake\TestSuite\TestCase;
use TinyAuth\Utility\Cache;

class CacheTest extends TestCase {

	/**
	 * Tests exception thrown when Cache is unavailable.
	 *
	 * @expectedException \Cake\Core\Exception\Exception
	 * @return void
	 */
	public function testCacheInvalid() {
		Configure::write('TinyAuth.cache', 'foo');

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

}
