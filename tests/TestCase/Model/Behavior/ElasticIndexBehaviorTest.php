<?php
namespace Psa\ElasticIndex\Test\TestCase\Model\Behavior;

use Cake\TestSuite\TestCase;
use Cake\ORM\TableRegistry;

class ElasticIndexBehavior extends TestCase {

    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'Projects' => 'plugin.psa/elastic_index.projects',
        'Tasks' => 'plugin.psa/elastic_index.tasks',
    ];

    /**cls
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->Projects = TableRegistry::get('Projects');
        $this->Projects->hasMany('Tasks');
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->Tasks);
        parent::tearDown();
    }

    /**
     * testSomething
     *
     * @return void
     */
    public function testSomething()
    {
        $this->Projects->addBehavior('Psa/ElasticIndex.ElasticIndex', [
            'connection' => 'test_elastic'
        ]);
        $entity = $this->Projects->newEntity([
            'title' => 'Some Project',
            'body' => 'Some content',
            'tasks' => [
                ['title' => 'foo', 'body' => 'bar'],
                ['title' => 'foo2', 'body' => 'bar2'],
            ]
        ]);
        $this->Projects->save($entity);
    }

    public function testAfterSave()
    {

    }

    public function testAfterDelete()
    {

    }
}
