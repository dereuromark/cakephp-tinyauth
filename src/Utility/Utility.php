<?php
namespace TinyAuth\Utility;

use Cake\Core\Exception\Exception;

class Utility {

	/**
	 * Deconstructs an authentication ini section key into a named array with authentication parts.
	 *
	 * @param string $key INI section key as found in authentication.ini
	 * @return array Array with named keys for controller, plugin and prefix
	 */
	public static function deconstructIniKey($key) {
		$res = [
			'plugin' => null,
			'prefix' => null
		];

		if (strpos($key, '.') !== false) {
			list($res['plugin'], $key) = explode('.', $key);
		}
		if (strpos($key, '/') !== false) {
			list($res['prefix'], $key) = explode('/', $key);
		}
		$res['controller'] = $key;
		return $res;
	}

	/**
	 * Returns the ini file as an array.
	 *
	 * @param string $ini Full path to the ini file
	 * @return array List
	 * @throws \Cake\Core\Exception\Exception
	 */
	public static function parseFile($ini) {
		if (!file_exists($ini)) {
			throw new Exception(sprintf('Missing TinyAuth config file (%s)', $ini));
		}

		if (function_exists('parse_ini_file')) {
			$iniArray = parse_ini_file($ini, true);
		} else {
			$iniArray = parse_ini_string(file_get_contents($ini), true);
		}

		if (!is_array($iniArray)) {
			throw new Exception(sprintf('Invalid TinyAuth config file (%s)', $ini));
		}
		return $iniArray;
	}

}
