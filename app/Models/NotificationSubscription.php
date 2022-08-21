<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationSubscription extends Model
{
    use HasFactory;

    const TYPE_OWNERSHIP_CHANGES = 'ownership_changes';

    protected $guarded = ['id'];

    protected $casts = [
        'filters' => 'collection',
        'active' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
