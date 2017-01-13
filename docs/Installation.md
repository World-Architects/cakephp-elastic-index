# Installation

```sh
composer require psa/elastic-search-index
```

Load the plugin in your config/bootstrap.php

```php
Plugin::load('Psa/ElasticIndex');
```

Configure a connection `elastic` in your config/app.php

```php
    'elastic' => [
        'className' => 'Cake\ElasticSearch\Datasource\Connection',
        'driver' => 'Cake\ElasticSearch\Datasource\Connection',
        'host' => '127.0.0.1',
        'port' => 9200,
        'index' => 'search', // Or whatever your index name is
        'log' => false
    ],
```
