<?php

namespace AJAY0524\FilamentLogger\Resources;

use AJAY0524\FilamentLogger\Resources\ActivityResource\Pages;
use AJAY0524\FilamentLogger\Resources\ActivityResource\Schemas\ActivityForm;
use AJAY0524\FilamentLogger\Resources\ActivityResource\Tables\ActivityTable;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Spatie\Activitylog\ActivitylogServiceProvider;
use Illuminate\Contracts\Support\Htmlable;
class ActivityResource extends Resource
{
    protected static ?string $label = 'Activity Log';

    protected static ?string $slug = 'activity-logs';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-list';

    public static function getCluster(): ?string
    {
        return config('filament-logger.resources.cluster');
    }

    public static function form(Schema $schema): Schema
    {
        return ActivityForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ActivityTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListActivities::route('/'),
            'view' => Pages\ViewActivity::route('/{record}'),
        ];
    }

    public static function getModel(): string
    {
        return ActivitylogServiceProvider::determineActivityModel();
    }

    public static function getLabel(): string
    {
        return __('filament-logger::filament-logger.resource.label.log');
    }

    public static function getPluralLabel(): string
    {
        return __('filament-logger::filament-logger.resource.label.logs');
    }

    public static function getNavigationGroup(): ?string
    {
        return __(config('filament-logger.resources.navigation_group', 'Settings'));
    }

    public static function getNavigationLabel(): string
    {
        return __('filament-logger::filament-logger.nav.log.label');
    }

    public static function getNavigationIcon(): BackedEnum | Htmlable | null | string
    {
        return __('filament-logger::filament-logger.nav.log.icon');
    }

    public static function isScopedToTenant(): bool
    {
        return config('filament-logger.scoped_to_tenant', true);
    }

    public static function getNavigationSort(): ?int
    {
        return config('filament-logger.navigation_sort', null);
    }
}
