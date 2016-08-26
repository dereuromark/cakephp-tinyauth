<?php

namespace TinyAuth\Controller\Component;

use Cake\Cache\Cache;
use Cake\Controller\Component\AuthComponent as CakeAuthComponent;
use Cake\Controller\ComponentRegistry;
use Cake\Core\Configure;
use Cake\Core\Exception\Exception;
use Cake\Event\Event;

/**
 * TinyAuth AuthComponent to handle all authentication in a central ini file.
 */
class AuthComponent extends CakeAuthComponent {

    /**
     * @var array
     */
    protected $_defaultTinyAuthConfig = [
        'cache' => '_cake_core_',
        'cacheKey' => 'tiny_auth_authentication',
        'autoClearCache' => false, // usually done by Cache automatically in debug mode,
        'authPath' => null, // possible to locate authentication.ini at given path e.g. Plugin::configPath('Admin')
        'authFile' => 'authentication.ini'
    ];

    /**
     * @param \Cake\Controller\ComponentRegistry $registry
     * @param array $config
     * @throws \Cake\Core\Exception\Exception
     */
    public function __construct(ComponentRegistry $registry, array $config = []) {
        $config += (array)Configure::read('TinyAuth');
        $config += $this->_defaultTinyAuthConfig;

        parent::__construct($registry, $config);

        if (!in_array($config['cache'], Cache::configured())) {
            throw new Exception(sprintf('Invalid TinyAuthorization cache `%s`', $config['cache']));
        }
    }

    /**
     * @param \Cake\Event\Event $event Event instance.
     * @return \Cake\Network\Response|null
     */
    public function startup(Event $event) {
        $this->_prepareAuthentication();

        return parent::startup($event);
    }

    /**
     * @return void
     */
    protected function _prepareAuthentication() {
        $authentication = $this->_getAuth($this->_config['authPath']);

        $params = $this->request->params;
        foreach ($authentication as $array) {
            if ($params['plugin'] && $params['plugin'] !== $array['plugin']) {
                continue;
            }
            if (!empty($params['prefix']) && $params['prefix'] !== $array['prefix']) {
                continue;
            }
            if ($params['controller'] !== $array['controller']) {
                continue;
            }

            if ($array['actions'] === []) {
                $this->allow();
                return;
            }

            $this->allow($array['actions']);
            /*
            if (in_array($params['action'], $array['actions'])) {
                $this->allow($params['action']);
                return;
            }
            */
        }
    }

    /**
     * Parse ini file and returns the allowed actions.
     *
     * Uses cache for maximum performance.
     *
     * @param string|null $path
     * @return array Actions
     */
    protected function _getAuth($path = null) {
        if ($path === null) {
            $path = ROOT . DS . 'config' . DS;
        }

        if ($this->_config['autoClearCache'] && Configure::read('debug')) {
            Cache::delete($this->_config['cacheKey'], $this->_config['cache']);
        }
        $roles = Cache::read($this->_config['cacheKey'], $this->_config['cache']);
        if ($roles !== false) {
            return $roles;
        }

        $iniArray = $this->_parseFile($path . $this->_config['authFile']);

        $res = [];
        foreach ($iniArray as $key => $actions) {
            $res[$key] = $this->_deconstructIniKey($key);
            $res[$key]['map'] = $actions;

            $actions = explode(',', $actions);

            if (in_array('*', $actions)) {
                //$this->allow();
                $res[$key]['actions'] = [];
                continue;
            }

            foreach ($actions as $action) {
                $action = trim($action);
                if (!$action) {
                    continue;
                }

                $res[$key]['actions'][] = $action;
            }
        }

        Cache::write($this->_config['cacheKey'], $res, $this->_config['cache']);
        return $res;
    }

    /**
     * Returns the authentication.ini file as an array.
     *
     * @param string $ini Full path to the authentication.ini file
     * @return array List with all available roles
     * @throws \Cake\Core\Exception\Exception
     */
    protected function _parseFile($ini) {
        if (!file_exists($ini)) {
            throw new Exception(sprintf('Missing TinyAuthorize authentication file (%s)', $ini));
        }

        if (function_exists('parse_ini_file')) {
            $iniArray = parse_ini_file($ini, true);
        } else {
            $iniArray = parse_ini_string(file_get_contents($ini), true);
        }

        if (!is_array($iniArray)) {
            throw new Exception('Invalid TinyAuthorize authentication file');
        }
        return $iniArray;
    }

    /**
     * Deconstructs an authentication ini section key into a named array with authentication parts.
     *
     * @param string $key INI section key as found in authentication.ini
     * @return array Array with named keys for controller, plugin and prefix
     */
    protected function _deconstructIniKey($key) {
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

}
