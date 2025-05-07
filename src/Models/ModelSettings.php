<?php

namespace Kaiserkiwi\ModelSettings\Models;

use Illuminate\Database\Eloquent\Model;

class ModelSettings extends Model
{
	public function getTable(): string
	{
		return config('model_settings.table', 'model_settings');
	}

	protected $guarded = [
		'id',
		'created_at',
		'updated_at',
	];

	protected function casts(): array
	{
		return [
			'value' => 'array',
		];
	}
}
