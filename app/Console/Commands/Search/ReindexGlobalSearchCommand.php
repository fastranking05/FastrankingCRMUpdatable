<?php

namespace App\Console\Commands\Search;

use App\Services\Search\GlobalSearchService;
use Illuminate\Console\Command;

class ReindexGlobalSearchCommand extends Command
{
    protected $signature = 'search:reindex {--fresh : Delete and recreate the index before reindexing}';

    protected $description = 'Rebuild the Elasticsearch global search index from database records';

    public function handle(GlobalSearchService $globalSearchService): int
    {
        $this->info('Starting global search reindex...');

        try {
            $counts = $globalSearchService->reindex($this->option('fresh'));

            $this->table(
                ['Entity Type', 'Indexed Documents'],
                collect($counts)
                    ->except('total')
                    ->map(fn (int $count, string $type) => [$type, $count])
                    ->values()
                    ->all()
            );

            $this->info('Total indexed documents: ' . ($counts['total'] ?? 0));

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Reindex failed: ' . $e->getMessage());

            return self::FAILURE;
        }
    }
}
