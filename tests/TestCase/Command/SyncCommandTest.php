<?php

namespace TinyAuth\Test\TestCase\Command;

use Cake\Command\Command;
use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\Core\Configure;
use Cake\TestSuite\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class SyncCommandTest extends TestCase {

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
		$this->loadPlugins(['TinyAuth']);
	}

	/**
	 * @return void
	 */
	public function testSync() {
		Configure::write('TinyAuth.aclFilePath', TESTS . 'test_files/subfolder/');
		Configure::write('TinyAuth.allowFilePath', TESTS . 'test_files/');

		$this->copyDirectory(TESTS . 'test_app' . DS . 'Controller' . DS, '/tmp' . DS . 'src' . DS . 'Controller' . DS);

		$this->exec('tiny_auth sync foo,bar -d -v');

		$this->assertExitCode(Command::CODE_SUCCESS);
		$this->assertOutputContains('index = admin');
		$this->assertOutputContains('[Offers]');
		$this->assertOutputContains('* = foo, bar');
	}

	/**
	 * Hidden directories (e.g. `.git`, `.svn`, `.DS_Store`) must not be descended into
	 * during controller discovery — that was the behavior of the legacy `Folder::read`
	 * we replaced, and the scan would otherwise spend time recursing through editor /
	 * VCS metadata and possibly choke on weird filenames.
	 *
	 * @return void
	 */
	public function testSyncSkipsHiddenDirectories() {
		Configure::write('TinyAuth.aclFilePath', TESTS . 'test_files/subfolder/');
		Configure::write('TinyAuth.allowFilePath', TESTS . 'test_files/');

		$target = '/tmp' . DS . 'src' . DS . 'Controller' . DS;
		$this->copyDirectory(TESTS . 'test_app' . DS . 'Controller' . DS, $target);

		$hiddenDir = $target . '.svn';
		if (!is_dir($hiddenDir)) {
			mkdir($hiddenDir, 0777, true);
		}
		file_put_contents($hiddenDir . DS . 'SecretController.php', "<?php\nclass SecretController {}\n");

		$this->exec('tiny_auth sync foo,bar -d -v');

		$this->assertExitCode(Command::CODE_SUCCESS);
		$this->assertOutputNotContains('Secret');
	}

	/**
	 * Recursively copy $source into $target, creating directories as needed.
	 *
	 * Drop-in replacement for the previously-used `Folder::copy()` from the now-removed
	 * vendored legacy CakePHP filesystem package. Test-only helper.
	 *
	 * @param string $source
	 * @param string $target
	 * @return void
	 */
	protected function copyDirectory(string $source, string $target): void {
		if (!is_dir($target)) {
			mkdir($target, 0777, true);
		}

		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
			RecursiveIteratorIterator::SELF_FIRST,
		);
		foreach ($iterator as $item) {
			$dest = $target . DS . $iterator->getSubPathName();
			if ($item->isDir()) {
				if (!is_dir($dest)) {
					mkdir($dest, 0777, true);
				}
			} else {
				copy($item->getPathname(), $dest);
			}
		}
	}

}
