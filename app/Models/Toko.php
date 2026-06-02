<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Toko extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'tokos';
    protected $primaryKey = '_id';
    protected $keyType = 'string';

    protected $fillable = [
        'user_id',
        'nama_toko',
        'deskripsi',
        'alamat',
        'tipe_domain',       // 'gratis' atau 'custom'
        'nama_domain',       // misal: hachimi.lapakku.id atau customdomain.com
        'durasi_layanan',    // 1 sampai 5 (tahun)
        'metode_pembayaran', // 'QRIS' atau 'transfer_bank'
        'bukti_pembayaran',  // nama/path file gambar bukti transfer
        'status_pembayaran', // 'pending', 'sukses', 'gagal'
        'status_toko',       // 'pending', 'aktif', 'suspended'
        'wa_number',
        'instagram',
        'email_toko',
        'jam_operasional',
        'theme_color',
        'template_style'
    ];
}
