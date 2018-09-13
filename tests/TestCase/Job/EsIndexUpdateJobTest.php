<?php
namespace Psa\ElasticIndex\Test\TestCase\Model\Behavior;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use josegonzalez\Queuesadilla\Job\Base;
use Psa\ElasticIndex\Job\EsIndexUpdateJob;

/**
 * EsIndexUpdateJobTest
 */
class EsIndexUpdateJobTest extends TestCase
{
    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'plugin.Psa/ElasticIndex.Projects'
    ];

    /**
     * @inheritDoc
     */
    public function setUp() {
        parent::setUp();
    }

    /**
     * testUpdateIndexMissingModel
     *
     * @return void
     */
    public function testUpdateIndexMissingModel() {
        $this->markTestSkipped();

        $engine = $this->getMockBuilder(Base::class)
            ->setMethods([
                'data', 'acknowledge', 'reject'
            ])
            ->getMock();

        $engine->expects($this->any())
            ->method('data')
            ->willReturn([
                'message' => json_encode(['id' => 1])
            ]);

        $engine->expects($this->once())
            ->method('reject');

        $job = new EsIndexUpdateJob();
        $job->updateIndex($engine);
    }

    /**
     * testUpdateIndexMissingId
     *
     * @return void
     */
    public function testUpdateIndexMissingId() {
        $this->markTestSkipped();
        $engine = $this->getMockBuilder(Base::class)
            ->setMethods([
                'data', 'acknowledge', 'reject'
            ])
            ->getMock();

        $engine->expects($this->any())
            ->method('data')
            ->willReturn([
                'message' => json_encode(['model' => 'Articles'])
            ]);

        $engine->expects($this->once())
            ->method('reject');

        $job = new EsIndexUpdateJob();
        $job->updateIndex($engine);
    }

    /**
     * testUpdateIndexMissingId
     *
     * @return void
     */
    public function testUpdateIndexModelIsMissingBehavior() {
        $this->markTestSkipped();
        $engine = $this->getMockBuilder(Base::class)
            ->setMethods([
                'data', 'acknowledge', 'reject'
            ])
            ->getMock();

        $engine->expects($this->any())
            ->method('data')
            ->willReturn([
                'message' => json_encode([
                    'model' => 'Articles',
                    'id' => 1
                ])
            ]);

        $engine->expects($this->once())
            ->method('reject');

        $job = new EsIndexUpdateJob();
        $job->updateIndex($engine);
    }

    /**
     * testUpdateIndex
     *
     * @return void
     */
    public function testUpdateIndex() {
        $projects = TableRegistry::getTableLocator()->get('Projects');
        $projects->addBehavior('Psa/ElasticIndex.ElasticIndex');

        $engine = $this->getMockBuilder(Base::class)
            ->setMethods([
                'data', 'acknowledge', 'reject'
            ])
            ->getMock();

        $engine->expects($this->any())
            ->method('data')
            ->willReturn([
                'message' => json_encode([
                    'model' => 'Projects',
                    'id' => '8176e72b-11f8-48c6-90ca-5f3cae439aca'
                ])
            ]);

        $engine->expects($this->once())
            ->method('acknowledge');

        $job = new EsIndexUpdateJob();
        $job->updateIndex($engine);
    }

}
