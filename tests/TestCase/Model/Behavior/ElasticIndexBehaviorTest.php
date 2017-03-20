<?php
namespace Psa\ElasticIndex\Test\TestCase\Model\Behavior;

use Cake\ElasticSearch\Document;
use Cake\ElasticSearch\Type;
use Cake\ElasticSearch\TypeRegistry;
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
     * testSaving
     *
     * @return void
     */
    public function testSaving()
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

        $result = $this->Projects->getElasticIndex()->get($entity->get('id'));

        $this->assertInstanceOf(Document::class, $result);
        $this->assertEquals($result->get('id'), $entity->get('id'));
        $this->assertCount(2, $result->get('tasks'));
        $this->assertEquals('Some Project', $result->get('title'));
sleep(5);
        $this->Projects->delete($entity);
        $result = $this->Projects->getElasticIndex()->get($entity->get('id'));
        //print_r($result);

    }
}
