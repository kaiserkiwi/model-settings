<?php

namespace Kaiserkiwi\ModelSettings;

use Illuminate\Support\ServiceProvider as LaravelServiceProvider;
use Kaiserkiwi\ModelSettings\Commands\PruneOrphanedSettings;

class ServiceProvider extends LaravelServiceProvider
{
	public function register(): void
	{
		$this->mergeConfigFrom(__DIR__ . '/../config/model_settings.php', 'model_settings');
	}

	public function boot(): void
	{
		if ($this->app->runningInConsole()) {
			$this->commands([
				PruneOrphanedSettings::class,
			]);

			$this->publishes([
				__DIR__ . '/../config/model_settings.php' => config_path('model_settings.php'),
			], 'config');

			$this->publishes([
				__DIR__ . '/../database/migrations/create_model_settings_table.php.stub' => database_path('migrations/' . date('Y_m_d_His', time()) . '_create_model_settings_table.php'),
			], 'migrations');
		}
	}
}
