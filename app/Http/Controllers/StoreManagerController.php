<?php

namespace App\Http\Controllers;

use App\Models\Toko;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class StoreManagerController extends Controller
{
    private function currentStore(Request $request)
    {
        return Toko::where('user_id', $request->user()->_id)->first()
            ?: ($request->user()->store_id ? Toko::find($request->user()->store_id) : null);
    }

    private function managerResource(User $user): array
    {
        return [
            'id' => $user->_id,
            '_id' => $user->_id,
            'nama_lengkap' => $user->nama_lengkap,
            'email' => $user->email,
            'role' => $user->role ?: 'tenant_staff',
            'manager_role' => $user->manager_role ?: 'Operasional',
            'status' => $user->status ?: 'active',
            'store_id' => $user->store_id,
            'created_at' => $user->created_at,
        ];
    }

    public function index(Request $request)
    {
        $toko = $this->currentStore($request);
        if (!$toko) {
            return response()->json(['status' => false, 'message' => 'Toko tidak ditemukan'], 404);
        }

        $owner = $request->user();
        $managers = User::where('store_id', $toko->_id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($user) => $this->managerResource($user))
            ->values();

        return response()->json([
            'status' => true,
            'data' => [
                [
                    'id' => $owner->_id,
                    '_id' => $owner->_id,
                    'nama_lengkap' => $owner->nama_lengkap,
                    'email' => $owner->email,
                    'role' => $owner->role ?: 'tenant',
                    'manager_role' => 'Pemilik Utama',
                    'status' => 'active',
                    'store_id' => $toko->_id,
                    'is_owner' => true,
                ],
                ...$managers->all(),
            ],
        ], 200);
    }

    public function store(Request $request)
    {
        $toko = $this->currentStore($request);
        if (!$toko) {
            return response()->json(['status' => false, 'message' => 'Toko tidak ditemukan'], 404);
        }

        $validator = Validator::make($request->all(), [
            'nama_lengkap' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'manager_role' => 'required|string|max:80',
            'status' => 'nullable|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $manager = User::create([
            'nama_lengkap' => $request->nama_lengkap,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'tenant_staff',
            'manager_role' => $request->manager_role,
            'status' => $request->status ?: 'active',
            'store_id' => $toko->_id,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Pengelola toko berhasil ditambahkan',
            'data' => $this->managerResource($manager),
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $toko = $this->currentStore($request);
        if (!$toko) {
            return response()->json(['status' => false, 'message' => 'Toko tidak ditemukan'], 404);
        }

        $manager = User::where('_id', $id)->where('store_id', $toko->_id)->first();
        if (!$manager) {
            return response()->json(['status' => false, 'message' => 'Pengelola tidak ditemukan'], 404);
        }

        $validator = Validator::make($request->all(), [
            'nama_lengkap' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($manager->_id, '_id')],
            'password' => 'nullable|string|min:6',
            'manager_role' => 'required|string|max:80',
            'status' => 'required|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $payload = [
            'nama_lengkap' => $request->nama_lengkap,
            'email' => $request->email,
            'manager_role' => $request->manager_role,
            'status' => $request->status,
        ];

        if ($request->filled('password')) {
            $payload['password'] = Hash::make($request->password);
        }

        $manager->update($payload);

        return response()->json([
            'status' => true,
            'message' => 'Pengelola toko berhasil diperbarui',
            'data' => $this->managerResource(User::find($manager->_id)),
        ], 200);
    }

    public function destroy(Request $request, $id)
    {
        $toko = $this->currentStore($request);
        if (!$toko) {
            return response()->json(['status' => false, 'message' => 'Toko tidak ditemukan'], 404);
        }

        $manager = User::where('_id', $id)->where('store_id', $toko->_id)->first();
        if (!$manager) {
            return response()->json(['status' => false, 'message' => 'Pengelola tidak ditemukan'], 404);
        }

        $manager->delete();

        return response()->json([
            'status' => true,
            'message' => 'Pengelola toko berhasil dihapus',
        ], 200);
    }
}
