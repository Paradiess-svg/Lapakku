<?php

namespace App\Http\Controllers;

use App\Models\Gallery;
use App\Models\Produk;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class GalleryController extends Controller
{
    // Tenant: Tambah Foto Detail ke Galeri Produk
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'produk_id' => 'required|string',
            'foto_path' => 'required|image|mimes:jpeg,png,jpg|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        if ($request->hasFile('foto_path')) {
            $file = $request->file('foto_path');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('uploads/gallery'), $filename);
            $galleryPath = 'uploads/gallery/' . $filename;
        }

        $gallery = Gallery::create([
            'produk_id' => $request->produk_id,
            'foto_path' => $galleryPath
        ]);

        return response()->json(['status' => true, 'message' => 'Foto berhasil ditambahkan ke galeri produk', 'data' => $gallery], 201);
    }

    // Public: Ambil Semua Foto Galeri Kepunyaan 1 Produk
    public function publicGallery($produk_id)
    {
        $gallery = Gallery::where('produk_id', $produk_id)->get();
        return response()->json(['status' => true, 'data' => $gallery], 200);
    }
}