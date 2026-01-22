<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Pgvector\Laravel\HasNeighbors;
use Pgvector\Laravel\Vector;

class LessonEmbedding extends Model
{
    use HasNeighbors;

    /** @var list<string> */
    protected $fillable = [
        'lesson',
        'content',
        'start',
        'end',
        'embedding',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'embedding' => Vector::class,
            'start' => 'float',
            'end' => 'float',
        ];
    }
}
