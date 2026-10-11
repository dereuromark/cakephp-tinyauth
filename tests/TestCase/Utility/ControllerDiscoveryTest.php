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

	/**
	 * @return void
	 */
	public function testNestedPrefixesKeepTheirParentScope(): void {
		$folder = TMP . 'nested_controller_discovery' . DS;
		$nested = $folder . 'Admin' . DS . 'Sub' . DS;
		mkdir($nested, 0777, true);
		file_put_contents($nested . 'FooController.php', '<?php');
		file_put_contents($nested . 'PagesController.php', '<?php');

		try {
			foreach ([new Adder(), new Syncer()] as $scanner) {
				$rules = new ReflectionProperty($scanner, 'authAllow');
				$rules->setValue($scanner, ['Admin/Sub/Pages' => '*', 'Blog.Admin/Sub/Pages' => '*']);
				$method = new ReflectionMethod($scanner, '_parseControllers');
				$this->assertSame(['Admin/Sub/Foo'], $method->invoke($scanner, $folder, null));
				$this->assertSame(['Blog.Admin/Sub/Foo'], $method->invoke($scanner, $folder, 'Blog'));
			}
		} finally {
			unlink($nested . 'FooController.php');
			unlink($nested . 'PagesController.php');
			rmdir($nested);
			rmdir($folder . 'Admin');
			rmdir($folder);
		}
	}

}
