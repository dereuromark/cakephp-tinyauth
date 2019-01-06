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

}
