<?php
namespace TestApp\Controller\Admin\MyPrefix;

use Cake\Controller\Controller;
use Exception;

class MyTestController extends Controller {

	/**
	 * Unauthenticated action.
	 *
	 * @return void
	 */
	public function myPublic() {
	}

	/**
	 * Denied via ! in ACL file.
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function myDenied() {
		throw new Exception('Should not be reached!');
	}

	/**
	 * Only for mod role.
	 *
	 * @return void
	 */
	public function myModerator() {
	}

	/**
	 * For every logged in user.
	 *
	 * @return void
	 */
	public function myAll() {
	}

}
