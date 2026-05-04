<?php

namespace TinyAuth\Auth\AllowAdapter;

use Cake\Core\Configure;
use TinyAuth\Utility\Utility;

abstract class AbstractAllowAdapter implements AllowAdapterInterface {

	/**
	 * Loads the raw section => actions array from the underlying config source.
	 *
	 * The returned shape matches `parse_ini_file($path, false)` — top-level keys
	 * are section identifiers (e.g. `Users`, `Admin/Users`) and each value is the
	 * raw comma-separated action list.
	 *
	 * @param array<string, mixed> $config Current TinyAuth configuration values.
	 * @return array<string, string>
	 */
	abstract protected function parseConfig(array $config): array;

	/**
	 * @param array<string, mixed> $config Current TinyAuth configuration values.
	 * @return array
	 */
	public function getAllow(array $config): array {
		$sections = $this->parseConfig($config);

		$auth = [];
		foreach ($sections as $key => $actions) {
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

			if (Configure::read('debug')) {
				$auth[$key]['map'] = $actions;
			}
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
