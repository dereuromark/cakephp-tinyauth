<?php

namespace TinyAuth\Test\TestCase\Auth\AclAdapter;

use Cake\Core\Exception\CakeException;
use Cake\Core\Plugin;
use Cake\TestSuite\TestCase;
use TinyAuth\Auth\AclAdapter\PhpAclAdapter;

class PhpAclAdapterTest extends TestCase {

	/**
	 * @var \TinyAuth\Auth\AclAdapter\PhpAclAdapter
	 */
	public $adapter;

	/**
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$this->adapter = new PhpAclAdapter();
	}

	/**
	 * @return void
	 */
	public function testGetAcl() {
		$availableRoles = [
			'user' => 1,
			'moderator' => 2,
			'admin' => 3,
		];
		$config = [
			'filePath' => Plugin::path('TinyAuth') . 'tests' . DS . 'test_files' . DS,
			'file' => 'auth_acl.php',
		];
		$result = $this->adapter->getAcl($availableRoles, $config);

		$this->assertCount(15, $result);
	}

	/**
	 * @return void
	 */
	public function testMissingFileThrows() {
		$config = [
			'filePath' => Plugin::path('TinyAuth') . 'tests' . DS . 'test_files' . DS,
			'file' => 'does_not_exist.php',
		];

		$this->expectException(CakeException::class);
		$this->expectExceptionMessageMatches('/Missing TinyAuth config file/');

		$this->adapter->getAcl([], $config);
	}

}
