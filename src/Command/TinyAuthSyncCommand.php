<?php

namespace TinyAuth\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Core\Configure;
use TinyAuth\Sync\Syncer;
use TinyAuth\Utility\TinyAuth;

/**
 * Command to synchronize all controllers with ACL configuration.
 *
 * This command scans your application for all controllers and ensures they
 * have entries in the ACL INI file (default: config/auth_acl.ini).
 *
 * Usage examples:
 * - `bin/cake tiny_auth_sync user,admin` - Add all controllers with access for user and admin roles
 * - `bin/cake tiny_auth_sync "*" -p all` - Add all controllers (including plugins) with access for all roles
 * - `bin/cake tiny_auth_sync user -d` - Dry run, shows what would be added without modifying files
 *
 * @see config/auth_acl.ini - The file that gets modified by this command
 */
class TinyAuthSyncCommand extends Command {

	/**
	 * Execute the command - syncs all discovered controllers to the ACL file.
	 *
	 * Files modified:
	 * - config/auth_acl.ini (or custom path via TinyAuth.aclFilePath config)
	 *
	 * The command will:
	 * 1. Scan for all controllers in the application (and plugins if specified)
	 * 2. Check which controllers don't have ACL entries yet
	 * 3. Add missing controllers with wildcard action (*) and specified roles
	 * 4. Write the updated configuration back to the INI file
	 *
	 * Note: Existing entries are never modified, only new controllers are added.
	 *
	 * @param \Cake\Console\Arguments $args The command arguments.
	 * @param \Cake\Console\ConsoleIo $io The console io
	 * @return int
	 */
	public function execute(Arguments $args, ConsoleIo $io) {
		$syncer = $this->_getSyncer();
		$syncer->syncAcl($args, $io);

		$path = Configure::read('TinyAuth.aclFilePath', ROOT . DS . 'config' . DS);
		$file = Configure::read('TinyAuth.aclFile', 'auth_acl.ini');
		$io->success('Controllers synced to: ' . $path . $file);

		return static::CODE_SUCCESS;
	}

	/**
	 * @return \TinyAuth\Sync\Syncer
	 */
	protected function _getSyncer() {
		return new Syncer();
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
			'Scan all controllers and add missing ones to the ACL configuration.' . PHP_EOL .
			PHP_EOL .
			'This command modifies: config/auth_acl.ini (or custom path via TinyAuth.aclFilePath)' . PHP_EOL .
			PHP_EOL .
			'The command will:' . PHP_EOL .
			'  1. Scan src/Controller/ for all controllers' . PHP_EOL .
			'  2. Add any missing controllers with wildcard (*) access for specified roles' . PHP_EOL .
			'  3. Preserve existing entries (never overwrites)' . PHP_EOL .
			PHP_EOL .
			'Examples:' . PHP_EOL .
			'  bin/cake tiny_auth_sync user,admin' . PHP_EOL .
			'    → Adds all missing controllers with: * = user, admin' . PHP_EOL .
			PHP_EOL .
			'  bin/cake tiny_auth_sync "*" -p all' . PHP_EOL .
			'    → Adds all missing controllers (including plugins) with: * = *' . PHP_EOL .
			PHP_EOL .
			'  bin/cake tiny_auth_sync admin -d' . PHP_EOL .
			'    → Dry run - shows what would be added without modifying files',
		)->addArgument('roles', [
			'help' => 'Role names, comma separated, e.g. `user,admin`.' . ($roles ? PHP_EOL . 'Available roles: ' . implode(', ', $roles) . '.' : ''),
			'required' => true,
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
