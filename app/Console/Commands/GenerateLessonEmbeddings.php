<?php

namespace App\Console\Commands;

use App\Models\LessonEmbedding;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Prism\Prism\Facades\Prism;

class GenerateLessonEmbeddings extends Command
{
    protected $signature = 'lessons:generate-embeddings';

    protected $description = 'Generate embeddings for lesson transcriptions';

    public function handle(): int
    {
        LessonEmbedding::truncate();

        $files = collect(Storage::disk('public')->files('transcriptions'))
            ->filter(fn ($file) => str_ends_with($file, '.json'));

        foreach ($files as $file) {
            $lessonName = pathinfo($file, PATHINFO_FILENAME);
            $data = json_decode(Storage::disk('public')->get($file), true);

            if (empty($data['segments'])) {
                continue;
            }

            $this->info("Processing: {$lessonName}");

            $chunks = $this->createChunks($data['segments']);

            foreach (array_chunk($chunks, 20) as $batch) {
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

        $this->info('Done!');

        return self::SUCCESS;
    }

    private function createChunks(array $segments): array
    {
        $fullText = collect($segments)
            ->pluck('text')
            ->map(fn ($t) => trim($t))
            ->filter()
            ->implode(' ');

        $fullText = mb_convert_encoding($fullText, 'UTF-8', 'UTF-8');

        $chunks = [];
        $position = 0;
        $length = strlen($fullText);

        while ($position < $length) {
            $chunk = substr($fullText, $position, 800);

            $chunks[] = [
                'content' => $chunk,
                'start' => $segments[0]['start'] ?? 0,
                'end' => $segments[array_key_last($segments)]['end'] ?? 0,
            ];

            $position += 600; // 800 - 200 overlap
        }

        return $chunks;
    }
}
