<?php

namespace TinyAuth\Auth\AllowAdapter;

use TinyAuth\Utility\Utility;

class IniAllowAdapter implements AllowAdapterInterface {

	/**
	 * {@inheritdoc}
	 *
	 * @return array
	 */
	public function getAllow(array $config) {
		$iniArray = Utility::parseFiles($config['filePath'], $config['file']);

		$auth = [];
		foreach ($iniArray as $key => $actions) {
			$auth[$key] = Utility::deconstructIniKey($key);

			$actions = explode(',', $actions);
			foreach ($actions as $k => $action) {
				$action = trim($action);
				if ($action === '') {
					unset($actions[$k]);
					continue;
				}
				$actions[$k] = $action;
			}

			$auth[$key]['map'] = $actions;
			$auth[$key]['deny'] = [];
			$auth[$key]['allow'] = [];

			foreach ($actions as $action) {
				$denied = mb_substr($action, 0, 1) === '!';
				if ($denied) {
					$auth[$key]['deny'][] = mb_substr($action, 1);
					continue;
				}

				$auth[$key]['allow'][] = $action;
			}
		}

		return $auth;
	}

}
