<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Produk extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'produks';
    protected $primaryKey = '_id';
    protected $keyType = 'string';

    protected $fillable = [
        'toko_id',
        'kategori_id',
        'nama_produk',
        'deskripsi',
        'harga',
        'harga_diskon', // <-- Menggantikan diskon persen
        'stok',
        'gambar_produk'
    ];

    protected $casts = [
        'harga'        => 'integer',
        'stok'         => 'integer',
        'harga_diskon' => 'integer' // <-- Cast otomatis ke angka/integer
    ];
}