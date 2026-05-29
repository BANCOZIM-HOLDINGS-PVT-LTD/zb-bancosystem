<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WarrantySetting extends Model
{
    protected $fillable = ['warranty_enabled', 'warranty_text'];

    protected $casts = ['warranty_enabled' => 'boolean'];

    public static function current(): self
    {
        return self::firstOrCreate([], [
            'warranty_enabled' => true,
            'warranty_text'    => '12 month warranty',
        ]);
    }
}
