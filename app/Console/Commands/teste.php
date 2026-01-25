<?php

namespace App\Console\Commands;

use App\Models\LessonEmbedding;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Prism\Prism\Facades\Prism;

class GenerateLessonEmbeddings extends Command
{
    protected $signature = 'lessons:generate-embeddings
                            {--chunk-size=800 : Size of each text chunk in characters}
                            {--overlap=200 : Overlap between chunks in characters}
                            {--fresh : Delete existing embeddings before generating}';

    protected $description = 'Generate embeddings for lesson transcriptions';

    private const BATCH_SIZE = 20;

    public function handle(): int
    {
        if ($this->option('fresh')) {
            $this->warn('Deleting existing embeddings...');
            LessonEmbedding::truncate();
        }

        $files = Storage::disk('public')->files('transcriptions');
        $jsonFiles = array_filter($files, fn ($file) => str_ends_with($file, '.json'));

        if (empty($jsonFiles)) {
            $this->error('No transcription files found in storage/app/public/transcriptions');

            return self::FAILURE;
        }

        $this->info(sprintf('Found %d transcription files', count($jsonFiles)));

        $chunkSize = (int) $this->option('chunk-size');
        $overlap = (int) $this->option('overlap');

        $progressBar = $this->output->createProgressBar(count($jsonFiles));
        $progressBar->start();

        $totalChunks = 0;

        foreach ($jsonFiles as $file) {
            $lessonName = pathinfo($file, PATHINFO_FILENAME);

            if ($this->lessonAlreadyProcessed($lessonName)) {
                $progressBar->advance();

                continue;
            }

            $content = Storage::disk('public')->get($file);
            $data = json_decode($content, true);

            if (! isset($data['segments'])) {
                $this->newLine();
                $this->warn("Skipping {$file}: no segments found");
                $progressBar->advance();

                continue;
            }

            $chunks = $this->createChunks($data['segments'], $chunkSize, $overlap);

            $this->processChunksInBatches($lessonName, $chunks);

            $totalChunks += count($chunks);
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        $this->info(sprintf('Generated embeddings for %d chunks', $totalChunks));

        return self::SUCCESS;
    }

    private function lessonAlreadyProcessed(string $lessonName): bool
    {
        return LessonEmbedding::where('lesson', $lessonName)->exists();
    }

    /**
     * @param  array<int, array{text: string, start: float, end: float}>  $segments
     * @return array<int, array{content: string, start: float, end: float}>
     */
    private function createChunks(array $segments, int $chunkSize, int $overlap): array
    {
        $chunks = [];
        $currentChunk = '';
        $currentStart = null;
        $currentEnd = null;

        foreach ($segments as $segment) {
            $text = trim($segment['text'] ?? '');

            if (empty($text)) {
                continue;
            }

            if ($currentStart === null) {
                $currentStart = $segment['start'];
            }

            $currentEnd = $segment['end'];

            if (strlen($currentChunk) + strlen($text) + 1 <= $chunkSize) {
                $currentChunk .= ($currentChunk ? ' ' : '').$text;
            } else {
                if ($currentChunk) {
                    $chunks[] = [
                        'content' => $currentChunk,
                        'start' => $currentStart,
                        'end' => $currentEnd,
                    ];
                }

                $overlapText = $this->getOverlapText($currentChunk, $overlap);
                $currentChunk = $overlapText.($overlapText ? ' ' : '').$text;
                $currentStart = $segment['start'];
            }
        }

        if ($currentChunk && $currentStart !== null) {
            $chunks[] = [
                'content' => $currentChunk,
                'start' => $currentStart,
                'end' => $currentEnd,
            ];
        }

        return $chunks;
    }

    private function getOverlapText(string $text, int $overlap): string
    {
        if (strlen($text) <= $overlap) {
            return $text;
        }

        $overlapPortion = substr($text, -$overlap);
        $lastSpace = strrpos($overlapPortion, ' ');

        if ($lastSpace !== false) {
            return substr($overlapPortion, $lastSpace + 1);
        }

        return $overlapPortion;
    }

    /**
     * @param  array<int, array{content: string, start: float, end: float}>  $chunks
     */
    private function processChunksInBatches(string $lessonName, array $chunks): void
    {
        $batches = array_chunk($chunks, self::BATCH_SIZE);

        foreach ($batches as $batch) {
            $texts = array_column($batch, 'content');

            $response = Prism::embeddings()
                ->using('openai', 'text-embedding-3-small')
                ->fromArray($texts)
                ->asEmbeddings();

            foreach ($batch as $index => $chunk) {
                LessonEmbedding::create([
                    'lesson' => $lessonName,
                    'content' => $chunk['content'],
                    'start' => $chunk['start'],
                    'end' => $chunk['end'],
                    'embedding' => $response->embeddings[$index]->embedding,
                ]);
            }
        }
    }
}
