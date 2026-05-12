<?php

namespace Kaiserkiwi\ModelSettings;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
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
		if (! $this->isCachingEnabled()) {
			return $this->settings()->get();
		}

		return cache()->remember(
			$this->modelCacheKey(),
			$this->cacheTtl(),
			fn () => $this->settings()->get(),
		);
	}

	/**
	 * Get a setting for the model by key.
	 *
	 * @param  string  $key  The key of the setting to retrieve.
	 * @param  mixed  $default  The default value to return if the setting is not found. (Default null)
	 */
	public function getSetting(string $key, mixed $default = null): mixed
	{
		if (! $this->isCachingEnabled()) {
			return $this->fetchSetting($key, $default);
		}

		return cache()->remember(
			$this->settingCacheKey($key),
			$this->cacheTtl(),
			fn () => $this->fetchSetting($key, $default),
		);
	}

	/**
	 * Fetch a setting from the database, optionally persisting the default value.
	 *
	 * @param  string  $key  The key of the setting to fetch.
	 * @param  mixed  $default  The default value to return (and optionally persist) if not found.
	 */
	private function fetchSetting(string $key, mixed $default): mixed
	{
		$record = $this->settings()->firstWhere('key', $key);

		if (! $record) {
			if ($this->isSaveDefaultEnabled() && ! is_null($default)) {
				$this->setSetting($key, $default);
			}

			return $default;
		}

		return $record->value;
	}

	/**
	 * Set a setting for the model by key.
	 *
	 * @param  string  $key  The key of the setting to set.
	 * @param  mixed  $value  The value to set for the setting.
	 */
	public function setSetting(string $key, mixed $value): void
	{
		try {
			$this->settings()->updateOrCreate(
				['key' => $key],
				['value' => $value],
			);
		} catch (QueryException) {
			// A concurrent request already inserted this key (race on the unique constraint).
			// Fall back to a plain update since the record now exists.
			$this->settings()->where('key', $key)->update(['value' => $value]);
		}

		$this->invalidateSettingCaches($key, $value);
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
		$this->invalidateSettingCaches($key);
	}

	/**
	 * Invalidate the caches for a specific setting and the model's settings.
	 * If a value is provided, the key will be written back to the cache with the new value.
	 *
	 * @param  string  $key  The key of the setting to invalidate.
	 * @param  mixed|null  $value  The value of the setting (optional).
	 */
	private function invalidateSettingCaches(string $key, mixed $value = null): void
	{
		if (! $this->isCachingEnabled()) {
			return;
		}

		cache()->forget($this->settingCacheKey($key));

		// Re-cache all settings
		cache()->put(
			$this->modelCacheKey(),
			$this->settings()->get(),
			$this->cacheTtl(),
		);

		if (! is_null($value)) {
			cache()->put(
				$this->settingCacheKey($key),
				$value,
				$this->cacheTtl(),
			);
		}
	}

	/**
	 * Check if saving default values to the database is enabled.
	 */
	private function isSaveDefaultEnabled(): bool
	{
		return (bool) config('model_settings.save_default', false);
	}

	/**
	 * Check if caching is enabled for model settings.
	 */
	private function isCachingEnabled(): bool
	{
		return (bool) config('model_settings.caching.enabled', false);
	}

	/**
	 * Get the cache key for the model's settings.
	 */
	private function modelCacheKey(): string
	{
		return sprintf(
			'%s:%s:%s',
			$this->cachePrefix(),
			$this->getMorphClass(),
			$this->getKey(),
		);
	}

	/**
	 * Get the cache key for a specific setting of the model.
	 *
	 * @param  string  $key  The key of the setting.
	 */
	private function settingCacheKey(string $key): string
	{
		return $this->modelCacheKey() . ':' . $key;
	}

	/**
	 * Get the cache key prefix.
	 */
	private function cachePrefix(): string
	{
		return (string) config('model_settings.caching.key_prefix', 'model_settings');
	}

	/**
	 * Get the cache time-to-live (TTL).
	 */
	private function cacheTtl(): int|Carbon
	{
		return config('model_settings.caching.ttl', 60 * 60 * 24 * 30);
	}
}
