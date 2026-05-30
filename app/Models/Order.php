<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Order extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'orders';
    protected $primaryKey = '_id';
    protected $keyType = 'string';

    protected $fillable = [
        'toko_id',
        'nama_pembeli',
        'email_pembeli',
        'alamat_pengiriman',
        'items',             // Embedded array: [[product_id, nama_produk, qty, harga, subtotal]]
        'total_harga',
        'status_pesanan'     // 'pending', 'diproses', 'selesai', 'dibatalkan'
    ];

    protected $casts = [
        'items' => 'array',
        'total_harga' => 'integer'
    ];
}