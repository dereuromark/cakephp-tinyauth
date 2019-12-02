<?php

namespace TinyAuth\Test\Auth\AclAdapter;

use Cake\Core\Plugin;
use Cake\TestSuite\TestCase;
use TinyAuth\Auth\AclAdapter\IniAclAdapter;

class IniAclAdapterTest extends TestCase {

	/**
	 * @var \TinyAuth\Auth\AclAdapter\IniAclAdapter
	 */
	public $adapter;

	/**
	 * @return void
	 */
	public function setUp() {
		parent::setUp();

		$this->adapter = new IniAclAdapter();
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
			'file' => 'auth_acl.ini',
		];
		$result = $this->adapter->getAcl($availableRoles, $config);

		$this->assertCount(15, $result);
	}

}
