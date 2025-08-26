<?php

namespace onamfc\EloquentJsonSchema\Examples;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use onamfc\EloquentJsonSchema\Attributes as S;

#[S\SchemaName('BlogPost')]
class Post extends Model
{
    protected $fillable = [
        'title',
        'content',
        'published_at',
        'user_id',
    ];

    protected $casts = [
        'id' => 'string',
        'published_at' => 'datetime',
        'is_published' => 'boolean',
    ];

    protected $appends = [
        'is_published',
        'excerpt',
    ];

    #[S\Title('Post ID')]
    #[S\Format('uuid')]
    public $id;

    #[S\Description('Post title')]
    public $title;

    #[S\Description('Post content in markdown format')]
    public $content;

    #[S\Description('Publication date')]
    #[S\Format('date-time')]
    public $published_at;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getIsPublishedAttribute(): bool
    {
        return $this->published_at !== null && $this->published_at->isPast();
    }

    public function getExcerptAttribute(): string
    {
        return substr(strip_tags($this->content), 0, 150) . '...';
    }

    public function rules(string $schemaType = 'request'): array
    {
        return [
            'title' => 'required|string|min:5|max:255',
            'content' => 'required|string|min:10',
            'published_at' => 'nullable|date',
            'user_id' => 'required|uuid|exists:users,id',
        ];
    }
}