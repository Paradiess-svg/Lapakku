<?php

namespace App\Http\Controllers;

use App\Models\Hero;
use App\Models\Toko;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class HeroController extends Controller
{
    public function index(Request $request)
    {
        $toko = Toko::where('user_id', $request->user()->_id)->first();

        if (!$toko) {
            return response()->json(['status' => false, 'message' => 'Setup toko dahulu'], 404);
        }

        $hero = Hero::where('toko_id', $toko->_id)->get();
        return response()->json(['status' => true, 'data' => $hero], 200);
    }

    // Tenant: Tambah Banner Slider Baru
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'gambar_hero' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            'judul'       => 'required|string|max:255',
            'deskripsi'   => 'required|string',
            'link_tujuan' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $toko = Toko::where('user_id', $request->user()->_id)->first();
        if (!$toko) {
            return response()->json(['status' => false, 'message' => 'Setup toko dahulu'], 403);
        }

        if ($request->hasFile('gambar_hero')) {
            $file = $request->file('gambar_hero');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('uploads/hero'), $filename);
            $heroPath = 'uploads/hero/' . $filename;
        }

        $hero = Hero::create([
            'toko_id'     => $toko->_id,
            'gambar_hero' => $heroPath,
            'judul'       => $request->judul,
            'deskripsi'   => $request->deskripsi,
            'link_tujuan' => $request->link_tujuan
        ]);

        return response()->json(['status' => true, 'message' => 'Banner hero berhasil diupload', 'data' => $hero], 201);
    }

    // Public: Ambil Banner Slider Milik Toko Tertentu
    public function publicHero($toko_id)
    {
        $hero = Hero::where('toko_id', $toko_id)->get();
        return response()->json(['status' => true, 'data' => $hero], 200);
    }
}
