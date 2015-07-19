<?php namespace Hienning\Taxonomy;


class ServiceProvider extends \Illuminate\Support\ServiceProvider
{

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
//        $viewPath = __DIR__.'/../resources/views';
//        $this->loadViewsFrom($viewPath, 'taxonomy');
//
        $configPath = __DIR__ . '/../config/taxonomy.php';
        $this->publishes([$configPath => config_path('taxonomy.php')], 'config');
    }



    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $configPath = __DIR__ . '/../config/taxonomy.php';
        $this->mergeConfigFrom($configPath, 'taxonomy');

        $this->app['command.taxonomy.createtable'] = $this->app->share(
            function ($app) {
                return new Console\CreateTableCommand($app['config'], $app['files'], $app['view']);
            }
        );

        $this->commands('command.taxonomy.createtable');
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['command.taxonomy.createtable'];
    }

}
