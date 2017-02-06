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
        $this->elasticIndex($this->getConfig('type'), $this->config('connection'));
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
            if (empty($connection)) {
                $connection = $this->getConfig('connection');
            }
            if (!TypeRegistry::exists($this->getConfig('type'))) {
                $this->_elasticType = TypeRegistry::get($this->getConfig('type'), [
                    'connection' => ConnectionManager::get($this->getConfig('connection'))
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
    public function afterSave(Event $event, EntityInterface $entity)
    {
        if ($this->getConfig('autoIndex') === true) {
            $this->saveIndexDocument($entity);
        }
    }

    /**
     * Saves and updates a document in the index used by the table the behavior is attached to.
     *
     * @param \Cake\Datasource\EntityInterface
     * @return void
     */
    public function saveIndexDocument(EntityInterface $entity)
    {
        if (method_exists($this->_table, 'getIndexData')) {
            $indexData = $this->_table->getIndexData($entity);
        } else {
            $indexData = $entity;
        }

        if ($indexData instanceof EntityInterface) {
            $indexData = $indexData->toArray();
        }

        if ($entity->isNew()) {
            $elasticEntity = $this->elasticIndex()->newEntity($indexData);
        } else {
            $elasticEntity = $this->_findElasticDocument($entity);
            if (empty($elasticEntity)) {
                $elasticEntity = $this->elasticIndex()->newEntity($indexData);
            } else {
                $elasticEntity = $this->elasticIndex()->patchEntity($elasticEntity, $indexData);
            }
        }

        return $this->elasticIndex()->save($elasticEntity);
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
     * @return void
     */
    public function deleteIndexDocument(EntityInterface $entity)
    {
        $elasticEntity = $this->_findElasticDocument($entity);
        if (empty($elasticEntity)) {
            return;
        }
        $this->elasticIndex()->delete($elasticEntity);
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
