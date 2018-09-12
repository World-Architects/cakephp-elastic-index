<?php
declare(strict_types = 1);

namespace Psa\ElasticIndex\Job;

use Cake\Datasource\ModelAwareTrait;
use Cake\Log\LogTrait;
use Exception;
use josegonzalez\Queuesadilla\Job\Base;
use Psr\Log\LogLevel;
use RuntimeException;

/**
 * EsIndexUpdateJob
 */
class EsIndexUpdateJob {

	use LogTrait;
	use ModelAwareTrait;

	/**
	 * Updates the ES index for a given model and record
	 *
	 * @param \josegonzalez\Queuesadilla\Job\Base $engine Engine
	 * @return void
	 */
	public function updateEsIndex(Base $engine) {
		$data = $engine->data();
		$data = json_decode($data['message'], true);

		try {
			if (isset($data['model']) || !isset($data['id'])) {
				throw new RuntimeException('Missing `model` and / or `id` in the job data!');
			}

			$model = $this->loadModel($data['model']);
			if (!$model->hasBehavior('ElasticIndex')) {
				throw new RuntimeException(sprintf(
					'Model `%s` is not using the ES Index Behavior',
					$data['model']
				));
			}

			// useQueue false is important here to avoid endless recursion!!!
			$model->saveIndexDocument($model->get($data['id']), [
				'getIndexData' => true,
				'useQueue' => false
			]);

			$engine->acknowledge();

			return;
		} catch (Exception $e) {
			$this->log($e->getMessage(), LogLevel::ERROR);
		}

		$engine->reject();
	}

}
