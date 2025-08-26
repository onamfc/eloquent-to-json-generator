<?php

namespace onamfc\EloquentJsonSchema\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class SchemaDiffCommand extends Command
{
    protected $signature = 'schema:diff {--from=} {--to=}';
    protected $description = 'Show differences between schema versions';

    public function handle(): int
    {
        $from = $this->option('from') ?? $this->getPreviousVersion();
        $to = $this->option('to') ?? config('laravel-schema.version');

        if (!$from) {
            $this->error('No previous version found. Specify --from option.');
            return self::FAILURE;
        }

        $this->info("Comparing schemas from {$from} to {$to}");

        $changes = $this->compareVersions($from, $to);

        if (empty($changes)) {
            $this->info('No changes detected.');
            return self::SUCCESS;
        }

        $this->displayChanges($changes);

        return self::SUCCESS;
    }

    private function getPreviousVersion(): ?string
    {
        $outputDir = storage_path(config('laravel-schema.output_directory'));
        $versions = collect(File::directories($outputDir))
            ->map(fn($dir) => basename($dir))
            ->sort()
            ->values();

        return $versions->count() > 1 ? $versions->get(-2) : null;
    }

    private function compareVersions(string $from, string $to): array
    {
        $fromDir = storage_path(config('laravel-schema.output_directory') . "/{$from}/models");
        $toDir = storage_path(config('laravel-schema.output_directory') . "/{$to}/models");

        if (!File::exists($fromDir) || !File::exists($toDir)) {
            return [];
        }

        $changes = [];
        $fromFiles = collect(File::files($fromDir))->keyBy(fn($file) => $file->getFilename());
        $toFiles = collect(File::files($toDir))->keyBy(fn($file) => $file->getFilename());

        // Find added files
        foreach ($toFiles->keys()->diff($fromFiles->keys()) as $filename) {
            $changes[] = [
                'type' => 'added',
                'file' => $filename,
                'breaking' => false
            ];
        }

        // Find removed files
        foreach ($fromFiles->keys()->diff($toFiles->keys()) as $filename) {
            $changes[] = [
                'type' => 'removed',
                'file' => $filename,
                'breaking' => true
            ];
        }

        // Find modified files
        foreach ($fromFiles->keys()->intersect($toFiles->keys()) as $filename) {
            $fromContent = json_decode(File::get($fromFiles[$filename]->getPathname()), true);
            $toContent = json_decode(File::get($toFiles[$filename]->getPathname()), true);

            if ($fromContent !== $toContent) {
                $isBreaking = $this->isBreakingChange($fromContent, $toContent);
                $changes[] = [
                    'type' => 'modified',
                    'file' => $filename,
                    'breaking' => $isBreaking,
                    'details' => $this->getChangeDetails($fromContent, $toContent)
                ];
            }
        }

        return $changes;
    }

    private function isBreakingChange(array $from, array $to): bool
    {
        // Removed required fields
        $fromRequired = $from['required'] ?? [];
        $toRequired = $to['required'] ?? [];
        if (count(array_diff($fromRequired, $toRequired)) > 0) {
            return true;
        }

        // Removed properties
        $fromProperties = array_keys($from['properties'] ?? []);
        $toProperties = array_keys($to['properties'] ?? []);
        if (count(array_diff($fromProperties, $toProperties)) > 0) {
            return true;
        }

        // Type changes
        foreach ($fromProperties as $property) {
            if (isset($from['properties'][$property]['type'], $to['properties'][$property]['type'])) {
                if ($from['properties'][$property]['type'] !== $to['properties'][$property]['type']) {
                    return true;
                }
            }
        }

        return false;
    }

    private function getChangeDetails(array $from, array $to): array
    {
        $details = [];

        // Check required fields
        $fromRequired = $from['required'] ?? [];
        $toRequired = $to['required'] ?? [];
        
        $addedRequired = array_diff($toRequired, $fromRequired);
        $removedRequired = array_diff($fromRequired, $toRequired);

        if ($addedRequired) {
            $details[] = 'Added required: ' . implode(', ', $addedRequired);
        }
        if ($removedRequired) {
            $details[] = 'Removed required: ' . implode(', ', $removedRequired);
        }

        // Check properties
        $fromProperties = array_keys($from['properties'] ?? []);
        $toProperties = array_keys($to['properties'] ?? []);
        
        $addedProperties = array_diff($toProperties, $fromProperties);
        $removedProperties = array_diff($fromProperties, $toProperties);

        if ($addedProperties) {
            $details[] = 'Added properties: ' . implode(', ', $addedProperties);
        }
        if ($removedProperties) {
            $details[] = 'Removed properties: ' . implode(', ', $removedProperties);
        }

        return $details;
    }

    private function displayChanges(array $changes): void
    {
        $breaking = collect($changes)->where('breaking', true);
        $nonBreaking = collect($changes)->where('breaking', false);

        if ($breaking->isNotEmpty()) {
            $this->error('Breaking Changes:');
            foreach ($breaking as $change) {
                $this->line("  🔴 {$change['type']}: {$change['file']}");
                if (isset($change['details'])) {
                    foreach ($change['details'] as $detail) {
                        $this->line("     - {$detail}");
                    }
                }
            }
        }

        if ($nonBreaking->isNotEmpty()) {
            $this->info('Non-breaking Changes:');
            foreach ($nonBreaking as $change) {
                $this->line("  🟢 {$change['type']}: {$change['file']}");
                if (isset($change['details'])) {
                    foreach ($change['details'] as $detail) {
                        $this->line("     - {$detail}");
                    }
                }
            }
        }
    }
}