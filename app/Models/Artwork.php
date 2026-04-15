<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Artwork extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_order_id',
        'filename',
        'is_approved',
        'uploaded_by',
    ];

    protected $casts = [
        'is_approved' => 'boolean',
    ];

    public function jobOrder(): BelongsTo
    {
        return $this->belongsTo(JobOrder::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getUrlAttribute(): ?string
    {
        return $this->filename ? Storage::disk('s3')->url($this->filename) : null;
    }
}
