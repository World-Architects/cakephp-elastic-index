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

## Preparing your models

You can use the plugin without implementing these finders but it will only fetch the record and no associated data.

If you want to get more or more complex data you'll have to implement these two finders. If you don't know how to implement custom finders [check the official CakePHP documentation](https://book.cakephp.org/3.0/en/orm/retrieving-data-and-resultsets.html#custom-finder-methods).

### findIndexData()

This method should call contain() on the query object for everything you want to contain in the ES document. Make sure it has also the same conditions as findIndexDataCount() so it will fetch the same records that were counted before
 doing the actual indexing.

### findIndexDataCount()

This method should only contain what you really need for the count query. Usually you
don't need a lot of associated data to get a count of all the records you want to put into the index.

If this method is not present the shell will fall back to findIndexData() and might
end up causing a slowdown.
