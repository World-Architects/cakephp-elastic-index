<?php
namespace Psa\ElasticIndex\Model\Behavior;

use Cake\Event\Event;
use Cake\ORM\Behavior;
use Cake\Datasource\EntityInterface;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use RuntimeException;

/**
 * Elastic Update Trigger Behavior
 *
 * Use this behavior on related data models to update your index documents for
 * your primary indexing model.
 *
 * For example when your indexing is relying on a lot of data and has associated
 * data, all your associated models should use this behavior to trigger the
 * index building method of your primary indexing model.
 */
class ElasticUpdateTriggerBehavior extends Behavior {

    /**
     * Default Config
     *
     * @var array
     */
    protected $_defaultConfig = [
        'updateMethodName' => 'saveIndexDocument',
        'deleteMethodName' => 'deleteIndexDocument',
        'models' => []
    ];

    protected $_enabled = true;

    /**
     * Disables the ES trigger
     *
     * @return void
     */
    public function disableElasticTrigger()
    {
        $this->_enabled = false;
    }

    /**
     * Enables the ES trigger
     *
     * @return void
     */
    public function enableElasticTrigger()
    {
        $this->_enabled = true;
    }

    /**
     * afterDelete callback
     *
     * @param \Cake\Event\Event $event Event
     * @param \Cake\Datasource\EntityInterface $entity Entity
     * @return void
     */
    public function afterDelete(Event $event, EntityInterface $entity)
    {
        if ($this->_enabled) {
            $this->updateRelatedIndexDocuments($entity);
        }
    }

    /**
     * afterSave callback
     *
     * @param \Cake\Event\Event $event Event
     * @param \Cake\Datasource\EntityInterface $entity Entity
     * @return void
     */
    public function afterSave(Event $event, EntityInterface $entity)
    {
        if ($this->_enabled) {
            $this->updateRelatedIndexDocuments($entity);
        }
    }

    /**
     * Checks that the callback table implements the ES callback method
     *
     * @throws \RuntimeException
     * @param \Cake\ORM\Table $table Table object.
     * @param string $method Method name.
     * @return void
     */
    protected function _modelCheck(Table $table, $method)
    {
        if (!$table->behaviors()->hasMethod($method)
            && !method_exists($table, $method))
        {
            throw new RuntimeException(sprintf(
                '`%s` must implement a method `%s`',
                get_class($table),
                $method
            ));
        }
    }

    /**
     * Triggers the index update or delete
     *
     * @param \Cake\Datasource\EntityInterface $entity Entity
     * @param string $method Method to call
     */
    protected function _triggerIndex($entity, $method)
    {
        $models = (array)$this->getConfig('models');

        foreach ($models as $model => $field) {
            $model = TableRegistry::get($model);
            $this->_modelCheck($model, $method);

            $id = null;
            if (is_string($field)) {
                $id = $entity->get($field);
            }

            if (is_callable($field)) {
                $id = $field($entity, $model, $this->_table);
            }

            if ($id === false) {
                return;
            }

            if (empty($id)) {
                throw new RuntimeException(sprintf(
                    'Empty ID given, could not update the ES index for `%s`',
                    get_class($model)
                ));
            }

            $entity = $model->newEntity();
            $entity->set(
                [(string)$model->getPrimaryKey() => $id],
                ['guard' => false]
            );

            $model->{$method}($entity, ['getIndexData' => true]);
        }
    }

    /**
     * Triggers the ES delete on related models
     *
     * @return void
     */
    public function deleteRelatedIndexDocuments($entity)
    {
        $this->_triggerIndex(
            $entity,
            (string)$this->getConfig('deleteMethodName')
        );
    }

    /**
     * Triggers the ES update on related models
     *
     * @return void
     */
    public function updateRelatedIndexDocuments($entity)
    {
        $this->_triggerIndex(
            $entity,
            (string)$this->getConfig('updateMethodName')
        );
    }
}
