<?php

namespace Kaiserkiwi\ModelSettings\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder;
use Kaiserkiwi\ModelSettings\Models\ModelSettings;

class PruneOrphanedSettings extends Command
{
	protected $signature = 'model-settings:prune {--pretend : Report what would be deleted without deleting anything}';

	protected $description = 'Delete settings whose owning model no longer exists';

	/**
	 * Settings are stored polymorphically, so the table cannot have a foreign key and
	 * nothing removes a model's settings when the model itself is deleted. Model events
	 * are no help either: a database cascade deletes rows without firing any. Sweeping
	 * the table covers every delete path, including the ones that already happened.
	 */
	public function handle(): int
	{
		$settings = new ModelSettings;
		$connection = $settings->getConnection();
		$table = $settings->getTable();

		$types = $connection->table($table)
			->distinct()
			->orderBy('settingable_type')
			->pluck('settingable_type');

		$total = 0;

		foreach ($types as $type) {
			$owner = $this->resolveOwner($type);

			if (! $owner instanceof Model) {
				// Never guess here. A type this installation cannot resolve may well
				// belong to a feature that is not deployed yet, and its settings have to
				// survive that.
				$this->outputComponents()->warn("Skipping '{$type}': cannot be resolved to a model.");

				continue;
			}

			if ($owner->getConnection()->getName() !== $connection->getName()) {
				// The check below is a single SQL statement, which cannot span two
				// connections.
				$this->outputComponents()->warn("Skipping '{$type}': model uses another database connection.");

				continue;
			}

			$orphaned = $connection->table($table)
				->where('settingable_type', $type)
				->whereNotExists(fn (Builder $query): Builder => $query
					->selectRaw('1')
					->from($owner->getTable())
					->whereColumn(
						$owner->getTable() . '.' . $owner->getKeyName(),
						$table . '.settingable_id'
					));

			$count = $this->option('pretend') ? $orphaned->count() : $orphaned->delete();
			$total += $count;

			$this->outputComponents()->twoColumnDetail($type, $count . ' orphaned');
		}

		$this->outputComponents()->info($this->option('pretend')
			? $total . ' orphaned settings would be deleted.'
			: $total . ' orphaned settings successfully deleted.');

		return self::SUCCESS;
	}

	/**
	 * Turn a settingable_type into a model instance, honouring a configured morph map.
	 */
	private function resolveOwner(string $type): ?Model
	{
		$class = Relation::getMorphedModel($type) ?? $type;

		if (! class_exists($class) || ! is_subclass_of($class, Model::class)) {
			return null;
		}

		return new $class;
	}
}
