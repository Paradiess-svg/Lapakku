<?php

namespace App\Http\Controllers;

use App\Models\Produk;
use App\Models\Toko;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\File;

class ProdukController extends Controller
{
    // 1. CREATE: Tambah Produk Baru (Wajib ada kategori_id)
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'kategori_id'   => 'required|string',
            'nama_produk'   => 'required|string|max:255',
            'deskripsi'     => 'required|string',
            'harga'         => 'required|integer|min:0',
            'stok'          => 'required|integer|min:0',
            'gambar_produk' => 'required|image|mimes:jpeg,png,jpg|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => 'Validasi produk gagal',
                'errors'  => $validator->errors()
            ], 422);
        }

        $toko = Toko::where('user_id', $request->user()->_id)->first();
        if (!$toko) {
            return response()->json([
                'status'  => false,
                'message' => 'Akses ditolak: Anda harus mendirikan toko terlebih dahulu'
            ], 403);
        }

        if ($request->hasFile('gambar_produk')) {
            $file = $request->file('gambar_produk');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('uploads/produk'), $filename);
            $gambarPath = 'uploads/produk/' . $filename;
        }

        $produk = Produk::create([
            'toko_id'       => $toko->_id,
            'kategori_id'   => $request->kategori_id,
            'nama_produk'   => $request->nama_produk,
            'deskripsi'     => $request->deskripsi,
            'harga'         => (int) $request->harga,
            'stok'          => (int) $request->stok,
            'gambar_produk' => $gambarPath
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'Produk berhasil ditambahkan',
            'data'    => $produk
        ], 201);
    }

    // 2. READ (ALL): Ambil Semua Produk Khusus Toko Tenant (Bisa filter via Kategori)
    public function index(Request $request)
    {
        $toko = Toko::where('user_id', $request->user()->_id)->first();
        if (!$toko) {
            return response()->json(['status' => false, 'message' => 'Toko tidak ditemukan'], 404);
        }

        // Mulai query pencarian produk berdasarkan toko_id
        $query = Produk::where('toko_id', $toko->_id);

        // Fitur opsional buat FE: jika mereka kirim parameter ?kategori_id=xxx
        if ($request->has('kategori_id')) {
            $query->where('kategori_id', $request->kategori_id);
        }

        $produk = $query->get();

        return response()->json([
            'status'  => true,
            'message' => 'Daftar produk berhasil diambil',
            'data'    => $produk
        ], 200);
    }

    // 3. READ (SINGLE): Ambil Detail Satu Produk
    public function show(Request $request, $id)
    {
        $produk = Produk::find($id);
        if (!$produk) {
            return response()->json(['status' => false, 'message' => 'Produk tidak ditemukan'], 404);
        }

        return response()->json([
            'status'  => true,
            'message' => 'Detail produk berhasil diambil',
            'data'    => $produk
        ], 200);
    }

    // 4. UPDATE: Ubah Data Produk (Sinkron dengan kategori_id baru)
    public function update(Request $request, $id)
    {
        $produk = Produk::find($id);
        if (!$produk) {
            return response()->json(['status' => false, 'message' => 'Produk tidak ditemukan'], 404);
        }

        $validator = Validator::make($request->all(), [
            'kategori_id'   => 'required|string',
            'nama_produk'   => 'required|string|max:255',
            'deskripsi'     => 'required|string',
            'harga'         => 'required|integer|min:0',
            'stok'          => 'required|integer|min:0',
            'gambar_produk' => 'nullable|image|mimes:jpeg,png,jpg|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => 'Validasi ubah produk gagal',
                'errors'  => $validator->errors()
            ], 422);
        }

        $gambarPath = $produk->gambar_produk;

        // Jika tenant ganti foto produk, hapus foto lama biar local storage ga bengkak
        if ($request->hasFile('gambar_produk')) {
            if (File::exists(public_path($produk->gambar_produk))) {
                File::delete(public_path($produk->gambar_produk));
            }

            $file = $request->file('gambar_produk');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('uploads/produk'), $filename);
            $gambarPath = 'uploads/produk/' . $filename;
        }

        $produk->update([
            'kategori_id'   => $request->kategori_id,
            'nama_produk'   => $request->nama_produk,
            'deskripsi'     => $request->deskripsi,
            'harga'         => (int) $request->harga,
            'stok'          => (int) $request->stok,
            'gambar_produk' => $gambarPath
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'Produk berhasil diperbarui',
            'data'    => $produk
        ], 200);
    }

    // 5. DELETE: Hapus Produk Beserta File Fisiknya
    public function destroy($id)
    {
        $produk = Produk::find($id);
        if (!$produk) {
            return response()->json(['status' => false, 'message' => 'Produk tidak ditemukan'], 404);
        }

        if (File::exists(public_path($produk->gambar_produk))) {
            File::delete(public_path($produk->gambar_produk));
        }

        $produk->delete();

        return response()->json([
            'status'  => true,
            'message' => 'Produk berhasil dihapus'
        ], 200);
    }
}