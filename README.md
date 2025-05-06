# Model Settings for Laravel

This package provides a simple way to define model settings in Laravel applications, without anything fancy. Just simple settings. 

The package comes with a `HasSettings` trait that can be added to any eloquent model and a migration that creates a `model_settings` (Name configurable) table to store the settings. The settings are stored as JSON in the database, so you can store any type of data you want. 

## Installation

You can install the package via composer:

```bash
composer require kaiserkiwi/model-settings
```

### Publishing the files
If you want to publish the migration and config files, you can do so by running the following command:
```bash
php artisan vendor:publish --provider="Kaiserkiwi\ModelSettings\ServiceProvider"
```

If you only want to publish the migration file, you can do so by running the following command:
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

## Support
If you require any support you're welcome to open an issue on this GitHub repository.
