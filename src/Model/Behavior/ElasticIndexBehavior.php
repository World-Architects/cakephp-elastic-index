<?php
namespace Psa\ElasticIndex\Model\Behavior;

use Cake\Datasource\ConnectionManager;
use Cake\Event\Event;
use Cake\ORM\Behavior;
use Cake\ORM\Table;
use Cake\Datasource\EntityInterface;
use Cake\ElasticSearch\TypeRegistry;
use Cake\Utility\Inflector;

/**
 * ElasticIndexBehavior
 *
 * This behavior will automatically add documents into a type for you. It will
 * also remove the document from the index as well on after delete.
 */
class ElasticIndexBehavior extends Behavior {

    /**
     * Default config
     *
     * @param array
     */
    protected $_defaultConfig = [
        'type' => null,
        'connection' => 'elastic',
        'autoIndex' => true
    ];

    /**
     * Elastic Search Type object
     *
     * @param \Cake\Elastic\Type
     */
    protected $_elasticType = null;

    /**
     * Constructor
     *
     * @param \Cake\ORM\Table
     * @param array $config
     */
    public function __construct(Table $table, array $config = [])
    {
        $this->_defaultConfig['type'] = Inflector::underscore($table->table());
        parent::__construct($table, $config);
        $this->getElasticIndex($this->getConfig('type'), $this->getConfig('connection'));
    }

    public function elasticIndex($type = null, $connection = null) {
        return $this->getElasticIndex($type, $connection);
    }

    /**
     * Gets and sets an index type to use for building the index.
     *
     * @param string $type
     * @param string $connection
     * @return \Cake\ElasticSearch\Type;
     */
    public function getElasticIndex($type = null, $connection = null)
    {
        if (!empty($type)) {
            if (!TypeRegistry::exists($this->getConfig('type'))) {
                if (empty($connection)) {
                    $connection = $this->getConfig('connection');
                }
                $this->_elasticType = TypeRegistry::get($this->getConfig('type'), [
                    'connection' => ConnectionManager::get($connection)
                ]);
            } else {
                $this->_elasticType = TypeRegistry::get($this->getConfig('type'));
            }
        }

        return $this->_elasticType;
    }

    /**
     * Disables the automatic indexing
     *
     * @return void
     */
    public function disableIndexing()
    {
        $this->setConfig('autoIndex', false);
    }

    /**
     * Enables the automatic indexing
     *
     * @return void
     */
    public function enableIndexing()
    {
        $this->setConfig('autoIndex', true);
    }

    /**
     * After save
     *
     * @param \Cake\Event\Event;
     * @param \Cake\Datasource\EntityInterface
     * @return void
     */
    public function afterSave(Event $event, EntityInterface $entity, $options = [])
    {
        $autoIndex = $this->getConfig('autoIndex');
        if (isset($options['autoIndex'])) {
            $autoIndex = (bool)$options['autoIndex'];
        }

        if ($autoIndex === true) {
            $this->saveIndexDocument($entity, [
                'getIndexData' => true
            ]);
        }
    }

    /**
     * Turns the data into an elastic document and gets the data if required
     *
     * The 2nd argument is used for the following use cases:
     *
     * 1) When the shell of this plugin generates the index we don't want to
     * call getIndexData() even in the case it exists because the shell is
     * already using the 'indexData' finder if present to read the records
     * in a batch. Calling `getIndexData()` for each row would result in a
     * huge performance slowdown
     *
     * 2) When data inside the application gets updated, not using the shell,
     * usually only a tiny amount of data changes. Also when associated data is
     * updated we need to call `getIndexData()` to ensure all data is properly
     * fetched and updated. The associated models will trigger the callback to
     * build the data via `getIndexData()`.
     *
     * @param \Cake\Datasource\EntityInterface $entity
     * @param bool $getIndexData To fetch the data from the table or not.
     * @return \Cake\ElasticSearch\Document
     */
    protected function _toDocument(EntityInterface $entity)
    {
        return $this->getElasticIndex()->newEntity($entity->toArray());
    }

    /**
     * The 2nd argument is used for the following use cases:
     *
     * 1) When the shell of this plugin generates the index we don't want to
     * call getIndexData() even in the case it exists because the shell is
     * already using the 'indexData' finder if present to read the records
     * in a batch. Calling `getIndexData()` for each row would result in a
     * huge performance slowdown
     *
     * 2) When data inside the application gets updated, not using the shell,
     * usually only a tiny amount of data changes. Also when associated data is
     * updated we need to call `getIndexData()` to ensure all data is properly
     * fetched and updated. The associated models will trigger the callback to
     * build the data via `getIndexData()`.
     *
     * @param \Cake\Datasource\EntityInterface $entity
     * @param bool $getIndexData To fetch the data from the table or not.
     * @return bool|\Cake\ElasticSearch\Document
     */
    protected function _getIndexData(EntityInterface $entity, $getIndexData = false) {
        if ($getIndexData && method_exists($this->_table, 'getIndexData')) {
            return $this->_table->getIndexData($entity);
        } elseif ($getIndexData) {
            return $this->_table->get($entity->get((string)$this->_table->getPrimaryKey()));
        }

        return $entity;
    }

    /**
     * Saves multiple documents in a bulk save
     *
     * @param array $entities
     * @param array $options
     * @return bool
     */
    public function saveIndexDocuments($entities, array $options = [])
    {
        $getIndexData = isset($options['getIndexData'])
            ? (bool)$options['getIndexData']
            : false;

        foreach ($entities as $key => $entity) {
            $indexData = $this->_getIndexData($entity, $getIndexData);
            if ($indexData) {
                $entities[$key] = $this->_toDocument($indexData);
            }
        }

        return $this->getElasticIndex()->saveMany($entities);
    }

    /**
     * Saves and updates a document in the index used by the table the behavior is attached to.
     *
     * @param \Cake\Datasource\EntityInterface
     * @param array $options
     * @return bool|array
     */
    public function saveIndexDocument(EntityInterface $entity, array $options = [])
    {
        $getIndexData = isset($options['getIndexData'])
            ? (bool)$options['getIndexData']
            : false;

        $indexData = $this->_getIndexData($entity, $getIndexData);
        if ($indexData) {
            return $this->getElasticIndex()->save($this->_toDocument($entity));
        }

        return false;
    }

    /**
     * After delete
     *
     * @param \Cake\Event\Event;
     * @param \Cake\Datasource\EntityInterface
     * @return void
     */
    public function afterDelete(Event $event, EntityInterface $entity)
    {
        if ($this->getConfig('autoIndex') === true) {
            $this->deleteIndexDocument($entity);
        }
    }

    /**
     * Deletes an index document.
     *
     * @param \Cake\Datasource\EntityInterface
     * @return bool
     */
    public function deleteIndexDocument(EntityInterface $entity)
    {
        $elasticEntity = $this->_findElasticDocument($entity);

        if (empty($elasticEntity)) {
            return false;
        }

        return $this->getElasticIndex()->delete($elasticEntity);
    }

    /**
     * Finds an elastic index document for an entity of the current table.
     *
     * @param \Cake\ORM\EntityInterface
     * @return \Cake\ElasticSearch\Datasource\Document
     */
    protected function _findElasticDocument(EntityInterface $entity)
    {
        $id = $entity->get((string)$this->_table->getPrimaryKey());
        return $this->getElasticIndex()
            ->find()
            ->where([
                '_id' => $id
            ])
            ->first();
    }
}
