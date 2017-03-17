<?php
namespace Psa\ElasticIndex\Model\Behavior;

use Cake\Event\Event;
use Cake\ORM\Behavior;
use Cake\Datasource\EntityInterface;
use Cake\ORM\TableRegistry;

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

    protected $_defaultConfig = [
        'updateMethodName' => 'updateIndexDocument',
        'models' => []
    ];

    protected $_enabled = true;

    public function disableElasticTrigger() {
        $this->_enabled = false;
    }

    public function enableElasticTrigger() {
        $this->_enabled = true;
    }

    public function afterDelete(Event $event, EntityInterface $entity)
    {
        if ($this->_enabled) {
            $this->updateRelatedIndexDocuments($entity);
        }
    }

    public function afterSave(Event $event, EntityInterface $entity)
    {
        if ($this->_enabled) {
            $this->updateRelatedIndexDocuments($entity);
        }
    }

    public function updateRelatedIndexDocuments($entity)
    {
        $models = (array)$this->getConfig('models');
        $method = (string)$this->getConfig('updateMethodName');

        foreach ($models as $model => $field) {
            $model = TableRegistry::get($model);
            if (!$model->behaviors()->hasMethod($method) || !method_exists($model, $method)) {
                throw new \RuntimeException(sprintf(
                    '`%s` must implement a method `%s`',
                    get_class($model),
                    $method
                ));
            }

            $id = null;
            if (is_string($field)) {
                $id = $entity->get($field);
            }
            if (is_callable($field)) {
                $id = $field($this->_table, $entity);
            }
            if (empty($id)) {
                throw new \RuntimeException(sprintf('Could not update the ES index for `%s`', $model));
            }

            $model->updateIndexDocument($id);
        }
    }

}
