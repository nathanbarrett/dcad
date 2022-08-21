<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Property extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function owners(): BelongsToMany
    {
        return $this->belongsToMany(Owner::class)
            ->withPivot('id', 'deed_transferred_at', 'active', 'account_num', 'ownership_percent')
            ->withTimestamps();
    }

    public function activeOwners(): BelongsToMany
    {
        return $this->belongsToMany(Owner::class)
            ->wherePivot('active', 1)
            ->withPivot('deed_transferred_at', 'active', 'account_num', 'ownership_percent')
            ->withTimestamps()
            ->orderByPivot('id', 'desc');
    }

    public function changes(): HasMany
    {
        return $this->hasMany(PropertyChange::class);
    }
}
