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
    protected $registeredTypes = array(
        'enum' => 'string'
    );

    /**
     * Maps possible field types to matching field types
     * @var array
     */
    protected $fieldTypeMap = array(
        'guid' => 'string',
        'bigint' => 'integer',
        'littleint' => 'integer',
        'datetimetz' => 'datetime'
    );

    public function __construct(MigrationGenerator $generator)
    {
        parent::__construct();

        $this->generator = $generator;
    }


    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        $this->setRegisteredTypes($this->option('register-types'));
        $this->setFieldTypeMap($this->option('type-map'));

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

        foreach ($this->registeredTypes as $convertFrom=>$convertTo) {
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
     * Converts the register-types option values into a usable array
     * and merges it with our default values
     *
     * @param  string $typeList key:value,key2:value
     * @return void
     */
    protected function setRegisteredTypes($typeList)
    {
        //Turns a key:value,key2:value string into key=value&key2=value string
        //so it can be parsed like a query string
        parse_str(str_replace(",", "&", str_replace(':','=',$typeList)), $types);
        $this->registeredTypes = array_merge($this->registeredTypes,$types);
    }

    /**
     * Converts the type-map option values into a usable array
     * and merges it with our default values
     *
     * @param  string $typeList key:value,key2:value
     * @return void
     */
    protected function setFieldTypeMap($typeList)
    {
        //Turns a key:value,key2:value string into key=value&key2=value string
        //so it can be parsed like a query string
        parse_str(str_replace(",", "&", str_replace(':','=',$typeList)), $types);
        $this->fieldTypeMap = array_merge($this->fieldTypeMap,$types);
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
            array('path', null, InputOption::VALUE_OPTIONAL, 'The path to store the migration', 'app/database/migrations'),
            array('register-types', null, InputOption::VALUE_OPTIONAL, 'Additional Doctrine type mappings'),
            array('type-map', null, InputOption::VALUE_OPTIONAL, 'Additional field type mappings')
        );
    }


}
