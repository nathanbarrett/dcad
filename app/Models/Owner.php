<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Owner extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function properties(): BelongsToMany
    {
        return $this->belongsToMany(Property::class);
    }

    public function currentlyOwnedProperties(): BelongsToMany
    {
        return $this->belongsToMany(Property::class)
            ->wherePivot('active', 1)
            ->orderByPivot('id', 'desc');
    }
}
