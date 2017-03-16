<?php
namespace Psa\ElasticIndex\Shell;

use Cake\Console\Shell;
use Cake\Core\Configure;
use Cake\Datasource\ConnectionManager;
use Cake\ElasticSearch\TypeRegistry;
use Cake\ORM\TableRegistry;

class ElasticIndexShell extends Shell {

    /**
     * Indexable tables
     *
     * @var array
     */
    protected $_indexableTables = [];

    /**
     * Counter
     *
     * @var int
     */
    protected $_counter = 0;

    /**
     * {@inheritdoc}
     */
    public function initialize()
    {
        parent::initialize();
        $this->_indexableTables = (array)Configure::read('ElasticIndex.indexableTables');
    }

    /**
     * Main entry point
     *
     * @return void
     */
    public function main()
    {
        $this->chooseModel();
    }

    /**
     * Let the user choose a model to index.
     *
     * @return void
     */
    public function chooseModel()
    {
        if (empty($this->_indexableTables)) {
            $this->out(__d('elastic_index', 'No tables that could be indexed were found.'));
            return;
        }

        $this->out(__d('elastic_index', 'Choose a model to index:'));
        $this->nl(2);

        foreach ($this->_indexableTables as $key => $table) {
            $this->out('[' . $key . ']' . ' ' . $table);
        }

        $selection = $this->in(__d('elastic_index', 'Choose the table you want to index:'));
        $this->params['table'] = $this->_indexableTables[$selection];

        $this->build();
    }

    /**
     * Gets a table object of a table to index.
     *
     * If the table doesn't have the behavior loaded it will be loaded.
     *
     * @param string $tableName
     * @return \Cake\ORM\Table
     */
    protected function _getTable($tableName = null)
    {
        if ($tableName === null) {
            $tableName = $this->param('table');
        }

        if (empty($tableName)) {
            $this->abort('No --table option provided!');
        }

        try {
            $table = TableRegistry::get($tableName);
            if (!in_array('ElasticIndex', $table->behaviors()->loaded())) {
                $table->addBehavior('Psa/ElasticIndex.ElasticIndex');
            }

            return $table;
        } catch (\Exception $e) {
            $this->printException($e);
        }
    }

    /**
     * Gets table name(s) from the input
     *
     * @return array
     */
    protected function _getTablesFromInput()
    {
        $table = $this->param('table');
        if (empty($table)) {
            return $this->_indexableTables;
        }

        return explode(',', $table);
    }

    /**
     * (Re-)Builds the whole index for a given table.
     *
     * @return void
     */
    public function build()
    {
        $tables = $this->_getTablesFromInput();
        foreach ($tables as $table) {
            $this->out(__d('elastic_index', 'Start building index for table "{0}".', [$table]));
            $this->_buildIndex($table);
            $this->out(__d('elastic_index', 'Finished building index for table "{0}".', [$table]));
        }

        $this->out(__d('elastic_index', 'Done indexing.'));
    }

    /**
     * The actual index building method that iterates over the table data.
     *
     * @param string $tableName
     * @return void
     */
    protected function _buildIndex($tableName)
    {
        $table = $this->_getTable($tableName);

        $query = $table->find();
        if ($table->hasFinder('buildIndex')) {
            $query->find('buildIndex');
        }

        $total = $query->all()->count();
        $offset = $this->param('offset');
        $limit = $this->param('limit');

        if ($offset > $total) {
            $this->abort(sprintf('Offset (%d) is bigger than the total number (%d) of records', $offset, $total));
        }

        if ($offset > 0) {
            $this->out(sprintf('Going to process %d records of %d.', $total - $offset, $total));
        } else {
            $this->out(sprintf('Going to process %d records.', $total));
        }

        $this->helper('progress')->output([
            'total' => $total,
            'callback' => function ($progress) use ($total, $table, $offset, $limit) {
                while ($offset <= $total) {
                    $this->_process($table, $offset, $limit);
                    $offset = $offset + $limit;
                    $progress->increment($limit);
                    $progress->draw();
                }

                return;
            }
        ]);
    }

    /**
     * Processes the records.
     *
     * @param \Cake\ORM\Table $table
     * @param int $offset
     * @param int $limit
     * @return void
     */
    protected function _process($table, $offset, $limit, $bulk = false)
    {
        $query = $table->find();
        if ($table->hasFinder('buildIndex')) {
            $query->find('buildIndex');
        }

        $results = $query
            ->offset($offset)
            ->limit($limit)
            ->orderDesc($table->aliasField($table->primaryKey()))
            ->all();

        if (empty($results)) {
            return;
        }

        if ($bulk === true) {
            try {
                $table->saveIndexDocuments($results);
                $offset = $offset + $limit;
                $this->_counter = $this->_counter + $limit;
                $stop = $this->param('stop');

                if ($stop && $this->_counter >= $stop) {
                    $this->info(sprintf('Stopped after %d records.', $stop), 0);
                    exit(0);
                }
            } catch (\Exception $e) {
                $this->printException($e);
            }
        } else {
            foreach ($results as $result) {
                try {
                    $table->saveIndexDocument($result);
                    $offset++;
                    $this->_counter++;
                    $stop = $this->param('stop');

                    if ($stop && $this->_counter >= $stop) {
                        $this->info(sprintf('Stopped after %d records.', $stop), 0);
                        exit(0);
                    }
                } catch (\Exception $e) {
                    $this->printException($e);
                }
            }
        }
    }

