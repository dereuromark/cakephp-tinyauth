<?php

namespace TinyAuth\Test\TestCase\Command;

use Cake\Command\Command;
use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\Core\Configure;
use Cake\TestSuite\TestCase;

class TinyAuthSyncCommandTest extends TestCase {

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
		//$this->useCommandRunner();
	}

	/**
	 * @return void
	 */
	public function testSync() {
		Configure::write('TinyAuth.aclFilePath', TESTS . 'test_files/subfolder/');
		Configure::write('TinyAuth.allowFilePath', TESTS . 'test_files/');

		//$folder = new Folder();
		//$folder->copy('/tmp' . DS . 'src' . DS . 'Controller' . DS, ['from' => TESTS . 'test_app' . DS . 'Controller' . DS]);

		$this->exec('tiny_auth_sync foo,bar -d -v');

		$this->assertExitCode(Command::CODE_SUCCESS);
		$this->assertOutputContains('index = admin');
		$this->assertOutputContains('[Offers]');
		$this->assertOutputContains('* = foo,bar');
	}

}
