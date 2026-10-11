<?php

namespace TinyAuth\Test\TestCase\Utility;

use Cake\TestSuite\TestCase;
use ReflectionMethod;
use ReflectionProperty;
use TinyAuth\Sync\Adder;
use TinyAuth\Sync\Syncer;

class ControllerDiscoveryTest extends TestCase {

	/**
	 * @return void
	 */
	public function testPublicRulesOnlySkipTheirMatchingScope(): void {
		foreach ([new Adder(), new Syncer()] as $scanner) {
			$rules = new ReflectionProperty($scanner, 'authAllow');
			$rules->setValue($scanner, [
				'Pages' => '*',
				'Blog.Pages' => '*',
				'Admin/Posts' => '*',
			]);
			$method = new ReflectionMethod($scanner, '_noAuthenticationNeeded');

			$this->assertTrue($method->invoke($scanner, 'Pages', null, null));
			$this->assertFalse($method->invoke($scanner, 'Pages', null, 'Admin'));
			$this->assertFalse($method->invoke($scanner, 'Pages', 'Other', null));
			$this->assertTrue($method->invoke($scanner, 'Pages', 'Blog', null));
			$this->assertTrue($method->invoke($scanner, 'Posts', null, 'Admin'));
		}
	}

}
