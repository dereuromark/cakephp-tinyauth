<?php

namespace TinyAuth\Panel;

use Cake\Core\InstanceConfigTrait;
use Cake\Event\EventInterface;
use DebugKit\DebugPanel;
use TinyAuth\Auth\AclTrait;
use TinyAuth\Auth\AllowTrait;
use TinyAuth\Utility\Config;
use TinyAuth\Utility\TinyAuth;

/**
 * A panel to show authentication and authorization data for current request.
 */
class AuthPanel extends DebugPanel {

	use AclTrait;
	use AllowTrait;
	use InstanceConfigTrait;

	/**
	 * @var string
	 */
	public const ICON_RESTRICTED = "\u{1f512}";

	/**
	 * @var string
	 */
	public const ICON_PUBLIC = "\u{1f513}";

	/**
	 * Defines which plugin this panel is from so the element can be located.
	 *
	 * @var string
	 */
	public string $plugin = 'TinyAuth';

	/**
	 * @var bool|null
	 */
	protected $isPublic;

	/**
	 * @var array
	 */
	protected array $_defaultConfig = [
	];

	public function __construct() {
		$this->setConfig(Config::all());
	}

	/**
	 * Data collection callback.
	 *
	 * @param \Cake\Event\EventInterface $event The shutdown event.
	 *
	 * @return void
	 */
	public function shutdown(EventInterface $event): void {
		/** @var \Cake\Controller\Controller $controller */
		$controller = $event->getSubject();
		$request = $controller->getRequest();

		$params = $this->_getParams($request->getAttribute('params'));
		$availableRoles = (new TinyAuth())->getAvailableRoles();
		$data = [
			'params' => $params,
			'path' => $this->_getPath($params),
			'availableRoles' => $availableRoles,
		];

		$rule = $this->_getAllowRule($params);
		$this->isPublic = $this->_isActionAllowed($rule, $params['action']);

		if (!$controller->components()->has('AuthUser')) {
			$controller->loadComponent('TinyAuth.AuthUser');
		}

		/** @var \TinyAuth\Controller\Component\AuthUserComponent $authUserComponent */
		$authUserComponent = $controller->components()->get('AuthUser');
		$user = $authUserComponent->user();
		$data['user'] = $user;

		$roles = $authUserComponent->roles();
		$data['roles'] = $roles;

		$access = [];
		foreach ($availableRoles as $role => $id) {
			if ($user) {
				$tmpUser = $this->_injectRole($user, $role, $id);
			} else {
				$tmpUser = $this->_generateUser($role, $id);
			}
			$access[$role] = $this->_checkUser($tmpUser, $params);
		}
		$data['access'] = $access;

		$this->_data = $data;
	}

	/**
	 * Get the data for this panel
	 *
	 * @return array
	 */
	public function data(): array {
		$data = [
			'isPublic' => $this->isPublic,
		];

		return $this->_data + $data;
	}

	/**
	 * Get the summary data for a panel.
	 *
	 * This data is displayed in the toolbar even when the panel is collapsed.
	 *
	 * @return string
	 */
	public function summary(): string {
		if ($this->isPublic === null) {
			return '';
		}

		return $this->isPublic ? static::ICON_PUBLIC : static::ICON_RESTRICTED; // For now no HTML possible.
	}

	/**
	 * @param array $user
	 * @param string $role
	 * @param string|int $id
	 *
	 * @return array
	 */
	protected function _injectRole(array $user, $role, $id) {
		if (!$this->getConfig('multiRole')) {
			$user[$this->getConfig('roleColumn')] = $id;

			return $user;
		}

		if (isset($user[$this->getConfig('rolesTable')])) {
			$user[$this->getConfig('rolesTable')] = [$role => $id];

			return $user;
		}

		$pivotTableName = $this->_pivotTableName();
		if (isset($user[$pivotTableName])) {
			$user[$pivotTableName] = [$role => $id];

			return $user;
		}

		//TODO: other edge cases?

		return $user;
	}

	/**
	 * @param string $role
	 * @param string|int $id
	 *
	 * @return array
	 */
	protected function _generateUser($role, $id) {
		$user = [
			'id' => 0,
		];
		if (!$this->getConfig('multiRole')) {
			$user[$this->getConfig('roleColumn')] = $id;

			return $user;
		}

		$user[$this->getConfig('rolesTable')] = [$role => $id];

		return $user;
	}

	/**
	 * @param array $params
	 *
	 * @return array
	 */
	protected function _getParams(array $params) {
		$params += [
			'prefix' => null,
			'plugin' => null,
		];
		unset($params['isAjax']);
		unset($params['_csrfToken']);
		unset($params['_Token']);

		return $params;
	}

	/**
	 * @param array $params
	 *
	 * @return string
	 */
	protected function _getPath(array $params) {
		$path = $params['controller'];
		if ($params['prefix']) {
			$path = $params['prefix'] . '/' . $path;
		}
		if ($params['plugin']) {
			$path = $params['plugin'] . '.' . $path;
		}

		return $path;
	}

}
