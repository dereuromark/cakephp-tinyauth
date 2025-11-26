<?php

namespace TinyAuth;

use Cake\Console\CommandCollection;
use Cake\Core\BasePlugin;
use TinyAuth\Command\AddCommand;
use TinyAuth\Command\SyncCommand;

/**
 * Plugin for TinyAuth
 */
class TinyAuthPlugin extends BasePlugin {

	/**
	 * @var bool
	 */
	protected bool $middlewareEnabled = false;

	/**
	 * @var bool
	 */
	protected bool $bootstrapEnabled = false;

	/**
	 * @var bool
	 */
	protected bool $routesEnabled = false;

	/**
	 * @param \Cake\Console\CommandCollection $commands The command collection to add to.
	 * @return \Cake\Console\CommandCollection
	 */
	public function console(CommandCollection $commands): CommandCollection {
		$commands->add('tiny_auth add', AddCommand::class);
		$commands->add('tiny_auth sync', SyncCommand::class);

		return $commands;
	}

}
