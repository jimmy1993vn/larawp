<?php

namespace LaraPress\View;

use LaraPress\Support\ServiceProvider;
use LaraPress\View\Compilers\BladeCompiler;
use LaraPress\View\Engines\CompilerEngine;
use LaraPress\View\Engines\EngineResolver;
use LaraPress\View\Engines\FileEngine;
use LaraPress\View\Engines\PhpEngine;

class ViewServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerFactory();
        $this->registerViewFinder();
        $this->registerBladeCompiler();
        $this->registerEngineResolver();
    }

    /**
     * Register the view environment.
     *
     * @return void
     */
    public function registerFactory()
    {
        $this->app->singleton('view', function ($app) {
            // Next we need to grab the engine resolver instance that will be used by the
            // environment. The resolver will be used by an environment to get each of
            // the various engine implementations such as plain PHP or Blade engine.
            $resolver = $app['view.engine.resolver'];

            $finder = $app['view.finder'];

            $factory = $this->createFactory($resolver, $finder, $app['events']);

            // We will also set the container instance on this view environment since the
            // view composers may be classes registered in the container, which allows
            // for great testable, flexible composers for the application developer.
            $factory->setContainer($app);

            $factory->share('app', $app);

            return $factory;
        });
    }

    /**
     * Create a new Factory Instance.
     *
     * @param \LaraPress\View\Engines\EngineResolver $resolver
     * @param \LaraPress\View\ViewFinderInterface $finder
     * @param \LaraPress\Contracts\Events\Dispatcher $events
     * @return \LaraPress\View\Factory
     */
    protected function createFactory($resolver, $finder, $events)
    {
        return new Factory($resolver, $finder, $events);
    }

    /**
     * Register the view finder implementation.
     *
     * @return void
     */
    public function registerViewFinder()
    {
        $this->app->bind('view.finder', function ($app) {
            return new FileViewFinder($app['files'], $app['config']['view.paths']);
        });
    }

    /**
     * Register the Blade compiler implementation.
     *
     * @return void
     */
    public function registerBladeCompiler()
    {
        $this->app->singleton('blade.compiler', function ($app) {
            return lp_tap(new BladeCompiler($app['files'], $app['config']['view.compiled']), function ($blade) {
                $blade->component('dynamic-component', DynamicComponent::class);
            });
        });
    }

    /**
     * Register the engine resolver instance.
     *
     * @return void
     */
    public function registerEngineResolver()
    {
        $this->app->singleton('view.engine.resolver', function () {
            $resolver = new EngineResolver;

            // Next, we will register the various view engines with the resolver so that the
            // environment will resolve the engines needed for various views based on the
            // extension of view file. We call a method for each of the view's engines.
            foreach (['file', 'php', 'blade'] as $engine) {
                $this->{'register' . ucfirst($engine) . 'Engine'}($resolver);
            }

            return $resolver;
        });
    }

    /**
     * Register the file engine implementation.
     *
     * @param \LaraPress\View\Engines\EngineResolver $resolver
     * @return void
     */
    public function registerFileEngine($resolver)
    {
        $resolver->register('file', function () {
            return new FileEngine($this->app['files']);
        });
    }

    /**
     * Register the PHP engine implementation.
     *
     * @param \LaraPress\View\Engines\EngineResolver $resolver
     * @return void
     */
    public function registerPhpEngine($resolver)
    {
        $resolver->register('php', function () {
            return new PhpEngine($this->app['files']);
        });
    }

    /**
     * Register the Blade engine implementation.
     *
     * @param \LaraPress\View\Engines\EngineResolver $resolver
     * @return void
     */
    public function registerBladeEngine($resolver)
    {
        $resolver->register('blade', function () {
            return new CompilerEngine($this->app['blade.compiler'], $this->app['files']);
        });
    }
}