<?php
namespace Psa\ElasticSearch\Test\Fixture\ElasticSearch;

/**
 * ProjectsFixture
 */
class ProjectsFixture extends BaseFixture {

    /**
     * Elastic Type
     * It's named table because it inherits and re-uses the property.
     *
     * @var string
     */
    public $table = 'projects';

    /**
     * Schema
     *
     * @var array
     */
    public $schema = [
        'title' => [
            'type' => 'string',
        ],
        'body' => [
            'type' => 'string',
        ],
        'tasks' => [
            'type' => 'nested'
        ],
        'created' => [
            'type' => 'date',
            'format' => 'yyyy-MM-dd HH:mm:ss'
        ],
    ];

    /**
     * Records
     *
     * @var array
     */
    public $records = [
        [
            'id' => '8176e72b-11f8-48c6-90ca-5f3cae439aca',
            'title' => 'First project',
            'body' => 'Lorem ipsum dolor sit amet, aliquet feugiat. Convallis morbi fringilla gravida, phasellus feugiat dapibus velit nunc, pulvinar eget sollicitudin venenatis cum nullam, vivamus ut a sed, mollitia lectus. Nulla vestibulum massa neque ut et, id hendrerit sit, feugiat in taciti enim proin nibh, tempor dignissim, rhoncus duis vestibulum nunc mattis convallis.',
            'created' => '2015-03-21 10:19:17',
        ],
    ];
}
