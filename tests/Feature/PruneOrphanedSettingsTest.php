<?php

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Kaiserkiwi\ModelSettings\Tests\Models\TestGuest;
use Kaiserkiwi\ModelSettings\Tests\Models\TestThing;
use Kaiserkiwi\ModelSettings\Tests\Models\TestUser;

/**
 * Write a settings row directly, so an owner id that does not exist can be used.
 */
function settingRow(string $type, int $id, string $key = 'theme', ?string $table = null): int
{
	return DB::table($table ?? config('model_settings.table', 'model_settings'))->insertGetId([
		'settingable_type' => $type,
		'settingable_id' => $id,
		'key' => $key,
		'value' => json_encode('dark'),
		'created_at' => now(),
		'updated_at' => now(),
	]);
}

function settingExists(int $id, ?string $table = null): bool
{
	return DB::table($table ?? config('model_settings.table', 'model_settings'))
		->where('id', $id)
		->exists();
}

it('deletes settings whose owner no longer exists', function () {
	$orphan = settingRow(TestUser::class, 999);

	$this->artisan('model-settings:prune')->assertSuccessful();

	expect(settingExists($orphan))->toBeFalse();
});

it('keeps settings whose owner still exists', function () {
	$user = TestUser::create();
	$user->setSetting('theme', 'dark');

	$this->artisan('model-settings:prune')->assertSuccessful();

	expect($user->getSetting('theme'))->toBe('dark');
});

it('reports without deleting when pretending', function () {
	$orphan = settingRow(TestUser::class, 999);

	$this->artisan('model-settings:prune', ['--pretend' => true])->assertSuccessful();

	expect(settingExists($orphan))->toBeTrue();
});

// A type this installation cannot resolve may belong to a feature that is not deployed
// yet, so its settings have to survive rather than be guessed away.
it('keeps settings whose type cannot be resolved', function () {
	$unknown = settingRow('App\Models\DoesNotExist', 1);

	$this->artisan('model-settings:prune')->assertSuccessful();

	expect(settingExists($unknown))->toBeTrue();
});

it('resolves a type through the morph map', function () {
	Relation::enforceMorphMap(['test-user' => TestUser::class]);

	$user = TestUser::create();
	$living = settingRow('test-user', $user->id);
	$orphan = settingRow('test-user', 999);

	$this->artisan('model-settings:prune')->assertSuccessful();

	expect(settingExists($orphan))->toBeFalse()
		->and(settingExists($living))->toBeTrue();
});

// The owner's primary key is not necessarily called "id".
it('respects a custom primary key on the owner', function () {
	$thing = TestThing::create();
	$living = settingRow(TestThing::class, $thing->thing_id);
	$orphan = settingRow(TestThing::class, 999);

	$this->artisan('model-settings:prune')->assertSuccessful();

	expect(settingExists($orphan))->toBeFalse()
		->and(settingExists($living))->toBeTrue();
});

// The table name is configurable, so the command must not assume the default.
it('uses the configured settings table', function () {
	$default = settingRow(TestUser::class, 999);

	Schema::create('custom_settings', function (Blueprint $table) {
		$table->id();
		$table->morphs('settingable');
		$table->string('key');
		$table->json('value')->nullable();
		$table->timestamps();
	});

	config(['model_settings.table' => 'custom_settings']);
	$orphan = settingRow(TestUser::class, 999, table: 'custom_settings');

	$this->artisan('model-settings:prune')->assertSuccessful();

	expect(settingExists($orphan, 'custom_settings'))->toBeFalse()
		// Untouched, because it is not in the configured table.
		->and(settingExists($default, 'model_settings'))->toBeTrue();
});

// A single SQL statement cannot span two connections, so such owners are reported and
// left alone instead of being wrongly treated as gone.
it('skips owners living on another connection', function () {
	$orphan = settingRow(TestGuest::class, 999);

	$this->artisan('model-settings:prune')
		->expectsOutputToContain('another database connection')
		->assertSuccessful();

	expect(settingExists($orphan))->toBeTrue();
});
