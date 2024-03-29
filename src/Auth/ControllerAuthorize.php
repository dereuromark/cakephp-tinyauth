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
 * @since 2.0.0
 * @license https://opensource.org/licenses/mit-license.php MIT License
 */

namespace TinyAuth\Auth;

use Cake\Controller\ComponentRegistry;
use Cake\Controller\Controller;
use Cake\Core\Exception\CakeException;
use Cake\Http\ServerRequest;

/**
 * An authorization adapter for AuthComponent. Provides the ability to authorize
 * using a controller callback. Your controller's isAuthorized() method should
 * return a boolean to indicate whether the user is authorized.
 *
 * ```
 *  public function isAuthorized($user)
 *  {
 *      if ($this->request->getParam('admin')) {
 *          return $user['role'] === 'admin';
 *      }
 *      return !empty($user);
 *  }
 * ```
 *
 * The above is simple implementation that would only authorize users of the
 * 'admin' role to access admin routing.
 *
 * @see \Cake\Controller\Component\AuthComponent::$authenticate
 */
class ControllerAuthorize extends BaseAuthorize {

	/**
	 * Controller for the request.
	 *
	 * @var \Cake\Controller\Controller
	 */
	protected $_Controller;

	/**
	 * @inheritDoc
	 */
	public function __construct(ComponentRegistry $registry, array $config = []) {
		parent::__construct($registry, $config);
		$this->controller($registry->getController());
	}

	/**
	 * Get/set the controller this authorize object will be working with. Also
	 * checks that isAuthorized is implemented.
	 *
	 * @param \Cake\Controller\Controller|null $controller null to get, a controller to set.
	 * @return \Cake\Controller\Controller
	 */
	public function controller(?Controller $controller = null): Controller {
		if ($controller) {
			$this->_Controller = $controller;
		}

		return $this->_Controller;
	}

	/**
	 * Checks user authorization using a controller callback.
	 *
	 * @param \ArrayAccess|array $user Active user data
	 * @param \Cake\Http\ServerRequest $request Request instance.
	 * @throws \Cake\Core\Exception\CakeException If controller does not have method `isAuthorized()`.
	 * @return bool
	 */
	public function authorize($user, ServerRequest $request): bool {
		if (!method_exists($this->_Controller, 'isAuthorized')) {
			throw new CakeException(sprintf(
				'%s does not implement an isAuthorized() method.',
				get_class($this->_Controller),
			));
		}

		return (bool)$this->_Controller->isAuthorized($user);
	}

}
