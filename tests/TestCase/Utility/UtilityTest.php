<?php

namespace TinyAuth\Test\TestCase\Utility;

use Cake\TestSuite\TestCase;
use TinyAuth\Utility\Utility;

class UtilityTest extends TestCase {

	/**
	 * @return void
	 */
	public function testGenerateFile() {
		$content = [
			'Root' => [
				'x' => 'y',
			],
		];

		$file = TMP . 'file.ini';
		$result = Utility::generateFile($file, $content);
		$this->assertTrue($result);
	}

	/**
	 * @return void
	 */
	public function testDeconstructIniKey() {
		$key = 'api/foo/bar/MyController';
		$result = Utility::deconstructIniKey($key);

		$expected = [
			'plugin' => null,
			'prefix' => 'api/foo/bar',
			'controller' => 'MyController',
		];
		$this->assertEquals($expected, $result);

		$key = 'My/Foo/Bar.admin/MyController';
		$result = Utility::deconstructIniKey($key);

		$expected = [
			'plugin' => 'My/Foo/Bar',
			'prefix' => 'admin',
			'controller' => 'MyController',
		];
		$this->assertEquals($expected, $result);
	}

}
