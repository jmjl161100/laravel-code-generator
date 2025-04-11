<?php

namespace Jmjl161100\LaravelCodeGenerator;

use Illuminate\Support\ServiceProvider;
use Jmjl161100\LaravelCodeGenerator\Commands\GenerateCodeCommand;
use Jmjl161100\LaravelCodeGenerator\Services\CodeGeneratorService;

class CodeGeneratorServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                GenerateCodeCommand::class,
            ]);

            $this->publishes([
                __DIR__.'/../config/codegenerator.php' => config_path('codegenerator.php'),
            ], 'codegenerator-config');

            $this->publishes([
                __DIR__.'/Stubs' => base_path('stubs/codegenerator'),
            ], 'codegenerator-stubs');
        }
    }

    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/codegenerator.php', 'codegenerator'
        );

        $this->app->singleton('codegenerator', function ($app) {
            return new CodeGeneratorService;
        });
    }
}
