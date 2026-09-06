<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class RbacController extends Controller
{
    /**
     * Menampilkan daftar penugasan role pegawai
     */
    public function index(Request $request)
    {
        try {
            $query = DB::table('akd_pegawai_role as apr')
                ->join('simpeg_pegawai as sp', 'apr.id_pegawai', '=', 'sp.id')
                ->join('akd_role_master as arm', 'apr.role_code', '=', 'arm.role_code')
                ->leftJoin('akd_program_studi as aps', function ($join) {
                    $join->on('apr.unit_id', '=', 'aps.kode_program_studi')
                        ->where('apr.unit_type', '=', 'prodi');
                })
                ->leftJoin('akd_fakultas as af', function ($join) {
                    $join->on('apr.unit_id', '=', 'af.kode_fakultas')
                        ->where('apr.unit_type', '=', 'fakultas');
                })
                ->select(
                    'apr.id',
                    'apr.id_pegawai',
                    'sp.nama as nama_pegawai',
                    'sp.nidn',
                    'sp.kode_prodi as prodi_homebase',
                    'apr.role_code',
                    'arm.role_name',
                    'arm.level_hierarki',
                    'apr.unit_type',
                    'apr.unit_id',
                    DB::raw("CASE 
                        WHEN apr.unit_type = 'prodi' THEN COALESCE(aps.nama_program_studi, apr.unit_id)
                        WHEN apr.unit_type = 'fakultas' THEN COALESCE(af.nama_fakultas, apr.unit_id)
                        ELSE 'Universitas'
                    END as nama_unit"),
                    'apr.status_jabatan',
                    'apr.is_active',
                    'apr.is_primary',
                    'apr.tgl_mulai',
                    'apr.tgl_selesai',
                    'apr.sk_nomor',
                    'apr.keterangan',
                    'apr.created_at',
                    'apr.updated_at'
                );

            if ($request->has('role_code') && !empty($request->role_code)) {
                $query->where('apr.role_code', $request->role_code);
            }

            if ($request->has('unit_id') && !empty($request->unit_id)) {
                $query->where('apr.unit_id', $request->unit_id);
            }

            if ($request->has('is_active') && $request->is_active !== null && $request->is_active !== '') {
                $query->where('apr.is_active', (int)$request->is_active);
            }

            $data = $query->orderBy('sp.nama', 'ASC')
                ->orderBy('apr.is_primary', 'DESC')
                ->get();

            return response()->json([
                'status' => true,
                'data'   => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Gagal memuat data penugasan role: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Master Role list
     */
    public function getMasterRoles()
    {
        try {
            $roles = DB::table('akd_role_master')
                ->orderBy('level_hierarki', 'ASC')
                ->orderBy('role_name', 'ASC')
                ->get();

            return response()->json([
                'status' => true,
                'data'   => $roles
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Opsi Pegawai/Dosen untuk Select2
     */
    public function getPegawaiOptions(Request $request)
    {
        try {
            $search = $request->get('q');
            $query = DB::table('simpeg_pegawai')
                ->select('id', 'nama', 'nidn', 'kode_prodi');

            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('nama', 'LIKE', "%{$search}%")
                        ->orWhere('nidn', 'LIKE', "%{$search}%");
                });
            }

            $pegawai = $query->orderBy('nama', 'ASC')->limit(50)->get();

            return response()->json([
                'status' => true,
                'data'   => $pegawai
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Opsi Program Studi & Fakultas
     */
    public function getUnitOptions()
    {
        try {
            $prodi = DB::table('akd_program_studi')
                ->select('kode_program_studi', 'nama_program_studi', 'kode_fakultas')
                ->orderBy('nama_program_studi', 'ASC')
                ->get();

            $fakultas = DB::table('akd_fakultas')
                ->select('kode_fakultas', 'nama_fakultas')
                ->orderBy('nama_fakultas', 'ASC')
                ->get();

            return response()->json([
                'status'   => true,
                'prodi'    => $prodi,
                'fakultas' => $fakultas
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Menyimpan penugasan baru
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_pegawai'     => 'required|integer',
            'role_code'      => 'required|string',
            'unit_type'      => 'required|in:universitas,fakultas,prodi',
            'unit_id'        => 'required|string',
            'status_jabatan' => 'required|in:definitif,plt,pj,interim',
            'is_primary'     => 'nullable|boolean',
            'tgl_mulai'      => 'nullable|date',
            'tgl_selesai'    => 'nullable|date',
            'sk_nomor'       => 'nullable|string|max:100',
            'keterangan'     => 'nullable|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => 'Validasi data gagal!',
                'errors'  => $validator->errors()
            ], 422);
        }

        try {
            // Cek apakah penugasan yang sama persis sudah ada
            $exists = DB::table('akd_pegawai_role')
                ->where('id_pegawai', $request->id_pegawai)
                ->where('role_code', $request->role_code)
                ->where('unit_id', $request->unit_id)
                ->where('is_active', 1)
                ->first();

            if ($exists) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Pegawai ini sudah memiliki peran aktif pada unit tersebut!'
                ], 400);
            }

            $isPrimary = $request->boolean('is_primary') ? 1 : 0;

            // Jika ditandai primary, normalkan record lain milik pegawai ini
            if ($isPrimary) {
                DB::table('akd_pegawai_role')
                    ->where('id_pegawai', $request->id_pegawai)
                    ->update(['is_primary' => 0]);
            } else {
                // Jika belum ada record primary sama sekali, jadikan yang pertama sebagai primary
                $hasPrimary = DB::table('akd_pegawai_role')
                    ->where('id_pegawai', $request->id_pegawai)
                    ->where('is_primary', 1)
                    ->exists();
                if (!$hasPrimary) {
                    $isPrimary = 1;
                }
            }

            $id = DB::table('akd_pegawai_role')->insertGetId([
                'id_pegawai'     => $request->id_pegawai,
                'role_code'      => $request->role_code,
                'unit_type'      => $request->unit_type,
                'unit_id'        => $request->unit_id,
                'status_jabatan' => $request->status_jabatan,
                'is_active'      => 1,
                'is_primary'     => $isPrimary,
                'tgl_mulai'      => $request->tgl_mulai ?: null,
                'tgl_selesai'    => $request->tgl_selesai ?: null,
                'sk_nomor'       => $request->sk_nomor ?: null,
                'keterangan'     => $request->keterangan ?: null,
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);

            return response()->json([
                'status'  => true,
                'message' => 'Penugasan role berhasil disimpan!',
                'id'      => $id
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Gagal menyimpan penugasan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Memperbarui penugasan
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'id_pegawai'     => 'required|integer',
            'role_code'      => 'required|string',
            'unit_type'      => 'required|in:universitas,fakultas,prodi',
            'unit_id'        => 'required|string',
            'status_jabatan' => 'required|in:definitif,plt,pj,interim',
            'is_primary'     => 'nullable|boolean',
            'is_active'      => 'nullable|boolean',
            'tgl_mulai'      => 'nullable|date',
            'tgl_selesai'    => 'nullable|date',
            'sk_nomor'       => 'nullable|string|max:100',
            'keterangan'     => 'nullable|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => 'Validasi data gagal!',
                'errors'  => $validator->errors()
            ], 422);
        }

        try {
            $isPrimary = $request->boolean('is_primary') ? 1 : 0;

            if ($isPrimary) {
                DB::table('akd_pegawai_role')
                    ->where('id_pegawai', $request->id_pegawai)
                    ->where('id', '!=', $id)
                    ->update(['is_primary' => 0]);
            }

            DB::table('akd_pegawai_role')
                ->where('id', $id)
                ->update([
                    'id_pegawai'     => $request->id_pegawai,
                    'role_code'      => $request->role_code,
                    'unit_type'      => $request->unit_type,
                    'unit_id'        => $request->unit_id,
                    'status_jabatan' => $request->status_jabatan,
                    'is_active'      => $request->has('is_active') ? ($request->boolean('is_active') ? 1 : 0) : 1,
                    'is_primary'     => $isPrimary,
                    'tgl_mulai'      => $request->tgl_mulai ?: null,
                    'tgl_selesai'    => $request->tgl_selesai ?: null,
                    'sk_nomor'       => $request->sk_nomor ?: null,
                    'keterangan'     => $request->keterangan ?: null,
                    'updated_at'     => now(),
                ]);

            return response()->json([
                'status'  => true,
                'message' => 'Penugasan role berhasil diperbarui!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Gagal memperbarui penugasan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Menghapus penugasan
     */
    public function destroy($id)
    {
        try {
            $role = DB::table('akd_pegawai_role')->where('id', $id)->first();
            if (!$role) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Data penugasan tidak ditemukan!'
                ], 404);
            }

            DB::table('akd_pegawai_role')->where('id', $id)->delete();

            // Jika yang dihapus adalah primary, jadikan salah satu sisa yang aktif sebagai primary
            if ($role->is_primary) {
                $next = DB::table('akd_pegawai_role')
                    ->where('id_pegawai', $role->id_pegawai)
                    ->where('is_active', 1)
                    ->first();
                if ($next) {
                    DB::table('akd_pegawai_role')->where('id', $next->id)->update(['is_primary' => 1]);
                }
            }

            return response()->json([
                'status'  => true,
                'message' => 'Penugasan role berhasil dihapus!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Gagal menghapus penugasan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle status aktif / nonaktif
     */
    public function toggleStatus($id)
    {
        try {
            $role = DB::table('akd_pegawai_role')->where('id', $id)->first();
            if (!$role) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Data penugasan tidak ditemukan!'
                ], 404);
            }

            $newStatus = $role->is_active == 1 ? 0 : 1;

            DB::table('akd_pegawai_role')
                ->where('id', $id)
                ->update([
                    'is_active'  => $newStatus,
                    'updated_at' => now()
                ]);

            return response()->json([
                'status'     => true,
                'new_status' => $newStatus,
                'message'    => 'Status penugasan berhasil diubah menjadi ' . ($newStatus == 1 ? 'Aktif' : 'Nonaktif') . '!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Gagal mengubah status: ' . $e->getMessage()
            ], 500);
        }
    }
}
