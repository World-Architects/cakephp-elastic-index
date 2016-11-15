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
        $selection = $this->in('Choose the table you want to index:');
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
    protected function _getTable($tableName)
    {
        $table = TableRegistry::get($tableName);
        if (!in_array('ElasticIndex', $table->behaviors()->loaded()))
        {
            $table->addBehavior('Psa/ElasticIndex.ElasticIndex');
        }
        return $table;
    }

    /**
     * Gets table name(s) from the input
     *
     * @return array
     */
    protected function _getTablesFromInput()
    {
        if (empty($this->params['table'])) {
            return $this->_indexableTables;
        }
        return explode(',', $this->params['table']);
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
            $this->out(sprintf('Start building index for table "%s".', $table));
            $this->_buildIndex($table);
            $this->out(sprintf('Finished building index for table "%s".', $table));
        }
        $this->out('Done indexing.');
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
        $offset = $chunkCount = $this->param('offset');
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
            'callback' => function ($progress) use ($total, $table, $chunkCount) {
                $chunkSize = $this->param('chunkSize');
                while ($chunkCount <= $total) {
                    $this->_process($table, $chunkCount, $chunkSize);
                    $chunkCount = $chunkCount + $chunkSize;
                    $progress->increment($chunkSize);
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
     * @param int $chunkCount
     * @param int $chunkSize
     * @return void
     */
    protected function _process($table, $chunkCount, $chunkSize)
    {
        $query = $table->find();
        if ($table->hasFinder('buildIndex')) {
            $query->find('buildIndex');
        }

        $results = $query
            ->offset($chunkCount)
            ->limit($chunkSize)
            ->orderDesc($table->aliasField($table->primaryKey()))
            ->all();

        if (empty($results)) {
            return;
        }

        foreach ($results as $result) {
            try {
                $table->saveIndexDocument($result);
                $chunkCount++;
            } catch (\Exception $e) {
                $this->printException($e);
            }
        }
    }

    /**
     * Updates a single document in the index.
     */
    public function updateDocument()
    {
        $table = $this->_getTable();
        $entity = $table->get($this->args[0]);
        $table->saveIndexDocument($entity);
    }

    /**
     * Deletes a single document in the index.
     */
    public function deleteDocument()
    {
        $table = $this->_getTable();
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
        return ConnectionManager::get($this->params['connection'])
            ->getIndex($this->params['index']);
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
        ])->addOption('chunkSize', [
            'short' => 's',
            'help' => __d('elastic_index', 'Chunk size.'),
            'default' => 50
        ])->addOption('offset', [
            'short' => 'o',
            'help' => __d('elastic_index', 'Offset to start at.'),
            'default' => 0
        ])->addOption('stop', [
            'short' => 's',
            'help' => __d('elastic_index', 'Stop after X records'),
            'default' => null
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
            $this->err('No type given!');
        }

        $typeClass = TypeRegistry::get($this->args[0]);
        if (!method_Exists($typeClass, 'applyMapping')) {
            $this->abort(__d('elastic_index', 'Type `{0}` doesn\'t have a method applyMapping() implemented!', get_class($typeClass)));
        }
        $typeClass->applyMapping();

        $this->out('Schema mapping for "' . $this->args[0] . '"" created.');
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
            $this->out('Index "' . $this->params['index'] . '" dropped.');
            return;
        }
        $this->abort('Index "' . $this->params['index'] . '" does not exist.');
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
            $this->out('Index "' . $this->params['index'] . '" dropped.');
            return;
        }
        $this->abort('Index "' . $this->params['index'] . '" does not exist.');
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
            $this->abort('Index ' . $this->params['index'] . ' already exist.');
        }
        $elasticaIndex->create();
        $this->out('Index "' . $this->params['index'] . '" created.');
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
    }
}
