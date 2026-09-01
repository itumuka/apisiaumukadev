<?php

$routesContent = file_get_contents(__DIR__ . '/../routes/api.php');

$collection = [
    "info" => [
        "_postman_id" => "siakad-umuka-api-collection-" . uniqid(),
        "name" => "SIAKAD UMUKA API Documentation",
        "description" => "Dokumentasi Lengkap API SIAKAD UMUKA (Laravel API)\n\n### Cara Penggunaan:\n1. Pastikan Postman Environment `SIAKAD_DEV` aktif.\n2. Panggil request `01. Authentication & System / Auth / Auth Login` dengan username & password.\n3. Script otomatis akan menyimpan token JWT ke variable `{{token}}` dan `{{username}}`.\n4. Seluruh request lain akan otomatis mewarisi header `Authorization: Bearer {{token}}` dan `username: {{username}}`.",
        "schema" => "https://schema.getpostman.com/json/collection/v2.1.0/collection.json"
    ],
    "variable" => [
        [
            "key" => "base_url",
            "value" => "http://localhost/siakaddev/apisiaumukadev/public/api",
            "type" => "string"
        ]
    ],
    "item" => []
];

// Helper to create a request item
function createRequestItem($name, $method, $path, $folderCategory, $description = '', $params = [], $bodyType = 'none', $bodyData = [], $isAuth = false) {
    $urlSegments = array_values(array_filter(explode('/', trim($path, '/'))));
    
    // Headers
    $headers = [
        [
            "key" => "Accept",
            "value" => "application/json",
            "type" => "text"
        ]
    ];

    if (!$isAuth) {
        $headers[] = [
            "key" => "Authorization",
            "value" => "Bearer {{token}}",
            "type" => "text"
        ];
        $headers[] = [
            "key" => "username",
            "value" => "{{username}}",
            "type" => "text"
        ];
    }

    $urlObj = [
        "raw" => "{{base_url}}/" . trim($path, '/'),
        "host" => ["{{base_url}}"],
        "path" => $urlSegments
    ];

    if (!empty($params) && $method === 'GET') {
        $queryParams = [];
        foreach ($params as $k => $v) {
            $queryParams[] = [
                "key" => is_string($k) ? $k : $v,
                "value" => is_string($k) ? (string)$v : "",
                "description" => ""
            ];
        }
        $urlObj["query"] = $queryParams;
        $urlObj["raw"] .= '?' . http_build_query(array_combine(
            array_column($queryParams, 'key'),
            array_column($queryParams, 'value')
        ));
    }

    $request = [
        "method" => strtoupper($method),
        "header" => $headers,
        "url" => $urlObj,
        "description" => $description
    ];

    if ($method === 'POST' || $method === 'PUT' || $method === 'PATCH' || $method === 'DELETE') {
        if ($bodyType === 'urlencoded') {
            $urlencodedData = [];
            foreach ($bodyData as $k => $v) {
                $urlencodedData[] = [
                    "key" => is_string($k) ? $k : $v,
                    "value" => is_string($k) ? (string)$v : "",
                    "type" => "text"
                ];
            }
            $request["body"] = [
                "mode" => "urlencoded",
                "urlencoded" => $urlencodedData
            ];
        } elseif ($bodyType === 'formdata') {
            $formdata = [];
            foreach ($bodyData as $k => $v) {
                $keyName = is_string($k) ? $k : $v;
                $isFileInput = (strpos($keyName, 'file') !== false || strpos($keyName, 'naskah') !== false || strpos($keyName, 'foto') !== false || strpos($keyName, 'berkas') !== false || strpos($keyName, 'bukti') !== false);
                $formdata[] = [
                    "key" => $keyName,
                    "type" => $isFileInput ? "file" : "text",
                    "value" => $isFileInput ? "" : (is_string($k) ? (string)$v : "")
                ];
            }
            $request["body"] = [
                "mode" => "formdata",
                "formdata" => $formdata
            ];
        } elseif ($bodyType === 'raw_json') {
            $request["body"] = [
                "mode" => "raw",
                "raw" => json_encode($bodyData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
                "options" => [
                    "raw" => [
                        "language" => "json"
                    ]
                ]
            ];
        }
    }

    $item = [
        "name" => $name,
        "request" => $request,
        "response" => []
    ];

    // Add post-response test script for login
    if ($path === '/auth-login') {
        $item["event"] = [
            [
                "listen" => "test",
                "script" => [
                    "exec" => [
                        "if (pm.response.code === 200) {",
                        "    var jsonData = pm.response.json();",
                        "    if (jsonData.token) {",
                        "        pm.environment.set(\"token\", jsonData.token);",
                        "        console.log(\"Token JWT updated in environment\");",
                        "    }",
                        "    if (pm.request.body && pm.request.body.urlencoded) {",
                        "        var u = pm.request.body.urlencoded.find(p => p.key === 'username');",
                        "        if (u && u.value) {",
                        "            pm.environment.set(\"username\", u.value);",
                        "        }",
                        "    }",
                        "}"
                    ],
                    "type" => "text/javascript"
                ]
            ]
        ];
    }

    return $item;
}

// Data Definition for all folders & routes
$modules = [
    "01. Authentication & System" => [
        "description" => "Endpoint autentikasi, session check, bearer token, notifikasi sistem",
        "subfolders" => [
            "Auth" => [
                createRequestItem("Auth Login", "POST", "/auth-login", "Auth", "Login pengguna (Mahasiswa/Dosen/Pegawai). Mengembalikan JWT token.", [], "urlencoded", ["username" => "2021010001", "password" => "password123"], true),
                createRequestItem("Check Session", "GET", "/check-session", "Auth", "Mengecek session pengguna saat ini.", [], "none", [], true),
                createRequestItem("Get Bearer Token Info", "GET", "/bearerToken", "Auth", "Mendapatkan data sub dari Bearer token.", [], "none", [], false),
                createRequestItem("Logout", "GET", "/logout", "Auth", "Logout dari sesi saat ini.", [], "none", [], false),
                createRequestItem("Debug DB Triggers", "GET", "/debug-db-triggers", "Auth", "Debug trigger database MySQL.", [], "none", [], true),
            ],
            "Notifications" => [
                createRequestItem("Get System Notifications", "GET", "/notifications", "Notifications", "Mendapatkan daftar notifikasi pengguna.", ["limit" => "20"], "none", []),
                createRequestItem("Mark Notification as Read", "POST", "/notifications/read", "Notifications", "Menandai notifikasi telah dibaca.", [], "urlencoded", ["id_notification" => "1"]),
            ]
        ]
    ],
    "02. Mahasiswa (Student Portal)" => [
        "description" => "Layanan mahasiswa mencakup Profil, KRS, KHS, Transkrip, Jadwal, Tagihan VA, Presensi, SKPI & SK Transkrip",
        "subfolders" => [
            "Profil & Biodata" => [
                createRequestItem("Get Profil Personal", "POST", "/mahasiswa/profil-personal", "Profil", "Mendapatkan biodata personal mahasiswa.", [], "none", []),
                createRequestItem("Simpan Profil Mahasiswa", "POST", "/mahasiswa/simpan-profil-mahasiswa", "Profil", "Update biodata pribadi mahasiswa.", [], "urlencoded", ["nik" => "3313...", "tempat_lahir" => "Surakarta", "tgl_lahir" => "2000-01-01", "jk" => "L", "agama" => "Islam", "telepon" => "08123456789", "email" => "mhs@email.com", "alamat" => "Jl. Slamet Riyadi"]),
                createRequestItem("Upload Foto Profil", "POST", "/mahasiswa/upload-foto", "Profil", "Upload foto profil mahasiswa.", [], "formdata", ["foto" => ""]),
                createRequestItem("Simpan Profil Ayah", "POST", "/mahasiswa/simpan-ayah-mahasiswa", "Profil", "Simpan / update data ayah kandung.", [], "urlencoded", ["nama_ayah" => "Nama Ayah", "nik_ayah" => "3313...", "pekerjaan_ayah" => "Wiraswasta", "penghasilan_ayah" => "3000000"]),
                createRequestItem("Simpan Profil Ibu", "POST", "/mahasiswa/simpan-ibu-mahasiswa", "Profil", "Simpan / update data ibu kandung.", [], "urlencoded", ["nama_ibu" => "Nama Ibu", "nik_ibu" => "3313...", "pekerjaan_ibu" => "IRT", "penghasilan_ibu" => "0"]),
                createRequestItem("Simpan Riwayat Pendidikan", "POST", "/mahasiswa/simpan-pendidikan-mahasiswa", "Profil", "Simpan data asal sekolah/pendidikan sebelumnya.", [], "urlencoded", ["asal_sekolah" => "SMA Negeri 1", "jurusan" => "IPA", "tahun_lulus" => "2020"]),
                createRequestItem("Ganti Password Mahasiswa", "POST", "/mahasiswa/edit-password", "Profil", "Ubah password akun mahasiswa.", [], "urlencoded", ["password_lama" => "oldpass", "password_baru" => "newpass"]),
                createRequestItem("Master Wilayah - Provinsi", "GET", "/mahasiswa/tampilprovinsi", "Profil", "Daftar provinsi untuk form biodata.", [], "none", []),
                createRequestItem("Master Wilayah - Kabupaten", "GET", "/mahasiswa/tampilkabupaten", "Profil", "Daftar kabupaten berdasarkan ID provinsi.", ["id_provinsi" => "33"], "none", []),
                createRequestItem("Master Wilayah - Kecamatan", "GET", "/mahasiswa/tampilkecamatan", "Profil", "Daftar kecamatan berdasarkan ID kabupaten.", ["id_kabupaten" => "3313"], "none", []),
            ],
            "KRS & Perkuliahan" => [
                createRequestItem("Cek Status Heregistrasi", "POST", "/mahasiswa/cekhereg", "KRS", "Cek apakah mahasiswa sudah her-registrasi pada TA aktif.", [], "urlencoded", ["tahun_akademik" => "2023/2024", "semester" => "1"]),
                createRequestItem("Ambil Data Form KRS", "GET", "/mahasiswa/ambil-krs", "KRS", "Daftar matakuliah penawaran yang bisa diambil pada semester aktif.", [], "none", []),
                createRequestItem("Simpan Pengambilan KRS", "POST", "/mahasiswa/simpan-krs", "KRS", "Submit matakuliah yang dipilih mahasiswa.", [], "urlencoded", ["id_makul_penawaran" => "1,2,3"]),
                createRequestItem("Data Revisi KRS", "GET", "/mahasiswa/revisi-krs", "KRS", "Melihat data matakuliah dalam masa revisi KRS.", [], "none", []),
                createRequestItem("Hapus Matakuliah Revisi KRS", "GET", "/mahasiswa/-hapus-revisi-krs", "KRS", "Hapus matakuliah yang dibatalkan saat revisi KRS.", ["id_krs" => "1"], "none", []),
                createRequestItem("Jadwal Kuliah Mahasiswa", "GET", "/mahasiswa/tampil-jadwal-makul", "KRS", "Tampilkan jadwal kuliah mahasiswa per semester.", [], "none", []),
                createRequestItem("Presensi Perkuliahan Mahasiswa", "GET", "/mahasiswa/tampil-presensi-makul", "KRS", "Melihat riwayat kehadiran per matakuliah.", ["id_krs" => "1"], "none", []),
                createRequestItem("Simpan Presensi QR/Mandiri Mhs", "POST", "/mahasiswa/simpan-presensi", "KRS", "Submit kehadiran kuliah via scan / kode presensi.", [], "urlencoded", ["id_jadwal" => "1", "kode_presensi" => "ABC123"]),
                createRequestItem("Cek Kalender Jadwal KRS", "GET", "/mahasiswa/kalender-krs", "KRS", "Cek periode jadwal pengisian KRS.", [], "none", []),
                createRequestItem("Cek Kalender Revisi KRS", "GET", "/mahasiswa/kalender-revisikrs", "KRS", "Cek periode jadwal revisi KRS.", [], "none", []),
                createRequestItem("Cek Kalender Cetak Kartu Ujian", "GET", "/mahasiswa/kalender-cetak-kartu", "KRS", "Cek periode cetak kartu ujian UTS/UAS.", [], "none", []),
            ],
            "KHS & Transkrip" => [
                createRequestItem("Filter Semester KHS", "GET", "/mahasiswa/filter-khs", "KHS", "Daftar semester yang telah ditempuh mahasiswa untuk filter KHS.", [], "none", []),
                createRequestItem("Pilih KHS Semester Tertentu", "GET", "/mahasiswa/select-khs", "KHS", "Menampilkan nilai KHS berdasarkan semester terpilih.", ["semester" => "1"], "none", []),
                createRequestItem("Data KHS Mahasiswa", "GET", "/mahasiswa/data-khs", "KHS", "Menampilkan detail KHS semester aktif.", [], "none", []),
                createRequestItem("Transkrip Nilai Kumulatif", "GET", "/mahasiswa/transkrip-nilai", "KHS", "Transkrip nilai lengkap seluruh semester.", [], "none", []),
                createRequestItem("Dispensasi KHS", "GET", "/mahasiswa/dispensasikhs", "KHS", "Cek status dispensasi KHS mahasiswa.", [], "none", []),
                createRequestItem("Get Ajuan Cetak Transkrip", "GET", "/mahasiswa/transkrip-ajuan", "KHS", "Melihat riwayat pengajuan cetak transkrip resmi.", [], "none", []),
                createRequestItem("Submit Ajuan Cetak Transkrip", "POST", "/mahasiswa/transkrip-ajuan", "KHS", "Mengajukan cetak transkrip nilai resmi ke BAA.", [], "urlencoded", ["keperluan" => "Persyaratan Yudisium / Magang"]),
            ],
            "Keuangan & Virtual Account" => [
                createRequestItem("Status Virtual Account (VA)", "GET", "/mahasiswa/tampilstatusva", "Keuangan", "Cek nomor Virtual Account pembayaran mahasiswa.", [], "none", []),
                createRequestItem("Status Tagihan Pembayaran", "GET", "/mahasiswa/tampilstatuspembayaran", "Keuangan", "Daftar tagihan pembayaran SPP/Heregistrasi aktif.", [], "none", []),
                createRequestItem("Riwayat Pembayaran", "GET", "/mahasiswa/tampilstatuspembayaranriwayat", "Keuangan", "Histori transaksi pembayaran yang telah lunas.", [], "none", []),
                createRequestItem("Generate Group VA", "POST", "/mahasiswa/generate-group-va", "Keuangan", "Generate kode billing / VA paket pembayaran.", [], "urlencoded", ["kode_billing" => "BIL123456"]),
            ],
            "SKPI & Prestasi" => [
                createRequestItem("Cek Status Verifikasi Semester", "GET", "/mahasiswa/check-verifikasi-semester", "SKPI", "Cek apakah mahasiswa sudah verifikasi data semester.", [], "none", []),
                createRequestItem("Submit Verifikasi Semester", "POST", "/mahasiswa/submit-verifikasi-semester", "SKPI", "Kirim konfirmasi verifikasi data semester.", [], "urlencoded", ["semester" => "5", "status" => "1"]),
                createRequestItem("Daftar Prestasi SKPI", "GET", "/mahasiswa/get-skpi-prestasi", "SKPI", "List capaian prestasi & sertifikasi SKPI mahasiswa.", [], "none", []),
                createRequestItem("Tambah Prestasi SKPI", "POST", "/mahasiswa/add-skpi-prestasi", "SKPI", "Upload bukti sertifikat & prestasi SKPI.", [], "formdata", ["nama_prestasi" => "Juara 1 Hackathon Nasional", "jenis" => "Prestasi", "tingkat" => "Nasional", "tahun" => "2023", "bukti" => ""]),
                createRequestItem("Hapus Prestasi SKPI", "POST", "/mahasiswa/delete-skpi-prestasi", "SKPI", "Hapus data prestasi SKPI.", [], "urlencoded", ["id" => "1"]),
                createRequestItem("Translate Prestasi (En/Id)", "POST", "/mahasiswa/translate", "SKPI", "Terjemahkan judul prestasi untuk format bilingual SKPI.", [], "urlencoded", ["text" => "Juara 1 Lomba Web Design"]),
                createRequestItem("Cek Kuisioner EDOM", "GET", "/mahasiswa/check-edom", "SKPI", "Cek status pengisian Evaluasi Dosen oleh Mahasiswa.", [], "none", []),
            ]
        ]
    ],
    "03. Dosen & Dosen Pembimbing" => [
        "description" => "Layanan perkuliahan dosen: Jadwal mengajar, Presensi & BAP, Penilaian UTS/UAS, Bimbingan PA, & BAP Ujian",
        "subfolders" => [
            "Perkuliahan & Jadwal" => [
                createRequestItem("Riwayat & Jadwal Mengajar", "GET", "/akademik/riwayat-mengajar", "Dosen", "Daftar kelas matakuliah yang diampu dosen semester ini.", [], "none", []),
                createRequestItem("Ganti Password Dosen", "POST", "/akademik/edit-password-dosen", "Dosen", "Ubah password akun dosen.", [], "urlencoded", ["password_lama" => "oldpass", "password_baru" => "newpass"]),
                createRequestItem("Daftar Mahasiswa Bimbingan PA", "GET", "/akademik/daftarmhs-pa", "Dosen", "List mahasiswa bimbingan akademik / perwalian.", [], "none", []),
                createRequestItem("Cek Transkrip KRS Mahasiswa PA", "GET", "/akademik/cek-transkrip-krs", "Dosen", "Cek transkrip dan riwayat nilai mahasiswa PA.", ["nim" => "2021010001"], "none", []),
                createRequestItem("Modal SKS Diambil Mhs PA", "GET", "/akademik/modal-sks-ambil", "Dosen", "Lihat rincian matakuliah yang diambil mahasiswa saat KRS.", ["nim" => "2021010001"], "none", []),
            ],
            "Presensi & Berita Acara Kuliah" => [
                createRequestItem("Presensi Perkuliahan Kelas", "GET", "/akademik/presensi-permakul", "Presensi", "Menampilkan data kehadiran kelas per matakuliah.", ["id_makul_penawaran" => "1"], "none", []),
                createRequestItem("List Berita Acara Perkuliahan (BAP)", "GET", "/akademik/list-ba", "Presensi", "Daftar BAP pertemuan kuliah yang telah dibuat dosen.", ["id_makul_penawaran" => "1"], "none", []),
                createRequestItem("Simpan Berita Acara Pertemuan", "POST", "/akademik/simpan-ba", "Presensi", "Buat BAP kuliah baru (materi, capaian, tgl, jam).", [], "urlencoded", ["id_makul_penawaran" => "1", "pertemuan_ke" => "1", "tgl_kuliah" => "2023-10-01", "materi" => "Pengantar Perkuliahan", "metode" => "Tatap Muka"]),
                createRequestItem("Update Berita Acara Kuliah", "POST", "/akademik/ubah-ba", "Presensi", "Ubah data berita acara kuliah.", [], "urlencoded", ["id_ba" => "1", "materi" => "Revisi Materi Pertemuan 1"]),
                createRequestItem("Hapus Berita Acara Kuliah", "GET", "/akademik/hapus-ba", "Presensi", "Hapus data BAP pertemuan kuliah.", ["id_ba" => "1"], "none", []),
                createRequestItem("Validasi / Tanda Tangan BAP", "GET", "/akademik/validated-ba", "Presensi", "Validasi BAP perkuliahan.", ["id_ba" => "1"], "none", []),
                createRequestItem("Simpan Presensi Mahasiswa", "POST", "/akademik/simpan-presensi", "Presensi", "Simpan kehadiran mahasiswa per pertemuan.", [], "urlencoded", ["id_makul_penawaran" => "1", "pertemuan_ke" => "1", "presensi_data" => "[{\"nim\":\"2021010001\",\"status\":\"H\"}]"]),
                createRequestItem("Edit Status Kehadiran Mhs", "POST", "/akademik/edit-presensi", "Presensi", "Update status presensi mahasiswa spesifik.", [], "urlencoded", ["id_presensi" => "1", "status" => "H"]),
                createRequestItem("Auto Generate Pertemuan BAP", "POST", "/akademik/autopertemuan-ba", "Presensi", "Generate 16 jadwal pertemuan BAP otomatis.", [], "urlencoded", ["id_makul_penawaran" => "1"]),
                createRequestItem("Hitung Rekap Persentase Kehadiran", "POST", "/akademik/data-hitungpresensi", "Presensi", "Hitung persentase kehadiran seluruh mhs dalam kelas.", [], "urlencoded", ["id_makul_penawaran" => "1"]),
                createRequestItem("Import Presensi dari Excel", "POST", "/akademik/import-presensi", "Presensi", "Import presensi mahasiswa via file Excel.", [], "formdata", ["file_excel" => "", "id_makul_penawaran" => "1"]),
                createRequestItem("Export Rekap BAP Perkuliahan", "GET", "/akademik/export-ba", "Presensi", "Export data berita acara kuliah ke Excel.", ["id_makul_penawaran" => "1"], "none", []),
            ],
            "Input & Rekap Nilai" => [
                createRequestItem("Daftar Mahasiswa Input Nilai", "GET", "/akademik/data-mhs-inputnilai", "Nilai", "Daftar mahasiswa dalam kelas untuk entri nilai.", ["id_makul_penawaran" => "1"], "none", []),
                createRequestItem("Bobot & Persentase Nilai MK", "GET", "/akademik/persen-nilai-mk", "Nilai", "Melihat setting persentase komponen nilai (Tugas, UTS, UAS).", ["id_makul_penawaran" => "1"], "none", []),
                createRequestItem("Master Predikat Nilai Huruf", "GET", "/akademik/select-predikat-nilai", "Nilai", "Range konversi angka ke grade huruf (A, B, C, D, E).", [], "none", []),
                createRequestItem("Cek Batas Waktu Input Nilai", "GET", "/akademik/cekkalenderbatasinputnilai", "Nilai", "Cek periode jadwal input nilai dosen.", [], "none", []),
                createRequestItem("Simpan Nilai UTS", "POST", "/akademik/simpan-nilai-uts", "Nilai", "Simpan nilai UTS mahasiswa per kelas.", [], "urlencoded", ["id_makul_penawaran" => "1", "nilai" => "[{\"nim\":\"2021010001\",\"nilai_uts\":85}]"]),
                createRequestItem("Simpan Nilai UAS & Akhir", "POST", "/akademik/simpan-nilai-uas", "Nilai", "Simpan nilai UAS & nilai akhir mahasiswa.", [], "urlencoded", ["id_makul_penawaran" => "1", "nilai" => "[{\"nim\":\"2021010001\",\"nilai_uas\":88}]"]),
            ],
            "Berita Acara Ujian" => [
                createRequestItem("Matakuliah Penawaran BA Ujian", "GET", "/akademik/makulpenawaran-ba-ujian", "BA Ujian", "Daftar jadwal ujian yang diampu pengawas/dosen.", [], "none", []),
                createRequestItem("List Berita Acara Ujian", "GET", "/akademik/list-ba-ujian", "BA Ujian", "Daftar berita acara pelaksanaan ujian.", ["id_makul_penawaran" => "1"], "none", []),
                createRequestItem("Simpan Berita Acara Ujian", "POST", "/akademik/simpan-ba-ujian", "BA Ujian", "Input BAP pelaksanaan UTS/UAS.", [], "urlencoded", ["id_makul_penawaran" => "1", "jenis_ujian" => "UTS", "tgl_ujian" => "2023-11-15", "jml_hadir" => "35", "jml_tidak_hadir" => "2", "catatan" => "Ujian tertib"]),
                createRequestItem("Lihat Absensi Mahasiswa Ujian", "POST", "/akademik/lihat-absen-ujian", "BA Ujian", "Cek presensi mahasiswa pada saat ujian.", [], "urlencoded", ["id_makul_penawaran" => "1", "jenis_ujian" => "UTS"]),
            ]
        ]
    ],
    "04. Modul Tugas Akhir & Skripsi (OBE)" => [
        "description" => "Sistem Informasi Tugas Akhir / Skripsi berbasis Outcome-Based Education (OBE), mencakup Bimbingan, Sempro, Ujian Skripsi, Rubrik Penilaian Indikator CPL, SK Kolektif, dan Penetapan Nilai",
        "subfolders" => [
            "Mahasiswa - Skripsi" => [
                createRequestItem("Dashboard Skripsi Mahasiswa", "GET", "/mahasiswa/skripsi/dashboard", "Skripsi Mhs", "Menampilkan status tahapan skripsi mahasiswa saat ini.", [], "none", []),
                createRequestItem("Konfigurasi Skripsi Prodi", "GET", "/mahasiswa/skripsi/config-prodi", "Skripsi Mhs", "Melihat syarat SKS, IPK, dan dokumen pengajuan skripsi prodi.", [], "none", []),
                createRequestItem("Cek Kelayakan Skripsi Mhs", "GET", "/mahasiswa/skripsi/cek-kelayakan", "Skripsi Mhs", "Validasi otomatis apakah SKS & syarat terpenuhi.", [], "none", []),
                createRequestItem("Simpan Pengajuan Judul Proposal", "POST", "/mahasiswa/skripsi/simpan-proposal", "Skripsi Mhs", "Ajukan judul proposal tugas akhir.", [], "urlencoded", ["judul" => "Penerapan Machine Learning untuk Deteksi Kanker", "deskripsi" => "Penelitian ini berfokus...", "bidang_minat" => "AI / Data Science"]),
                createRequestItem("Upload Naskah / Draft Skripsi", "POST", "/mahasiswa/skripsi/upload-naskah", "Skripsi Mhs", "Upload dokumen naskah (PDF/DOCX).", [], "formdata", ["id_skripsi" => "1", "jenis_naskah" => "proposal", "naskah" => ""]),
                createRequestItem("Ajukan Seminar Proposal (Sempro)", "POST", "/mahasiswa/skripsi/ajukan-sempro", "Skripsi Mhs", "Kirim pengajuan pendaftaran sempro ke Kaprodi.", [], "urlencoded", ["id_skripsi" => "1"]),
                createRequestItem("Ajukan Ujian Sidang Skripsi", "POST", "/mahasiswa/skripsi/ajukan-ujian", "Skripsi Mhs", "Kirim pengajuan pendaftaran sidang skripsi.", [], "urlencoded", ["id_skripsi" => "1"]),
                createRequestItem("Batalkan Pengajuan Ujian", "POST", "/mahasiswa/skripsi/batalkan-ujian", "Skripsi Mhs", "Batalkan pendaftaran sempro/sidang jika ada revisi berkas.", [], "urlencoded", ["id_skripsi" => "1"]),
                createRequestItem("Upload Berkas Persyaratan Skripsi", "POST", "/mahasiswa/skripsi/upload-berkas", "Skripsi Mhs", "Upload berkas syarat (Transkrip, Sertifikat TOEFL, dll).", [], "formdata", ["id_skripsi" => "1", "id_syarat" => "1", "berkas" => ""]),
                createRequestItem("Log Catatan Bimbingan Mahasiswa", "GET", "/mahasiswa/skripsi/log-bimbingan", "Skripsi Mhs", "Melihat riwayat log bimbingan skripsi.", ["id_skripsi" => "1"], "none", []),
                createRequestItem("Tambah Catatan Log Bimbingan", "POST", "/mahasiswa/skripsi/tambah-bimbingan", "Skripsi Mhs", "Entri catatan hasil konsultasi dengan dosen pembimbing.", [], "urlencoded", ["id_skripsi" => "1", "pembimbing_ke" => "1", "tgl_bimbingan" => "2023-10-10", "catatan" => "Revisi BAB 1 dan 2", "kemajuan" => "60"]),
                createRequestItem("Update Log Bimbingan", "POST", "/mahasiswa/skripsi/update-bimbingan/1", "Skripsi Mhs", "Ubah catatan log bimbingan.", [], "urlencoded", ["catatan" => "Perbaikan BAB 3", "kemajuan" => "75"]),
                createRequestItem("Hapus Log Bimbingan", "POST", "/mahasiswa/skripsi/hapus-bimbingan/1", "Skripsi Mhs", "Hapus catatan bimbingan.", [], "none", []),
                createRequestItem("Get Data Luaran Skripsi", "GET", "/mahasiswa/skripsi/get-luaran", "Skripsi Mhs", "Melihat luaran publikasi ilmiah/HAKI skripsi.", ["id_skripsi" => "1"], "none", []),
                createRequestItem("Simpan Luaran Publikasi Skripsi", "POST", "/mahasiswa/skripsi/simpan-luaran", "Skripsi Mhs", "Input data publikasi jurnal / HAKI tugas akhir.", [], "urlencoded", ["id_skripsi" => "1", "jenis_luaran" => "Jurnal Nasional Sinta 2", "judul_luaran" => "Analysis of...", "url_publikasi" => "https://journal.org/..."]),
                createRequestItem("Portofolio Capaian CPL Mahasiswa", "GET", "/mahasiswa/skripsi/portofolio-cpl", "Skripsi Mhs", "Rekapitulasi pencapaian CPL OBE tugas akhir mahasiswa.", [], "none", []),
            ],
            "Dosen - Pembimbing & Penguji OBE" => [
                createRequestItem("Dashboard Dosen Skripsi", "GET", "/dosen/skripsi/dashboard", "Skripsi Dosen", "Statistik bimbingan, jadwal menguji, dan tugas akhir aktif.", [], "none", []),
                createRequestItem("Log Bimbingan Mahasiswa Dosen", "GET", "/dosen/skripsi/log-bimbingan", "Skripsi Dosen", "List catatan bimbingan yang perlu divalidasi pembimbing.", ["id_skripsi" => "1"], "none", []),
                createRequestItem("Validasi / ACC Log Bimbingan", "POST", "/dosen/skripsi/validasi-bimbingan", "Skripsi Dosen", "Setujui atau tolak catatan bimbingan mahasiswa.", [], "urlencoded", ["id_bimbingan" => "1", "status" => "1", "komentar" => "Sudah baik, lanjutkan ke BAB 4"]),
                createRequestItem("ACC Mahasiswa Maju Ujian Sidang", "POST", "/dosen/skripsi/acc-ujian", "Skripsi Dosen", "Dosen memberikan rekomendasi/ACC mahasiswa maju ujian.", [], "urlencoded", ["id_skripsi" => "1", "status_acc" => "1"]),
                createRequestItem("List Mahasiswa yang Diuji Dosen", "GET", "/dosen/skripsi/list-mahasiswa-diuji", "Skripsi Dosen", "Daftar mahasiswa yang dijadwalkan diuji oleh dosen.", [], "none", []),
                createRequestItem("Get Rubrik & Indikator Penilaian Ujian", "GET", "/dosen/skripsi/get-rubrik-indikator", "Skripsi Dosen", "Mengambil rubrik indikator OBE untuk penilaian ujian.", ["id_ujian" => "1"], "none", []),
                createRequestItem("Get Nilai Ujian Indikator Dosen", "GET", "/dosen/skripsi/get-nilai-ujian-indikator", "Skripsi Dosen", "Melihat nilai yang telah diinput penguji sebelumnya.", ["id_ujian" => "1"], "none", []),
                createRequestItem("Simpan Penilaian Ujian Indikator OBE", "POST", "/dosen/skripsi/simpan-nilai-ujian-indikator", "Skripsi Dosen", "Input skor rubrik indikator CPL per aspek ujian.", [], "raw_json", ["id_ujian" => 1, "catatan_revisi" => "Perbaiki tinjauan pustaka", "nilai_indikator" => [["id_indikator" => 1, "nilai" => 85], ["id_indikator" => 2, "nilai" => 90]]]),
                createRequestItem("Get Berita Acara Ujian Sidang", "GET", "/dosen/skripsi/berita-acara/1", "Skripsi Dosen", "Melihat rangkuman berita acara dan hasil sidang ujian.", [], "none", []),
                createRequestItem("Persetujuan BA Ujian oleh Penguji", "POST", "/dosen/skripsi/setuju-berita-acara", "Skripsi Dosen", "Dosen menyetujui / menandatangani BAP hasil ujian skripsi.", [], "urlencoded", ["id_ujian" => "1", "status_setuju" => "1"]),
            ],
            "Kaprodi - Manajemen Skripsi" => [
                createRequestItem("Daftar Mahasiswa Skripsi Prodi", "GET", "/kaprodi/skripsi/list-mahasiswa", "Kaprodi Skripsi", "List seluruh mahasiswa skripsi prodi & status tahapan.", ["status_tahap" => "sempro"], "none", []),
                createRequestItem("Plotting Dosen Pembimbing Skripsi", "POST", "/kaprodi/skripsi/plot-pembimbing", "Kaprodi Skripsi", "Plotting pembimbing 1 & pembimbing 2 mahasiswa.", [], "urlencoded", ["id_skripsi" => "1", "id_pembimbing_1" => "101", "id_pembimbing_2" => "102"]),
                createRequestItem("Plotting Jadwal & Penguji Sempro", "POST", "/kaprodi/skripsi/plot-jadwal-sempro", "Kaprodi Skripsi", "Plotting tanggal, jam, ruangan, dan dosen penguji sempro.", [], "urlencoded", ["id_skripsi" => "1", "tgl_ujian" => "2023-11-20", "jam_mulai" => "09:00", "jam_selesai" => "10:30", "ruangan" => "R. Sidang 1", "penguji_1" => "101", "penguji_2" => "102", "penguji_3" => "103"]),
                createRequestItem("Plotting Jadwal & Penguji Ujian Sidang", "POST", "/kaprodi/skripsi/plot-jadwal-ujian", "Kaprodi Skripsi", "Plotting jadwal sidang skripsi dan dewan penguji.", [], "urlencoded", ["id_skripsi" => "1", "tgl_ujian" => "2023-12-15", "jam_mulai" => "10:00", "jam_selesai" => "11:30", "ruangan" => "R. Sidang Utama", "ketua_sidang" => "101", "sekretaris" => "102", "penguji_utama" => "103"]),
                createRequestItem("Get Detail Jadwal Ujian Mahasiswa", "GET", "/kaprodi/skripsi/get-jadwal-ujian/1", "Kaprodi Skripsi", "Melihat rincian jadwal ujian skripsi mahasiswa.", [], "none", []),
                createRequestItem("List Mahasiswa Siap Terbit SK", "GET", "/kaprodi/skripsi/list-siap-sk", "Kaprodi Skripsi", "Daftar mahasiswa yang telah disetujui untuk penerbitan SK Dospem/Ujian.", [], "none", []),
                createRequestItem("Issue SK Kolektif Pembimbing/Ujian", "POST", "/kaprodi/skripsi/issue-sk-kolektif", "Kaprodi Skripsi", "Terbitkan nomor SK penetapan pembimbing/sidang kolektif.", [], "urlencoded", ["nomor_sk" => "123/SK/FT/2023", "tgl_sk" => "2023-10-01", "jenis_sk" => "pembimbing", "id_skripsi_list" => "1,2,3"]),
                createRequestItem("Daftar SK Kolektif Terbit", "GET", "/kaprodi/skripsi/list-sk-terbit", "Kaprodi Skripsi", "Histori seluruh SK yang telah diterbitkan.", [], "none", []),
                createRequestItem("Get Detail SK Kolektif", "GET", "/kaprodi/skripsi/get-sk-detail/1", "Kaprodi Skripsi", "Melihat lampiran nama-nama mahasiswa di dalam SK.", [], "none", []),
                createRequestItem("Update Data SK Kolektif", "POST", "/kaprodi/skripsi/update-sk", "Kaprodi Skripsi", "Ubah nomor/tanggal SK kolektif.", [], "urlencoded", ["id_sk" => "1", "nomor_sk" => "123.A/SK/FT/2023"]),
                createRequestItem("Get Config Bobot Nilai Skripsi Prodi", "GET", "/kaprodi/skripsi/config-grading/55201", "Kaprodi Skripsi", "Melihat persentase bobot nilai bimbingan vs sidang prodi.", [], "none", []),
                createRequestItem("Update Config Bobot Nilai Skripsi", "POST", "/kaprodi/skripsi/update-config-grading", "Kaprodi Skripsi", "Ubah konfigurasi bobot nilai skripsi prodi.", [], "urlencoded", ["kode_prodi" => "55201", "bobot_bimbingan" => "40", "bobot_ujian" => "60"]),
                createRequestItem("Get Config Seminar Proposal Prodi", "GET", "/kaprodi/skripsi/config-sempro/55201", "Kaprodi Skripsi", "Setting batas minimal bimbingan sempro, kuota, dsb.", [], "none", []),
                createRequestItem("Update Config Seminar Proposal", "POST", "/kaprodi/skripsi/update-config-sempro", "Kaprodi Skripsi", "Ubah setting konfigurasi sempro prodi.", [], "urlencoded", ["kode_prodi" => "55201", "min_bimbingan_sempro" => "4", "min_sks" => "110"]),
                createRequestItem("Get Rubrik Indikator Penilaian Prodi", "GET", "/kaprodi/skripsi/get-rubrik-indikator/55201", "Kaprodi Skripsi", "Master indikator rubrik penilaian skripsi OBE prodi.", [], "none", []),
                createRequestItem("Simpan Rubrik Indikator Penilaian", "POST", "/kaprodi/skripsi/save-rubrik-indikator", "Kaprodi Skripsi", "Tambah/Update indikator penilaian OBE.", [], "urlencoded", ["kode_prodi" => "55201", "id_aspek" => "1", "nama_indikator" => "Kemampuan Argumentasi Ilmiah", "bobot" => "25"]),
                createRequestItem("Reset Rubrik Indikator ke Default", "POST", "/kaprodi/skripsi/reset-rubrik-indikator", "Kaprodi Skripsi", "Reset indikator penilaian prodi ke template default universitas.", [], "urlencoded", ["kode_prodi" => "55201"]),
                createRequestItem("Get Master Aspek Penilaian Prodi", "GET", "/kaprodi/skripsi/get-aspek/55201", "Kaprodi Skripsi", "Daftar aspek penilaian (Naskah, Presentasi, Penguasaan Materi).", [], "none", []),
                createRequestItem("Simpan Aspek Penilaian", "POST", "/kaprodi/skripsi/save-aspek", "Kaprodi Skripsi", "Tambah/Update aspek penilaian.", [], "urlencoded", ["kode_prodi" => "55201", "nama_aspek" => "Penguasaan Metodologi", "bobot" => "30"]),
                createRequestItem("Delete Aspek Penilaian", "POST", "/kaprodi/skripsi/delete-aspek/1", "Kaprodi Skripsi", "Hapus aspek penilaian.", [], "none", []),
                createRequestItem("Daftar Syarat Berkas Skripsi Prodi", "GET", "/kaprodi/skripsi/syarat-prodi/55201", "Kaprodi Skripsi", "Daftar dokumen wajib yang harus diupload mahasiswa.", [], "none", []),
                createRequestItem("Master Template Syarat Skripsi", "GET", "/kaprodi/skripsi/master-syarat", "Kaprodi Skripsi", "Master bank syarat dokumen skripsi universitas.", [], "none", []),
                createRequestItem("Simpan Syarat Berkas Prodi", "POST", "/kaprodi/skripsi/save-syarat-prodi", "Kaprodi Skripsi", "Aktifkan/tambahkan syarat dokumen pada prodi.", [], "urlencoded", ["kode_prodi" => "55201", "id_master_syarat" => "1", "wajib" => "1"]),
                createRequestItem("Hapus Syarat Berkas Prodi", "DELETE", "/kaprodi/skripsi/delete-syarat-prodi/1", "Kaprodi Skripsi", "Hapus syarat berkas dari prodi.", [], "none", []),
                createRequestItem("Daftar Penetapan Nilai Akhir Sidang", "GET", "/kaprodi/skripsi/penetapan-nilai", "Kaprodi Skripsi", "List mahasiswa yang telah ujian dan menunggu penetapan yudisium skripsi.", [], "none", []),
                createRequestItem("Tetapkan Nilai Akhir Skripsi Mhs", "POST", "/kaprodi/skripsi/tetapkan-nilai", "Kaprodi Skripsi", "Finalisasi nilai akhir (A/B/C/D) dan kelulusan skripsi.", [], "urlencoded", ["id_skripsi" => "1", "nilai_angka" => "86.5", "nilai_huruf" => "A", "status_lulus" => "Lulus"]),
                createRequestItem("List Approval Bimbingan Kaprodi", "GET", "/kaprodi/skripsi/bimbingan/list", "Kaprodi Skripsi", "Monitoring & approval bimbingan mahasiswa oleh Kaprodi.", [], "none", []),
                createRequestItem("Approve Bimbingan Mahasiswa Kaprodi", "POST", "/kaprodi/skripsi/bimbingan/approve", "Kaprodi Skripsi", "Kaprodi menyetujui rekap bimbingan untuk syarat sidang.", [], "urlencoded", ["id_skripsi" => "1"]),
                createRequestItem("Cari Matakuliah Prasyarat Skripsi", "GET", "/kaprodi/skripsi/search-matakuliah", "Kaprodi Skripsi", "Pencarian MK prasyarat (Metodologi Penelitian, dsb).", ["q" => "Metode"], "none", []),
            ],
            "Dekanat - Monitoring Skripsi" => [
                createRequestItem("Rekap Penilaian Ujian Fakultas", "GET", "/skripsi/dekanat/rekap-penilaian", "Dekanat Skripsi", "Laporan rekapitulasi ujian skripsi & nilai seluruh prodi di fakultas.", [], "none", []),
                createRequestItem("Detail Penilaian Sidang Mahasiswa", "GET", "/skripsi/dekanat/detail-penilaian/1", "Dekanat Skripsi", "Lihat rincian skor rubrik penguji pada suatu ujian sidang.", [], "none", []),
                createRequestItem("Daftar Prodi di Bawah Fakultas", "GET", "/skripsi/dekanat/prodi-list", "Dekanat Skripsi", "List program studi di bawah naungan fakultas dekan.", [], "none", []),
            ],
            "Admin - Laporan & Cetak Skripsi" => [
                createRequestItem("Rekap Log Bimbingan Skripsi Kampus", "GET", "/akademik/rekap-bimbingan", "Admin Skripsi", "Laporan agregat intensitas bimbingan mahasiswa per periode.", [], "none", []),
                createRequestItem("Cetak Lembar Log Bimbingan", "GET", "/akademik/skripsi/bimbingan/cetak-data", "Admin Skripsi", "Get payload data untuk cetak kartu bimbingan skripsi resmi.", ["id_skripsi" => "1"], "none", []),
                createRequestItem("Update Berita Acara Skripsi by Admin", "POST", "/akademik/skripsi/update-berita-acara", "Admin Skripsi", "Koreksi data BAP ujian skripsi oleh admin akademik.", [], "urlencoded", ["id_ujian" => "1", "catatan" => "Koreksi nomor ruangan"]),
                createRequestItem("List Konfigurasi Sempro Seluruh Prodi", "GET", "/akademik/skripsi/list-config-sempro", "Admin Skripsi", "Daftar setting sempro seluruh prodi universitas.", [], "none", []),
            ]
        ]
    ],
    "05. Dekanat & Kaprodi (Fakultas / Prodi)" => [
        "description" => "Layanan fakultas dan prodi untuk ACC KRS, setting dosen wali, dan monitoring nilai",
        "subfolders" => [
            "Approval & Validasi KRS" => [
                createRequestItem("Data Pengajuan ACC KRS Mahasiswa", "GET", "/dekanat/data-acckrs", "Dekanat", "Daftar mahasiswa yang mengajukan persetujuan KRS.", [], "none", []),
                createRequestItem("Ubah Status ACC KRS Mahasiswa", "GET", "/dekanat/ubahstatus-acckrs", "Dekanat", "Setujui (ACC) atau tolak KRS mahasiswa.", ["id_krs" => "1", "status" => "1"], "none", []),
            ],
            "Setting & Plotting Dosen Wali" => [
                createRequestItem("Daftar Setting Dosen Wali", "GET", "/dekanat/setting-dosenwali", "Dekanat", "List dosen wali beserta jumlah mahasiswa binaan.", [], "none", []),
                createRequestItem("Daftar Mahasiswa Belum Berwali", "GET", "/dekanat/daftar-mahasiswa", "Dekanat", "List mahasiswa yang belum di-assign dosen wali.", [], "none", []),
                createRequestItem("Daftar Mahasiswa Per Dosen Wali", "GET", "/dekanat/daftarmhs-pa", "Dekanat", "List mahasiswa di bawah bimbingan dosen wali terpilih.", ["id_dosen" => "101"], "none", []),
                createRequestItem("Daftar Mahasiswa Prodi Tertentu", "GET", "/kaprodi/daftarmhs-prodi", "Dekanat", "List seluruh mahasiswa aktif di prodi.", ["kode_prodi" => "55201"], "none", []),
                createRequestItem("List Mahasiswa Sudah Berwali", "GET", "/dekanat/list-mhs-already", "Dekanat", "Daftar mahasiswa yang sudah memiliki dosen wali.", [], "none", []),
                createRequestItem("Plotting Mahasiswa ke Dosen Wali", "POST", "/dekanat/add-mhs-dosenwali", "Dekanat", "Assign satu/beberapa mahasiswa ke dosen wali.", [], "urlencoded", ["id_dosen" => "101", "nim" => "2021010001,2021010002"]),
                createRequestItem("Nonaktifkan Mahasiswa dari Dosen Wali", "GET", "/dekanat/nonaktif-mhs-dosenwali", "Dekanat", "Nonaktifkan perwalian mahasiswa.", ["id_wali" => "1"], "none", []),
                createRequestItem("Hapus Mahasiswa dari Dosen Wali", "GET", "/dekanat/hapus-mhs-dosenwali", "Dekanat", "Hapus relasi perwalian mahasiswa.", ["id_wali" => "1"], "none", []),
            ],
            "Rekap Nilai & Transkrip Prodi" => [
                createRequestItem("Rekap Transkrip Nilai Mahasiswa Prodi", "GET", "/dekanat/data-transkripnilai", "Dekanat", "Melihat data transkrip nilai mahasiswa per prodi.", ["kode_prodi" => "55201"], "none", []),
                createRequestItem("Simpan Nilai UTS by Dekanat", "POST", "/dekanat/simpan-nilai-uts", "Dekanat", "Input/koreksi nilai UTS oleh pihak dekanat/fakultas.", [], "urlencoded", ["id_makul_penawaran" => "1", "nilai" => "[{\"nim\":\"2021010001\",\"nilai_uts\":80}]"]),
                createRequestItem("Simpan Nilai UAS by Dekanat", "POST", "/dekanat/simpan-nilai-uas", "Dekanat", "Input/koreksi nilai UAS oleh pihak dekanat/fakultas.", [], "urlencoded", ["id_makul_penawaran" => "1", "nilai" => "[{\"nim\":\"2021010001\",\"nilai_uas\":85}]"]),
                createRequestItem("Ubah Password Dekan / Admin Fakultas", "POST", "/dekanat/edit_password_dekanadmin", "Dekanat", "Ganti password akun dekanat.", [], "urlencoded", ["password_lama" => "oldpass", "password_baru" => "newpass"]),
            ]
        ]
    ],
    "06. Admin Akademik & Master Data" => [
        "description" => "Manajemen data master universitas (Fakultas, Prodi, Matakuliah, Kurikulum, Dosen, Mahasiswa), Operasional & Tools Import/Export",
        "subfolders" => [
            "Master Kelembagaan (Fakultas & Prodi)" => [
                createRequestItem("Daftar Fakultas", "GET", "/akademik/fakultas", "Kelembagaan", "List data fakultas aktif.", [], "none", []),
                createRequestItem("Simpan Fakultas Baru", "POST", "/akademik/simpan-fakultas", "Kelembagaan", "Tambah data fakultas baru.", [], "urlencoded", ["nama_fakultas" => "Fakultas Ilmu Komputer", "dekan" => "Dr. Ir. Dekan"]),
                createRequestItem("Edit Fakultas", "POST", "/akademik/edit-fakultas", "Kelembagaan", "Update data fakultas.", [], "urlencoded", ["id_fakultas" => "1", "nama_fakultas" => "Fakultas Teknik & Ilmu Komputer"]),
                createRequestItem("Hapus Fakultas", "GET", "/akademik/hapus-fakultas", "Kelembagaan", "Hapus fakultas.", ["id_fakultas" => "1"], "none", []),
                createRequestItem("Ubah Status Aktif Fakultas", "GET", "/akademik/ubahstatus-fakultas", "Kelembagaan", "Aktif/Nonaktifkan fakultas.", ["id_fakultas" => "1", "status" => "1"], "none", []),
                createRequestItem("Daftar Program Studi", "GET", "/akademik/programstudi", "Kelembagaan", "List seluruh program studi.", [], "none", []),
                createRequestItem("Daftar Prodi per Fakultas", "GET", "/akademik/programstudi-fak", "Kelembagaan", "Filter prodi berdasarkan ID fakultas.", ["id_fakultas" => "1"], "none", []),
                createRequestItem("Simpan Program Studi Baru", "POST", "/akademik/simpan-programstudi", "Kelembagaan", "Tambah data prodi baru.", [], "urlencoded", ["kode_prodi" => "55201", "nama_prodi" => "Informatika", "jenjang" => "S1", "id_fakultas" => "1"]),
                createRequestItem("Edit Program Studi", "POST", "/akademik/edit-programstudi", "Kelembagaan", "Update data program studi.", [], "urlencoded", ["id_prodi" => "1", "nama_prodi" => "Teknik Informatika"]),
                createRequestItem("Hapus Program Studi", "GET", "/akademik/hapus-programstudi", "Kelembagaan", "Hapus data prodi.", ["id_prodi" => "1"], "none", []),
                createRequestItem("Ubah Status Program Studi", "GET", "/akademik/ubahstatus-programstudi", "Kelembagaan", "Aktif/Nonaktifkan prodi.", ["id_prodi" => "1", "status" => "1"], "none", []),
            ],
            "Master Kurikulum & Matakuliah" => [
                createRequestItem("Daftar Kurikulum", "GET", "/akademik/kurikulum", "Kurikulum", "Daftar kurikulum universitas.", [], "none", []),
                createRequestItem("Simpan Kurikulum Baru", "POST", "/akademik/simpan-kurikulum", "Kurikulum", "Tambah data kurikulum.", [], "urlencoded", ["kode_kurikulum" => "KUR-2024", "nama_kurikulum" => "Kurikulum MBKM 2024", "kode_prodi" => "55201"]),
                createRequestItem("Edit Kurikulum", "POST", "/akademik/edit-kurikulum", "Kurikulum", "Update data kurikulum.", [], "urlencoded", ["id_kurikulum" => "1", "nama_kurikulum" => "Kurikulum OBE 2024"]),
                createRequestItem("Hapus Kurikulum", "GET", "/akademik/hapus-kurikulum", "Kurikulum", "Hapus kurikulum.", ["id_kurikulum" => "1"], "none", []),
                createRequestItem("Daftar Matakuliah", "GET", "/akademik/matakuliah", "Kurikulum", "Daftar seluruh matakuliah.", ["kode_prodi" => "55201"], "none", []),
                createRequestItem("Simpan Matakuliah", "POST", "/akademik/simpan-matakuliah", "Kurikulum", "Tambah matakuliah baru.", [], "urlencoded", ["kode_makul" => "IF101", "nama_makul" => "Algoritma & Pemrograman", "nama_makul_en" => "Algorithm & Programming", "sks" => "3", "semester" => "1", "kode_prodi" => "55201"]),
                createRequestItem("Edit Matakuliah", "POST", "/akademik/edit-matakuliah", "Kurikulum", "Update data matakuliah.", [], "urlencoded", ["id_makul" => "1", "nama_makul" => "Algoritma dan Struktur Data"]),
                createRequestItem("Hapus Matakuliah", "GET", "/akademik/hapus-matakuliah", "Kurikulum", "Hapus matakuliah.", ["id_makul" => "1"], "none", []),
                createRequestItem("Update Terjemahan Bahasa Inggris Matakuliah", "POST", "/akademik/update-translate-matakuliah", "Kurikulum", "Update nama matakuliah versi bahasa Inggris.", [], "urlencoded", ["id_makul" => "1", "nama_makul_en" => "Advanced Data Structures"]),
                createRequestItem("Daftar Matakuliah Prasyarat", "GET", "/akademik/makulprasyarat", "Kurikulum", "List relasi matakuliah dan prasyaratnya.", [], "none", []),
                createRequestItem("Simpan Matakuliah Prasyarat", "POST", "/akademik/simpan-makulprasyarat", "Kurikulum", "Setting prasyarat matakuliah.", [], "urlencoded", ["id_makul" => "2", "id_makul_prasyarat" => "1", "nilai_minimal" => "C"]),
                createRequestItem("Hapus Matakuliah Prasyarat", "GET", "/akademik/hapus-makulprasyarat", "Kurikulum", "Hapus prasyarat matakuliah.", ["id_prasyarat" => "1"], "none", []),
            ],
            "Matakuliah Penawaran & Jadwal" => [
                createRequestItem("Daftar Matakuliah Penawaran", "GET", "/akademik/makulpenawaran", "Penawaran", "Daftar kelas matakuliah penawaran semester aktif.", ["tahun_akademik" => "2023/2024", "semester" => "1"], "none", []),
                createRequestItem("Simpan Matakuliah Penawaran", "POST", "/akademik/simpan-makulpenawaran", "Penawaran", "Buka kelas matakuliah penawaran baru.", [], "urlencoded", ["id_makul" => "1", "kelas" => "A", "id_dosen" => "101", "hari" => "Senin", "jam_mulai" => "08:00", "jam_selesai" => "10:30", "ruangan" => "Lab 1", "kuota" => "40"]),
                createRequestItem("Edit Matakuliah Penawaran", "POST", "/akademik/edit-makulpenawaran", "Penawaran", "Update kelas penawaran matakuliah.", [], "urlencoded", ["id_penawaran" => "1", "kuota" => "45"]),
                createRequestItem("Hapus Matakuliah Penawaran", "GET", "/akademik/hapus-makulpenawaran", "Penawaran", "Hapus kelas penawaran.", ["id_penawaran" => "1"], "none", []),
                createRequestItem("Update Link RPS Matakuliah", "POST", "/akademik/update-rps", "Penawaran", "Update tautan dokumen Rencana Pembelajaran Semester (RPS).", [], "urlencoded", ["id_penawaran" => "1", "url_rps" => "https://cloud.umuka.ac.id/rps/if101.pdf"]),
                createRequestItem("Edit Jadwal Ujian Matakuliah", "POST", "/akademik/edit-jadwalujian", "Penawaran", "Setting tgl & jam pelaksanaan UTS/UAS kelas.", [], "urlencoded", ["id_penawaran" => "1", "tgl_uts" => "2023-11-10", "tgl_uas" => "2024-01-15"]),
            ],
            "Tahun Ajaran & Kalender Akademik" => [
                createRequestItem("Daftar Tahun Ajaran", "GET", "/akademik/tahunajaran", "Kalender", "Daftar seluruh tahun akademik universitas.", [], "none", []),
                createRequestItem("Simpan Tahun Ajaran Baru", "POST", "/akademik/simpan-tahunajaran", "Kalender", "Tambah tahun akademik baru.", [], "urlencoded", ["tahun_akademik" => "2024/2025", "semester" => "1"]),
                createRequestItem("Ubah Status Aktif Tahun Ajaran", "GET", "/akademik/ubahstatus-tahunajaran", "Kalender", "Set tahun ajaran yang sedang aktif.", ["id_tahun" => "1", "status" => "1"], "none", []),
                createRequestItem("Daftar Kalender Akademik", "GET", "/akademik/kalenderakademik", "Kalender", "Jadwal kegiatan penting akademik per semester.", [], "none", []),
                createRequestItem("Simpan Jadwal Kegiatan Akademik", "POST", "/akademik/simpan-kalenderakademik", "Kalender", "Tambah agenda kalender akademik.", [], "urlencoded", ["kegiatan" => "Masa Pembayaran SPP", "tgl_mulai" => "2024-08-01", "tgl_selesai" => "2024-08-25"]),
                createRequestItem("Edit Kalender Akademik", "POST", "/akademik/edit-kalenderakademik", "Kalender", "Update agenda kalender akademik.", [], "urlencoded", ["id_kalender" => "1", "tgl_selesai" => "2024-08-30"]),
                createRequestItem("Hapus Kalender Akademik", "GET", "/akademik/hapus-kalenderakademik", "Kalender", "Hapus agenda kalender akademik.", ["id_kalender" => "1"], "none", []),
            ],
            "Data Dosen & Mahasiswa" => [
                createRequestItem("Daftar Master Dosen", "GET", "/akademik/dosen", "Civitas", "Daftar seluruh dosen universitas.", [], "none", []),
                createRequestItem("Daftar Master Mahasiswa", "GET", "/akademik/mahasiswa", "Civitas", "Daftar seluruh mahasiswa universitas.", ["kode_prodi" => "55201"], "none", []),
                createRequestItem("Edit Data Mahasiswa", "POST", "/akademik/edit-mahasiswa", "Civitas", "Update biodata & status mahasiswa oleh admin.", [], "urlencoded", ["nim" => "2021010001", "nama" => "Mahasiswa Test", "status_mhs" => "A"]),
                createRequestItem("Reset Password Mahasiswa", "POST", "/akademik/edit-passwordmahasiswamhs", "Civitas", "Admin mereset password portal mahasiswa.", [], "urlencoded", ["nim" => "2021010001", "password_baru" => "123456"]),
                createRequestItem("Reset Password Portal Orang Tua", "POST", "/akademik/edit-passwordmahasiswaortu", "Civitas", "Admin mereset password akun wali/ortu.", [], "urlencoded", ["nim" => "2021010001", "password_baru" => "123456"]),
                createRequestItem("Cek Status Kelulusan Mahasiswa", "GET", "/akademik/status_lulus_mahasiswa", "Civitas", "Rekapitulasi status mahasiswa lulus.", [], "none", []),
                createRequestItem("Sinkronisasi Transkrip Nilai Mahasiswa", "POST", "/akademik/sinkron-transkrip", "Civitas", "Hitung ulang IPK dan SKS kumulatif mahasiswa dari KHS.", [], "urlencoded", ["nim" => "2021010001"]),
            ],
            "Cetak Dokumen & BAP Resmi" => [
                createRequestItem("Cetak KHS Mahasiswa", "POST", "/akademik/cetak-khs", "Cetak", "Generate data PDF / Cetak Kartu Hasil Studi.", [], "urlencoded", ["nim" => "2021010001", "semester" => "1"]),
                createRequestItem("Cetak KRS Mahasiswa", "POST", "/akademik/cetak-krs", "Cetak", "Generate data PDF / Cetak Kartu Rencana Studi.", [], "urlencoded", ["nim" => "2021010001", "tahun_akademik" => "2023/2024", "semester" => "1"]),
                createRequestItem("Cetak Kartu Ujian Mahasiswa", "POST", "/akademik/cetakkartuujian", "Cetak", "Generate data Kartu Ujian UTS/UAS.", [], "urlencoded", ["nim" => "2021010001", "jenis_ujian" => "UTS"]),
                createRequestItem("Cetak Transkrip Nilai Akademik", "POST", "/akademik/cetaktranskipakademik", "Cetak", "Generate data Transkrip Akademik resmi (Indonesia).", [], "urlencoded", ["nim" => "2021010001"]),
                createRequestItem("Cetak Transkrip Akademik Bahasa Inggris", "POST", "/akademik/cetaktranskipakademikinggris", "Cetak", "Generate data Transkrip Akademik versi English.", [], "urlencoded", ["nim" => "2021010001"]),
                createRequestItem("Cetak Daftar Hadir Kuliah", "POST", "/akademik/cetakdaftarhadirkuliah", "Cetak", "Cetak blangko presensi perkuliahan kelas.", [], "urlencoded", ["id_makul_penawaran" => "1"]),
                createRequestItem("Cetak Daftar Hadir Ujian", "POST", "/akademik/cetakdaftarhadirujian", "Cetak", "Cetak blangko presensi pelaksanaan ujian.", [], "urlencoded", ["id_makul_penawaran" => "1", "jenis_ujian" => "UTS"]),
            ],
            "Tools Import & Transkrip Ajuan" => [
                createRequestItem("Download Template Nilai UTS Excel", "GET", "/akademik/template-input-nilai-uts", "Tools", "Download template Excel format import nilai UTS.", [], "none", [], true),
                createRequestItem("Download Template Nilai UAS Excel", "GET", "/akademik/template-input-nilai-uas", "Tools", "Download template Excel format import nilai UAS.", [], "none", [], true),
                createRequestItem("Import Nilai UTS Excel", "POST", "/akademik/import-nilai-uts", "Tools", "Import nilai UTS kelas dari file Excel.", [], "formdata", ["file_excel" => "", "id_makul_penawaran" => "1"], true),
                createRequestItem("Import Nilai UAS Excel", "POST", "/akademik/import-nilai-uas", "Tools", "Import nilai UAS kelas dari file Excel.", [], "formdata", ["file_excel" => "", "id_makul_penawaran" => "1"], true),
                createRequestItem("Download Template Jadwal Ujian", "GET", "/akademik/jadwalujian/export-template", "Tools", "Download template Excel jadwal ujian.", [], "none", [], true),
                createRequestItem("Import Jadwal Ujian Excel", "POST", "/akademik/jadwalujian/import", "Tools", "Import jadwal ujian kelas dari file Excel.", [], "formdata", ["file_excel" => ""]),
                createRequestItem("Import Penawaran Matakuliah", "POST", "/akademiktools/import-makul-penawaran", "Tools", "Import matakuliah penawaran massal dari Excel.", [], "formdata", ["file_excel" => ""]),
                createRequestItem("Master Data PKKMB Mahasiswa", "GET", "/akademik/pkkmb", "Tools", "Daftar kelulusan PKKMB mahasiswa baru.", [], "none", []),
                createRequestItem("Import Data PKKMB Excel", "POST", "/akademik/pkkmb/import", "Tools", "Import kelulusan PKKMB mahasiswa dari file Excel.", [], "formdata", ["file_excel" => ""]),
                createRequestItem("List Semua Pengajuan Transkrip Mahasiswa", "GET", "/akademik/transkrip-ajuan", "Tools", "Daftar antrian pengajuan cetak transkrip resmi mhs.", [], "none", []),
                createRequestItem("Approve Pengajuan Transkrip", "POST", "/akademik/transkrip-ajuan/approve", "Tools", "BAA menyetujui pengajuan transkrip mahasiswa.", [], "urlencoded", ["id_ajuan" => "1", "nomor_transkrip" => "123/TRA/2024"]),
                createRequestItem("Cancel / Tolak Pengajuan Transkrip", "POST", "/akademik/transkrip-ajuan/cancel", "Tools", "BAA menolak pengajuan transkrip mahasiswa.", [], "urlencoded", ["id_ajuan" => "1", "alasan" => "Masih ada tanggungan SPP"]),
            ]
        ]
    ]
];

// Build collection tree
foreach ($modules as $moduleName => $moduleData) {
    $folderItem = [
        "name" => $moduleName,
        "description" => $moduleData["description"] ?? "",
        "item" => []
    ];

    foreach ($moduleData["subfolders"] as $subfolderName => $requests) {
        $subfolderItem = [
            "name" => $subfolderName,
            "item" => $requests
        ];
        $folderItem["item"][] = $subfolderItem;
    }

    $collection["item"][] = $folderItem;
}

// Build Environment JSON
$environment = [
    "id" => "siakad-umuka-env-" . uniqid(),
    "name" => "SIAKAD_DEV (Local Laragon)",
    "values" => [
        [
            "key" => "base_url",
            "value" => "http://localhost/siakaddev/apisiaumukadev/public/api",
            "type" => "default",
            "enabled" => true
        ],
        [
            "key" => "username",
            "value" => "2021010001",
            "type" => "default",
            "enabled" => true
        ],
        [
            "key" => "password",
            "value" => "password123",
            "type" => "secret",
            "enabled" => true
        ],
        [
            "key" => "token",
            "value" => "",
            "type" => "secret",
            "enabled" => true
        ],
        [
            "key" => "kode_prodi",
            "value" => "55201",
            "type" => "default",
            "enabled" => true
        ],
        [
            "key" => "id_skripsi",
            "value" => "1",
            "type" => "default",
            "enabled" => true
        ],
        [
            "key" => "id_ujian",
            "value" => "1",
            "type" => "default",
            "enabled" => true
        ],
        [
            "key" => "id_makul_penawaran",
            "value" => "1",
            "type" => "default",
            "enabled" => true
        ]
    ],
    "_postman_variable_scope" => "environment"
];

$postmanDir = __DIR__ . '/../postman';
if (!is_dir($postmanDir)) {
    mkdir($postmanDir, 0777, true);
}

$collectionPath = $postmanDir . '/SIAKAD_API.postman_collection.json';
$environmentPath = $postmanDir . '/SIAKAD_DEV.postman_environment.json';

file_put_contents($collectionPath, json_encode($collection, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
file_put_contents($environmentPath, json_encode($environment, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

echo "SUCCESS: Generated Postman Collection with " . count($collection["item"]) . " main modules.\n";
echo "Collection File: " . realpath($collectionPath) . "\n";
echo "Environment File: " . realpath($environmentPath) . "\n";
