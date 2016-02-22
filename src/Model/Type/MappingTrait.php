<?php
namespace Psa\ElasticIndex\Model\Type;

use Elastica\Type\Mapping as ElasticaMapping;
use Elastica\Type as ElasticaType;

/**
 * The mapping trait will allow you to add the mapping definition to type objects
 * and apply the mapping.
 */
trait MappingTrait {

    /**
     * The ES mapping definition
     *
     * @var array
     */
    public $mapping = [];

    /**
     * Use this method to define your mapping.
     *
     * @return array The mapping structure.
     */
    public function mapping()
    {
        return [];
    }

    /**
     * Applies the mapping to the current connection of the type object.
     *
     * @return void
     */
    public function applyMapping()
    {
        $connection = $this->connection();
        $index = $this->connection()->getConfigValue('index');;
        $elasticaIndex = $connection->getIndex($index);
        $this->_applyMapping(
            $elasticaIndex->getType($this->name()),
            $this->mapping()
        );
    }

    /**
     * This method will execute the mapping on the current connection.
     *
     * @param \Elastica\Type\Type $type
     * @param array $mapping
     * @return void
     */
    protected function _applyMapping(ElasticaType $type, array $mapping)
    {
        $elasticMapping = new ElasticaMapping();
        $elasticMapping->setType($type);
        $elasticMapping->setProperties($mapping);
        $elasticMapping->send();
    }
}
