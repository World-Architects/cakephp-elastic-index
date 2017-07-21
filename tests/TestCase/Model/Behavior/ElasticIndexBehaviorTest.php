<?php
namespace Psa\ElasticIndex\Test\TestCase\Model\Behavior;

use Cake\ElasticSearch\Document;
use Cake\ElasticSearch\Type;
use Cake\ElasticSearch\TypeRegistry;
use Cake\TestSuite\TestCase;
use Cake\ORM\TableRegistry;
use Elastica\Exception\NotFoundException;
use TestApp\Model\Table\ProjectsTable;

class ElasticIndexBehavior extends TestCase
{

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
        $this->Projects = TableRegistry::get('Projects', [
            'className' => ProjectsTable::class
        ]);
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
     * testSavingAndDeleting
     *
     * @return void
     */
    public function testSavingAndDeleting()
    {
        $this->Projects->addBehavior('Psa/ElasticIndex.ElasticIndex', [
            'type' => 'Projects',
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

        $result = $this->Projects->save($entity);
        $this->assertNotFalse($result);

        // The the project from the SQL DB and check if it was saved correctly
        $project = $this->Projects->find()
            ->where([
                'Projects.id' => $entity->get('id')
            ])
            ->contain([
                'Tasks'
            ])
            ->first();

        $this->assertCount(2, $project->get('tasks'));

        // Now get the document from the index that should have been generated
        // after the project record + associated records was saved.
        $result = $this->Projects->getElasticIndex()->get($entity->get('id'));

        $this->assertInstanceOf(Document::class, $result);
        $this->assertEquals($result->get('id'), $entity->get('id'));
        $this->assertCount(2, $result->get('tasks'));
        $this->assertEquals('Some Project', $result->get('title'));

        // Need to sleep a moment here, otherwise the get() call will return
        // the data before the index was updated due to the asynchronous nature
        // of the RESTful ES API
        sleep(1);
        $this->Projects->delete($entity);
        sleep(1);
        try {
            $this->Projects->getElasticIndex()->get($entity->get('id'));
            $this->fail('Document was not deleted');
        } catch (NotFoundException $e) {
            // Everything OK
        }
    }
}
