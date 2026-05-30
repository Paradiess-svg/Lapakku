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
        'kategori_id', // <-- Tambahkan ini untuk relasi dropdown kategori
        'nama_produk',
        'deskripsi',
        'harga',
        'stok',
        'gambar_produk'
    ];
    protected $casts = [
        'harga' => 'integer',
        'stok'  => 'integer'
    ];
}