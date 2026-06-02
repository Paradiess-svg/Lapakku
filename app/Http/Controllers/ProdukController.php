<?php

namespace App\Http\Controllers;

use App\Models\Produk;
use App\Models\Toko;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\File;

class ProdukController extends Controller
{
    private function currentStore(Request $request)
    {
        return Toko::where('user_id', $request->user()->_id)->first()
            ?: ($request->user()->store_id ? Toko::find($request->user()->store_id) : null);
    }

    // 1. CREATE produk baru
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'kategori_id'   => 'required|string',
            'nama_produk'   => 'required|string|max:255',
            'deskripsi'     => 'required|string',
            'harga'         => 'required|integer|min:0',
            'harga_diskon'  => 'nullable|integer|min:0|lt:harga', // Harus lebih kecil dari harga asli
            'stok'          => 'required|integer|min:0',
            'gambar_produk' => 'required|image|mimes:jpeg,png,jpg|max:5120'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $toko = $this->currentStore($request);
        if (!$toko) {
            return response()->json(['status' => false, 'message' => 'Setup toko dahulu'], 403);
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
            'harga_diskon'  => $request->filled('harga_diskon') ? (int) $request->harga_diskon : null,
            'stok'          => (int) $request->stok,
            'gambar_produk' => $gambarPath
        ]);

        return response()->json(['status' => true, 'message' => 'Produk berhasil ditambahkan', 'data' => $produk], 201);
    }

    // 2. READ ALL produk tenant
    public function index(Request $request)
    {
        $toko = $this->currentStore($request);
        if (!$toko) {
            return response()->json(['status' => false, 'message' => 'Toko tidak ditemukan'], 404);
        }

        $query = Produk::where('toko_id', $toko->_id);
        if ($request->has('kategori_id')) {
            $query->where('kategori_id', $request->kategori_id);
        }

        return response()->json(['status' => true, 'data' => $query->get()], 200);
    }

    // 3. DETAIL single produk
    public function show(Request $request, $id)
    {
        $toko = $this->currentStore($request);
        if (!$toko) return response()->json(['status' => false, 'message' => 'Toko tidak ditemukan'], 404);

        $produk = Produk::find($id);
        if (!$produk) return response()->json(['status' => false, 'message' => 'Produk tidak ditemukan'], 404);
        if ((string) $produk->toko_id !== (string) $toko->_id) {
            return response()->json(['status' => false, 'message' => 'Produk bukan milik toko ini'], 403);
        }

        return response()->json(['status' => true, 'data' => $produk], 200);
    }

    // 4. UPDATE produk tenant
    public function update(Request $request, $id)
    {
        $produk = Produk::find($id);
        if (!$produk) return response()->json(['status' => false, 'message' => 'Produk tidak ditemukan'], 404);
        $toko = $this->currentStore($request);
        if (!$toko || (string) $produk->toko_id !== (string) $toko->_id) {
            return response()->json(['status' => false, 'message' => 'Produk bukan milik toko ini'], 403);
        }

        $validator = Validator::make($request->all(), [
            'kategori_id'   => 'required|string',
            'nama_produk'   => 'required|string|max:255',
            'deskripsi'     => 'required|string',
            'harga'         => 'required|integer|min:0',
            'harga_diskon'  => 'nullable|integer|min:0|lt:harga',
            'stok'          => 'required|integer|min:0',
            'gambar_produk' => 'nullable|image|mimes:jpeg,png,jpg|max:5120'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $gambarPath = $produk->gambar_produk;
        if ($request->hasFile('gambar_produk')) {
            if (File::exists(public_path($produk->gambar_produk))) File::delete(public_path($produk->gambar_produk));
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
            'harga_diskon'  => $request->filled('harga_diskon') ? (int) $request->harga_diskon : null,
            'stok'          => (int) $request->stok,
            'gambar_produk' => $gambarPath
        ]);

        return response()->json(['status' => true, 'message' => 'Produk berhasil diperbarui', 'data' => $produk], 200);
    }

    // 5. DELETE produk
    public function destroy(Request $request, $id)
    {
        $produk = Produk::find($id);
        if (!$produk) return response()->json(['status' => false, 'message' => 'Produk tidak ditemukan'], 404);
        $toko = $this->currentStore($request);
        if (!$toko || (string) $produk->toko_id !== (string) $toko->_id) {
            return response()->json(['status' => false, 'message' => 'Produk bukan milik toko ini'], 403);
        }
        if (File::exists(public_path($produk->gambar_produk))) File::delete(public_path($produk->gambar_produk));
        $produk->delete();
        return response()->json(['status' => true, 'message' => 'Produk berhasil dihapus'], 200);
    }
}
