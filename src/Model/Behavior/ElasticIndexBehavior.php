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
        $this->elasticIndex($this->getConfig('type'), $this->getConfig('connection'));
    }

    /**
     * Gets and sets an index type to use for building the index.
     *
     * @param string $type
     * @param string $connection
     * @return \Cake\ElasticSearch\Type;
     */
    public function elasticIndex($type = null, $connection = null)
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
            $this->saveIndexDocument($entity);
        }
    }

    /**
     * Turns the data into an elastic document
     *
     * @param \Cake\Datasource\EntityInterface $entity
     * @param bool $getIndexData
     * @return \Cake\ElasticSearch\Document
     */
    protected function _toDocument(EntityInterface $entity, $getIndexData = false)
    {
        if ($getIndexData && method_exists($this->_table, 'getIndexData')) {
            $indexData = $this->_table->getIndexData($entity);
        } else {
            $indexData = $entity;
        }

        if ($indexData instanceof EntityInterface) {
            $indexData = $indexData->toArray();
        }

        return $this->elasticIndex()->newEntity($indexData);

//        if ($entity->isNew()) {
//            return $this->elasticIndex()->newEntity($indexData);
//        }
//
//        $elasticEntity = $this->_findElasticDocument($entity);
//        if (empty($elasticEntity)) {
//            return $this->elasticIndex()->newEntity($indexData);
//        }
//
//        return $this->elasticIndex()->patchEntity($elasticEntity, $indexData);
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
            $entities[$key] = $this->_toDocument($entity, $getIndexData);
        }

        return $this->elasticIndex()->saveMany($entities);
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

        return $this->elasticIndex()->save($this->_toDocument($entity, $getIndexData));
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

        return $this->elasticIndex()->delete($elasticEntity);
    }

    /**
     * Finds an elastic index document for an entity of the current table.
     *
     * @param \Cake\ORM\EntityInterface
     * @return \Cake\ElasticSearch\Datasource\Document
     */
    protected function _findElasticDocument($entity)
    {
        return $this->elasticIndex()
            ->find()
            ->where([
                '_id' => (string)$entity->{$this->_table->primaryKey()}
            ])
            ->first();
    }
}
