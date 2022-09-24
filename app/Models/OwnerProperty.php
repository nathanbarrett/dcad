<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OwnerProperty extends Model
{
    use HasFactory;

    protected $table = 'owner_property';

    protected $guarded = ['id'];

    protected $casts = [
        'context' => 'collection',
        'deed_transferred_at' => 'date',
        'active' => 'boolean',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(Owner::class);
    }
}
