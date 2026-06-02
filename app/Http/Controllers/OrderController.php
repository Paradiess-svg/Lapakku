<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Produk;
use App\Models\Toko;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    private function currentStore(Request $request)
    {
        return Toko::where('user_id', $request->user()->_id)->first()
            ?: ($request->user()->store_id ? Toko::find($request->user()->store_id) : null);
    }

    public function publicProducts($toko_id)
    {
        $toko = Toko::find($toko_id);
        if (!$toko) return response()->json(['status' => false, 'message' => 'Lapak tidak ditemukan'], 404);
        $produk = Produk::where('toko_id', $toko_id)->get();
        return response()->json(['status' => true, 'data' => $produk], 200);
    }

    // KRUSIAL: Logika Hitung Kasir Pembeli Berdasarkan Aturan Harga Baru
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

        foreach ($request->items as $item) {
            $produk = Produk::find($item['produk_id']);

            if (!$produk || (string) $produk->toko_id !== (string) $request->toko_id) {
                return response()->json(['status' => false, 'message' => 'Produk tidak cocok dengan toko ini'], 400);
            }

            if ($produk->stok < $item['qty']) {
                return response()->json(['status' => false, 'message' => 'Stok produk ' . $produk->nama_produk . ' habis'], 400);
            }

            // ATURAN PAKEM BARU: Cek pakai harga diskon atau harga normal
            $hargaAktif = ($produk->harga_diskon !== null && $produk->harga_diskon > 0) ? $produk->harga_diskon : $produk->harga;

            $subtotal = $hargaAktif * $item['qty'];
            $totalHarga += $subtotal;

            $produk->stok -= $item['qty'];
            $produk->save();

            $orderItems[] = [
                'produk_id'   => $produk->_id,
                'nama_produk' => $produk->nama_produk,
                'harga_dipakai'=> $hargaAktif, // Mengunci harga yang dibayar saat itu
                'qty'         => $item['qty'],
                'subtotal'    => $subtotal
            ];
        }

        $order = Order::create([
            'toko_id'           => $request->toko_id,
            'nama_pembeli'      => $request->nama_pembeli,
            'email_pembeli'     => $request->email_pembeli,
            'alamat_pengiriman' => $request->alamat_pengiriman,
            'items'             => $orderItems,
            'total_harga'       => $totalHarga,
            'status_pesanan'    => 'pending'
        ]);

        return response()->json(['status' => true, 'message' => 'Pesanan sukses dibuat!', 'data' => $order], 201);
    }

    public function publicTrackOrder($toko_id, $order_id)
    {
        $order = Order::where('_id', $order_id)->where('toko_id', $toko_id)->first();
        if (!$order) {
            return response()->json(['status' => false, 'message' => 'Pesanan tidak ditemukan di toko ini'], 404);
        }
        return response()->json(['status' => true, 'data' => $order], 200);
    }

    public function tenantOrders(Request $request)
    {
        $toko = $this->currentStore($request);
        if (!$toko) return response()->json(['status' => false, 'message' => 'Setup toko dahulu'], 404);
        $orders = Order::where('toko_id', $toko->_id)->get();
        return response()->json(['status' => true, 'data' => $orders], 200);
    }

    public function updateStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), ['status_pesanan' => 'required|in:pending,diproses,selesai,dibatalkan']);
        if ($validator->fails()) return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        
        $order = Order::find($id);
        if (!$order) return response()->json(['status' => false, 'message' => 'Order tidak ada'], 404);
        $toko = $this->currentStore($request);
        if (!$toko || (string) $order->toko_id !== (string) $toko->_id) {
            return response()->json(['status' => false, 'message' => 'Order bukan milik toko ini'], 403);
        }
        
        $order->update(['status_pesanan' => $request->status_pesanan]);
        return response()->json(['status' => true, 'message' => 'Status berhasil diubah', 'data' => $order], 200);
    }
}
