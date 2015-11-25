<?php
namespace Psa\ElasticIndex\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * TasksFixture

 */
class TasksFixture extends TestFixture {

	/**
	 * Fields
	 *
	 * @var array
	 */
	// @codingStandardsIgnoreStart
	public $fields = [
		'id' => ['type' => 'uuid', 'length' => null, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null],
		'project_id' => ['type' => 'uuid', 'length' => null, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null],
		'title' => ['type' => 'string', 'length' => 128, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null, 'fixed' => null],
		'body' => ['type' => 'text', 'length' => null, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null],
		'created' => ['type' => 'datetime', 'length' => null, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null],
		'modified' => ['type' => 'datetime', 'length' => null, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null],
		'_constraints' => [
			'primary' => ['type' => 'primary', 'columns' => ['id'], 'length' => []],
		],
		'_options' => [
			'engine' => 'InnoDB', 'collation' => 'utf8_general_ci'
		],
	];
	// @codingStandardsIgnoreEnd

	/**
	 * Records
	 *
	 * @var array
	 */
	public $records = [
		[
			'id' => 'b92a58b8-b4b3-4e49-88ab-e21e07d04b49',
			'project_id' => '8176e72b-11f8-48c6-90ca-5f3cae439aca',
			'title' => 'First Task',
			'body' => 'Lorem ipsum dolor sit amet, aliquet feugiat. Convallis morbi fringilla gravida, phasellus feugiat dapibus velit nunc, pulvinar eget sollicitudin venenatis cum nullam, vivamus ut a sed, mollitia lectus. Nulla vestibulum massa neque ut et, id hendrerit sit, feugiat in taciti enim proin nibh, tempor dignissim, rhoncus duis vestibulum nunc mattis convallis.',
			'created' => '2015-03-21 10:19:17',
			'modified' => '2015-03-21 10:19:17'
		],
		[
			'id' => '0d163903-052f-43e4-a3ea-8836b4d5a2db',
			'project_id' => '8176e72b-11f8-48c6-90ca-5f3cae439aca',
			'title' => 'Second Task',
			'body' => 'Lorem ipsum dolor sit amet, aliquet feugiat. Convallis morbi fringilla gravida, phasellus feugiat dapibus velit nunc, pulvinar eget sollicitudin venenatis cum nullam, vivamus ut a sed, mollitia lectus. Nulla vestibulum massa neque ut et, id hendrerit sit, feugiat in taciti enim proin nibh, tempor dignissim, rhoncus duis vestibulum nunc mattis convallis.',
			'created' => '2015-03-21 10:19:17',
			'modified' => '2015-03-21 10:19:17'
		],
	];
}
