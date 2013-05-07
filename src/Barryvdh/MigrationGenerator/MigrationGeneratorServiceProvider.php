<?php namespace Barryvdh\MigrationGenerator;

use Way\Generators\Commands;
use Way\Generators\Generators;
use Way\Generators\Cache;
use Illuminate\Support\ServiceProvider;

class MigrationGeneratorServiceProvider extends ServiceProvider {

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->package('barryvdh/laravel-migration-generator');
    }

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
        $this->app['command.migration-generator'] = $this->app->share(function($app)
        {
            $cache = new Cache($app['files']);
            $generator = new Generators\MigrationGenerator($app['files'], $cache);

            return new MigrationGeneratorCommand($generator);
        });
        $this->commands('command.migration-generator');
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
        return array('command.migration-generator');
	}

}
