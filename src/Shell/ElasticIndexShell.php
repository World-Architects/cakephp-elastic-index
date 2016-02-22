<?php
namespace Psa\ElasticIndex\Shell;

use Cake\Core\Configure;
use Cake\Console\Shell;
use Cake\ORM\TableRegistry;

class ElasticIndexShell extends Shell {

    /**
     * Indexable tables
     *
     * @var array
     */
   	protected $_indexableTables = [];

    public function initialize()
    {
        parent::initialize();
        $this->_indexableTables = (array)Configure::read('ElasticIndex.indexableTables');
    }

    public function main()
    {
        $this->chooseModel();
    }

    /**
     * Let the user choose a model to index.
     */
    public function chooseModel() {
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

    protected function _getTable($table)
    {
        $table = TableRegistry::get($table);
        if (!in_array('ElasticIndex', $table->behaviors()->loaded()))
        {
            $table->addBehavior('Psa/ElasticIndex.ElasticIndex');
        }
        return $table;
    }

    protected function _getTablesFromInput()
    {
        if (empty($this->params['table'])) {
            return $this->_indexableTables;
        }
        return explode(',', $this->params['table']);
    }

    /**
     * (Re-)Builds the whole index for a given table.
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

    protected function _buildIndex($tableName)
    {
        $table = TableRegistry::get($tableName);
        if (!$table->hasBehavior('ElasticIndex')) {
            $this->error(__d('elastic_index', 'Table `{0}` is not using the ElasticIndex behavior!', get_class($table)));
        }

        $query = $table->find();
        if ($table->hasFinder('buildIndex')) {
            $query->find('buildIndex');
        }

        $total = $query->all()->count();

        $this->helper('progress')->output([
            'total' => $total,
            'callback' => function ($progress) use ($total, $table) {
                $chunkSize = 25;
                $chunkCount = 0;
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
    protected function _process($table, $chunkCount, $chunkSize) {
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
     * @return
     */
    protected function _getIndex()
    {
        $connection = ConnectionManager::get($this->params['connection']);
        return $connection->getIndex($this->params['index']);
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
        ])->addOption('connection', [
            'short' => 'c',
            'help' => __d('elastic_index', 'The connection you want to use.'),
            'default' => 'elastic'
        ])->addOption('index', [
            'short' => 'i',
            'help' => __d('elastic_index', 'The index you want to use.'),
            'default' => ''
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
            $this->error('No type given!');
        }

        $typeClass = TypeRegistry::get($this->args[0]);
        if (!method_Exists($typeClass, 'applyMapping')) {
            $this->error(__d('elastic_index', 'Type `{0}` doesn\'t have a method applyMapping() implemented!', get_class($typeClass)));
        }
        $typeClass->applyMapping();

        $this->out('Schema mapping for "' . $this->args[0] . '"" created.');
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
            $this->error($e->getMessage());
        }
        $this->err('<error>Error: ' . $e->getMessage() . '</error>');
    }
}
