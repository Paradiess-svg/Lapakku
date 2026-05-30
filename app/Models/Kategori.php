<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Kategori extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'kategoris';
    protected $primaryKey = '_id';
    protected $keyType = 'string';

    protected $fillable = [
        'toko_id',
        'nama_kategori',
        'foto_icon'
    ];
}