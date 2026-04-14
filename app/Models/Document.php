<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Attributes\SearchUsingFullText;
use Laravel\Scout\Searchable;

#[SearchUsingFullText(['name', 'description', 'content', 'keywords'])]
class Document extends Model
{
    use Searchable;

    protected $fillable = [
        'name',
        'path',
        'description',
        'content',
        'keywords',
    ];

    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'path' => $this->path,
            'description' => $this->description,
            'content' => $this->content,
            'keywords' => $this->keywords,
        ];
    }
}
