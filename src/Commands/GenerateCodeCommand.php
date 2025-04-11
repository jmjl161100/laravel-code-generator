<?php

namespace Jmjl161100\LaravelCodeGenerator\Commands;

use Illuminate\Console\Command;
use Jmjl161100\LaravelCodeGenerator\Services\CodeGeneratorService;

class GenerateCodeCommand extends Command
{
    protected $signature = 'generate:code 
                            {name : The name of the generated component}
                            {--stub_id=default : The stub ID in config}
                            {--target= : The target directory}
                            {--f|force : Overwrite existing files}';

    protected $description = 'Generate code from stubs templates';

    public function handle(CodeGeneratorService $generator): void
    {
        $name = $this->argument('name');
        $stubId = $this->option('stub_id');
        $targetPath = $this->option('target');
        $force = $this->option('force');

        $replacements = [
            'name' => $name,
            'stub_id' => $stubId,
            'target_path' => $targetPath,
            'force' => $force,
        ];

        try {
            $generator->generateFromStubs($replacements, $this);
            $this->info('Code generated successfully!');
        } catch (\Exception $e) {
            $this->error('Error generating code: '.$e->getMessage());
        }
    }
}
