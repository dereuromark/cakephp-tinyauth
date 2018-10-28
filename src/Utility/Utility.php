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
	 * Returns the found INI file(s) as an array.
	 *
	 * @param array|string|null $paths Paths to look for INI files.
	 * @param string $file INI file name.
	 * @return array List with all found files.
	 */
	public static function parseFiles($paths, $file) {
		if ($paths === null) {
			$paths = ROOT . DS . 'config' . DS;
		}

		$list = [];
		foreach ((array)$paths as $path) {
			$list += static::parseFile($path . $file);
		}

		return $list;
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
			$content = file_get_contents($ini);
			if ($content === false) {
				throw new Exception('Cannot load INI file.');
			}
			$iniArray = parse_ini_string($content, true);
		}

		if (!is_array($iniArray)) {
			throw new Exception(sprintf('Invalid TinyAuth config file (%s)', $ini));
		}
		return $iniArray;
	}

	/**
	 * @param string $file
	 * @param array $content
	 *
	 * @return bool
	 */
	public static function generateFile($file, array $content) {
		$string = static::buildIniString($content);

		return (bool)file_put_contents($file, $string);
	}

	/**
	 * @param array $a
	 *
	 * @return string
	 */
	public static function buildIniString(array $a) {
		$out = [];
		foreach ($a as $rootkey => $rootvalue) {
			$out[] = "[$rootkey]";

			// loop through items under a section heading
			foreach ($rootvalue as $key => $value) {
				$out[] = "$key = $value";
			}

			$out[] = '';
		}

		return implode(PHP_EOL, $out);
	}

}
