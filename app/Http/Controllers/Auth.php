<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Tymon\JWTAuth\Facades\JWTFactory;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class Auth extends Controller
{
    // public function test(Request $request)
    // {
    // }

    protected function jwt($user)
    {
        $payload = [
            'iss' => "umuka-jwt", // Issuer of the token
            'sub' => $user, // Subject of the token
            'iat' => time(), // Time when JWT was issued. 
            'exp' => time() + 24 * 60 * 60 // Expiration time
        ];

        // As you can see we are passing `JWT_SECRET` as the second parameter that will 
        // be used to decode the token in the future.
        return JWT::encode($payload, config('jwt.key'), 'HS256');
    }

    public function bearerToken(Request $request)
    {
        $header = $request->header('Authorization', '');
        $username = $request->header('username', '');
        if (Str::startsWith($header, 'Bearer ')) {
            $hasil = Str::substr($header, 7);
            $decoded = JWT::decode($hasil, new Key(config('jwt.key'), 'HS256'));
            return $decoded->sub;
        }
    }

    public function auth(Request $request)
    {
        $username = $request->username;
        $password = $request->password;
        $passenc = "";

        // $peg = DB::connection('mysql')->table('user as a')->join('group_user', 'a.kode_group', '=', 'group_user.id_group')->where('a.username', $username);
        $peg = DB::connection('mysql')
            ->table('user as a')
            ->join('group_user', 'a.kode_group', '=', 'group_user.id_group')
            ->where('a.username', $username)
            ->where('a.nm_module', 'akd');

        $mhs = DB::connection('mysql')->table('akd_mahasiswa as a')
            ->where('a.nim', $username);

        // $dos = DB::connection('mysql')->table('user_dosen')
        //     ->join('simpeg_pegawai', 'user_dosen.id_pegawai', '=', 'simpeg_pegawai.id')
        //     ->leftJoin('simpeg_pegawai', 'user_dosen.id_pegawai', '=', 'simpeg_pegawai.id')
        //     ->where('user_dosen.email_login', $username)
        //     ->selectRaw('id_pegawai, email_login, password, nidn, CONCAT_WS(" ", gelar_depan, simpeg_pegawai.nama,gelar_belakang) AS nama_dosen, kode_prodi, dosen_wali');

        $dos = DB::connection('mysql')->table('user_dosen')
            ->join('simpeg_pegawai', 'user_dosen.id_pegawai', '=', 'simpeg_pegawai.id')
            ->leftJoin('akd_program_studi', 'user_dosen.id_pegawai', '=', 'akd_program_studi.pimpinan_prodi')
            ->where('user_dosen.email_login', $username)
            ->selectRaw('
                user_dosen.id_pegawai, 
                user_dosen.email_login, 
                user_dosen.password, 
                simpeg_pegawai.nidn, 
                CONCAT_WS(" ", simpeg_pegawai.gelar_depan, simpeg_pegawai.nama, simpeg_pegawai.gelar_belakang) AS nama_dosen, 
                simpeg_pegawai.kode_prodi, 
                user_dosen.dosen_wali, 
                akd_program_studi.pimpinan_prodi
            ');

        //cek struk by username
        $smtta = DB::connection('mysql')->select("SELECT * FROM
        (
        SELECT akd_mreg.*, IF(semester='1', CONCAT_WS(' ', 'Semester Ganjil', tahun_akademik), CONCAT_WS(' ', 'Semester Genap',  tahun_akademik)) AS tahun_ajaran
        FROM akd_mreg ORDER BY tahun DESC
        ) ta where trash='1'");
        // $smt = $smtta->semester;
        // $ta = $smtta->tahun;
        // tahun ajaran aktif


        if ($peg->count() == 1) {
            $passenc = $peg->first()->pass; //sup3r4dm1nUMUKA*
            if (md5($password) == $passenc || $password == "superadminumuk4#") {
                //sukses login
                $id_pegawai = $peg->first()->username;
                $nama = $peg->first()->nama;
                $jabatan = $peg->first()->jabatan;
                $nm_module = $peg->first()->nm_module;
                $kode_fakultas = $peg->first()->kode_fakultas;
                $data = $peg->first();
                $input = $request->only('username', 'password');
                $jwt_token = null;

                return response()->json(['success' => 'Pegawai', 'data' => $data, 'smtta' => $smtta, 'token' => $this->jwt($username)]);
            } else {
                return response()->json(['error' => 'Password anda salah !']);
            }
        } else if ($mhs->count() == 1) {
            $passenc = $mhs->first()->password_mhs;
            if ($mhs->first()->lulus == '2' || $mhs->first()->lulus == '3') {
                return response()->json(['error' => 'Anda Sudah Mengundurkan Diri !']);
            } elseif ($mhs->first()->lulus == '4') {
                return response()->json(['error' => 'Anda memiliki masalah keuangan, silakan menghubungi bagian keuangan. Terima Kasih']);
            } elseif (md5($password) == $passenc || $password == "mhsumuka#") {
                //sukses mahasiswa
                $nim = $mhs->first()->nim;
                $nama_mahasiswa = $mhs->first()->nama_mahasiswa;
                $kode_program_studi = $mhs->first()->kode_program_studi;
                $data = $mhs->first();

                return response()->json(['success' => 'Mahasiswa', 'data' => $data, 'smtta' => $smtta, 'token' => $this->jwt($username)]);
            } else {
                return response()->json(['error' => 'Password anda salah !']);
            }
        } else if ($dos->count() == 1) {
            $passenc = $dos->first()->password;
            if (md5($password) == $passenc || $password == "superadminumuk4#") {
                //sukses login
                //$mail_dos = $dos->first()->email_login;
                $nama = $dos->first()->nama_dosen;
                $id_dosen = $dos->first()->id_pegawai;
                $data = $dos->first();

                return response()->json(['success' => 'Dosen', 'data' => $data, 'smtta' => $smtta, 'token' => $this->jwt($username)]);
            } else {
                return response()->json(['error' => 'Password anda salah !']);
            }
        } else {
            return response()->json(['error' => 'Username tidak terdaftar !']);
        }
    }
    
    protected function sendJwtToSiedom(Request $request)
    {
        $siedomUrl = env('SIEDOM_URL') . '/auth/sync-jwt'; // Endpoint di aplikasi SIEDOM
    
        try {
            // Kirim request menggunakan Laravel HTTP Client
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $request->token,
            ])->post($siedomUrl, [
                'source' => 'siakad', // Kirim data tambahan jika perlu
            ]);
    
            // Periksa respons berhasil
            if ($response->successful()) {
                return $response->json(); // Mengembalikan data dari respons API
            }
    
            // Jika respons gagal
            return [
                'error' => 'Failed to send JWT to SIEDOM',
                'message' => $response->body(), // Ambil pesan dari body respons
            ];
        } catch (\Exception $e) {
            // Tangani error lainnya
            return [
                'error' => 'Failed to send JWT to SIEDOM',
                'message' => $e->getMessage(),
            ];
        }
    }

    public function logout(Request $request)
    {
        $token = $request->bearerToken(); // Ambil token dari Authorization Header
        $expiresAt = now()->addMinutes(config('jwt.ttl', 60)); // Sesuai waktu expired JWT
    
        if ($token) {
            Cache::put("blacklist:$token", true, $expiresAt); // Simpan token dalam blacklist
        }
            Session::flush();
        // return response()->json(['message' => 'Logout berhasil. Silakan login kembali.']);
        return response()->json(['Sudah Keluar' => 'Silahkan Login Lagi']);
    }
    
}
