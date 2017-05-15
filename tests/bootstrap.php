<?php
use Cake\Core\Plugin;

$findRoot = function ($root) {
	do {
		$lastRoot = $root;
		$root = dirname($root);
		if (is_dir($root . '/vendor/cakephp/cakephp')) {
			return $root;
		}
	} while ($root !== $lastRoot);
	throw new \Exception('Cannot find the root of the application, unable to run tests');
};

$root = $findRoot(__FILE__);
unset($findRoot);
chdir($root);

require $root . '/vendor/cakephp/cakephp/tests/bootstrap.php';
require $root . '/vendor/autoload.php';

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

use Cake\Datasource\ConnectionManager;
use Cake\ElasticSearch\TypeRegistry;

ConnectionManager::drop('test');
ConnectionManager::config('test', ['url' => getenv('db_dsn')]);
ConnectionManager::config('test_elastic', ['url' => getenv('db_dsn_elastic')]);

TypeRegistry::get('app_index', ['connection' => 'test_elastic']);
