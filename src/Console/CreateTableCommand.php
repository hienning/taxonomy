<?php namespace Hienning\Taxonomy\Console;


use Hienning\Taxonomy\Model;

use Illuminate\Console\Command;
use Illuminate\Database\Schema\Blueprint;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;


/**
 * Artisan command to create table for taxonomy.
 *
 * Class CreateTableCommand
 * @package Hienning\Taxonomy\Commands
 */
class CreateTableCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'taxonomy:create-table';


   	/**
   	 * The console command description.
   	 *
   	 * @var string
   	 */
   	protected $description = 'Create a table for taxonomy.';



	/**
	 * Create a new command instance.
	 *
	 * @return void
	 */
	public function __construct()
	{
		parent::__construct();
	}



    /**
   	 * Get the console command arguments.
   	 *
   	 * @return array
   	 */
   	protected function getArguments()
   	{
   		return [
   			['name', InputArgument::OPTIONAL, 'The name of the table to be created.'],
   		];
   	}



	/**
	 * Execute the command.
	 *
	 * @return void
	 */
	public function handle()
	{
		$name = trim($this->argument('name'));

        if (empty($name)) {
            $name = 'taxonomy';
        }

        \Schema::create($name, function(Blueprint $table) {
            $table->unsignedInteger('id', true);

            $table->string('name', 50)
                  ->comment = 'Name for the term/vocabulary.';

            $table->string('code', 50)
                  ->comment = 'The unique code to identified the term/vocabulary.';

            $table->tinyInteger('depth', false, true)
                  ->comment = 'Depth of the current level of hierarchy.';

            $table->unsignedInteger('left')
                  ->comment = 'Left tree.';

            $table->unsignedInteger('right')
                  ->comment = 'Right tree.';

            $table->timestamp('created_at')
                  ->default(\DB::raw('CURRENT_TIMESTAMP'))
                  ->comment = 'Creation time.';

            $table->timestamp('updated_at')
                  ->default(\DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'))
                  ->comment = 'Modification time';
        });


        Model::root();
	}
}
