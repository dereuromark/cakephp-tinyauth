<?php

namespace TinyAuth\Test\TestCase\Utility;

use Cake\Core\Configure;
use Cake\TestSuite\TestCase;
use TinyAuth\Utility\Config;

class ConfigTest extends TestCase {

	/**
	 * @return void
	 */
	public function testConfigGet() {
		Config::drop();
		Configure::delete('TinyAuth');

		$content = [
			'root' => [
				'x' => 'y',
			],
		];
		Configure::write('TinyAuth', $content);

		$result = Config::get('root');
		$this->assertSame($content['root'], $result);
	}

	/**
	 * @return void
	 */
	public function testConfigAll() {
		Config::drop();
		Configure::delete('TinyAuth');

		$content = [
			'root' => [
				'x' => 'y',
			],
		];
		Configure::write('TinyAuth', $content);

		$result = Config::all();
		$this->assertSame($content['root'], $result['root']);
	}

}
