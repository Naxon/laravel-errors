<?php

namespace Naxon\Errors;

use Illuminate\Support\ServiceProvider;

class ErrorsServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
           __DIR__.'/../config/errors.php' => config_path('errors.php'),
        ], 'config');
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->registerLoader();

        $this->app->singleton('errorLoader', function ($app) {
            $loader = $app['errors.loader'];

            $locale = $app['config']['app.locale'];

            $ErrorLoader = new ErrorLoader($loader, $locale);

            $ErrorLoader->setFallback($app['config']['app.fallback_locale']);

            return $ErrorLoader;
        });
    }

    /**
     * Register the error line loader.
     *
     * @return void
     */
    protected function registerLoader(): void
    {
        $this->app->singleton('errors.loader', function ($app) {
            return new FileLoader($app['files'], $app['config']['errors.path']);
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['errorLoader', 'errors.loader'];
    }
}
