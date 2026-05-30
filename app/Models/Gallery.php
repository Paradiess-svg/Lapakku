<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Gallery extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'galleries';
    protected $primaryKey = '_id';
    protected $keyType = 'string';

    protected $fillable = [
        'produk_id',
        'foto_path'
    ];
}