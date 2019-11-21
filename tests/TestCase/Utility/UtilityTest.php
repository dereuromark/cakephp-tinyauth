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
		$key = 'Api/Foo/Bar/MyController';
		$result = Utility::deconstructIniKey($key);

		$expected = [
			'plugin' => null,
			'prefix' => 'Api/Foo/Bar',
			'controller' => 'MyController',
		];
		$this->assertEquals($expected, $result);

		$key = 'My/Foo/Bar.Admin/MyController';
		$result = Utility::deconstructIniKey($key);

		$expected = [
			'plugin' => 'My/Foo/Bar',
			'prefix' => 'Admin',
			'controller' => 'MyController',
		];
		$this->assertEquals($expected, $result);
	}

	/**
	 * @return void
	 */
	public function testBuildIniString() {
		$array = [
			'root-one' => [
				'k1' => 'v1',
				'k2' => 'v2',
			],
		];
		$result = Utility::buildIniString($array);

		$expected = <<<TXT
[root-one]
k1 = v1
k2 = v2

TXT;
		$this->assertTextEquals($expected, $result);
	}

}
