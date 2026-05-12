<?php

namespace Kaiserkiwi\ModelSettings\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Kaiserkiwi\ModelSettings\ServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
	protected function getPackageProviders($app): array
	{
		return [ServiceProvider::class];
	}

	protected function getEnvironmentSetUp($app): void
	{
		$app['config']->set('database.default', 'testing');
		$app['config']->set('database.connections.testing', [
			'driver' => 'sqlite',
			'database' => ':memory:',
			'prefix' => '',
		]);
	}

	protected function defineDatabaseMigrations(): void
	{
		Schema::create('test_users', function (Blueprint $table) {
			$table->id();
			$table->timestamps();
		});

		Schema::create(config('model_settings.table', 'model_settings'), function (Blueprint $table) {
			$table->id();
			$table->morphs('settingable');
			$table->string('key');
			$table->json('value')->nullable();
			$table->timestamps();

			$table->unique(['settingable_type', 'settingable_id', 'key']);
		});
	}
}
