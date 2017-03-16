<?php
namespace Psa\ElasticIndex\Model\Type;

use ArrayObject;
use Cake\Datasource\EntityInterface;
use Cake\ORM\RulesChecker;
use Elastica\Document as ElasticaDocument;

trait SaveManyTrait {

    /**
     * Persists an entity based on the fields that are marked as dirty and
     * returns the same entity after a successful save or false in case
     * of any error.
     * Triggers the `Model.beforeSave` and `Model.afterSave` events.
     * ## Options
     * - `checkRules` Defaults to true. Check deletion rules before deleting the record.
     *
     * @param array An array of entities
     * @param array $options An array of options to be used for the event
     * @return bool
     */
    public function saveMany($entities, $options = []) {
        $options += ['checkRules' => true];
        $options = new ArrayObject($options);

        $documents = [];

        foreach ($entities as $key => $entity) {
            if (!$entity instanceof EntityInterface) {
                throw new RuntimeException(sprintf(
                    'Invalid items in the list. Found `%s` but expected `%s`',
                    is_object($entity) ? get_class($entity) : gettype($entity),
                    EntityInterface::class
                ));
            }

            $this->dispatchEvent('Model.beforeSave', [
                'entity' => $entity,
                'options' => $options
            ]);

            if ($entity->errors()) {
                return false;
            }

            $mode = $entity->isNew() ? RulesChecker::CREATE : RulesChecker::UPDATE;
            if ($options['checkRules'] && !$this->checkRules($entity, $mode, $options)) {
                return false;
            }

            $id = $entity->id ?: null;

            $data = $entity->toArray();
            unset($data['id'], $data['_version']);

            $doc = new ElasticaDocument($id, $data);
            $doc->setAutoPopulate(true);

            $documents[$key] = $doc;
        }

        $type = $this->connection()->getIndex()->getType($this->name());
        $type->addDocuments($documents);

        foreach ($documents as $key => $document) {
            $entities[$key]->id = $doc->getId();
            $entities[$key]->_version = $doc->getVersion();
            $entities[$key]->isNew(false);
            $entities[$key]->source($this->name());
            $entities[$key]->clean();

            $this->dispatchEvent('Model.afterSave', [
                'entity' => $entities[$key],
                'options' => $options
            ]);
        }

        return true;
    }
}
