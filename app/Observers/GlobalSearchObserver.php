<?php

namespace App\Observers;

use App\Services\Search\Index\GlobalSearchIndexer;
use Illuminate\Database\Eloquent\Model;

class GlobalSearchObserver
{
    public function __construct(
        private readonly GlobalSearchIndexer $indexer,
    ) {}

    public function created(Model $model): void
    {
        $this->indexer->indexModel($model);
    }

    public function updated(Model $model): void
    {
        $this->indexer->indexModel($model);
    }

    public function deleted(Model $model): void
    {
        $this->indexer->deleteModel($model);
    }
}
