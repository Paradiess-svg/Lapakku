<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Produk;
use App\Models\Toko;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    // 1. STOREFRONT: Lihat Produk Toko Tertentu (Akses Publik Pembeli)
    public function publicProducts($toko_id)
    {
        $toko = Toko::find($toko_id);
        if (!$toko) {
            return response()->json(['status' => false, 'message' => 'Lapak tidak ditemukan'], 404);
        }

        $produk = Produk::where('toko_id', $toko_id)->get();

        return response()->json([
            'status' => true,
            'message' => 'Berhasil mengambil etalase toko ' . $toko->nama_toko,
            'data' => $produk
        ], 200);
    }

    // 2. CHECKOUT: Pembelian Produk & Potong Stok Otomatis (Akses Publik Pembeli)
    public function checkout(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'toko_id'           => 'required|string',
            'nama_pembeli'      => 'required|string|max:255',
            'email_pembeli'     => 'required|email',
            'alamat_pengiriman' => 'required|string',
            'items'             => 'required|array|min:1',
            'items.*.produk_id' => 'required|string',
            'items.*.qty'       => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $orderItems = [];
        $totalHarga = 0;

        // Loop keranjang belanja untuk cek stok & hitung subtotal
        foreach ($request->items as $item) {
            $produk = Produk::find($item['produk_id']);

            if (!$produk || $produk->toko_id !== $request->toko_id) {
                return response()->json(['status' => false, 'message' => 'Produk tidak valid atau bukan milik toko ini'], 400);
            }

            if ($produk->stok < $item['qty']) {
                return response()->json(['status' => false, 'message' => 'Stok produk ' . $produk->nama_produk . ' tidak mencukupi'], 400);
            }

            $subtotal = $produk->harga * $item['qty'];
            $totalHarga += $subtotal;

            // Potong stok fisik produk di database
            $produk->stok -= $item['qty'];
            $produk->save();

            // Susun dokumen embedded array untuk item order
            $orderItems[] = [
                'produk_id'   => $produk->_id,
                'nama_produk' => $produk->nama_produk,
                'harga'       => $produk->harga,
                'qty'         => $item['qty'],
                'subtotal'    => $subtotal
            ];
        }

        // Simpan dokumen utama transaksi
        $order = Order::create([
            'toko_id'           => $request->toko_id,
            'nama_pembeli'      => $request->nama_pembeli,
            'email_pembeli'     => $request->email_pembeli,
            'alamat_pengiriman' => $request->alamat_pengiriman,
            'items'             => $orderItems,
            'total_harga'       => $totalHarga,
            'status_pesanan'    => 'pending'
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'Pesanan berhasil dibuat, stok telah diamankan!',
            'data'    => $order
        ], 201);
    }

    // 3. TENANT AREA: Lihat Pesanan Masuk (Wajib Login Token Tenant)
    public function tenantOrders(Request $request)
    {
        $toko = Toko::where('user_id', $request->user()->_id)->first();
        if (!$toko) {
            return response()->json(['status' => false, 'message' => 'Lapak Anda belum disetup'], 404);
        }

        // Ambal semua orderan yang masuk khusus ke toko milik tenant ini
        $orders = Order::where('toko_id', $toko->_id)->get();

        return response()->json([
            'status'  => true,
            'message' => 'Daftar pesanan masuk berhasil diambil',
            'data'    => $orders
        ], 200);
    }

    // 4. TENANT AREA: Update Status Pesanan (Wajib Login Token Tenant)
    public function updateStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status_pesanan' => 'required|in:pending,diproses,selesai,dibatalkan'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $order = Order::find($id);
        if (!$order) {
            return response()->json(['status' => false, 'message' => 'Data pesanan tidak ditemukan'], 404);
        }

        $order->update([
            'status_pesanan' => $request->status_pesanan
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'Status pesanan berhasil diubah menjadi ' . $request->status_pesanan,
            'data'    => $order
        ], 200);
    }
}