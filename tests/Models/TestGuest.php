<?php

namespace Kaiserkiwi\ModelSettings\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Kaiserkiwi\ModelSettings\HasSettings;

/**
 * An owner living on a different database connection than the settings table.
 */
class TestGuest extends Model
{
	use HasSettings;

	protected $connection = 'secondary';

	protected $table = 'test_guests';
}
