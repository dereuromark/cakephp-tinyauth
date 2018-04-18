<?php
/**
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

require dirname(__DIR__) . '/vendor/cakephp/cakephp/src/basics.php';
require dirname(__DIR__) . '/vendor/autoload.php';

define('ROOT', dirname(__DIR__));
define('APP_DIR', 'src');

define('APP', rtrim(sys_get_temp_dir(), DS) . DS . APP_DIR . DS);
if (!is_dir(APP)) {
	mkdir(APP, 0770, true);
}

define('CONFIG', dirname(__FILE__) . DS . 'config' . DS);

define('TMP', ROOT . DS . 'tmp' . DS);
if (!is_dir(TMP)) {
	mkdir(TMP, 0770, true);
}

define('LOGS', TMP . 'logs' . DS);
define('CACHE', TMP . 'cache' . DS);

define('CAKE_CORE_INCLUDE_PATH', ROOT . '/vendor/cakephp/cakephp');
define('CORE_PATH', CAKE_CORE_INCLUDE_PATH . DS);

use Composer\Json\JsonFile;

$jsonFile = new JsonFile(ROOT . DIRECTORY_SEPARATOR . 'composer.json');
$lockFile = new JsonFile(ROOT . DIRECTORY_SEPARATOR . 'composer.lock');
if ($jsonFile->exists() && $lockFile->exists()) {
		$jsonContent = $jsonFile->read();
		$lockContent = $lockFile->read();

		$minVer = $jsonContent['require']['cakephp/cakephp'];
		$packages = $lockContent['packages'];
		$minVer = trim($minVer, '\^\~');
		$minVer = (substr_count($minVer, '.') == 1) ? $minVer . '.0' : $minVer;
		foreach ($packages as $package) {
				if ($package['name'] == 'cakephp/cakephp') {
						$cakeVer = $package['version'];
						$cakeVer = (substr_count($cakeVer, '.') == 1) ? $cakeVer . '.0' : $cakeVer;
						if (version_compare($minVer, $cakeVer) < 0) {
								error_reporting(E_ALL ^ E_USER_DEPRECATED);
						}
						break;
				}
		}
}

Cake\Core\Configure::write('App', [
	'namespace' => 'App'
]);

Cake\Core\Configure::write('debug', true);

$cache = [
	'default' => [
		'engine' => 'File',
		'path' => CACHE
	],
	'_cake_core_' => [
		'className' => 'File',
		'prefix' => 'crud_myapp_cake_core_',
		'path' => CACHE . 'persistent/',
		'serialize' => true,
		'duration' => '+10 seconds'
	],
	'_cake_model_' => [
		'className' => 'File',
		'prefix' => 'crud_my_app_cake_model_',
		'path' => CACHE . 'models/',
		'serialize' => 'File',
		'duration' => '+10 seconds'
	]
];

Cake\Cache\Cache::config($cache);

Cake\Core\Plugin::load('TinyAuth', ['path' => ROOT . DS, 'autoload' => true]);

// Ensure default test connection is defined
if (!getenv('db_class')) {
	putenv('db_class=Cake\Database\Driver\Sqlite');
	putenv('db_dsn=sqlite::memory:');
}

Cake\Datasource\ConnectionManager::config('test', [
	'className' => 'Cake\Database\Connection',
	'driver' => getenv('db_class'),
	'dsn' => getenv('db_dsn'),
	'database' => getenv('db_database'),
	'username' => getenv('db_username'),
	'password' => getenv('db_password'),
	'timezone' => 'UTC',
	'quoteIdentifiers' => true,
	'cacheMetadata' => true,
]);
