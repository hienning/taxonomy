<?php namespace Hienning\Taxonomy\Commands;


use Illuminate\Console\Command;
use Illuminate\Contracts\Bus\SelfHandling;


class CreateTableCommand extends Command implements SelfHandling {


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
		//
	}

	/**
	 * Execute the command.
	 *
	 * @return void
	 */
	public function handle()
	{
		//
	}

}
