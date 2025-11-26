<?php

namespace TinyAuth\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Core\Configure;
use TinyAuth\Sync\Adder;
use TinyAuth\Utility\TinyAuth;

/**
 * Command to add specific controller/action entries to ACL configuration.
 *
 * This command modifies the ACL INI file (default: config/auth_acl.ini) by adding
 * or updating specific controller/action permissions for given roles.
 *
 * Usage examples:
 * - `bin/cake tiny_auth add Articles index user,admin` - Allow users and admins to access Articles::index
 * - `bin/cake tiny_auth add Articles` - Interactive mode, prompts for action and roles
 * - `bin/cake tiny_auth add Articles "*" "*"` - Allow all roles to access all Articles actions
 *
 * @see config/auth_acl.ini - The file that gets modified by this command
 */
class AddCommand extends Command {

	/**
	 * @inheritDoc
	 */
	public static function getDescription(): string {
		return 'Add or update specific controller/action permissions in the ACL configuration.';
	}

	/**
	 * Execute the command - adds a specific controller/action/roles entry to the ACL file.
	 *
	 * Files modified:
	 * - config/auth_acl.ini (or custom path via TinyAuth.aclFilePath config)
	 *
	 * The command will:
	 * 1. Read the existing ACL configuration
	 * 2. Add or update the specified controller/action with the given roles
	 * 3. Write the updated configuration back to the INI file
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

		$path = Configure::read('TinyAuth.aclFilePath', ROOT . DS . 'config' . DS);
		$file = Configure::read('TinyAuth.aclFile', 'auth_acl.ini');
		$io->success('ACL entry added/updated in: ' . $path . $file);

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
			static::getDescription() . PHP_EOL
			. PHP_EOL
			. 'This command modifies: config/auth_acl.ini (or custom path via TinyAuth.aclFilePath)' . PHP_EOL
			. PHP_EOL
			. 'Examples:' . PHP_EOL
			. '  bin/cake tiny_auth add Articles index user,admin' . PHP_EOL
			. '    → Adds: [Articles] index = user, admin' . PHP_EOL
			. PHP_EOL
			. '  bin/cake tiny_auth add Articles "*" admin' . PHP_EOL
			. '    → Adds: [Articles] * = admin' . PHP_EOL
			. PHP_EOL
			. '  bin/cake tiny_auth add MyPlugin.Admin/Articles edit admin' . PHP_EOL
			. '    → Adds: [MyPlugin.Admin/Articles] edit = admin',
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
