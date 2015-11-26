<?php
namespace Psa\ElasticIndex\Shell;

use Cake\Console\Shell;
use Cake\ORM\TableRegistry;

class ElasticIndexShell extends Shell {

	public function main() {

	}

	protected function _geTable($table) {
		$table = TableRegistry::get($table);
		if (!in_array('ElasticIndex', $table->behaviors()->loaded())) {
			$table->addBehavior('Psa/ElasticIndex.ElasticIndex');
		}
		return $table;
	}

	/**
	 * (Re-)Builds the whole index for a given table.
	 */
	public function build() {
		$table = $this->_getTable();
		$total = $table->find()->all()->count();

		$this->helper('progress')->init([
			'total' => $total
		]);

		$this->helper('progress')->output([
			'callback' => function($progress) use ($total, $table) {
				$chunkSize = 50;
				$chunkCount = 0;
				while ($chunkCount < $total) {
					if (empty($records)) {
						break;
					}
					$records = $table
						->find()
						->all()
						->limit($chunkSize)
						->offset($chunkCount);
					foreach ($records as $records) {
						// @todo implement indexing here
						$progress->increment(1);
					}
				}
			}
		]);
	}

	/**
	 * Updates a single document in the index.
	 */
	public function updateDocument() {

	}

	/**
	 * Deletes a single document in the index.
	 */
	public function deleteDocument() {

	}
}
