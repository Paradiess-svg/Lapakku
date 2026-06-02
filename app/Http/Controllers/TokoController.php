<?php

namespace App\Http\Controllers;

use App\Models\Toko;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TokoController extends Controller
{
    private function currentStore(Request $request)
    {
        $user = $request->user();
        $toko = Toko::where('user_id', $user->_id)->first();

        if (!$toko && $user->store_id) {
            $toko = Toko::find($user->store_id);
        }

        return $toko;
    }

    public function me(Request $request)
    {
        $toko = $this->currentStore($request);

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

    public function contactSettings(Request $request)
    {
        $toko = $this->currentStore($request);

        if (!$toko) {
            return response()->json([
                'status' => false,
                'message' => 'Toko tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $this->contactResource($toko)
        ], 200);
    }

    public function updateContactSettings(Request $request)
    {
        $toko = $this->currentStore($request);

        if (!$toko) {
            return response()->json([
                'status' => false,
                'message' => 'Toko tidak ditemukan'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'wa_number' => 'nullable|string|max:30',
            'instagram' => 'nullable|string|max:80',
            'email_toko' => 'nullable|email|max:255',
            'alamat' => 'nullable|string|max:500',
            'jam_operasional' => 'nullable|array',
            'jam_operasional.weekday' => 'nullable|string|max:80',
            'jam_operasional.saturday' => 'nullable|string|max:80',
            'jam_operasional.sunday' => 'nullable|string|max:80',
            'theme_color' => 'nullable|string|max:80',
            'template_style' => 'nullable|string|max:80',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validasi kontak toko gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        $toko->update([
            'wa_number' => $request->wa_number,
            'instagram' => ltrim((string) $request->instagram, '@'),
            'email_toko' => $request->email_toko,
            'alamat' => $request->alamat ?: $toko->alamat,
            'jam_operasional' => $request->jam_operasional ?: $toko->jam_operasional,
            'theme_color' => $request->theme_color ?: $toko->theme_color,
            'template_style' => $request->template_style ?: $toko->template_style,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Kontak toko berhasil diperbarui',
            'data' => $this->contactResource(Toko::find($toko->_id))
        ], 200);
    }

    public function publicContactSettings($toko_id)
    {
        $toko = Toko::find($toko_id);

        if (!$toko) {
            return response()->json([
                'status' => false,
                'message' => 'Lapak tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $this->contactResource($toko)
        ], 200);
    }

    private function contactResource(Toko $toko): array
    {
        return [
            'id' => $toko->_id,
            '_id' => $toko->_id,
            'nama_toko' => $toko->nama_toko,
            'nama_domain' => $toko->nama_domain,
            'deskripsi' => $toko->deskripsi,
            'alamat' => $toko->alamat,
            'wa_number' => $toko->wa_number,
            'instagram' => $toko->instagram,
            'email_toko' => $toko->email_toko,
            'jam_operasional' => $toko->jam_operasional ?: [
                'weekday' => '09:00 - 21:00',
                'saturday' => '10:00 - 20:00',
                'sunday' => 'Libur',
            ],
            'theme_color' => $toko->theme_color ?: 'navy',
            'template_style' => $toko->template_style ?: 'classic',
        ];
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
            'status_pembayaran' => 'pending',
            'status_toko'       => 'pending',
            'jam_operasional'   => [
                'weekday' => '09:00 - 21:00',
                'saturday' => '10:00 - 20:00',
                'sunday' => 'Libur',
            ]
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
