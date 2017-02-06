<?php
require dirname(__DIR__) . '/vendor/autoload.php';

define('APP', __DIR__);

use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Datasource\ConnectionManager;
use Cake\ElasticSearch\TypeRegistry;

Configure::write('App', [
    'namespace' => 'App',
    'paths' => [
        'plugins' => [APP . DS . 'testapp' . DS . 'Plugin' . DS],
    ]
]);

Cache::setConfig('_cake_core_', [
    'className' => 'File',
    'path' => sys_get_temp_dir(),
]);

if (!getenv('db_dsn')) {
    putenv('db_dsn=sqlite:///:memory:');
}
if (!getenv('db_dsn_elastic')) {
    putenv('db_dsn_elastic=Cake\ElasticSearch\Datasource\Connection://127.0.0.1:9200?index=cake_test_db&driver=Cake\ElasticSearch\Datasource\Connection');
}

Plugin::load('Cake/ElasticSearch', [
    'path' => dirname(dirname(__FILE__)) . DS,
    'bootstrap' => false
]);
Plugin::load('Psa/ElasticIndex', [
    'path' => dirname(dirname(__FILE__)) . DS,
    'autoload' => false,
    'bootstrap' => false
]);

ConnectionManager::config('test', ['url' => getenv('db_dsn')]);
ConnectionManager::config('test_elastic', ['url' => getenv('db_dsn_elastic')]);

TypeRegistry::get('app_index', ['connection' => 'test_elastic']);
