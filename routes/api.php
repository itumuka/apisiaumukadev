<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\Cors;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Route::middleware('auth:api')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::post("/auth-login", "Auth@auth")->name('auth_login');
Route::get("/bearerToken", "Auth@bearerToken")->name('bearerToken');
Route::get("/logout", "Auth@logout")->name('logout');
Route::get("/check-session", "Mahasiswa@check_session")->name('check_session');
Route::get('/debug-db-triggers', function() {
    return response()->json(Illuminate\Support\Facades\DB::select("SHOW TRIGGERS"));
});

Route::get('/akademik/template-input-nilai-uts', 'Akademik@templatenilai_uts_export')->name('templatenilai_uts_export');
Route::get('/akademik/template-input-nilai-uas', 'Akademik@templatenilai_uas_export')->name('templatenilai_uas_export');
Route::post('/akademik/import-nilai-uts', 'Akademik@import_nilai_uts')->name('import_nilai_uts');
Route::post('/akademik/import-nilai-uas', 'Akademik@import_nilai_uas')->name('import_nilai_uas');

Route::get('/akademik/template-presensi', 'Akademik@templatepresensiexport')->name('templatepresensiexport');
Route::get('/akademik/pkkmb/template', 'Akademik@pkkmbTemplate')->name('akpkkmbTemplate');
Route::get("/akademik/download-bantuan", "Akademik@download_bantuan")->name('dsndownload_bantuan');
Route::get("/mahasiswa/download-bantuan-mhs", "Akademik@download_bantuan_mhs")->name('mhsdownload_bantuan');
Route::get("/akademik/jadwalujian/export-template", "AkademikTools@export_template_jadwalujian")->name('export_template_jadwalujian');
Route::middleware(['jwtverifie'])->group(function () {
    Route::post("/mahasiswa/cekhereg", "Mahasiswa@cekhereg")->name('cekhereg');
    Route::get("/mahasiswa/filter-khs", "Mahasiswa@filter_khs")->name('filter_khs');
    Route::get("/mahasiswa/select-khs", "Mahasiswa@select_khs")->name('select_khs');
    Route::get("/mahasiswa/tampil-jadwal-makul", "Mahasiswa@tampiljadwalmakul")->name('mhstampiljadwalmakul');
    Route::get("/mahasiswa/dispensasikhs", "Mahasiswa@dispensasikhs")->name('mhsdispensasikhs');
    Route::get("/mahasiswa/tampil-presensi-makul", "Mahasiswa@presensimakul")->name('mhstampilpresensimakul');
    Route::post("/mahasiswa/simpan-presensi", "Mahasiswa@simpan_presensi_mhs")->name('save_presensi_mhs');
    Route::get("/mahasiswa/revisi-krs", "Mahasiswa@revisikrs")->name('mhsrevisikrs');
    Route::get("/mahasiswa/-hapus-revisi-krs", "Mahasiswa@hapus_revisikrs")->name('mhshapus_revisikrs');
    Route::get("/mahasiswa/ambil-krs", "Mahasiswa@ambilkrs")->name('mhsambilkrs');
    Route::post("/mahasiswa/simpan-krs", "Mahasiswa@simpan_krs")->name('simpan_krs');
    Route::get("/mahasiswa/data-khs", "Mahasiswa@datakhs")->name('mhsdatakhs');
    Route::get("/mahasiswa/transkrip-nilai", "Mahasiswa@transkripnilai")->name('mhstranskripnilai');
    Route::get("/mahasiswa/kalender-krs", "Mahasiswa@cek_bisa_krs")->name('mhscek_bisa_krs');
    Route::get("/mahasiswa/kalender-cetak-kartu", "Mahasiswa@cek_bisa_cetak_kartuujian")->name('mhscek_bisa_cetak_kartuujian');
    Route::get("/mahasiswa/kalender-revisikrs", "Mahasiswa@cek_bisa_revisikrs")->name('mhscek_bisa_revisikrs');
    Route::post("/mahasiswa/edit-password", "Mahasiswa@edit_password_mhs")->name('mhsedit_password');

    Route::post("/mahasiswa/profil-personal", "Mahasiswa@profil_personal")->name('mhsprofil_personal');
    Route::post("/mahasiswa/profil-ayah", "Mahasiswa@profil_ayah")->name('mhsprofil_ayah');
    Route::post("/mahasiswa/profil-ibu", "Mahasiswa@profil_ibu")->name('mhsprofil_ibu');
    Route::post("/mahasiswa/upload-foto", "Mahasiswa@upload_foto")->name('mhsupload_foto');

    Route::get("/mahasiswa/tampilstatusva", "Mahasiswa@tampilstatusva")->name('tampilstatusva');
    Route::get("/mahasiswa/tampilstatuspembayaran", "Mahasiswa@tampilstatuspembayaran")->name('tampilstatuspembayaran');
    Route::get("/mahasiswa/tampilstatuspembayaranriwayat", "Mahasiswa@tampilstatuspembayaranriwayat")->name('tampilstatuspembayaranriwayat');
    Route::post("/mahasiswa/generate-group-va", "PembayaranController@updateByKodeBiling")->name('mhsgenerate_group_va');
    
    Route::post("/mahasiswa/simpan-profil-mahasiswa", "Mahasiswa@simpan_user_profil")->name('mhssimpan_user_profil');
    Route::post("/mahasiswa/simpan-pendidikan-mahasiswa", "Mahasiswa@simpan_pendidikan_mahasiswa")->name('mhssimpan_pendidikan_mahasiswa');
    Route::post("/mahasiswa/simpan-ayah-mahasiswa", "Mahasiswa@simpan_ayah_mahasiswa")->name('mhssimpan_ayah_mahasiswa');
    Route::post("/mahasiswa/simpan-ibu-mahasiswa", "Mahasiswa@simpan_ibu_mahasiswa")->name('mhssimpan_ibu_mahasiswa');
    Route::get("/mahasiswa/tampilprovinsi", "Mahasiswa@tampilprovinsi")->name('mhstampilprovinsi');
    Route::get("/mahasiswa/tampilkabupaten", "Mahasiswa@tampilkabupaten")->name('mhstampilkabupaten');
    Route::get("/mahasiswa/tampilkecamatan", "Mahasiswa@tampilkecamatan")->name('mhstampilkecamatan');
    Route::get("/mahasiswa/check-edom", "Mahasiswa@checkedom")->name('mhscheckedom');
    Route::get('/mahasiswa/getbukti', 'Mahasiswa@getBukti')->name('mhsgetbukti');

    Route::get("/dekanat/data-acckrs", "Dekanat@data_acckrs")->name('dkndatacckrs');
    Route::post("/dekanat/edit_password_dekanadmin", "Dekanat@edit_password_dekanadmin")->name('edit_password_dekanadmin');
    // Route::get("/dekanat/data-makulpenawaran", "Dekanat@data_makulpenawaran")->name('dkndata_makulpenawaran');
    Route::get("/dekanat/data-transkripnilai", "Dekanat@data_transkripnilai")->name('dkndata_transkripnilai');
    Route::get("/dekanat/ubahstatus-acckrs", "Dekanat@ubahstatus_acckrs")->name('dknubahstatus_acckrs');
    Route::get("/dekanat/setting-dosenwali", "Dekanat@dosenwali")->name('dkndosenwali');
    Route::get("/dekanat/daftar-mahasiswa", "Dekanat@daftar_mahasiswa")->name('dkndaftar_mahasiswa');
    Route::get("/dekanat/daftarmhs-pa", "Dekanat@daftarmhs_pa")->name('dkndaftarmhs_pa');
    Route::get("/dekanat/list-mhs-already", "Dekanat@list_mhs_already")->name('dknlist_mhs_already');
    Route::post("/dekanat/add-mhs-dosenwali", "Dekanat@save_mhs_dosenwali")->name('dknsave_mhs_dosenwali');
    Route::get("/dekanat/nonaktif-mhs-dosenwali", "Dekanat@nonaktif_mhs_dosenwali")->name('dknnonaktif_mhs_dosenwali');
    Route::get("/dekanat/hapus-mhs-dosenwali", "Dekanat@hapus_mhs_dosen_wali")->name('dknhapus_mhs_dosen_wali');
    Route::get("/dekanat/makulpenawaran-ba-ujian", "Dekanat@data_makul_ba_ujian")->name('dkndata_makul_ba_ujian');
    Route::post("/dekanat/simpan-nilai-uts", "Dekanat@simpan_nilai_uts")->name('dknsimpan_nilai_uts');
    Route::post("/dekanat/simpan-nilai-uas", "Dekanat@simpan_nilai_uas")->name('dknsimpan_nilai_uas');


    // Route::get("/akademik/download-bantuan", "Akademik@download_bantuan")->name('dsndownload_bantuan');
    Route::get("/akademik/cekkalenderbatasinputnilai", "Akademik@cekkalenderbatasinputnilai")->name('cekkalenderbatasinputnilai');
    Route::get("/akademik/modal-sks-ambil", "Akademik@modal_sks_ambil")->name('dsnmodal_sks_ambil');

    Route::get("/akademik/cek-transkrip-krs", "Akademik@cek_transkrip_krs")->name('dsncek_transkrip_krs');
    Route::post("/akademik/cetak-khs", "Akademik@cetakkhs")->name('cetakkhs');
    Route::post("/akademik/cetak-krs", "Akademik@cetakkrs")->name('cetakkrs');
    Route::post("/akademik/get-mhs", "Akademik@getmhs_cetak")->name('getmhs_cetak');
    Route::post("/akademik/get-kelas-mk", "Akademik@getkelasmk_cetak")->name('getkelasmk_cetak');
    Route::post("/akademik/get-kelas-baujian", "Akademik@getkelasdanbaujian_cetak")->name('getkelasdanbaujian_cetak');
    Route::get("/akademik/riwayat-mengajar", "Akademik@riwayat_mengajar")->name('dsnriwayat_mengajar');

    Route::get("/akademik/presensi-mhs", "Akademik@presensi_mhs")->name('dsnpresensi_mhs');
    Route::get("/akademik/presensi-permakul1", "Akademik@presensi_permakul1")->name('dsnpersensi_permakul1');
    Route::get("/akademik/daftarmhs-pa", "Akademik@daftarmhs_pa")->name('dsndaftarmhs_pa');
    Route::get("/akademik/presensi-permakul", "Akademik@presensi_permakul")->name('dsnpersensi_permakul');
    Route::post("/akademik/simpan-presensi", "Akademik@simpan_presensi_mhs")->name('simpan_presensi_mhs');
    Route::post("/akademik/edit-presensi", "Akademik@edit_presensi_mhs")->name('edit_presensi_mhs');
    Route::get("/akademik/hapus-presensi", "Akademik@hapus_presensi")->name('dsnhapus_presensi');
    Route::post("/akademik/data-hitungpresensi", "Akademik@data_hitung_presensi")->name('data_hitung_presensi');
    Route::post("/akademik/autopertemuan-ba", "Akademik@auto_pertemuan")->name('auto_pertemuan');
    Route::post("/akademik/autopertemuan-presensi-mhs", "Akademik@auto_pertemuan_presensi")->name('auto_pertemuan_presensi');
    Route::post("/akademik/simpan-ba", "Akademik@simpan_ba")->name('dsnsimpan_ba');
    Route::get("/akademik/makulpenawaran-ba", "Akademik@data_makul_ba")->name('data_makul_ba');
    
    Route::get("/akademik/makul-ba-ujian", "Akademik@data_makul_ba_ujian_kaprodi")->name('data_makul_ba_ujian_kaprodi');
    
    Route::get("/akademik/makulpenawaran-ba-ujian", "Akademik@data_makul_ba_ujian")->name('data_makul_ba_ujian');
    Route::get("/akademik/rekap-ba", "Akademik@rekap_ba")->name('rekap_ba');
    Route::post("/akademik/ubah-ba", "Akademik@ubah_ba")->name('dsnubah_ba');
    Route::get("/akademik/hapus-ba", "Akademik@hapus_ba")->name('dsnhapus_ba');
    Route::get("/akademik/validated-ba", "Akademik@validated_ba")->name('dsnvalidated_ba');
    Route::get("/akademik/list-ba", "Akademik@list_ba")->name('dsnlist_ba');
    Route::post("/akademik/lihat-absen-ujian", "Akademik@data_lihat_absen_ujian")->name('data_lihat_absen_ujian');
    Route::post("/akademik/bantuan-nim-ba-ujian", "Akademik@list_mhs_help_ba_ujian")->name('list_mhs_help_ba_ujian');

    Route::post("/akademik/lihat-mhs-presensi", "Akademik@data_lihat_mhs_presensi")->name('data_lihat_mhs_presensi');
    Route::post("/akademik/edit-password-dosen", "Akademik@edit_password_dosen")->name('dsnedit_password');


    Route::get("/akademik/select-nim", "Akademik@select_nim_tidak_hadir")->name('dsnselect_nim');
    Route::post("/akademik/simpan-ba-ujian", "Akademik@simpan_ba_ujian")->name('dsnsimpan_ba_ujian');
    Route::get("/akademik/list-ba-ujian", "Akademik@list_ba_ujian")->name('dsnlist_ba_ujian');
    Route::post("/akademik/ubah-ba-ujian", "Akademik@ubah_ba_ujian")->name('dsnubah_ba_ujian');
    Route::get("/akademik/hapus-ba-ujian", "Akademik@hapus_ba_ujian")->name('dsnhapus_ba_ujian');
    Route::get("/akademik/makulpenawaran-ba-ujian", "Akademik@data_makul_ba_ujian")->name('dsndata_makul_ba_ujian');
    Route::post("/akademik/edit_password_dekanadmin", "Dekanat@edit_password_dekanadmin")->name('edit_password_dekanadmindekan');
    // Route::middleware(['Cors'])->group(function () {
    //     Route::post('/akademik/import-presensi', 'Akademik@import_presensi')->name('dsnimport_presensi');
    // });


    // Route::group(['middleware' => ['cors']], function () {
    //     Route::post('/akademik/import-presensi', 'Akademik@import_presensi')->name('dsnimport_presensi');
    // });


    Route::get("/akademik/data-mhs-inputnilai", "Akademik@list_mhs_inputnilai")->name('dsnlist_mhs_inputnilai');
    Route::get("/akademik/persen-nilai-mk", "Akademik@persen_nilai_mk")->name('dsnpersen_nilai_mk');
    Route::get("/akademik/select-predikat-nilai", "Akademik@select_predikat_nilai_huruf")->name('dsnselect_predikat_nilai_huruf');
    Route::post("/akademik/simpan-nilai-uts", "Akademik@simpan_nilai_uts")->name('dsnsimpan_nilai_uts');
    Route::post("/akademik/simpan-nilai-uas", "Akademik@simpan_nilai_uas")->name('dsnsimpan_nilai_uas');

    // Route::get('/akademik/template-input-nilai-uts', 'Akademik@templatenilai_uts_export')->name('templatenilai_uts_export');
    // Route::post('/akademik/import-nilai', 'Akademik@import_nilai_khs')->name('dsnimport_nilai_khs');
    Route::post('/akademik/import-presensi', 'Akademik@import_presensi')->name('dsnimport_presensi');

    Route::get('/akademik/export-ba', 'Akademik@export_berita_acara')->name('akexport_berita_acara');


    Route::get("/akademik/home-kalenderakademik", "Akademik@home_kalenderakademik")->name('home_kalenderakademik');
    Route::get("/akademik/home-kalenderakademikbase", "Akademik@home_kalenderakademikbase")->name('home_kalenderakademikbase');
    Route::get("/akademik/dashboard-stats", "Akademik@dashboard_stats")->name('dashboard_stats');
    Route::get("/akademik/change-session-tahunakademik", "Akademik@change_session_tahunakademik")->name('change_session_tahunakademik');
    Route::get("/akademik/select-tahunakademik", "Akademik@select_tahunakademik")->name('select_tahunakademik');
    Route::get("/akademik/tahunajaran", "Akademik@tahunajaran")->name('aktahunajaran');
    Route::post("/akademik/simpan-tahunajaran", "Akademik@simpan_tahunajaran")->name('simpan_tahunajaran');
    Route::post("/akademik/edit-tahunajaran", "Akademik@edit_tahunajaran")->name('edit_tahunajaran');
    Route::get("/akademik/hapus-tahunajaran", "Akademik@hapus_tahunajaran")->name('hapus_tahunajaran');
    Route::get("/akademik/ubahstatus-tahunajaran", "Akademik@ubahstatus_tahunajaran")->name('ubahstatus_tahunajaran');
    //makul prasyarat
    Route::get("/akademik/makulprasyarat", "Akademik@data_makulprasyarat")->name('data_makulprasyarat');
    Route::post("/akademik/dropdown-prodi", "Akademik@dropdown_prodi")->name('dropdown_prodi');
    Route::post("/akademik/dropdown-prodifakultas", "Akademik@dropdown_prodifakultas")->name('dropdown_prodifakultas');
    Route::get("/akademik/select-makul", "Akademik@select_makul")->name('select_makul');
    Route::post("/akademik/simpan-makulprasyarat", "Akademik@simpan_makulprasyarat")->name('simpan_makulprasyarat');
    Route::post("/akademik/edit-makulprasyarat", "Akademik@edit_makulprasyarat")->name('edit_makulprasyarat');
    Route::get("/akademik/hapus-makulprasyarat", "Akademik@hapus_makulprasyarat")->name('hapus_makulprasyarat');
    //makul penawaran
    Route::get("/akademik/makulpenawaran", "Akademik@data_makulpenawaran")->name('data_makulpenawaran');

    Route::post("/akademik/simpan-makulpenawaran", "Akademik@simpan_makulpenawaran")->name('simpan_makulpenawaran');
    Route::post("/akademik/update-rps", "Akademik@update_url_rps")->name('update_url_rps');
    Route::post("/akademik/edit-makulpenawaran", "Akademik@edit_makulpenawaran")->name('edit_makulpenawaran');
    Route::post("/akademik/edit-jadwalujian", "Akademik@edit_jadwalujian")->name('edit_jadwalujian');
    Route::post("/akademik/jadwalujian/import", "AkademikTools@import_jadwalujian")->name('import_jadwalujian');
    Route::post("/akademik/edit-makulpenawaran-dkn", "Akademik@edit_makulpenawaran_dkn")->name('edit_makulpenawaran_dkn');
    Route::get("/akademik/hapus-makulpenawaran", "Akademik@hapus_makulpenawaran")->name('hapus_makulpenawaran');
    //input khs
    Route::get("/akademik/input-khs", "Akademik@data_inputnilaikhs")->name('akinputnilaikhs');
    Route::get("/akademik/kegiatanakademik", "Akademik@kegiatanakademik")->name('akkegiatanakademik');
    Route::post("/akademik/simpan-kegiatanakademik", "Akademik@simpan_kegiatanakademik")->name('simpan_kegiatanakademik');
    Route::post("/akademik/edit-kegiatanakademik", "Akademik@edit_kegiatanakademik")->name('edit_kegiatanakademik');
    Route::get("/akademik/hapus-kegiatanakademik", "Akademik@hapus_kegiatanakademik")->name('hapus_kegiatanakademik');
    Route::get("/akademik/ubahstatus-kegiatanakademik", "Akademik@ubahstatus_kegiatanakademik")->name('ubahstatus_kegiatanakademik');
    Route::get("/akademik/fakultas", "Akademik@fakultas")->name('akfakultas');
    Route::get("/akademik/tampilpimpinan", "Akademik@tampilpimpinan")->name('aktampilpimpinan');
    Route::get("/akademik/edittampilpimpinan", "Akademik@edittampilpimpinan")->name('akedittampilpimpinan');
    Route::get("/akademik/edittampiljeniskelamin", "Akademik@edittampiljeniskelamin")->name('akedittampiljeniskelamin');
    Route::get("/akademik/tampiljenjang", "Akademik@tampiljenjang")->name('aktampiljenjang');
    Route::get("/akademik/tampiltahunangkatan", "Akademik@tampil_tahunajaran")->name('aktampiltahunangkatan');
    Route::get("/akademik/tampiltahunangkatanmaba", "Akademik@tampil_tahunajaranmaba")->name('aktampiltahunangkatanmaba');
    Route::post("/akademik/simpan-fakultas", "Akademik@simpan_fakultas")->name('simpan_fakultas');
    Route::post("/akademik/edit-fakultas", "Akademik@edit_fakultas")->name('edit_fakultas');
    Route::get("/akademik/hapus-fakultas", "Akademik@hapus_fakultas")->name('hapus_fakultas');
    Route::get("/akademik/ubahstatus-fakultas", "Akademik@ubahstatus_fakultas")->name('ubahstatus_fakultas');
    Route::get("/akademik/programstudi", "Akademik@programstudi")->name('akprogramstudi');
    Route::get("/akademik/tampilfakultas", "Akademik@tampilfakultas")->name('aktampilfakultas');
    Route::post("/akademik/simpan-programstudi", "Akademik@simpan_programstudi")->name('simpan_programstudi');
    Route::post("/akademik/edit-programstudi", "Akademik@edit_programstudi")->name('edit_programstudi');
    Route::get("/akademik/hapus-programstudi", "Akademik@hapus_programstudi")->name('hapus_programstudi');
    Route::get("/akademik/ubahstatus-programstudi", "Akademik@ubahstatus_programstudi")->name('ubahstatus_programstudi');
    Route::get("/akademik/kurikulum", "Akademik@kurikulum")->name('akkurikulum');
    Route::get("/akademik/select-kurikulum", "Akademik@select_kurikulum")->name('select_kurikulum');
    Route::get("/akademik/select-sifatmatakuliah", "Akademik@select_sifatmatakuliah")->name('select_sifatmatakuliah');
    Route::get("/akademik/tampilprogramstudi", "Akademik@tampilprogramstudi")->name('aktampilprogramstudi');
    Route::get("/akademik/programstudi-fak", "Akademik@tampilprodi_perfak")->name('dkntampilprodi_perfak');
    Route::post("/akademik/simpan-kurikulum", "Akademik@simpan_kurikulum")->name('simpan_kurikulum');
    Route::post("/akademik/edit-kurikulum", "Akademik@edit_kurikulum")->name('edit_kurikulum');
    Route::get("/akademik/hapus-kurikulum", "Akademik@hapus_kurikulum")->name('hapus_kurikulum');
    Route::get("/akademik/ubahstatus-kurikulum", "Akademik@ubahstatus_kurikulum")->name('ubahstatus_kurikulum');
    Route::get("/akademik/kalenderakademik", "Akademik@kalenderakademik")->name('akkalenderakademik');
    Route::get("/akademik/tampilkegiatan", "Akademik@tampilkegiatan")->name('aktampilkegiatan');
    Route::post("/akademik/simpan-kalenderakademik", "Akademik@simpan_kalenderakademik")->name('simpan_kalenderakademik');
    Route::post("/akademik/edit-kalenderakademik", "Akademik@edit_kalenderakademik")->name('edit_kalenderakademik');
    Route::get("/akademik/hapus-kalenderakademik", "Akademik@hapus_kalenderakademik")->name('hapus_kalenderakademik');
    Route::get("/akademik/ubahstatus-kalenderakademik", "Akademik@ubahstatus_kalenderakademik")->name('ubahstatus_kalenderakademik');
    // matakuliah
    Route::get("/akademik/matakuliah", "Akademik@matakuliah")->name('akmatakuliah');
    Route::post("/akademik/simpan-matakuliah", "Akademik@simpan_matakuliah")->name('simpan_matakuliah');
    Route::post("/akademik/edit-matakuliah", "Akademik@edit_matakuliah")->name('edit_matakuliah');
    Route::get("/akademik/hapus-matakuliah", "Akademik@hapus_matakuliah")->name('hapus_matakuliah');
    Route::get("/akademik/ubahstatus-matakuliah", "Akademik@ubahstatus_matakuliah")->name('ubahstatus_matakuliah');
    // Dosen
    Route::get("/akademik/dosen", "Akademik@dosen")->name('akdosen');
    Route::get("/akademik/qrdosen", "Akademik@qrdosen")->name('akqrdosen');
    Route::get("/akademik/qrdosenmanajemen", "Akademik@qrdosenmanajemen")->name('akqrdosenmanajemen');
    Route::post("/akademik/saveqrcode", "Akademik@saveAllQrCode")->name('aksaveqrcode');
    Route::post("/akademik/saveqrcodemanajemen", "Akademik@saveAllQrCodeManajemen")->name('aksaveqrcodemanajemen');
    Route::post("/akademik/saveqrcodeacc", "Akademik@saveAllQrCodeACC")->name('aksaveqrcodeacc');
    Route::get("/akademik/ceknimterakhir", "Akademik@ceknimterakhir")->name('ceknimterakhir');
    Route::get("/akademik/select-dosen", "Akademik@select_dosen")->name('akselect_dosen');
    // mahasiswa
    Route::get("/akademik/mahasiswa", "Akademik@mahasiswa")->name('akmahasiswa');
    // Password Mahasiswa
    Route::get("/akademik/passwordmahasiswa", "Akademik@passwordmahasiswa")->name('akpasswordmahasiswa');
    Route::post("/akademik/edit-passwordmahasiswamhs", "Akademik@edit_passwordmahasiswamhs")->name('edit_passwordmahasiswamhs');
    Route::post("/akademik/edit-passwordmahasiswaortu", "Akademik@edit_passwordmahasiswaortu")->name('edit_passwordmahasiswaortu');
    // Registrasi
    Route::get("/akademik/registrasi", "Akademik@registrasi")->name('akregistrasi');
    Route::post("/akademik/edit-registrasi", "Akademik@edit_registrasi")->name('edit_registrasi');
    // Her Registrasi
    Route::get("/akademik/herregistrasi", "Akademik@herregistrasi")->name('akherregistrasi');
    Route::post("/akademik/edit-herregistrasi", "Akademik@edit_herregistrasi")->name('edit_herregistrasi');
    // User
    Route::get("/akademik/user", "Akademik@user")->name('akuser');
    Route::post("/akademik/simpan-user", "Akademik@simpan_user")->name('simpan_user');
    Route::post("/akademik/edit-user", "Akademik@edit_user")->name('edit_user');
    Route::get("/akademik/hapus-user", "Akademik@hapus_user")->name('hapus_user');
    Route::get("/akademik/ubahstatus-user", "Akademik@ubahstatus_user")->name('ubahstatus_user');
    // Daftar Hadir Kuliah
    Route::get("/akademik/daftarhadirkuliah", "Akademik@daftarhadirkuliah")->name('akdaftarhadirkuliah');
    // Daftar Hadir Ujian
    Route::get("/akademik/daftarhadirujian", "Akademik@daftarhadirujian")->name('akdaftarhadirujian');
    // Kartu Ujian
    Route::get("/akademik/kartuujian", "Akademik@kartuujian")->name('akkartuujian');
    Route::post("/akademik/dropdown-angkatan", "Akademik@dropdown_angkatan")->name('dropdown_angkatan');
    // Kartu Hasil Studi
    Route::get("/akademik/hasilstudi", "Akademik@hasilstudi")->name('akhasilstudi');
    // Dosen Wali
    Route::get("/akademik/dosenwali", "Akademik@dosenwali")->name('akdosenwali');

    Route::get("/akademik/nilaimahasiswa", "Akademik@nilaimahasiswa")->name('nilaimahasiswa');
    // Route::post("/akademik/dropdown-prodi", "Akademik@dropdown_prodi")->name('dropdown_prodi');
    // Route::post("/akademik/dropdown-prodifakultas", "Akademik@dropdown_prodifakultas")->name('dropdown_prodifakultas');
    // Route::get("/akademik/select-makul", "Akademik@select_makul")->name('select_makul');
    Route::post("/akademik/simpan-nilaimahasiswa", "Akademik@simpan_nilaimahasiswa")->name('simpan_nilaimahasiswa');
    Route::post("/akademik/edit-nilaimahasiswa", "Akademik@edit_nilaimahasiswa")->name('edit_nilaimahasiswa');
    Route::get("/akademik/hapus-nilaimahasiswa", "Akademik@hapus_nilaimahasiswa")->name('hapus_nilaimahasiswa');
    // Laporan Her Registrasi
    Route::get("/akademik/tampiltahunakademik", "Akademik@tampiltahunakademik")->name('tampiltahunakademik');
    Route::get("/akademik/lapherregistrasi", "Akademik@lapherregistrasi")->name('aklapherregistrasi');
    Route::get("/akademik/batassksher", "Akademik@batassksher")->name('batassksher');
    Route::post("/akademik/dropdown-akademik", "Akademik@dropdown_akademik")->name('dropdown_akademik');
    // Kewarganegaraan
    Route::get("/akademik/kewarganegaraan", "Akademik@kewarganegaraan")->name('akkewarganegaraan');
    // Dispensasi
    Route::get("/akademik/dispensasi", "Akademik@dispensasi")->name('akdispensasi');
    Route::get("/akademik/lap_ipk_Mahasiswa_detail", "Akademik@lap_ipk_Mahasiswa_detail")->name('aklap_ipk_Mahasiswa_detail');
    Route::get("/akademik/forminputcamaba", "Akademik@forminputcamaba")->name('akforminputcamaba');
    // Route::get("/akademik/tampildispensasi", "Akademik@tampildispensasi")->name('aktampildispensasi');
    // Transkip Nilai
    Route::get("/akademik/transkipnilai", "Akademik@transkipnilai")->name('aktranskipnilai');
    // Transkip Akademik
    Route::get("/akademik/transkipakademik", "Akademik@transkipakademik")->name('aktranskipakademik');

    // Daftar Maba
    Route::get("/akademik/daftarmaba", "Akademik@daftarmaba")->name('akdaftarmaba');
    // Mahasiswa Lulusan
    Route::get("/akademik/mahasiswalulusan", "Akademik@mahasiswalulusan")->name('akmahasiswalulusan');
    Route::get("/akademik/mahasiswalulusan1", "Akademik@mahasiswalulusan1")->name('akmahasiswalulusan1');
    Route::get("/akademik/mahasiswalulusan2", "Akademik@mahasiswalulusan2")->name('akmahasiswalulusan2');
    Route::get("/akademik/status_lulus_mahasiswa", "Akademik@status_lulus_mahasiswa")->name('status_lulus_mahasiswa');
    Route::get("/akademik/status_mengundurkan_diri_mahasiswa", "Akademik@status_mengundurkan_diri_mahasiswa")->name('status_mengundurkan_diri_mahasiswa');
    Route::get("/akademik/status_dikeluarkan_mahasiswa", "Akademik@status_dikeluarkan_mahasiswa")->name('status_dikeluarkan_mahasiswa');
    Route::get("/akademik/status_batal_mahasiswa", "Akademik@status_batal_mahasiswa")->name('status_batal_mahasiswa');

    Route::get("/akademik/tampilkegiatanakademik", "Akademik@tampilkegiatanakademik")->name('aktampilkegiatanakademik');
    // Route::post("/akademik/hasilstudi", "Akademik@cetakkhs")->name('cetakkhs');
    Route::post("/akademik/cetaktranskipnilai", "Akademik@cetaktranskipnilai")->name('cetaktranskipnilai');
    Route::post("/akademik/cetaktranskipakademik", "Akademik@cetaktranskipakademik")->name('cetaktranskipakademik');
    Route::post("/akademik/cetaktranskipakademikinggris", "Akademik@cetaktranskipakademikinggris")->name('cetaktranskipakademikinggris');
    Route::post("/akademik/cetakdaftarhadirkuliah", "Akademik@cetakdaftarhadirkuliah")->name('cetakdaftarhadirkuliah');
    Route::post("/akademik/cetakdaftarhadirujian", "Akademik@cetakdaftarhadirujian")->name('cetakdaftarhadirujian');
    Route::post("/akademik/cetakkartuujian", "Akademik@cetakkartuujian")->name('cetakkartuujian');
    Route::post("/akademik/cetakkartuhasilstudi", "Akademik@cetakkartuhasilstudi")->name('cetakkartuhasilstudi');
    Route::get("/akademik/tampilsemester", "Akademik@tampilsemester")->name('aktampilsemester');
    Route::get("/akademik/edittampilfakultas", "Akademik@edittampilfakultas")->name('akedittampilfakultas');
    Route::get("/akademik/edittampilprogramstudi", "Akademik@edittampilprogramstudi")->name('akedittampilprogramstudi');
    Route::get("/akademik/tampil-mhs", "Akademik@tampilmhs")->name('aktampilmhs');
    Route::get("/akademik/tampilperprodi", "Akademik@tampilperprodi")->name('aktampilperprodi');
    Route::get("/akademik/tampiljalurpmb", "Akademik@tampiljalurpmb")->name('aktampiljalurpmb');
    Route::get("/akademik/tampilprovinsi", "Akademik@tampilprovinsi")->name('aktampilprovinsi');
    Route::get("/akademik/editmaba", "Akademik@editmaba")->name('akeditmaba');
    Route::get("/akademik/ubahstatus-camaba", "Akademik@ubahstatus_camaba")->name('ubahstatus_camaba');
    Route::get("/akademik/tampil_tahunakademik2", "Akademik@tampil_tahunakademik2")->name('aktampil_tahunakademik2');
    Route::post("/akademik/detail-camaba", "Akademik@detail_camaba")->name('detail_camaba');
    // Route::get("/akademik/select-nilai", "Akademik@select_nilai")->name('select_nilai');
    Route::get("/akademik/select-nilai", "Akademik@select_nilai")->name('akselect_nilai');
    Route::post("/akademik/simpan-nilai_akhir", "Akademik@simpan_nilai_akhir")->name('simpan_nilai_akhir');
    Route::post("/akademik/simpan-nilai_akhir1", "Akademik@simpan_nilai_akhir1")->name('simpan_nilai_akhir1');
    Route::get("/akademik/tampilkabupaten", "Akademik@tampilkabupaten")->name('aktampilkabupaten');
    Route::post("/akademik/simpan-camaba", "Akademik@simpan_camaba")->name('simpan_camaba');
    Route::post("/akademik/cetak-daftarhadirkuliah1", "Akademik@cetakdaftarhadirkuliah1")->name('cetakdaftarhadirkuliah1');
    Route::post("/akademik/get-daftarhadirkuliah", "Akademik@getdaftarhadirkuliah_cetak")->name('getdaftarhadirkuliah_cetak');
    Route::post("/akademik/get-daftarhadirkuliah1", "Akademik@getdaftarhadirkuliah_cetak1")->name('getdaftarhadirkuliah_cetak1');
    Route::post("/akademik/cetak-daftarhadirujian1", "Akademik@cetakdaftarhadirujian1")->name('cetakdaftarhadirujian1');
    Route::post("/akademik/get-daftarhadirujian", "Akademik@getdaftarhadirujian_cetak")->name('getdaftarhadirujian_cetak');
    Route::post("/akademik/cetak-kartuujian1", "Akademik@cetakkartuujian1")->name('cetakkartuujian1');
    Route::post("/akademik/get-kartuujian", "Akademik@getkartuujian_cetak")->name('getkartuujian_cetak');
    Route::get("/akademik/select-semester", "Akademik@select_semester")->name('select_semester');
    Route::get("/akademik/select-makulprasyarat", "Akademik@select_makulprasyarat")->name('select_makulprasyarat');
    Route::post("/akademik/edit-transkipnilai", "Akademik@edit_transkipnilai")->name('edit_transkipnilai');
    Route::post("/akademik/sinkron-transkrip", "Akademik@sinkron_transkrip")->name('sinkron_transkrip');
    Route::get("/akademik/ubahstatus-registrasi", "Akademik@ubahstatus_registrasi")->name('ubahstatus_registrasi');
    Route::get("/akademik/edittampilkurikulum", "Akademik@edittampilkurikulum")->name('akedittampilkurikulum');
    Route::get("/akademik/edittampiljenisher", "Akademik@edittampiljenisher")->name('akedittampiljenisher');
    Route::post("/akademik/simpan-herregistrasi", "Akademik@simpan_herregistrasi")->name('simpan_herregistrasi');
    Route::get("/akademik/hapus-herregistrasi", "Akademik@hapus_herregistrasi")->name('hapus_herregistrasi');
    Route::post("/akademik/cetak-daftarhadirujianjamak", "Akademik@cetakdaftarhadirujianjamak")->name('cetakdaftarhadirujianjamak');
    // KRS Mahasiswa
    Route::get("/akademik/krsmahasiswa", "Akademik@krsmahasiswa")->name('akkrsmahasiswa');
    Route::post("/akademik/cetakkrsmahasiswa", "Akademik@cetakkrsmahasiswa")->name('cetakkrsmahasiswa');
    Route::post("/akademik/cetak-krsmahasiswa1", "Akademik@cetakkrsmahasiswa1")->name('cetakkrsmahasiswa1');
    Route::post("/akademik/get-krsmahasiswa", "Akademik@getkrsmahasiswa_cetak")->name('getkrsmahasiswa_cetak');
    Route::post("/akademik/cetak-kartuhasilstudi1", "Akademik@cetakkartuhasilstudi1")->name('cetakkartuhasilstudi1');
    Route::post("/akademik/get-seluruh-khs1", "Akademik@getSeluruhKHS1")->name('getseluruhkhs1');
    Route::post("/akademik/get-kartuhasilstudi", "Akademik@getkartuhasilstudi_cetak")->name('getkartuhasilstudi_cetak');
    Route::get("/akademik/list-sksambil", "Akademik@list_sksambil_already")->name('list_sksambil_already');
    Route::get("/akademik/list-sksbayar", "Akademik@list_sksbayar_already")->name('list_sksbayar_already');
    // Cetak Transkip Nilai
    Route::post("/akademik/cetak-transkipnilai1", "Akademik@cetaktranskipnilai1")->name('cetaktranskipnilai1');
    Route::post("/akademik/cetaktranskipnilaikurikulum", "Akademik@cetaktranskipnilaikurikulum")->name('cetaktranskipnilaikurikulum');
    Route::post("/akademik/get-transkipnilai", "Akademik@gettranskipnilai_cetak")->name('gettranskipnilai_cetak');
    Route::get("/akademik/tampilno_transkip", "Akademik@tampilno_transkip")->name('aktampilno_transkip');
    // Route::get("/akademik/select-tahun", "Akademik@select_tahun")->name('select_tahun');
    Route::post("/akademik/add-mhs-dosenwali", "Akademik@save_mhs_dosenwali")->name('akdsave_mhs_dosenwali');
    Route::get("/akademik/setting-dosenwali", "Akademik@dosenwali")->name('akddosenwali');
    Route::get("/akademik/daftar-mahasiswa", "Akademik@daftar_mahasiswa")->name('akddaftar_mahasiswa');
    Route::get("/akademik/list-mhs-already", "Akademik@list_mhs_already")->name('akdlist_mhs_already');
    Route::get("/akademik/hapus-mhs-dosenwali", "Akademik@hapus_mhs_dosen_wali")->name('akdhapus_mhs_dosen_wali');
    Route::get("/akademik/nonaktif-mhs-dosenwali", "Akademik@nonaktif_mhs_dosenwali")->name('akdnonaktif_mhs_dosenwali');
    Route::post("/akademik/edit-transkipakademik", "Akademik@edittranskipakademik")->name('edittranskipakademik');
    Route::get("/akademik/cek-nimakademik", "Akademik@ceknimakademik")->name('ceknimakademik');
    Route::post("/akademik/edit-mahasiswa", "Akademik@edit_mahasiswa")->name('edit_mahasiswa');
    Route::post("/akademik/edit-camaba", "Akademik@edit_camaba")->name('edit_camaba');
    Route::get("/akademik/edittampilagama", "Akademik@edittampilagama")->name('edittampilagama');
    Route::get("/akademik/edittampilkelas", "Akademik@edittampilkelas")->name('edittampilkelas');
    Route::get("/akademik/edittampilstatusnikah", "Akademik@edittampilstatusnikah")->name('edittampilstatusnikah');
    Route::get("/akademik/edittampiljalurpmb", "Akademik@edittampiljalurpmb")->name('edittampiljalurpmb');
    Route::get("/akademik/edittampilkewarganegaraan", "Akademik@edittampilkewarganegaraan")->name('edittampilkewarganegaraan');
    Route::get("/akademik/edittampiljenjangpendidikan", "Akademik@edittampiljenjangpendidikan")->name('edittampiljenjangpendidikan');
    Route::get("/akademik/edittampiljenispekerjaan", "Akademik@edittampiljenispekerjaan")->name('edittampiljenispekerjaan');
    Route::get("/akademik/edittampilpenghasilan", "Akademik@edittampilpenghasilan")->name('edittampilpenghasilan');

    Route::get("/akademik/tampiljenistinggal", "Akademik@tampiljenistinggal")->name('tampiljenistinggal');
    Route::get("/akademik/tampiltransportasi", "Akademik@tampiltransportasi")->name('tampiltransportasi');
    Route::get("/akademik/tampiljalurpendaftaran", "Akademik@tampiljalurpendaftaran")->name('tampiljalurpendaftaran');
    Route::get("/akademik/tampiljenispendaftaran", "Akademik@tampiljenispendaftaran")->name('tampiljenispendaftaran');

    Route::get("/akademik/modal-sks-diambil", "Akademik@modal_sks_ambil2")->name('dsnmodal_sks_ambil2');
    Route::get("/akademik/modal-ips-diambil", "Akademik@modal_ips_ambil")->name('dsnmodal_ips_ambil');

    Route::get("/akademik/edittampilkegiatanakademik", "Akademik@edittampilkegiatanakademik")->name('akedittampilkegiatanakademik');

    // PKKMB Admin APIs
    Route::get("/akademik/pkkmb", "Akademik@pkkmbList")->name('akpkkmbList');
    Route::post("/akademik/pkkmb/update", "Akademik@pkkmbUpdate")->name('akpkkmbUpdate');
    Route::post("/akademik/pkkmb/import", "Akademik@pkkmbImport")->name('akpkkmbImport');
    Route::post("/akademiktools/import-makul-penawaran", "AkademikTools@import_makul_penawaran")->name('akimport_makul_penawaran');

    // Modul Skripsi Mahasiswa
 Route::get("/mahasiswa/skripsi/dashboard", "Skripsi@dashboard")->name('skripsi_dashboard');
    Route::get("/mahasiswa/skripsi/config-prodi", "Skripsi@config_prodi")->name('skripsi_config_prodi');
    Route::get("/mahasiswa/skripsi/cek-kelayakan", "Skripsi@cek_kelayakan")->name('skripsi_cek_kelayakan');
    Route::post("/mahasiswa/skripsi/simpan-proposal", "Skripsi@simpan_proposal")->name('skripsi_simpan_proposal');
    Route::post("/mahasiswa/skripsi/upload-naskah", "Skripsi@upload_naskah")->name('skripsi_upload_naskah');
    Route::post("/mahasiswa/skripsi/ajukan-sempro", "Skripsi@ajukan_sempro")->name('skripsi_ajukan_sempro');
    Route::post("/mahasiswa/skripsi/ajukan-ujian", "Skripsi@ajukan_ujian")->name('skripsi_ajukan_ujian');
    Route::post("/mahasiswa/skripsi/hapus-naskah", "Skripsi@hapus_naskah")->name('skripsi_hapus_naskah');
    Route::post("/mahasiswa/skripsi/upload-berkas", "Skripsi@upload_berkas")->name('skripsi_upload_berkas');
    Route::get("/mahasiswa/skripsi/log-bimbingan", "Skripsi@log_bimbingan")->name('skripsi_mhs_log_bimbingan');
    Route::post("/mahasiswa/skripsi/tambah-bimbingan", "Skripsi@tambah_bimbingan")->name('skripsi_mhs_tambah_bimbingan');
    Route::post("/mahasiswa/skripsi/update-bimbingan/{id}", "Skripsi@update_bimbingan")->name('skripsi_mhs_update_bimbingan');
    Route::post("/mahasiswa/skripsi/hapus-bimbingan/{id}", "Skripsi@hapus_bimbingan")->name('skripsi_mhs_hapus_bimbingan');
    Route::get("/mahasiswa/skripsi/get-luaran", "Skripsi@get_luaran")->name('skripsi_get_luaran');
    Route::post("/mahasiswa/skripsi/simpan-luaran", "Skripsi@simpan_luaran")->name('skripsi_simpan_luaran');
    Route::post("/mahasiswa/skripsi/batalkan-ujian", "Skripsi@batalkan_ujian")->name('skripsi_batalkan_ujian');
    Route::get("/mahasiswa/skripsi/portofolio-cpl", "Skripsi@get_portofolio_cpl")->name('skripsi_portofolio_cpl');
    // Admin Rekap Bimbingan
    Route::get("/akademik/rekap-bimbingan", "Skripsi@rekap_bimbingan")->name('skripsi_rekap_bimbingan');
    
    // Modul Skripsi Kaprodi & Dekanat
    Route::get("/kaprodi/skripsi/list-mahasiswa", "SkripsiKaprodi@list_mahasiswa_ta")->name('skripsi_kaprodi_list');
    Route::post("/kaprodi/skripsi/plot-pembimbing", "SkripsiKaprodi@plot_pembimbing")->name('skripsi_kaprodi_plot_pembimbing');
    Route::post("/kaprodi/skripsi/plot-jadwal-sempro", "SkripsiKaprodi@plot_jadwal_sempro")->name('skripsi_kaprodi_plot_sempro');
    Route::post("/kaprodi/skripsi/plot-jadwal-ujian", "SkripsiKaprodi@plot_jadwal_ujian")->name('skripsi_kaprodi_plot_ujian');
    Route::get("/kaprodi/skripsi/get-jadwal-ujian/{id_skripsi}", "SkripsiKaprodi@get_jadwal_ujian")->name('skripsi_kaprodi_get_jadwal_ujian');
    
    // SK Kolektif (Dekanat/Kaprodi)
    Route::get("/kaprodi/skripsi/list-siap-sk", "SkripsiKaprodi@list_siap_sk")->name('skripsi_list_siap_sk');
    Route::post("/kaprodi/skripsi/issue-sk-kolektif", "SkripsiKaprodi@simpan_sk_kolektif")->name('skripsi_issue_sk_kolektif');
    Route::get("/kaprodi/skripsi/list-sk-terbit", "SkripsiKaprodi@list_sk_terbit")->name('skripsi_list_sk_terbit');
    Route::get("/kaprodi/skripsi/get-sk-detail/{id}", "SkripsiKaprodi@get_sk_detail")->name('skripsi_get_sk_detail');
    Route::post("/kaprodi/skripsi/update-sk", "SkripsiKaprodi@update_sk")->name('skripsi_update_sk');

    // Konfigurasi Sempro & CPMK Rubrik
    Route::get("/kaprodi/skripsi/config-grading/{kode_prodi}", "SkripsiKaprodi@get_grading_config")->name('skripsi_kaprodi_get_config_grading');
    Route::post("/kaprodi/skripsi/update-config-grading", "SkripsiKaprodi@update_grading_config")->name('skripsi_kaprodi_update_config_grading');
    Route::get("/kaprodi/skripsi/config-sempro/{kode_prodi}", "SkripsiKaprodi@get_config_sempro")->name('skripsi_kaprodi_get_config_sempro');
    Route::post("/kaprodi/skripsi/update-config-sempro", "SkripsiKaprodi@update_config_sempro")->name('skripsi_kaprodi_update_config_sempro');
    Route::get("/kaprodi/skripsi/get-rubrik-cpmk/{kode_prodi}", "SkripsiKaprodi@get_rubrik_cpmk")->name('skripsi_kaprodi_get_rubrik_cpmk');
    Route::post("/kaprodi/skripsi/save-rubrik-cpmk", "SkripsiKaprodi@save_rubrik_cpmk")->name('skripsi_kaprodi_save_rubrik_cpmk');
    Route::post("/kaprodi/skripsi/reset-rubrik-cpmk", "SkripsiKaprodi@reset_rubrik_cpmk")->name('skripsi_kaprodi_reset_rubrik_cpmk');
    Route::get("/kaprodi/skripsi/get-cpl/{kode_prodi}", "SkripsiKaprodi@get_cpl")->name('skripsi_kaprodi_get_cpl');
    Route::post("/kaprodi/skripsi/save-cpl", "SkripsiKaprodi@save_cpl")->name('skripsi_kaprodi_save_cpl');
    Route::post("/kaprodi/skripsi/delete-cpl/{id}", "SkripsiKaprodi@delete_cpl")->name('skripsi_kaprodi_delete_cpl');
    Route::post("/kaprodi/skripsi/toggle-cpl/{id}", "SkripsiKaprodi@toggle_cpl")->name('skripsi_kaprodi_toggle_cpl');
    Route::get("/kaprodi/skripsi/search-matakuliah", "SkripsiKaprodi@search_matakuliah")->name('skripsi_kaprodi_search_matakuliah');
    Route::get("/kaprodi/skripsi/syarat-prodi/{kode_prodi}", "SkripsiKaprodi@list_syarat_prodi")->name('skripsi_kaprodi_list_syarat');
    Route::get("/kaprodi/skripsi/master-syarat", "SkripsiKaprodi@list_master_syarat")->name('skripsi_kaprodi_master_syarat');
    Route::post("/kaprodi/skripsi/save-syarat-prodi", "SkripsiKaprodi@save_syarat_prodi")->name('skripsi_kaprodi_save_syarat');
    Route::delete("/kaprodi/skripsi/delete-syarat-prodi/{id}", "SkripsiKaprodi@delete_syarat_prodi")->name('skripsi_kaprodi_delete_syarat');
    Route::get("/akademik/skripsi/list-config-sempro", "SkripsiKaprodi@list_config_sempro")->name('skripsi_admin_list_config_sempro');

    // Modul Skripsi Dosen Pembimbing
    Route::get("/dosen/skripsi/dashboard", "SkripsiDosen@dashboard")->name('skripsi_dosen_dashboard');
    Route::get("/dosen/skripsi/log-bimbingan", "SkripsiDosen@log_bimbingan")->name('skripsi_dosen_log_bimbingan');
    Route::post("/dosen/skripsi/validasi-bimbingan", "SkripsiDosen@validasi_bimbingan")->name('skripsi_dosen_validasi_bimbingan');
    Route::post("/dosen/skripsi/acc-ujian", "SkripsiDosen@acc_ujian")->name('skripsi_dosen_acc_ujian');

    // Modul Penilaian Ujian OBE Dosen
    Route::get("/dosen/skripsi/list-mahasiswa-diuji", "SkripsiDosen@list_mahasiswa_diuji")->name('skripsi_dosen_list_mahasiswa_diuji');
    Route::get("/dosen/skripsi/get-rubrik-cpmk", "SkripsiDosen@get_rubrik_cpmk")->name('skripsi_dosen_get_rubrik_cpmk');
    Route::get("/dosen/skripsi/get-nilai-ujian-cpmk", "SkripsiDosen@get_nilai_ujian_cpmk")->name('skripsi_dosen_get_nilai_ujian_cpmk');
    Route::post("/dosen/skripsi/simpan-nilai-ujian-cpmk", "SkripsiDosen@simpan_nilai_ujian_cpmk")->name('skripsi_dosen_simpan_nilai_ujian_cpmk');

    // Berita Acara & Penetapan Ujian
    Route::get("/dosen/skripsi/berita-acara/{id_skripsi_ujian}", "SkripsiDosen@get_berita_acara")->name('skripsi_dosen_get_berita_acara');
    Route::post("/dosen/skripsi/setuju-berita-acara", "SkripsiDosen@setuju_berita_acara")->name('skripsi_dosen_setuju_berita_acara');
    Route::get("/kaprodi/skripsi/penetapan-nilai", "SkripsiKaprodi@list_penetapan_nilai")->name('skripsi_kaprodi_list_penetapan_nilai');
    Route::post("/kaprodi/skripsi/tetapkan-nilai", "SkripsiKaprodi@tetapkan_nilai")->name('skripsi_kaprodi_tetapkan_nilai');

    // API Cetak Data & Approval Bimbingan
    Route::get("/akademik/skripsi/bimbingan/cetak-data", "Skripsi@get_cetak_bimbingan")->name('skripsi_cetak_bimbingan_data');

    // API Kaprodi Bimbingan Approval
    Route::get("/kaprodi/skripsi/bimbingan/list", "SkripsiKaprodi@list_bimbingan_prodi")->name('skripsi_kaprodi_list_bimbingan');
    Route::post("/kaprodi/skripsi/bimbingan/approve", "SkripsiKaprodi@approve_bimbingan_prodi")->name('skripsi_kaprodi_approve_bimbingan');
});
