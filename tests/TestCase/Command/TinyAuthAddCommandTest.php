<?php

namespace TestCase\Command;

use Cake\Command\Command;
use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\Core\Configure;
use Cake\TestSuite\TestCase;
use TinyAuth\Filesystem\Folder;

class TinyAuthAddCommandTest extends TestCase {

	use ConsoleIntegrationTestTrait;

	/**
	 * setup method
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		Configure::write('Roles', [
			'user' => 1,
			'moderator' => 2,
			'admin' => 3,
		]);

		$this->setAppNamespace();
	}

	/**
	 * @return void
	 */
	public function testAdd() {
		Configure::write('TinyAuth.aclFilePath', TESTS . 'test_files/subfolder/');
		Configure::write('TinyAuth.allowFilePath', TESTS . 'test_files/');

		$folder = new Folder();
		$folder->copy('/tmp' . DS . 'src' . DS . 'Controller' . DS, ['from' => TESTS . 'test_app' . DS . 'Controller' . DS]);

		$this->exec('tiny_auth_add Some action foo,bar -d -v');

		$this->assertExitCode(Command::CODE_SUCCESS);
		$this->assertOutputContains('[Some]');
		$this->assertOutputContains('action = foo, bar');
	}

}
