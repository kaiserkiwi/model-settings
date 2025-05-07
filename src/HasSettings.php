<?php

namespace Kaiserkiwi\ModelSettings;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Kaiserkiwi\ModelSettings\Models\ModelSettings;
use RuntimeException;

trait HasSettings
{
	public function settings(): MorphMany
	{
		return $this->morphMany(ModelSettings::class, 'settingable');
	}

	/**
	 * Get all settings for the model.
	 */
	public function getSettings(): Collection
	{
		return $this->settings()->get();
	}

	/**
	 * Get a setting for the model by key.
	 *
	 * @param  string  $key  The key of the setting to retrieve.
	 * @param  mixed  $default  The default value to return if the setting is not found. (Default null)
	 */
	public function getSetting(string $key, mixed $default = null): mixed
	{
		$record = $this->settings()->firstWhere('key', $key);

		return $record ? $record->value : $default;
	}

	/**
	 * Set a setting for the model by key.
	 *
	 * @param  string  $key  The key of the setting to set.
	 * @param  mixed  $value  The value to set for the setting.
	 */
	public function setSetting(string $key, mixed $value): void
	{
		$this->settings()->updateOrCreate(
			['key' => $key],
			['value' => $value]
		);
	}

	/**
	 * Push a value to an array setting for the model by key.
	 *
	 * @param  string  $key  The key of the setting to push to.
	 * @param  mixed  $value  The value to push to the setting.
	 * @param  bool  $force  Whether to force the push even if the current value is not an array. (Default false)
	 *
	 * @throws RuntimeException
	 */
	public function pushSetting(string $key, mixed $value, bool $force = false): void
	{
		$currentValue = $this->getSetting($key);

		if (is_null($currentValue)) {
			$this->setSetting($key, [$value]);

			return;
		}

		if (! is_array($currentValue)) {
			if (! $force) {
				throw new RuntimeException(sprintf("Cannot push to a non-array setting '%s'.", $key));
			}

			$currentValue = [$currentValue];
		}

		$currentValue[] = $value;

		$this->setSetting($key, $currentValue);
	}

	/**
	 * Remove a setting for the model by key.
	 *
	 * @param  string  $key  The key of the setting to remove.
	 */
	public function removeSetting(string $key): void
	{
		$this->settings()->where('key', $key)->delete();
	}
}
