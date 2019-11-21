<?php
namespace TinyAuth\Test\Auth\AllowAdapter;

use Cake\Core\Plugin;
use Cake\TestSuite\TestCase;
use TinyAuth\Auth\AllowAdapter\IniAllowAdapter;

class IniAllowAdapterTest extends TestCase {

	/**
	 * @var \TinyAuth\Auth\AllowAdapter\IniAllowAdapter
	 */
	public $adapter;

	/**
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$this->adapter = new IniAllowAdapter();
	}

	/**
	 * @return void
	 */
	public function testGetAllow() {
		$config = [
			'filePath' => Plugin::path('TinyAuth') . 'tests' . DS . 'test_files' . DS,
			'file' => 'auth_allow.ini',
		];
		$result = $this->adapter->getAllow($config);

		$this->assertCount(4, $result);
	}

}
