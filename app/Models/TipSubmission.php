<?php

namespace App\Models;

use App\Helpers\PathHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TipSubmission extends Model
{
    protected $fillable = [
        'name',
        'email',
        'phone',
        'title',
        'content',
        'city_id',
        'image_url',
        'status',
        'admin_note',
        'ip_address',
        'user_agent',
    ];

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'Beklemede',
            'reviewed' => 'İncelendi',
            'approved' => 'Onaylandı',
            'rejected' => 'Reddedildi',
            default => $this->status,
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'warning',
            'reviewed' => 'info',
            'approved' => 'success',
            'rejected' => 'danger',
            default => 'gray',
        };
    }

    public function getImageDisplayUrlAttribute(): ?string
    {
        return PathHelper::urlForStoredUpload($this->image_url);
    }
}
