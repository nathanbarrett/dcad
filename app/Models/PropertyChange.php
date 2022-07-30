<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PropertyChange extends Model
{
    use HasFactory;

    public const TYPE_OWNER_UPDATE = 'owner_update';
    public const TYPE_OWNER_PERCENTAGE_UPDATE = 'owner_percentage_update';

    protected $guarded = ['id'];

    protected $casts = [
        'context' => 'collection'
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }
}
