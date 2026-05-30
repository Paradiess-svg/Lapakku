<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Hero extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'heros';
    protected $primaryKey = '_id';
    protected $keyType = 'string';

    protected $fillable = [
        'toko_id',
        'gambar_hero',
        'judul',
        'deskripsi',
        'link_tujuan'
    ];
}