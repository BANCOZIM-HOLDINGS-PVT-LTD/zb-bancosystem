<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SchoolCategory extends Model
{
    protected $fillable = ['name', 'emoji'];

    public function businesses(): HasMany
    {
        return $this->hasMany(SchoolBusiness::class);
    }
}
