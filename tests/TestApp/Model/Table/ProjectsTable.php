<?php
namespace TestApp\Model\Table;

use Cake\ORM\Table;

class ProjectsTable extends Table
{
    /**
     * {@inheritdoc}
     */
    public function initialize(array $config) {
        parent::initialize($config);
        $this->addBehavior('Psa/ElasticIndex.ElasticIndex', [
            'type' => 'Projects',
            'connection' => 'test_elastic'
        ]);
        $this->hasMany('Tasks');
    }

    public function getIndexData($entity)
    {
        return $this->find()
            ->where([
                'Projects.id' => $entity->get('id')
            ])
            ->contain([
                'Tasks'
            ])
            ->firstOrFail();
    }
}
