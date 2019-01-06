<?php
namespace TinyAuth\Test\TestCase\Command;

use Cake\Console\Shell;
use Cake\Core\Configure;
use Cake\Filesystem\Folder;
use Cake\TestSuite\ConsoleIntegrationTestCase;

class TinyAuthSyncCommandTest extends ConsoleIntegrationTestCase {

	/**
	 * setup method
	 *
	 * @return void
	 */
	public function setUp() {
		parent::setUp();

		$this->skipIf(version_compare(Configure::version(), '3.6.0', '<'), 'Commands added in CakePHP 3.6+');

		$this->setAppNamespace();
		$this->useCommandRunner();
	}

	/**
	 * @return void
	 */
	public function testSync() {
		Configure::write('TinyAuth.file', '../tests/test_files/subfolder/tinyauth_acl.ini');

		$folder = new Folder();
		$folder->copy(['from' => TESTS . 'test_app' . DS . 'Controller' . DS, 'to' => '/tmp' . DS . 'src' . DS . 'Controller' . DS]);

		$this->exec('tiny_auth_sync foo,bar -d -v');

		$this->assertExitCode(Shell::CODE_SUCCESS);
		$this->assertOutputContains('index = admin');
		$this->assertOutputContains('[Offers]');
		$this->assertOutputContains('* = foo,bar');
	}

}
