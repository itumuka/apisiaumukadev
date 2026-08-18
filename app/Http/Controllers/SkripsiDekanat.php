<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SkripsiDekanat extends Controller
{
    /**
     * Rekap Penilaian Akhir Skripsi per Fakultas
     */
    public function rekap_penilaian(Request $request)
    {
        $v = Validator::make($request->all(), [
            'kode_fakultas' => 'required',
            'kode_prodi'    => 'nullable',
            'tahun'         => 'nullable',
            'semester'      => 'nullable',
        ]);

        if ($v->fails()) {
            return response()->json(['error' => $v->errors()->all()], 422);
        }

        $kode_fakultas = $request->kode_fakultas;
        $kode_prodi = $request->kode_prodi;

        $query = DB::table('akd_skripsi_ujian as u')
            ->join('akd_skripsi as s', 'u.id_skripsi', '=', 's.id')
            ->join('akd_mahasiswa as m', 's.nim', '=', 'm.nim')
            ->join('akd_program_studi as prodi', 'm.kode_program_studi', '=', 'prodi.kode_program_studi')
            ->leftJoin('simpeg_pegawai as p1', 'u.id_penguji1', '=', 'p1.id')
            ->leftJoin('simpeg_pegawai as p2', 'u.id_penguji2', '=', 'p2.id')
            ->leftJoin('simpeg_pegawai as p3', 'u.id_penguji3', '=', 'p3.id')
            ->leftJoin('simpeg_pegawai as pem1', 's.id_dosen_pembimbing1', '=', 'pem1.id')
            ->leftJoin('simpeg_pegawai as pem2', 's.id_dosen_pembimbing2', '=', 'pem2.id')
            ->leftJoin('akd_skripsi_berita_acara as ba', 'u.id', '=', 'ba.id_skripsi_ujian')
            ->select(
                'u.id as id_skripsi_ujian',
                'u.id_skripsi',
                'm.nim',
                'm.nama_mahasiswa',
                'm.kode_program_studi',
                'prodi.nama_program_studi',
                'prodi.kode_fakultas',
                's.judul',
                's.target_luaran',
                's.status as status_skripsi',
                's.valid_id_kaprodi',
                'u.status as status_ujian',
                'u.tanggal_ujian',
                'u.jam_mulai',
                'u.jam_selesai',
                'u.ruang',
                'u.nilai_angka as u_nilai_angka',
                'u.nilai_ujian as u_nilai_huruf',
                'ba.id as id_berita_acara',
                'ba.nomor_ba',
                'ba.nilai_angka as ba_nilai_angka',
                'ba.nilai_huruf as ba_nilai_huruf',
                'ba.keputusan',
                'ba.status as status_ba',
                'ba.setuju_penguji1',
                'ba.setuju_penguji2',
                'ba.setuju_penguji3',
                'u.id_penguji1',
                'p1.nama as p1_nama', 'p1.gelar_depan as p1_gd', 'p1.gelar_belakang as p1_gb',
                'u.id_penguji2',
                'p2.nama as p2_nama', 'p2.gelar_depan as p2_gd', 'p2.gelar_belakang as p2_gb',
                'u.id_penguji3',
                'p3.nama as p3_nama', 'p3.gelar_depan as p3_gd', 'p3.gelar_belakang as p3_gb',
                's.id_dosen_pembimbing1',
                'pem1.nama as pem1_nama', 'pem1.gelar_depan as pem1_gd', 'pem1.gelar_belakang as pem1_gb',
                's.id_dosen_pembimbing2',
                'pem2.nama as pem2_nama', 'pem2.gelar_depan as pem2_gd', 'pem2.gelar_belakang as pem2_gb'
            )
            ->where('prodi.kode_fakultas', $kode_fakultas);

        if ($kode_prodi) {
            $query->where('m.kode_program_studi', $kode_prodi);
        }

        $records = $query->orderBy('u.id', 'desc')->get();

        if ($records->isEmpty()) {
            return response()->json([]);
        }

        $ujian_ids = $records->pluck('id_skripsi_ujian')->toArray();

        // Get count of rubric scores per examiner for each exam
        $nilai_counts = DB::table('akd_skripsi_nilai_indikator')
            ->select('id_skripsi_ujian', 'id_dosen', DB::raw('COUNT(*) as total_indikator'), DB::raw('AVG(nilai) as avg_nilai'))
            ->whereIn('id_skripsi_ujian', $ujian_ids)
            ->groupBy('id_skripsi_ujian', 'id_dosen')
            ->get();

        // Group scores by id_skripsi_ujian and id_dosen
        $scores_map = [];
        foreach ($nilai_counts as $nc) {
            $scores_map[$nc->id_skripsi_ujian][$nc->id_dosen] = [
                'count' => $nc->total_indikator,
                'avg'   => round($nc->avg_nilai, 2)
            ];
        }

        $result = [];
        foreach ($records as $r) {
            $id_ujian = $r->id_skripsi_ujian;

            // Formatted examiner names
            $nama_p1 = $r->p1_nama ? trim(($r->p1_gd ? $r->p1_gd . ' ' : '') . $r->p1_nama . ($r->p1_gb ? ', ' . $r->p1_gb : '')) : '-';
            $nama_p2 = $r->p2_nama ? trim(($r->p2_gd ? $r->p2_gd . ' ' : '') . $r->p2_nama . ($r->p2_gb ? ', ' . $r->p2_gb : '')) : '-';
            $nama_p3 = $r->p3_nama ? trim(($r->p3_gd ? $r->p3_gd . ' ' : '') . $r->p3_nama . ($r->p3_gb ? ', ' . $r->p3_gb : '')) : '-';

            $nama_pem1 = $r->pem1_nama ? trim(($r->pem1_gd ? $r->pem1_gd . ' ' : '') . $r->pem1_nama . ($r->pem1_gb ? ', ' . $r->pem1_gb : '')) : '-';
            $nama_pem2 = $r->pem2_nama ? trim(($r->pem2_gd ? $r->pem2_gd . ' ' : '') . $r->pem2_nama . ($r->pem2_gb ? ', ' . $r->pem2_gb : '')) : '-';

            // Examiner evaluation status check (P1, P2, P3)
            $p1_evaluated = false;
            if ($r->id_penguji1) {
                $p1_evaluated = isset($scores_map[$id_ujian][$r->id_penguji1]) || !empty($r->setuju_penguji1);
            }

            $p2_evaluated = false;
            if ($r->id_penguji2) {
                $p2_evaluated = isset($scores_map[$id_ujian][$r->id_penguji2]) || !empty($r->setuju_penguji2);
            } else {
                $p2_evaluated = true; // No P2 assigned, consider ok
            }

            $p3_evaluated = false;
            if ($r->id_penguji3) {
                $p3_evaluated = isset($scores_map[$id_ujian][$r->id_penguji3]) || !empty($r->setuju_penguji3);
            } else {
                $p3_evaluated = true; // No P3 assigned, consider ok
            }

            $kaprodi_validated = !empty($r->valid_id_kaprodi) || ($r->status_ujian === 'lulus') || ($r->status_skripsi === 'lulus');

            // Determine if overall status is Final or Belum Final
            $is_final = ($p1_evaluated && $p2_evaluated && $p3_evaluated && $kaprodi_validated && ($r->status_ba === 'selesai' || $r->status_ujian === 'lulus' || $r->status_skripsi === 'lulus'));

            // Final score display
            $nilai_angka = $r->ba_nilai_angka ?: ($r->u_nilai_angka ?: '-');
            $nilai_huruf = $r->ba_nilai_huruf ?: ($r->u_nilai_huruf ?: '-');

            $result[] = [
                'id_skripsi_ujian' => $id_ujian,
                'id_skripsi' => $r->id_skripsi,
                'nim' => $r->nim,
                'nama_mahasiswa' => $r->nama_mahasiswa,
                'kode_program_studi' => $r->kode_program_studi,
                'nama_program_studi' => $r->nama_program_studi,
                'judul' => $r->judul,
                'target_luaran' => $r->target_luaran ?: 'buku_skripsi',
                'status_skripsi' => $r->status_skripsi,
                'status_ujian' => $r->status_ujian,
                'tanggal_ujian' => $r->tanggal_ujian,
                'nomor_ba' => $r->nomor_ba ?: '-',
                'nilai_angka' => $nilai_angka,
                'nilai_huruf' => $nilai_huruf,
                'keputusan' => $r->keputusan ?: '-',
                'status_ba' => $r->status_ba ?: 'draft',
                'is_final' => $is_final,
                'status_badge' => $is_final ? 'Final Penilaian' : 'Belum Final',
                'status_badge_class' => $is_final ? 'badge-success' : 'badge-warning',
                'penguji' => [
                    'p1' => [
                        'id' => $r->id_penguji1,
                        'nama' => $nama_p1,
                        'evaluated' => $p1_evaluated,
                        'setuju' => !empty($r->setuju_penguji1)
                    ],
                    'p2' => [
                        'id' => $r->id_penguji2,
                        'nama' => $nama_p2,
                        'evaluated' => $p2_evaluated,
                        'setuju' => !empty($r->setuju_penguji2)
                    ],
                    'p3' => [
                        'id' => $r->id_penguji3,
                        'nama' => $nama_p3,
                        'evaluated' => $p3_evaluated,
                        'setuju' => !empty($r->setuju_penguji3)
                    ],
                    'kaprodi' => [
                        'validated' => $kaprodi_validated
                    ]
                ],
                'pembimbing' => [
                    'pem1' => ['id' => $r->id_dosen_pembimbing1, 'nama' => $nama_pem1],
                    'pem2' => ['id' => $r->id_dosen_pembimbing2, 'nama' => $nama_pem2]
                ]
            ];
        }

        return response()->json($result);
    }

    /**
     * Get detail breakdown score per indicator for a specific exam
     */
    public function detail_penilaian(Request $request, $id_ujian)
    {
        $ujian = DB::table('akd_skripsi_ujian as u')
            ->join('akd_skripsi as s', 'u.id_skripsi', '=', 's.id')
            ->join('akd_mahasiswa as m', 's.nim', '=', 'm.nim')
            ->join('akd_program_studi as prodi', 'm.kode_program_studi', '=', 'prodi.kode_program_studi')
            ->leftJoin('akd_skripsi_berita_acara as ba', 'u.id', '=', 'ba.id_skripsi_ujian')
            ->select(
                'u.id as id_skripsi_ujian',
                'm.nim',
                'm.nama_mahasiswa',
                'prodi.nama_program_studi',
                's.judul',
                's.target_luaran',
                'u.tanggal_ujian',
                'u.nilai_angka as u_nilai_angka',
                'u.nilai_ujian as u_nilai_huruf',
                'ba.nomor_ba',
                'ba.nilai_angka as ba_nilai_angka',
                'ba.nilai_huruf as ba_nilai_huruf',
                'ba.keputusan',
                'ba.catatan'
            )
            ->where('u.id', $id_ujian)
            ->first();

        if (!$ujian) {
            return response()->json(['error' => 'Data Ujian tidak ditemukan'], 404);
        }

        $scores = DB::table('akd_skripsi_nilai_indikator as ni')
            ->leftJoin('simpeg_pegawai as peg', 'ni.id_dosen', '=', 'peg.id')
            ->leftJoin('akd_skripsi_rubrik_indikator as ind', 'ni.id_rubrik_indikator', '=', 'ind.id')
            ->select(
                'ni.id',
                'ni.id_dosen',
                'peg.nama as nama_dosen',
                'peg.gelar_depan',
                'peg.gelar_belakang',
                'ni.id_rubrik_indikator',
                'ni.nilai',
                'ni.nama_indikator_snapshot',
                'ni.aspek_snapshot',
                'ni.bobot_snapshot',
                'ind.nama_indikator',
                'ind.bobot'
            )
            ->where('ni.id_skripsi_ujian', $id_ujian)
            ->orderBy('ni.id_dosen')
            ->orderBy('ni.id_rubrik_indikator')
            ->get();

        $grouped_scores = [];
        foreach ($scores as $s) {
            $id_dosen = $s->id_dosen;
            if (!isset($grouped_scores[$id_dosen])) {
                $nama_dosen = $s->nama_dosen ? trim(($s->gelar_depan ? $s->gelar_depan . ' ' : '') . $s->nama_dosen . ($s->gelar_belakang ? ', ' . $s->gelar_belakang : '')) : "Dosen ID #{$id_dosen}";
                $grouped_scores[$id_dosen] = [
                    'id_dosen'   => $id_dosen,
                    'nama_dosen' => $nama_dosen,
                    'indikator'  => [],
                    'total_skor' => 0
                ];
            }

            $nama_ind = $s->nama_indikator_snapshot ?: ($s->nama_indikator ?: "Indikator #{$s->id_rubrik_indikator}");
            $bobot = (float)($s->bobot_snapshot ?: ($s->bobot ?: 0));
            $val = (float)$s->nilai;
            $terbobot = round(($val * $bobot) / 100, 2);

            $grouped_scores[$id_dosen]['total_skor'] += $terbobot;
            $grouped_scores[$id_dosen]['indikator'][] = [
                'nama_indikator' => $nama_ind,
                'aspek'          => $s->aspek_snapshot ?: '-',
                'bobot'          => $bobot,
                'nilai'          => $val,
                'skor_terbobot'  => $terbobot
            ];
        }

        return response()->json([
            'ujian'  => $ujian,
            'scores' => array_values($grouped_scores)
        ]);
    }

    /**
     * Get Prodis for a specific faculty
     */
    public function get_prodi_by_fakultas(Request $request)
    {
        $kode_fakultas = $request->kode_fakultas;
        if (!$kode_fakultas) {
            return response()->json(['error' => 'Parameter kode_fakultas wajib diisi'], 400);
        }

        $prodis = DB::table('akd_program_studi')
            ->where('kode_fakultas', $kode_fakultas)
            ->select('kode_program_studi', 'nama_program_studi', 'kode_jenjang_pendidikan')
            ->orderBy('kode_program_studi')
            ->get();

        return response()->json($prodis);
    }
}
