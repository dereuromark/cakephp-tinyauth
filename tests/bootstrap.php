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
define('TESTS', ROOT . DS . 'tests' . DS);

define('CONFIG', dirname(__FILE__) . DS . 'config' . DS);

define('TMP', ROOT . DS . 'tmp' . DS);
if (!is_dir(TMP)) {
	mkdir(TMP, 0770, true);
}

define('LOGS', TMP . 'logs' . DS);
define('CACHE', TMP . 'cache' . DS);

define('CAKE_CORE_INCLUDE_PATH', ROOT . '/vendor/cakephp/cakephp');
define('CORE_PATH', CAKE_CORE_INCLUDE_PATH . DS);

Cake\Core\Configure::write('App', [
	'namespace' => 'TestApp'
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

Cake\Cache\Cache::setConfig($cache);

define('ROLE_USER', 1);
define('ROLE_MODERATOR', 2);
define('ROLE_ADMIN', 3);

Cake\Core\Plugin::getCollection()->add(new TinyAuth\Plugin());

class_alias(\TestApp\View\AppView::class, 'App\View\AppView');

// Ensure default test connection is defined
if (!getenv('db_class')) {
	putenv('db_class=Cake\Database\Driver\Sqlite');
	putenv('db_dsn=sqlite::memory:');
}

Cake\Datasource\ConnectionManager::setConfig('test', [
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
