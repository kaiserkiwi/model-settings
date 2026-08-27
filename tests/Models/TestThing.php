<?php

namespace Kaiserkiwi\ModelSettings\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Kaiserkiwi\ModelSettings\HasSettings;

/**
 * An owner whose primary key is not called "id".
 */
class TestThing extends Model
{
	use HasSettings;

	protected $table = 'test_things';

	protected $primaryKey = 'thing_id';
}
