<?php

namespace Kaiserkiwi\ModelSettings;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Kaiserkiwi\ModelSettings\Models\ModelSettings;

trait HasSettings
{
	public function settings(): MorphMany
	{
		return $this->morphMany(ModelSettings::class, 'settingable');
	}

	public function getSettings(): Collection
	{
		return $this->settings()->get();
	}

	public function getSetting(string $key, $default = null): mixed
	{
		$record = $this->settings()->firstWhere('key', $key);

		return $record ? $record->value : $default;
	}

	public function setSetting(string $key, $value): void
	{
		$this->settings()->updateOrCreate(
			['key' => $key],
			['value' => $value]
		);
	}

	public function removeSetting(string $key): void
	{
		$this->settings()->where('key', $key)->delete();
	}
}
