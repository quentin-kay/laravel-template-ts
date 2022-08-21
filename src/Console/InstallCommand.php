<?php

namespace QuentinKay\LaravelTemplateTs\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Process\Process;

class InstallCommand extends Command
{
    protected $signature = 'laravel-template-ts:install';
    protected $description = 'Install laravel template.';

    public function handle()
    {
        // NPM Packages...
        $this->info('Add npm deps..');
        $this->updateNodePackages(function ($packages) {
            return [
                    '@vitejs/plugin-vue' => '^3.0.0',
                    '@inertiajs/inertia' => '^0.11.0',
                    '@inertiajs/inertia-vue3' => '^0.6.0',
                    '@inertiajs/progress' => '^0.2.0',
                    'vue' => '^3.2.0',
                    'vue-axios' => '^3.4.1',
                    'vue-tsc' => '^0.40.1',
                    'sass' => '^1.54.0',
                    'vite-plugin-checker' => '^0.4.9',
                    'typescript' => '^4.7.4',
                    '@types/lodash' => '^4.14.184',
                    '@types/node' => '^18.7.8',
                    '@types/ziggy-js' => '^1.3.2',
                    '@vuedx/typescript-plugin-vue' => '^0.7.5',
                ] + $packages;
        });

        // Middleware...
        $this->info('Install Middlewares..');
        $this->installMiddlewareAfter('SubstituteBindings::class', '\App\Http\Middleware\HandleInertiaRequests::class');
        (new Filesystem)->copy(__DIR__.'/../../stubs/app/Http/Middleware/HandleInertiaRequests.php', app_path('Http/Middleware/HandleInertiaRequests.php'));

        // Resources..
        $this->info('Install Resources..');

        // CSS + SASS..
        (new Filesystem)->delete(resource_path('css/app.css'));
        (new Filesystem)->ensureDirectoryExists(resource_path('scss'));
        (new Filesystem)->copy(__DIR__.'/../../stubs/resources/scss/app.scss', resource_path('scss/app.scss'));

        // Views..
        (new Filesystem)->delete(resource_path('views/welcome.blade.php'));
        (new Filesystem)->copy(__DIR__.'/../../stubs/resources/views/app.blade.php', resource_path('views/app.blade.php'));

        // TS + Vue Pages..
        (new Filesystem)->delete(resource_path('js/app.js'));
        (new Filesystem)->delete(resource_path('js/bootstrap.js'));
        (new Filesystem)->ensureDirectoryExists(resource_path('ts'));
        (new Filesystem)->copy(__DIR__.'/../../stubs/resources/ts/app.ts', resource_path('ts/app.ts'));
        (new Filesystem)->ensureDirectoryExists(resource_path('ts/Pages'));
        (new Filesystem)->copyDirectory(__DIR__.'/../../stubs/resources/ts/Pages', resource_path('ts/Pages'));

        // Routes..
        $this->info('Install Routes..');
        (new Filesystem)->copy(__DIR__.'/../../stubs/routes/web.php', base_path('routes/web.php'));

        // Vite config..
        $this->info('Install Vite Config..');
        (new Filesystem)->delete(base_path('vite.config.js'));
        (new Filesystem)->copy(__DIR__.'/../../stubs/vite.config.ts', base_path('vite.config.ts'));

        // TS config..
        $this->info('Install TS Config..');
        (new Filesystem)->copy(__DIR__.'/../../stubs/tsconfig.json', base_path('tsconfig.json'));

        $this->info('Run npm install..');
        $this->runCommands(['npm install']);

        $this->info('Template installed successfully !!');
    }

    /**
     * Update the "package.json" file.
     *
     * @param  callable  $callback
     * @param bool $dev
     * @return void
     */
    protected static function updateNodePackages(callable $callback, bool $dev = true): void
    {
        if (! file_exists(base_path('package.json'))) {
            return;
        }

        $configurationKey = $dev ? 'devDependencies' : 'dependencies';

        $packages = json_decode(file_get_contents(base_path('package.json')), true);

        $packages[$configurationKey] = $callback(
            array_key_exists($configurationKey, $packages) ? $packages[$configurationKey] : [],
            $configurationKey
        );

        ksort($packages[$configurationKey]);

        file_put_contents(
            base_path('package.json'),
            json_encode($packages, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT).PHP_EOL
        );
    }

    /**
     * Install the middleware to a group in the application Http Kernel.
     *
     * @param string $after
     * @param string $name
     * @param string $group
     * @return void
     */
    protected function installMiddlewareAfter(string $after, string $name, string $group = 'web'): void
    {
        $httpKernel = file_get_contents(app_path('Http/Kernel.php'));

        $middlewareGroups = Str::before(Str::after($httpKernel, '$middlewareGroups = ['), '];');
        $middlewareGroup = Str::before(Str::after($middlewareGroups, "'$group' => ["), '],');

        if (! Str::contains($middlewareGroup, $name)) {
            $modifiedMiddlewareGroup = str_replace(
                $after.',',
                $after.','.PHP_EOL.'            '.$name.',',
                $middlewareGroup,
            );

            file_put_contents(app_path('Http/Kernel.php'), str_replace(
                $middlewareGroups,
                str_replace($middlewareGroup, $modifiedMiddlewareGroup, $middlewareGroups),
                $httpKernel
            ));
        }
    }

    /**
     * Run the given commands.
     *
     * @param array $commands
     * @return void
     */
    protected function runCommands(array $commands): void
    {
        $process = Process::fromShellCommandline(implode(' && ', $commands), null, null, null, null);

        if ('\\' !== DIRECTORY_SEPARATOR && file_exists('/dev/tty') && is_readable('/dev/tty')) {
            try {
                $process->setTty(true);
            } catch (RuntimeException $e) {
                $this->output->writeln('  <bg=yellow;fg=black> WARN </> '.$e->getMessage().PHP_EOL);
            }
        }

        $process->run(function ($type, $line) {
            $this->output->write('    '.$line);
        });
    }
}
