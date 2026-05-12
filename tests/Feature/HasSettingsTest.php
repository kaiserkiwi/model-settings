<?php

use Kaiserkiwi\ModelSettings\Models\ModelSettings;
use Kaiserkiwi\ModelSettings\Tests\Models\TestUser;

beforeEach(function () {
	$this->user = TestUser::create();
});

describe('getSetting', function () {
	it('returns null when setting is not set', function () {
		expect($this->user->getSetting('theme'))->toBeNull();
	});

	it('returns the default when setting is not set', function () {
		expect($this->user->getSetting('theme', 'light'))->toBe('light');
	});

	it('returns the stored value', function () {
		$this->user->setSetting('theme', 'dark');

		expect($this->user->getSetting('theme'))->toBe('dark');
	});

	it('does not persist the default when save_default is disabled', function () {
		config(['model_settings.save_default' => false]);

		$this->user->getSetting('theme', 'light');

		expect($this->user->settings()->firstWhere('key', 'theme'))->toBeNull();
	});

	it('persists the default to the database when save_default is enabled', function () {
		config(['model_settings.save_default' => true]);

		$value = $this->user->getSetting('theme', 'light');

		expect($value)->toBe('light')
			->and($this->user->settings()->firstWhere('key', 'theme'))->not->toBeNull()
			->and($this->user->getSetting('theme'))->toBe('light');
	});

	it('does not persist a null default even when save_default is enabled', function () {
		config(['model_settings.save_default' => true]);

		$this->user->getSetting('theme');

		expect($this->user->settings()->firstWhere('key', 'theme'))->toBeNull();
	});
});

describe('getSetting with save_default and caching', function () {
	beforeEach(function () {
		config([
			'model_settings.save_default' => true,
			'model_settings.caching.enabled' => true,
		]);
	});

	it('persists the default to the database and caches it', function () {
		$value = $this->user->getSetting('theme', 'light');

		expect($value)->toBe('light')
			->and($this->user->settings()->firstWhere('key', 'theme'))->not->toBeNull();

		// Delete from DB directly – cache must serve the value
		$this->user->settings()->where('key', 'theme')->delete();

		expect($this->user->getSetting('theme'))->toBe('light');
	});

	it('does not persist the default to the database when save_default is disabled', function () {
		config(['model_settings.save_default' => false]);

		$this->user->getSetting('theme', 'light');

		expect($this->user->settings()->firstWhere('key', 'theme'))->toBeNull();
	});

	it('persists and caches falsy defaults correctly', function ($default) {
		$value = $this->user->getSetting('flag', $default);

		expect($value)->toBe($default)
			->and($this->user->settings()->firstWhere('key', 'flag'))->not->toBeNull();

		// Delete from DB – cache must still serve the falsy value
		$this->user->settings()->where('key', 'flag')->delete();

		expect($this->user->getSetting('flag'))->toBe($default);
	})->with([
		'boolean false' => [false],
		'integer zero' => [0],
		'string zero' => ['0'],
	]);

	it('does not persist or cache a null default', function () {
		$this->user->getSetting('theme');

		expect($this->user->settings()->firstWhere('key', 'theme'))->toBeNull();
	});
});

describe('setSetting with caching', function () {
	beforeEach(function () {
		config(['model_settings.caching.enabled' => true]);
	});

	it('caches falsy-but-meaningful values so they survive a direct db delete', function ($value) {
		$this->user->setSetting('flag', $value);

		$this->user->settings()->where('key', 'flag')->delete();

		expect($this->user->getSetting('flag'))->toBe($value);
	})->with([
		'integer zero' => [0],
		'boolean false' => [false],
		'string zero' => ['0'],
		'empty string' => [''],
	]);
});

describe('setSetting', function () {
	it('creates a new setting', function () {
		$this->user->setSetting('theme', 'dark');

		expect($this->user->getSetting('theme'))->toBe('dark');
	});

	it('updates an existing setting', function () {
		$this->user->setSetting('theme', 'dark');
		$this->user->setSetting('theme', 'light');

		expect($this->user->getSetting('theme'))->toBe('light')
			->and($this->user->settings()->where('key', 'theme')->count())->toBe(1);
	});

	it('handles a concurrent insert race by falling back to update', function () {
		// Simulate Request A having already won the race and inserted the record
		ModelSettings::create([
			'settingable_type' => $this->user->getMorphClass(),
			'settingable_id' => $this->user->getKey(),
			'key' => 'theme',
			'value' => json_encode('dark'),
		]);

		// Request B calls setSetting – updateOrCreate would hit the unique constraint.
		// The catch block must fall back to update without throwing.
		$this->user->setSetting('theme', 'light');

		expect($this->user->getSetting('theme'))->toBe('light')
			->and($this->user->settings()->where('key', 'theme')->count())->toBe(1);
	});
});

describe('getSettings', function () {
	it('returns an empty collection when no settings exist', function () {
		expect($this->user->getSettings())->toHaveCount(0);
	});

	it('returns all settings', function () {
		$this->user->setSetting('theme', 'dark');
		$this->user->setSetting('language', 'de');

		expect($this->user->getSettings())->toHaveCount(2);
	});
});

describe('removeSetting', function () {
	it('deletes an existing setting', function () {
		$this->user->setSetting('theme', 'dark');
		$this->user->removeSetting('theme');

		expect($this->user->getSetting('theme'))->toBeNull();
	});

	it('does not throw when removing a non-existent setting', function () {
		$this->user->removeSetting('nonexistent');
	})->throwsNoExceptions();
});

describe('pushSetting', function () {
	it('creates an array when pushing to a non-existent setting', function () {
		$this->user->pushSetting('notifications', 'email');

		expect($this->user->getSetting('notifications'))->toBe(['email']);
	});

	it('appends a value to an existing array setting', function () {
		$this->user->setSetting('notifications', ['email']);
		$this->user->pushSetting('notifications', 'sms');

		expect($this->user->getSetting('notifications'))->toBe(['email', 'sms']);
	});

	it('throws a RuntimeException when pushing to a non-array setting', function () {
		$this->user->setSetting('theme', 'dark');
		$this->user->pushSetting('theme', 'light');
	})->throws(RuntimeException::class);

	it('converts a non-array setting to an array when force is true', function () {
		$this->user->setSetting('theme', 'dark');
		$this->user->pushSetting('theme', 'light', true);

		expect($this->user->getSetting('theme'))->toBe(['dark', 'light']);
	});
});

