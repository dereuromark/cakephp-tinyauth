<?php

namespace TinyAuth\Test\TestCase\Auth\AclAdapter;

use Cake\Core\Exception\CakeException;
use Cake\Core\Plugin;
use Cake\TestSuite\TestCase;
use TinyAuth\Auth\AclAdapter\YamlAclAdapter;

class YamlAclAdapterTest extends TestCase {

	/**
	 * @var \TinyAuth\Auth\AclAdapter\YamlAclAdapter
	 */
	public $adapter;

	/**
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$this->adapter = new YamlAclAdapter();
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
			'file' => 'auth_acl.yml',
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
			'file' => 'does_not_exist.yml',
		];

		$this->expectException(CakeException::class);
		$this->expectExceptionMessageMatches('/Missing TinyAuth config file/');

		$this->adapter->getAcl([], $config);
	}

}
