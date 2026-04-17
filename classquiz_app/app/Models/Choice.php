<?php

namespace App\Models;

use App\Support\QuestionTextSanitizer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Choice extends Model
{
    public $timestamps = false;

    protected $fillable = ['question_id', 'text', 'is_correct', 'sort_order'];

    protected function casts(): array
    {
        return ['is_correct' => 'boolean'];
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    protected function setTextAttribute(?string $value): void
    {
        $this->attributes['text'] = QuestionTextSanitizer::sanitize($value);
    }
}
