<?php

namespace TinyAuth\Command;

use Cake\Console\Arguments;
use Cake\Console\Command;
use Cake\Console\CommandCollection;
use Cake\Console\CommandCollectionAwareInterface;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use TinyAuth\Sync\Syncer;

/**
 * Auth and ACL helper
 */
class TinyAuthSyncCommand extends Command implements CommandCollectionAwareInterface {

	/**
	 * The command collection to get help on.
	 *
	 * @var \Cake\Console\CommandCollection
	 */
	protected $commands;

	/**
	 * @return \Cake\Console\ConsoleOptionParser
	 */
	public function getOptionParser() {
		$this->setName('foo bar');

		return parent::getOptionParser();
	}

	public function __construct() {
		parent::__construct();
		$this->setName('foo bar');
	}

	/**
	 * @param \Cake\Console\CommandCollection $commands The commands to use.
	 * @return void
	 */
	public function setCommandCollection(CommandCollection $commands) {
		$this->commands = $commands;
	}

	/**
	 * Main function Prints out the list of shells.
	 *
	 * @param \Cake\Console\Arguments $args The command arguments.
	 * @param \Cake\Console\ConsoleIo $io The console io
	 * @return int
	 */
	public function execute(Arguments $args, ConsoleIo $io) {
		$syncer = $this->getSyncer();
		$syncer->syncAcl($args, $io);
		$io->out('Controllers and ACL synced.');

		return static::CODE_SUCCESS;
	}

	/**
	 * @return \TinyAuth\Sync\Syncer
	 */
	protected function getSyncer() {
		return new Syncer();
	}

	/**
	 * Gets the option parser instance and configures it.
	 *
	 * @param \Cake\Console\ConsoleOptionParser $parser The parser to build
	 * @return \Cake\Console\ConsoleOptionParser
	 */
	protected function buildOptionParser(ConsoleOptionParser $parser) {
		$parser->setDescription(
			'Get the list of '
		)->addArgument('roles', [
			'help' => 'String of comma separated roles',
			'required' => true,
		])->addOption('plugin', [
			'short' => 'p',
			'help' => 'Plugin',
			'default' => null,
		])->addOption('dry-run', [
			'short' => 'd',
			'help' => 'Dry Run (only output, do not modify ini files)',
			'boolean' => true,
		]);

		return $parser;
	}

}
