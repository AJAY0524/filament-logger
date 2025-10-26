<?php

namespace AJAY0524\FilamentLogger;

use Filament\Facades\Filament;
use Filament\Panel;
use Illuminate\Auth\Events\Login;
use Illuminate\Notifications\Events\NotificationFailed;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Support\Facades\Event;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FilamentLoggerServiceProvider extends PackageServiceProvider
{
    public static string $name = 'filament-logger';

    protected function getResources(): array
    {
        return array_filter([
            config('filament-logger.activity_resource'),
        ]);
    }

    public function configurePackage(Package $package): void
    {
        $package->name(static::$name)
            ->hasTranslations()
            ->hasConfigFile()
            ->hasInstallCommand(function (InstallCommand $installCommand) {
                $installCommand
                    ->publishConfigFile()
                    ->askToStarRepoOnGitHub('ajay0524/filament-logger')
                    ->startWith(function (InstallCommand $installCommand) {
                        $installCommand->call('vendor:publish', [
                            '--provider' => "Spatie\Activitylog\ActivitylogServiceProvider",
                            '--tag' => "activitylog-migrations"
                        ]);
                    });
            });
    }

    public function bootingPackage(): void
    {
        parent::bootingPackage();

        if (config('filament-logger.access.enabled')) {
            Event::listen(Login::class, config('filament-logger.access.logger'));
        }

        if (config('filament-logger.notifications.enabled')) {
            Event::listen(NotificationSent::class, config('filament-logger.notifications.logger'));
            Event::listen(NotificationFailed::class, config('filament-logger.notifications.logger'));
        }
    }

    public function packageBooted(): void
    {
        parent::packageBooted();

        if (config('filament-logger.resources.enabled', true)) {
            $exceptResources = [...config('filament-logger.resources.exclude'), config('filament-logger.activity_resource')];

            $loggableResources = collect(Filament::getPanels())
                ->flatMap(fn (Panel $panel) => $panel->getResources())
                ->unique()
                ->filter(fn (string $resource): bool => ! in_array($resource, $exceptResources, true));

            foreach ($loggableResources as $resource) {
                $resource::getModel()::observe(config('filament-logger.resources.logger'));
            }
        }

        if (config('filament-logger.models.enabled', true)) {
            $models = array_filter(config('filament-logger.models.register', []));
            $observer = config('filament-logger.models.logger');

            if ($observer) {
                foreach ($models as $model) {
                    $model::observe($observer);
                }
            }
        }
    }
}
