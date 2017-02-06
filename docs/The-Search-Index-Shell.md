# The Search Index Shell

The shell will allow you to index any table you have configured to be indexable before

```php
Configure::read('ElasticIndex.indexableTables', [
    'Jobs',
    /*...*/
]);
````

This will allow you to use the interactive shell.

```sh
.\bin\cake search_index

Welcome to CakePHP v3.4.0-RC3 Console
---------------------------------------------------------------
App : src
Path: C:\xampp\htdocs\project\src\
PHP : 7.0.0
---------------------------------------------------------------
Choose a model to index:
[0] AgendaItems
[1] Jobs
[2] Projects
[3] Profiles
[4] Psa/Cms.CmsPages
[5] CurriculaVitae
Choose the table you want to index:
> 0

Start building index for table "AgendaItems".
Going to process 65 records.
==========================================================================> 100%
Finished building index for table "AgendaItems".
Done indexing.
```
