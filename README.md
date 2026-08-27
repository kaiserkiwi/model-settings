# Model Settings for Laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/kaiserkiwi/model-settings.svg?style=flat-square)](https://packagist.org/packages/kaiserkiwi/model-settings)
[![Tests](https://img.shields.io/github/actions/workflow/status/kaiserkiwi/model-settings/tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/kaiserkiwi/model-settings/actions/workflows/tests.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/kaiserkiwi/model-settings.svg?style=flat-square)](https://packagist.org/packages/kaiserkiwi/model-settings)
[![License](https://img.shields.io/packagist/l/kaiserkiwi/model-settings.svg?style=flat-square)](https://packagist.org/packages/kaiserkiwi/model-settings)

This package provides a simple way to define model settings in Laravel applications, without anything fancy. Just simple settings. 

The package comes with a `HasSettings` trait that can be added to any eloquent model and a migration that creates a `model_settings` (Name configurable) table to store the settings. The settings are stored as JSON in the database, so you can store any type of data you want. 

---

## Installation

You can install the package via composer:

```bash
composer require kaiserkiwi/model-settings
```

### Changing the configuration
If you want to change the default configuration, you can do this via the environment variables or by publishing the config file.

#### Changing the configuration via environment variables
You can change the configuration via the following environment variables:

| Variable                          | Description                                              | Default                       |
|-----------------------------------|----------------------------------------------------------|-------------------------------|
| `MODEL_SETTINGS_TABLE`            | The name of the table to store the model settings        | `model_settings`              |
| `MODEL_SETTINGS_SAVE_DEFAULT`     | Whether to persist default values to the database        | `false`                       |
| `MODEL_SETTINGS_CACHE_ENABLED`    | Whether to enable caching for model settings             | `false`                       |
| `MODEL_SETTINGS_CACHE_TTL`        | The time to live for the model settings cache in seconds | `60 * 60 * 24 * 30` (30 days) |
| `MODEL_SETTINGS_CACHE_KEY_PREFIX` | The prefix for the model settings cache key              | `model_settings`              |

#### Changing the configuration via the config file
You can change the configuration in the `config/model_settings.php` file after  publishing it by running the following command:
```bash
php artisan vendor:publish --provider="Kaiserkiwi\ModelSettings\ServiceProvider" --tag="config"
```

### Publishing the migration file
To run the migration you have to publish the migration file, you can do so by running the following command:
```bash
php artisan vendor:publish --provider="Kaiserkiwi\ModelSettings\ServiceProvider" --tag="migrations"
```

### Running the migration
After publishing the migration file, you can run the migration to create the `model_settings` table in your database by running the following command:
```bash
php artisan migrate
```

### Adding the trait to your model
To use the package, you need to add the `HasSettings` trait to your model. For example:
```php
namespace App\Models;

use Kaiserkiwi\ModelSettings\HasSettings;

class User extends Model
{
	use HasSettings;

	// Your model code here
}
```

---

## Usage

Once you have added the `HasSettings` trait to your model, you can use the following methods to manage your settings:
### Setting a setting
You can set a setting by calling the `setSetting` method on your model instance. For example:
```php
$user = User::find(1);
$user->setSetting('theme', 'dark');
```

### Getting a setting
You can get a setting by calling the `getSetting` method on your model instance. For example:
```php
$user = User::find(1);
$theme = $user->getSetting('theme');
```

If the setting does not exist, you can provide a default value that will be returned instead:
```php
$user = User::find(1);
$theme = $user->getSetting('theme', 'light');
```

If `save_default` is enabled in the configuration (or via `MODEL_SETTINGS_SAVE_DEFAULT=true`), the default value will also be written to the database when the setting does not exist yet. This only applies when the default value is not `null`.

### Push a setting
You can push a setting by calling the `pushSetting` method on your model instance. This will append the value to the existing setting that is an array. For example:
```php
$user = User::find(1);
$user->pushSetting('notifications', 'email');
```

If the setting does not have an array value the method throws an exception. If for whatever reason you want convert the existing value to an array and append a value you can use the third $force parameter:
```php
$user = User::find(1);
$user->pushSetting('notifications', 'email', true);
```

So if the setting is a string, it will be converted to an array and the value will be appended. If the setting is not set at all, it will be created as an array with the value.

### Deleting a setting
You can delete a setting by calling the `deleteSetting` method on your model instance. For example:
```php
$user = User::find(1);
$user->removeSetting('theme');
```

### Getting all settings
You can get all settings by calling the `getSettings` method on your model instance. For example:
```php
$user = User::find(1);
$settings = $user->getSettings();
```

---

## Caching
If caching is enabled in the configuration, the settings will be cached for the duration specified in the configuration. The cache invalidates automatically when a setting is updated or deleted.

Cache keys for the whole model will be created using the following format:
```{cache_key_prefix}:{model_class}:{model_id}```

For example:
```
model_settings:App\Models\User:1
```

Cache keys for individual settings will be created using the following format:
```{cache_key_prefix}:{model_class}:{model_id}:{setting_key}```

For example:
```
model_settings:App\Models\User:1:theme
```

---

## Pruning orphaned settings
Settings are stored polymorphically, which means the table cannot have a foreign key on its owner. Nothing removes a model's settings when the model itself is deleted, so they stay behind as orphaned rows. Model events are no help either: if your database deletes the model through a cascade, no event fires at all.

The package ships a command for this, which you can run whenever you like:

```bash
php artisan model-settings:prune
```

To see what it would delete without deleting anything, use `--pretend`:

```bash
php artisan model-settings:prune --pretend
```

### Running it regularly
The package deliberately does not schedule anything for you. If you want it to run on a schedule, add it in your own application, for example in `routes/console.php`:

```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('model-settings:prune')->daily();
```

### What counts as orphaned
A setting is orphaned when no row with a matching primary key exists in the owner's table. A soft deleted owner therefore still counts as existing, because its row is still there. Only once it is really gone do its settings become orphaned.

Two cases are reported and skipped rather than deleted, because deleting them would be a guess:

* the `settingable_type` cannot be resolved to a model, for instance because it belongs to a feature that is not deployed in this installation
* the owning model uses a different database connection than the settings table, which a single query cannot span

---

## Support
If you require any support you're welcome to open an issue on this GitHub repository or [contact me on Mastodon](https://corteximplant.com/@kaiserkiwi).
