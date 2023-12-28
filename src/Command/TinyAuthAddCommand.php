<?php

namespace TinyAuth\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use TinyAuth\Sync\Adder;
use TinyAuth\Utility\TinyAuth;

/**
 * Auth and ACL helper
 */
class TinyAuthAddCommand extends Command {

	/**
	 * Main function Prints out the list of shells.
	 *
	 * @param \Cake\Console\Arguments $args The command arguments.
	 * @param \Cake\Console\ConsoleIo $io The console io
	 * @return int
	 */
	public function execute(Arguments $args, ConsoleIo $io) {
		$adder = $this->_getAdder();

		$controller = $args->getArgument('controller');
		if ($controller === null) {
			$controllerNames = $adder->controllers($args);
			$io->out('Select a controller:');
			foreach ($controllerNames as $controllerName) {
				$io->out(' - ' . $controllerName);
			}
			while (!$controller || !in_array($controller, $controllerNames, true)) {
				$controller = $io->ask('Controller name');
			}
		}

		$action = $args->getArgument('action') ?: '*';
		$roles = $args->getArgument('roles') ?: '*';
		$roles = array_map('trim', explode(',', $roles));
		$adder->addAcl($controller, $action, $roles, $args, $io);
		$io->out('Controllers and ACL synced.');

		return static::CODE_SUCCESS;
	}

	/**
	 * @return \TinyAuth\Sync\Adder
	 */
	protected function _getAdder() {
		return new Adder();
	}

	/**
	 * Gets the option parser instance and configures it.
	 *
	 * @param \Cake\Console\ConsoleOptionParser $parser The parser to build
	 * @return \Cake\Console\ConsoleOptionParser
	 */
	protected function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser {
		$roles = $this->_getAvailableRoles();

		$parser->setDescription(
			'Get the list of controllers and make sure, they are synced into the ACL file.',
		)->addArgument('controller', [
			'help' => 'Controller name (Plugin.Prefix/Name) without Controller suffix.',
			'required' => false,
		])->addArgument('action', [
			'help' => 'Action name (camelCased or under_scored), defaults to `*` (all).',
			'required' => false,
		])->addArgument('roles', [
			'help' => 'Role names, comma separated, e.g. `user,admin`, defaults to `*` (all).' . ($roles ? PHP_EOL . 'Available roles: ' . implode(', ', $roles) . '.' : ''),
			'required' => false,
		])->addOption('plugin', [
			'short' => 'p',
			'help' => 'Plugin, use `all` to include all loaded plugins.',
			'default' => null,
		])->addOption('dry-run', [
			'short' => 'd',
			'help' => 'Dry Run (only output, do not modify INI files).',
			'boolean' => true,
		]);

		return $parser;
	}

	/**
	 * @return array<string>
	 */
	protected function _getAvailableRoles() {
		$roles = (new TinyAuth())->getAvailableRoles();

		return array_keys($roles);
	}

}
