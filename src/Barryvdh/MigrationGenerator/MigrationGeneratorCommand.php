<?php namespace Barryvdh\MigrationGenerator;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Way\Generators\Generators\MigrationGenerator;

class MigrationGeneratorCommand extends Command
{


    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'migration-generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate migrations from an existing table';

    /**
     * Way's Generator
     *
     * @var \Way\Generators\Generators\MigrationGenerator
     */
    protected $generator;

    /**
     * Maps possible field types to matching Doctrine types
     * @var array
     */
    protected $registerTypes = array();

    /**
     * Maps database field types to alternate types
     * @var array
     */
    protected $fieldTypeMap = array();

    public function __construct(MigrationGenerator $generator, $config = array())
    {
        parent::__construct();

        $this->generator = $generator;

        foreach($config as $key=>$value){
            if (property_exists($this, $key)){
                $this->$key = $value;
            }
        }
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        $tables = explode(',', $this->argument('tables'));
        $prefix = \DB::getTablePrefix();

        foreach($tables as $table){
            $name = 'create_'.$table.'_table';
            $fields = $this->detectColumns($prefix.$table);
            $path =  $this->option('path') . '/' .$name.'.php';

            $created = $this->generator
                ->parse($name, $fields)
                ->make($path, null);

            if($created){
                $this->info("Created migration $path");
            }else{
                $this->error("Could not create $path with: $fields");
            }
        }
    }

    public function detectColumns($table){

        $fields = array();

        $schema = \DB::getDoctrineSchemaManager($table);

        foreach ($this->registerTypes as $convertFrom=>$convertTo) {
            $schema->getDatabasePlatform()->registerDoctrineTypeMapping($convertFrom, $convertTo);
        }

        $indexes = $schema->listTableIndexes($table);
        foreach ($indexes as $index) {
            if($index->isUnique()){
                $unique[$index->getName()] = true;
            }
        }

        $columns = $schema->listTableColumns($table);

        if($columns){
            foreach ($columns as $column) {
                $name = $column->getName();
                $type =  $column->getType()->getName();
                $length = $column->getLength();
                $default = $column->getDefault();
                if(isset($this->fieldTypeMap[$type])) {
                    $type = $this->fieldTypeMap[$type];
                }
                if(!in_array($name, array('id', 'created_at', 'updated_at'))){
                    $field = "$name:$type";
                    if($length){
                       $field .= "[$length]";
                    }
                    if(!$column->getNotNull()){
                        $field .= ':nullable';
                    }
                    if(isset($unique[$name])){
                        $field .= ':unique';
                    }
                    $fields[] = $field;
                }
            }
        }
        return implode(', ', $fields);
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return array(
            array('tables', InputArgument::REQUIRED, 'The table name')

        );
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return array(
            array('path', null, InputOption::VALUE_OPTIONAL, 'The path to store the migration', 'app/database/migrations')
        );
    }


}
