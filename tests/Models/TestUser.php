<?php

namespace Kaiserkiwi\ModelSettings\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Kaiserkiwi\ModelSettings\HasSettings;

class TestUser extends Model
{
	use HasSettings;

	protected $table = 'test_users';
}
