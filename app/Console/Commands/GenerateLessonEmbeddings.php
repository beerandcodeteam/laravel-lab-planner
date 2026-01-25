<?php

namespace App\Console\Commands;

use App\Models\LessonEmbedding;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Facades\Prism;
use function array_chunk;
use function array_column;
use function array_key_last;
use function count;
use function json_decode;
use function pathinfo;
use function sprintf;
use function strlen;
use function strrpos;
use function substr;
use function trim;
use const PATHINFO_FILENAME;

class GenerateLessonEmbeddings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:generate-lessons-embedding';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $files = Storage::files('transcriptions');

        $this->info(sprintf('Found %d transcription files', count($files)));

        $progressBar = $this->output->createProgressBar(count($files));
        $progressBar->start();

        foreach ($files as $file) {
            $lessonName = pathinfo($file, PATHINFO_FILENAME);

            $data = json_decode(Storage::get($file), true);

            if (empty($data['segments'])) {
                continue;
            }

            $this->info("Processing $lessonName");

            $chunks = $this->createChunks($data['segments'], 800, 200);

            foreach (array_chunk($chunks, 20) as $batch) {
                $texts = array_column($batch, 'content');

                $response = Prism::embeddings()
                    ->using(Provider::OpenAI, 'text-embedding-3-small')
                    ->fromArray($texts)
                    ->asEmbeddings();


                foreach($batch as $index => $chunk)
                {
                    LessonEmbedding::create([
                        'name' => $lessonName,
                        'content' => $chunk['content'],
                        'start' => $chunk['start'],
                        'end' => $chunk['end'],
                        'embedding' => $response->embeddings[$index]->embedding
                    ]);
                }

            }

            $progressBar->advance();
        }

        $progressBar->finish();
    }


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
}
