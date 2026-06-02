<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Paket extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'pakets';
    protected $primaryKey = '_id';
    protected $keyType = 'string';

    protected $fillable = [
        'nama_paket',
        'durasi_hari',
        'durasi_layanan',
        'harga',
        'bonus',
        'fitur',
        'is_popular',
        'status',
    ];

    protected $casts = [
        'durasi_hari' => 'integer',
        'durasi_layanan' => 'integer',
        'harga' => 'integer',
        'is_popular' => 'boolean',
        'fitur' => 'array',
    ];
}
