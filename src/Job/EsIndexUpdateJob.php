<?php
declare(strict_types = 1);

namespace Psa\ElasticIndex\Job;

use Cake\Datasource\ModelAwareTrait;
use Cake\Log\LogTrait;
use Cake\ORM\Entity;
use Exception;
use josegonzalez\Queuesadilla\Job\Base;
use Psr\Log\LogLevel;
use RuntimeException;

/**
 * EsIndexUpdateJob
 */
class EsIndexUpdateJob
{

    use LogTrait;
    use ModelAwareTrait;

    /**
     * Updates the ES index for a given model and record
     *
     * @param \josegonzalez\Queuesadilla\Job\Base $engine Engine
     * @return void
     */
    public function updateIndex(Base $engine)
    {
        $data = $engine->data();
        $data = json_decode($data['message'], true);

        try {
            if (!isset($data['model']) || !isset($data['id'])) {
                throw new RuntimeException('Missing `model` and / or `id` in the job data!');
            }

            // The model can be the FQCN, which results in a wonky alias and Cake got problems
            $model = $this->loadModel($data['model']);
            $model->setAlias($data['alias']);
            $this->_checkForBehavior($model);

            // useQueue false is important here to avoid endless recursion!
            $model->saveIndexDocument($model->get($data['id']), [
                'getIndexData' => true,
                'useQueue' => false
            ]);

            $engine->acknowledge();

            return;
        } catch (Exception $e) {
            throw $e;
            $this->log($e->getMessage(), LogLevel::ERROR);
        }

        $engine->reject();
    }

    /**
     * Deletes a document from the index
     *
     * @return void
     */
    public function deleteFromIndex(Base $engine)
    {
        $data = $engine->data();
        $data = json_decode($data['message'], true);

        try {
            if (!isset($data['model']) || !isset($data['id'])) {
                throw new RuntimeException('Missing `model` and / or `id` in the job data!');
            }

            // The model can be the FQCN, which results in a wonky alias and Cake got problems
            $model = $this->loadModel($data['model']);
            $model->setAlias($data['alias']);
            $this->_checkForBehavior($model);

            // This has to be a new entity because the SQL record might be already gone at this point in time
            $entity = new Entity([
                (string)$model->getPrimarykey() => $data['id']
            ]);

            // useQueue false is important here to avoid endless recursion!
            $model->deleteIndexDocument($entity, [
                'useQueue' => false
            ]);

            $engine->acknowledge();

            return;
        } catch (Exception $e) {
            throw $e;
            $this->log($e->getMessage(), LogLevel::ERROR);
        }

        $engine->reject();
    }

    /**
     * Check for behavior
     *
     * @param \Cake\ORM\Table $model Model
     * @return void
     */
    protected function _checkForBehavior($model)
    {
        if (!$model->hasBehavior('ElasticIndex')) {
            throw new RuntimeException(sprintf(
                'Model `%s` is not using the ES Index Behavior',
                $data['model']
            ));
        }
    }
}
