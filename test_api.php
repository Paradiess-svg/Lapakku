<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$tokos = App\Models\Toko::all();

foreach ($tokos as $t) {
    $id = $t->_id;
    $heroes = App\Models\Hero::where('toko_id', $id)->count();
    $kategories = App\Models\Kategori::where('toko_id', $id)->count();
    $produks = App\Models\Produk::where('toko_id', $id)->count();
    
    echo "ID: $id\n";
    echo "Nama: " . ($t->nama_toko ?: 'NULL') . "\n";
    echo "Status Toko: " . ($t->status_toko ?: 'NULL') . "\n";
    echo "Status Pembayaran: " . ($t->status_pembayaran ?: 'NULL') . "\n";
    echo "Heroes: $heroes, Kategories: $kategories, Produks: $produks\n";
    echo "-----------------------------------------\n";
}
