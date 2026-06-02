<?php

namespace App\Http\Controllers;

use App\Models\Toko;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TokoController extends Controller
{
    public function me(Request $request)
    {
        $toko = Toko::where('user_id', $request->user()->_id)->first();

        if (!$toko) {
            return response()->json([
                'status'  => false,
                'message' => 'Toko tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data'   => $toko
        ], 200);
    }

    // STEP 1: Pengisian Profil Toko & Pemilihan Domain
    public function setupStep1(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama_toko'   => 'required|string|max:255|unique:tokos,nama_toko',
            'deskripsi'   => 'required|string',
            'alamat'      => 'required|string',
            'tipe_domain' => 'required|in:gratis,custom',
            'nama_domain' => 'required|string|unique:tokos,nama_domain'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validasi Step 1 gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        // Simpan data tahap awal ke MongoDB
        $toko = Toko::create([
            'user_id'           => $request->user()->_id,
            'nama_toko'         => $request->nama_toko,
            'deskripsi'         => $request->deskripsi,
            'alamat'            => $request->alamat,
            'tipe_domain'       => $request->tipe_domain,
            'nama_domain'       => $request->nama_domain,
            'status_pembayaran' => 'pending' // Default awal sebelum bayar
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'Step 1 Berhasil: Data profil & domain disimpan',
            'data'    => $toko
        ], 201);
    }

    // STEP 2: Pemilihan Durasi, Metode, & Upload Bukti Pembayaran
    public function setupStep2(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'durasi_layanan'    => 'required|integer|between:1,5',
            'metode_pembayaran' => 'required|in:QRIS,transfer_bank',
            'bukti_pembayaran'  => 'required|image|mimes:jpeg,png,jpg|max:2048' // Validasi file gambar
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validasi Step 2 gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        // Cari data toko milik user yang statusnya masih pending
        $toko = Toko::where('user_id', $request->user()->_id)->first();

        if (!$toko) {
            return response()->json([
                'status'  => false,
                'message' => 'Silakan selesaikan Step 1 terlebih dahulu'
            ], 404);
        }

        // Proses penyimpanan file gambar bukti pembayaran ke folder storage lokal
        if ($request->hasFile('bukti_pembayaran')) {
            $file = $request->file('bukti_pembayaran');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('uploads/bukti_bayar'), $filename);
            $buktiPath = 'uploads/bukti_bayar/' . $filename;
        }

        // Update dokumen di MongoDB dengan data Step 2
        $toko->update([
            'durasi_layanan'    => (int) $request->durasi_layanan,
            'metode_pembayaran' => $request->metode_pembayaran,
            'bukti_pembayaran'  => $buktiPath
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'Step 2 Berhasil: Pendaftaran toko selesai, menunggu verifikasi admin',
            'data'    => $toko
        ], 200);
    }
}
