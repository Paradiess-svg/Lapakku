<?php

namespace App\Http\Controllers;

use App\Models\Kategori;
use App\Models\Toko;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\File;

class KategoriController extends Controller
{
    public function index(Request $request)
    {
        $toko = Toko::where('user_id', $request->user()->_id)->first();

        if (!$toko) {
            return response()->json(['status' => false, 'message' => 'Setup toko dahulu'], 404);
        }

        $kategori = Kategori::where('toko_id', $toko->_id)->get();
        return response()->json(['status' => true, 'data' => $kategori], 200);
    }

    // Tenant: Tambah Kategori Baru
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama_kategori' => 'required|string|max:255',
            'foto_icon'     => 'required|image|mimes:jpeg,png,jpg|max:1048'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $toko = Toko::where('user_id', $request->user()->_id)->first();
        if (!$toko) {
            return response()->json(['status' => false, 'message' => 'Setup toko dahulu'], 403);
        }

        if ($request->hasFile('foto_icon')) {
            $file = $request->file('foto_icon');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('uploads/kategori'), $filename);
            $iconPath = 'uploads/kategori/' . $filename;
        }

        $kategori = Kategori::create([
            'toko_id'       => $toko->_id,
            'nama_kategori' => $request->nama_kategori,
            'foto_icon'     => $iconPath
        ]);

        return response()->json(['status' => true, 'message' => 'Kategori berhasil dibuat', 'data' => $kategori], 201);
    }

    // Public: Ambil Kategori Toko Tertentu
    public function publicKategori($toko_id)
    {
        $kategori = Kategori::where('toko_id', $toko_id)->get();
        return response()->json(['status' => true, 'data' => $kategori], 200);
    }
}
