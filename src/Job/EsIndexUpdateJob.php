<?php
declare(strict_types = 1);

namespace Psa\ElasticIndex\Job;

use Cake\Datasource\ModelAwareTrait;
use Cake\Log\LogTrait;
use Exception;
use josegonzalez\Queuesadilla\Job\Base;
use Psr\Log\LogLevel;

/**
 * NewsletterMailJob
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
				// throw error
			}

			$model = $this->loadModel($data['model']);
			if (!$model->hasBehavior('ElasticIndex')) {
				// throw error
			}

			$model->saveIndexDocument($model->get($data['id']), ['getIndexData' => true]);

			$engine->acknowledge();

			return;
		} catch (Exception $e) {
			$this->log($e->getMessage(), LogLevel::ERROR);
		}

		$engine->reject();
	}

}