    /**
     * Updates a single document in the index.
     */
    public function updateDocument()
    {
        $table = $this->_getTable();
        if (!isset($this->args[0])) {
            $this->abort('No id passed');
        }

        if ($table->behaviors()->hasMethod('getIndexData')
            || method_exists($this, 'getIndexData'))
        {
            $entity = $table->getIndexData($this->args[0]);
        } else {
            $entity = $table->get($this->args[0]);
        }

        if ($table->saveIndexDocument($entity)) {
            $this->success('Updated');
        }
    }

    /**
     * Deletes a single document in the index.
     */
    public function deleteDocument()
    {
        $table = $this->_getTable();
        if (isset($this->args[0])) {
            $this->abort('No id passed');
        }

        $entity = $table->get($this->args[0]);
        $table->removeIndexDocument($entity);
    }

    /**
     * Gets an index from the active connection.
     *
     * @return \Elastica\Index
     */
    protected function _getIndex()
    {
        return ConnectionManager::get($this->param('connection'))
            ->getIndex($this->param('index'));
    }

    /**
   	 * {@inheritDoc}
   	 */
   	public function getOptionParser()
    {
        $parser = parent::getOptionParser();
        $parser->addArgument('build', [
            'help' => 'Builds the index.'
        ])->addArgument('createIndex', [
            'help' => 'Creates an index.'
        ])->addArgument('dropIndex', [
            'help' => 'Drops an index.'
        ])->addArgument('dropType', [
            'help' => 'Drops a type.'
        ])->addOption('connection', [
            'short' => 'c',
            'help' => __d('elastic_index', 'The connection you want to use.'),
            'default' => 'elastic'
        ])->addOption('index', [
            'short' => 'i',
            'help' => __d('elastic_index', 'The index you want to use.'),
            'default' => ''
        ])->addOption('offset', [
            'short' => 'o',
            'help' => __d('elastic_index', 'Offset to start at.'),
            'default' => 0
        ])->addOption('stop', [
            'short' => 's',
            'help' => __d('elastic_index', 'Stop after X records'),
            'default' => false
        ])->addOption('limit', [
            'short' => 'l',
            'help' => __d('elastic_index', 'Limit.'),
            'default' => 50
        ])->addOption('table', [
            'short' => 't',
            'help' => __d('elastic_index', 'The table you want to use.'),
            'default' => null
        ]);

        return $parser;
    }

    /**
     * Mapping
     *
     * @return void
     */
    public function mapping()
    {
        if (empty($this->args[0])) {
            $this->err(__d('elastic_index', 'No type given!'));
        }

        $typeClass = TypeRegistry::get($this->args[0]);
        if (!method_Exists($typeClass, 'applyMapping')) {
            $this->abort(__d('elastic_index', 'Type `{0}` doesn\'t have a method applyMapping() implemented!', get_class($typeClass)));
        }

        $typeClass->applyMapping();

        $this->out(__d('elastic_index', 'Schema mapping for `{0}` created.', [$this->args[0]]));
    }

    /**
     * Drops an index.
     *
     * @return void
     */
    public function dropIndex()
    {
        $elasticaIndex = $this->_getIndex();
        if ($elasticaIndex->exists()) {
            $elasticaIndex->delete();
            $this->out(__d('elastic_index', 'Index `{0}` dropped.', [$this->param('index')]));
            return;
        }

        $this->abort(__d('elastic_index', 'Index `{0}` does not exist.', [$this->param('index')]));
    }

    /**
     * Drops an index.
     *
     * @return void$
     */
    public function dropType()
    {
        $elasticaIndex = $this->_getIndex();
        if ($elasticaIndex->exists()) {
            $elasticaIndex->delete();
            $this->out(__d('elastic_index', 'Index `{0}` dropped.', [$this->param('index')]));

            return;
        }

        $this->abort(__d('elastic_index', 'Index `{0}` does not exist.', [$this->param('index')]));
    }

    /**
     * Creates a new index
     *
     * @return void
     */
    public function createIndex()
    {
        $elasticaIndex = $this->_getIndex();
        if ($elasticaIndex->exists()) {
            $this->abort(__d('elastic_index', 'Index `{0}` already exist.', [$this->param('index')]));
        }

        $elasticaIndex->create();
        $this->out(__d('elastic_index', 'Index `{0}` created.', [$this->param('index')]));
    }

    /**
   	 * Prints an exception in a pretty way
   	 *
   	 * @param \Exception $e
   	 * @param bool $quit Quit or not?
   	 * @return void
   	 */
    public function printException(\Exception $e, $quit = true)
    {
        if ($this->param('verbose') === true) {
            $this->out($e->getTraceAsString());
        }
        $this->err('<error>Error:</error> L' . $e->getLine() . ' ' . $e->getFile());
        if ($quit === true) {
            $this->err($e->getMessage());
        }
        $this->err('<error>Error: ' . $e->getMessage() . '</error>');
        $this->abort('');
    }
}
