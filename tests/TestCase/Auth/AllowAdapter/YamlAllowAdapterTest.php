<?php

namespace TinyAuth\Test\TestCase\Auth\AllowAdapter;

use Cake\Core\Exception\CakeException;
use Cake\Core\Plugin;
use Cake\TestSuite\TestCase;
use TinyAuth\Auth\AllowAdapter\YamlAllowAdapter;

class YamlAllowAdapterTest extends TestCase {

	/**
	 * @var \TinyAuth\Auth\AllowAdapter\YamlAllowAdapter
	 */
	public $adapter;

	/**
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$this->adapter = new YamlAllowAdapter();
	}

	/**
	 * @return void
	 */
	public function testGetAllow() {
		$config = [
			'filePath' => Plugin::path('TinyAuth') . 'tests' . DS . 'test_files' . DS,
			'file' => 'auth_allow.yml',
		];
		$result = $this->adapter->getAllow($config);

		$this->assertCount(4, $result);
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

		$this->adapter->getAllow($config);
	}

}
