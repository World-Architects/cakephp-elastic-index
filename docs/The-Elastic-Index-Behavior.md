# The Elastic Index Behavior

Just attach the behavior to any of your tables and it will automatically start putting any document on save in the ES search index.

On delete it will remove the document from the index if it was found in the index.

```php
$this->addBehavior('Psa/ElasticIndex.ElasticIndex');
```
