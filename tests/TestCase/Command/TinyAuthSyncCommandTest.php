<?php
namespace TinyAuth\Test\TestCase\Command;

use Cake\Console\Shell;
use Cake\TestSuite\ConsoleIntegrationTestCase;

class TinyAuthSyncCommandTest extends ConsoleIntegrationTestCase
{
    /**
     * setup method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->setAppNamespace();
        $this->useCommandRunner();
        //Plugin::load('TestPlugin');
    }

    /**
     * Test the command listing
     *
     * @return void
     */
    public function testMain()
    {
        $this->exec('tiny_auth_sync');
        $this->assertExitCode(Shell::CODE_SUCCESS);
        $this->assertCommandList();
    }

    /**
     * Assert the help output.
     *
     * @return void
     */
    protected function assertCommandList()
    {
        $this->assertOutputContains('- sample', 'app shell');
    }
}
