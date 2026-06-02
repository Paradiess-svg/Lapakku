<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Paket;
use App\Models\Produk;
use App\Models\Toko;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SuperAdminController extends Controller
{
    private function ensureSuperAdmin(Request $request)
    {
        if (($request->user()->role ?: 'tenant') !== 'super_admin') {
            return response()->json([
                'status' => false,
                'message' => 'Akses hanya untuk super admin'
            ], 403);
        }

        return null;
    }

    private function storeResource(Toko $toko): array
    {
        $owner = User::find($toko->user_id);

        return [
            'id' => $toko->_id,
            '_id' => $toko->_id,
            'user_id' => $toko->user_id,
            'nama_toko' => $toko->nama_toko,
            'deskripsi' => $toko->deskripsi,
            'alamat' => $toko->alamat,
            'tipe_domain' => $toko->tipe_domain,
            'nama_domain' => $toko->nama_domain,
            'durasi_layanan' => $toko->durasi_layanan,
            'metode_pembayaran' => $toko->metode_pembayaran,
            'bukti_pembayaran' => $toko->bukti_pembayaran,
            'status_pembayaran' => $toko->status_pembayaran ?: 'pending',
            'status_toko' => $toko->status_toko ?: (($toko->status_pembayaran === 'sukses') ? 'aktif' : 'pending'),
            'owner' => $owner ? [
                'id' => $owner->_id,
                'nama_lengkap' => $owner->nama_lengkap,
                'email' => $owner->email,
                'role' => $owner->role ?: 'tenant',
            ] : null,
            'produk_count' => Produk::where('toko_id', $toko->_id)->count(),
            'order_count' => Order::where('toko_id', $toko->_id)->count(),
            'payment_total' => (int) (($toko->durasi_layanan ?: 1) * 1200000),
            'created_at' => $toko->created_at,
            'updated_at' => $toko->updated_at,
        ];
    }

    public function summary(Request $request)
    {
        if ($forbidden = $this->ensureSuperAdmin($request)) {
            return $forbidden;
        }

        $stores = Toko::all();
        $orders = Order::all();

        return response()->json([
            'status' => true,
            'data' => [
                'total_stores' => $stores->count(),
                'pending_payments' => $stores->where('status_pembayaran', 'pending')->count(),
                'active_stores' => $stores->filter(fn ($store) => ($store->status_toko ?: '') === 'aktif' || $store->status_pembayaran === 'sukses')->count(),
                'total_users' => User::count(),
                'total_products' => Produk::count(),
                'total_orders' => $orders->count(),
                'total_revenue' => $orders->sum('total_harga'),
            ]
        ], 200);
    }

    public function stores(Request $request)
    {
        if ($forbidden = $this->ensureSuperAdmin($request)) {
            return $forbidden;
        }

        $stores = Toko::orderBy('created_at', 'desc')->get()->map(fn ($store) => $this->storeResource($store))->values();

        return response()->json(['status' => true, 'data' => $stores], 200);
    }

    public function storeDetail(Request $request, $id)
    {
        if ($forbidden = $this->ensureSuperAdmin($request)) {
            return $forbidden;
        }

        $store = Toko::find($id);
        if (!$store) {
            return response()->json(['status' => false, 'message' => 'Toko tidak ditemukan'], 404);
        }

        return response()->json([
            'status' => true,
            'data' => [
                ...$this->storeResource($store),
                'orders' => Order::where('toko_id', $store->_id)->orderBy('created_at', 'desc')->get(),
                'produk' => Produk::where('toko_id', $store->_id)->orderBy('created_at', 'desc')->get(),
            ]
        ], 200);
    }

    public function users(Request $request)
    {
        if ($forbidden = $this->ensureSuperAdmin($request)) {
            return $forbidden;
        }

        $users = User::orderBy('created_at', 'desc')->get()->map(function ($user) {
            $store = Toko::where('user_id', $user->_id)->first();

            return [
                'id' => $user->_id,
                '_id' => $user->_id,
                'nama_lengkap' => $user->nama_lengkap,
                'email' => $user->email,
                'role' => $user->role ?: 'tenant',
                'has_shop' => (bool) $store,
                'store' => $store ? [
                    'id' => $store->_id,
                    'nama_toko' => $store->nama_toko,
                    'nama_domain' => $store->nama_domain,
                    'status_toko' => $store->status_toko ?: 'pending',
                ] : null,
                'created_at' => $user->created_at,
            ];
        })->values();

        return response()->json(['status' => true, 'data' => $users], 200);
    }

    public function domains(Request $request)
    {
        if ($forbidden = $this->ensureSuperAdmin($request)) {
            return $forbidden;
        }

        $domains = Toko::orderBy('created_at', 'desc')->get()->map(function ($store) {
            $data = $this->storeResource($store);
            return [
                'id' => $store->_id,
                '_id' => $store->_id,
                'domain' => $store->nama_domain,
                'tipe_domain' => $store->tipe_domain,
                'type' => $store->tipe_domain === 'custom' ? 'Custom' : 'Subdomain',
                'store' => $store->nama_toko,
                'status_dns' => $store->status_pembayaran === 'sukses' ? 'Connected' : 'Pending',
                'status_toko' => $data['status_toko'],
                'expired' => $store->durasi_layanan ? now()->addYears((int) $store->durasi_layanan)->toDateString() : null,
            ];
        })->values();

        return response()->json(['status' => true, 'data' => $domains], 200);
    }

    public function updatePayment(Request $request, $id)
    {
        if ($forbidden = $this->ensureSuperAdmin($request)) {
            return $forbidden;
        }

        $validator = Validator::make($request->all(), [
            'status_pembayaran' => 'required|in:pending,sukses,gagal',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $store = Toko::find($id);
        if (!$store) {
            return response()->json(['status' => false, 'message' => 'Toko tidak ditemukan'], 404);
        }

        $statusToko = match ($request->status_pembayaran) {
            'sukses' => 'aktif',
            'gagal' => 'suspended',
            default => 'pending',
        };

        $store->update([
            'status_pembayaran' => $request->status_pembayaran,
            'status_toko' => $statusToko,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Status pembayaran toko berhasil diperbarui',
            'data' => $this->storeResource(Toko::find($store->_id))
        ], 200);
    }

    public function updateStoreStatus(Request $request, $id)
    {
        if ($forbidden = $this->ensureSuperAdmin($request)) {
            return $forbidden;
        }

        $validator = Validator::make($request->all(), [
            'status_toko' => 'required|in:pending,aktif,suspended',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $store = Toko::find($id);
        if (!$store) {
            return response()->json(['status' => false, 'message' => 'Toko tidak ditemukan'], 404);
        }

        $store->update(['status_toko' => $request->status_toko]);

        return response()->json([
            'status' => true,
            'message' => 'Status toko berhasil diperbarui',
            'data' => $this->storeResource(Toko::find($store->_id))
        ], 200);
    }

    public function updateDomain(Request $request, $id)
    {
        if ($forbidden = $this->ensureSuperAdmin($request)) {
            return $forbidden;
        }

        $validator = Validator::make($request->all(), [
            'tipe_domain' => 'required|in:gratis,custom',
            'nama_domain' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $store = Toko::find($id);
        if (!$store) {
            return response()->json(['status' => false, 'message' => 'Toko tidak ditemukan'], 404);
        }

        $domainExists = Toko::where('nama_domain', $request->nama_domain)
            ->where('_id', '!=', $store->_id)
            ->exists();

        if ($domainExists) {
            return response()->json([
                'status' => false,
                'message' => 'Domain sudah dipakai toko lain'
            ], 422);
        }

        $store->update([
            'tipe_domain' => $request->tipe_domain,
            'nama_domain' => strtolower($request->nama_domain),
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Domain toko berhasil diperbarui',
            'data' => $this->storeResource(Toko::find($store->_id))
        ], 200);
    }

    public function plans(Request $request)
    {
        if ($forbidden = $this->ensureSuperAdmin($request)) {
            return $forbidden;
        }

        $plans = Paket::orderBy('harga', 'asc')->get();
        return response()->json(['status' => true, 'data' => $plans], 200);
    }

    public function storePlan(Request $request)
    {
        if ($forbidden = $this->ensureSuperAdmin($request)) {
            return $forbidden;
        }

        $validator = Validator::make($request->all(), [
            'nama_paket' => 'required|string|max:255',
            'durasi_hari' => 'required|integer|min:1',
            'durasi_layanan' => 'nullable|integer|min:1|max:10',
            'harga' => 'required|integer|min:0',
            'bonus' => 'nullable|string|max:255',
            'fitur' => 'nullable|array',
            'is_popular' => 'nullable|boolean',
            'status' => 'nullable|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $plan = Paket::create([
            'nama_paket' => $request->nama_paket,
            'durasi_hari' => (int) $request->durasi_hari,
            'durasi_layanan' => (int) ($request->durasi_layanan ?: max(1, ceil($request->durasi_hari / 365))),
            'harga' => (int) $request->harga,
            'bonus' => $request->bonus,
            'fitur' => $request->fitur ?: [],
            'is_popular' => (bool) $request->is_popular,
            'status' => $request->status ?: 'active',
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Paket layanan berhasil ditambahkan',
            'data' => $plan
        ], 201);
    }

    public function updatePlan(Request $request, $id)
    {
        if ($forbidden = $this->ensureSuperAdmin($request)) {
            return $forbidden;
        }

        $plan = Paket::find($id);
        if (!$plan) {
            return response()->json(['status' => false, 'message' => 'Paket tidak ditemukan'], 404);
        }

        $validator = Validator::make($request->all(), [
            'nama_paket' => 'required|string|max:255',
            'durasi_hari' => 'required|integer|min:1',
            'durasi_layanan' => 'nullable|integer|min:1|max:10',
            'harga' => 'required|integer|min:0',
            'bonus' => 'nullable|string|max:255',
            'fitur' => 'nullable|array',
            'is_popular' => 'nullable|boolean',
            'status' => 'required|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $plan->update([
            'nama_paket' => $request->nama_paket,
            'durasi_hari' => (int) $request->durasi_hari,
            'durasi_layanan' => (int) ($request->durasi_layanan ?: max(1, ceil($request->durasi_hari / 365))),
            'harga' => (int) $request->harga,
            'bonus' => $request->bonus,
            'fitur' => $request->fitur ?: [],
            'is_popular' => (bool) $request->is_popular,
            'status' => $request->status,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Paket layanan berhasil diperbarui',
            'data' => Paket::find($plan->_id)
        ], 200);
    }

    public function deletePlan(Request $request, $id)
    {
        if ($forbidden = $this->ensureSuperAdmin($request)) {
            return $forbidden;
        }

        $plan = Paket::find($id);
        if (!$plan) {
            return response()->json(['status' => false, 'message' => 'Paket tidak ditemukan'], 404);
        }

        $plan->delete();

        return response()->json([
            'status' => true,
            'message' => 'Paket layanan berhasil dihapus'
        ], 200);
    }
}
