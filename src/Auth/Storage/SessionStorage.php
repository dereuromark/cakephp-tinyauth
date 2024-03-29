<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link https://cakephp.org CakePHP(tm) Project
 * @since 3.1.0
 * @license https://opensource.org/licenses/mit-license.php MIT License
 */

namespace TinyAuth\Auth\Storage;

use Cake\Core\InstanceConfigTrait;
use Cake\Http\Response;
use Cake\Http\ServerRequest;

/**
 * Session based persistent storage for authenticated user record.
 */
class SessionStorage implements StorageInterface {

	use InstanceConfigTrait;

	/**
	 * User record.
	 *
	 * Stores user record array if fetched from session or false if session
	 * does not have user record.
	 *
	 * @var \ArrayAccess|array|false|null
	 */
	protected $_user;

	/**
	 * Session object.
	 *
	 * @var \Cake\Http\Session
	 */
	protected $_session;

	/**
	 * Default configuration for this class.
	 *
	 * Keys:
	 *
	 * - `key` - Session key used to store user record.
	 * - `redirect` - Session key used to store redirect URL.
	 *
	 * @var array<string, mixed>
	 */
	protected $_defaultConfig = [
		'key' => 'Auth.User',
		'redirect' => 'Auth.redirect',
	];

	/**
	 * Constructor.
	 *
	 * @param \Cake\Http\ServerRequest $request Request instance.
	 * @param \Cake\Http\Response $response Response instance.
	 * @param array<string, mixed> $config Configuration list.
	 */
	public function __construct(ServerRequest $request, Response $response, array $config = []) {
		$this->_session = $request->getSession();
		$this->setConfig($config);
	}

	/**
	 * Read user record from session.
	 *
	 * @psalm-suppress InvalidReturnType
	 * @return \ArrayAccess|array|null User record if available else null.
	 */
	public function read() {
		if ($this->_user !== null) {
			return $this->_user ?: null;
		}

		/** @psalm-suppress PossiblyInvalidPropertyAssignmentValue */
		$this->_user = $this->_session->read($this->_config['key']) ?: false;

		/** @psalm-suppress InvalidReturnStatement */
		return $this->_user ?: null;
	}

	/**
	 * Write user record to session.
	 *
	 * The session id is also renewed to help mitigate issues with session replays.
	 *
	 * @param \ArrayAccess|array $user User record.
	 * @return void
	 */
	public function write($user): void {
		$this->_user = $user;

		$this->_session->renew();
		$this->_session->write($this->_config['key'], $user);
	}

	/**
	 * Delete user record from session.
	 *
	 * The session id is also renewed to help mitigate issues with session replays.
	 *
	 * @return void
	 */
	public function delete(): void {
		$this->_user = false;

		$this->_session->delete($this->_config['key']);
		$this->_session->renew();
	}

	/**
	 * @inheritDoc
	 */
	public function redirectUrl($url = null) {
		if ($url === null) {
			return $this->_session->read($this->_config['redirect']);
		}

		if ($url === false) {
			$this->_session->delete($this->_config['redirect']);

			return null;
		}

		$this->_session->write($this->_config['redirect'], $url);

		return null;
	}

}
