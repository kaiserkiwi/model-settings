<?php

return [
	'table' => env('MODEL_SETTINGS_TABLE', 'model_settings'),

	'caching' => [
		'enabled' => env('MODEL_SETTINGS_CACHE_ENABLED', false),
		'ttl' => env('MODEL_SETTINGS_CACHE_TTL', 60 * 60 * 24 * 30), // 30 days in seconds
		'key_prefix' => env('MODEL_SETTINGS_CACHE_KEY_PREFIX', 'model_settings'),
	],
];
