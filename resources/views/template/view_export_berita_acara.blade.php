<style>
table th b {
  display: inline-block;
  width: 50%;
  position: relative;
  padding-right: 10px; /* Ensures colon does not overlay the text */
}

table th b::after {
  content: ":";
  position: absolute;
  right: 10px;
}
</style>

<table class="table table-bordered mb-0" width='100%'>
  <tr>
    <th colspan="13" style="text-align: center;">UNIVERSITAS PROKLAMASI 45 YOGYAKARTA</th>
  </tr>
  <tr>
    <th colspan="13" style="text-align: center;">Fakultas {{$data_dep->nama_fakultas}}</th>
  </tr>
  <tr>
    <th colspan="13"  style="font-size:10pt;text-align: center;">Jl. Proklamasi No. 1, Babarsari, Yogyakarta 55281. Telp : (0274) 485517, 485535. Fax : (0274) 486008</th>
  </tr>
  <tr>
    <th colspan="13" ></th>
  </tr>
  <tr>
    <th colspan="13" ></th>
  </tr>
  <tr>
    <th colspan="13" style="text-align: center;font-size: 14pt;font-weight: bold ">BERITA ACARA</th>
  </tr>
  <tr>
    <th colspan="13" style="text-align: center;font-size: 10pt;font-weight: bold ">{{$data_dep->tahun_akademik}}</th>
  </tr>
</table>

<table class="table table-bordered mb-0" width="100%">
  <tr>
    <th style="text-align: left;">Mata Kuliah : </th>
    <th colspan="5"> {{ $data_dep->nama_matakuliah }} </th>
    <th></th>
    <th style="text-align: left;">KodeMK/SKS/Kelas :</th>
    <th colspan="5"> {{ $data_dep->kode_matakuliah }} / {{ $data_dep->sks_matakuliah }} sks / {{ $data_dep->nama_kelas }}</th>
  </tr>
  <tr>
    <th style="text-align: left;">Dosen : </th>
    <th colspan="5">{{ $data_dep->dosen}}</th>
    <th></th>
    <th style="text-align: left;">HARI/JAM/RUANG : </th>
    <th colspan="5"> {{ $data_dep->hari }} / {{$data_dep->jam_mulai}} - {{$data_dep->jam_selesai}} / {{$data_dep->kode_ruang}}</th>
  </tr>
</table>

<table class="table table-bordered mb-0" width='100%'>
  <thead>
    <tr>
      <th colspan="2" style="text-align:center;">Pertemuan</th>
      <th colspan="5" style="text-align:center;">(Diisi Oleh Dosen)</th>
      <th colspan="6" style="text-align:center;">(Diisi oleh pejabat yang ditunjuk)</th>
  </tr>
  <tr>
      <th rowspan="2" style="text-align:center; position: absolute;
      top: 50%; transform: translateY(-50%);">Tanggal</th>
      <th rowspan="2" style="text-align:center;">Ke</th>
      <th rowspan="2" style="text-align:center;">Materi Perkuliahan / Praktikum</th>
      <th rowspan="2" style="text-align:center;">Jumlah Hadir Mhs</th>
      <th colspan="2" style="text-align:center;">Alokasi Waktu</th>
      <th rowspan="2" style="text-align:center;">Tanda Tangan Dosen</th>
      <th colspan="3" style="text-align:center;">Kesesuaian materi dengan SAP</th>
      <th rowspan="2" style="text-align:center;">Tanda Tangan Pemeriksa</th>
      <th rowspan="2" style="text-align:center;">Catatan</th>
      <th rowspan="2" style="text-align:center;">Paraf petugas presensi</th>
  </tr>
  <tr>
      <th style="text-align:center;">Mulai</th>
      <th style="text-align:center;">Selesai</th>
      <th style="text-align:center;">Materi dan Waktu Tepat</th>
      <th style="text-align:center;">Materi tepat dan waktu tidak tepat</th>
      <th style="text-align:center;">Materi tidak tepat dan waktu tepat</th>
  </tr>
  </thead>
  <tbody>
    <?php $no = 0; ?>
      @foreach ($data as $row)
      <tr style="font-size:12pt;">
        <td style="text-align:center;">{{$row->tgl}}</td>
        <td style="text-align:center;"> {{$row->pertemuan_ke}}</td>
        <td style="text-align:center;"> {{$row->materi_makul}}</td>
        <td style="text-align:center;"> {{$row->peserta_hadir }}</td>
        <td style="text-align:center;"> {{$row->jam_mulai }}</td>
        <td style="text-align:center;"> {{$row->jam_selesai}}</td>
        <td style="text-align:center;"></td>
        <td style="text-align:center;"></td>
        <td style="text-align:center;"></td>
        <td style="text-align:center;"></td>
        <td style="text-align:center;"></td>
        <td style="text-align:center;"></td>
        <td style="text-align:center;"></td>
      </tr>
      @endforeach
  </tbody>
  <tfoot>
    <tr>
      <th colspan="13"></th>
    </tr>
    <tr>
      <th colspan="13"></th>
    </tr>
    <tr>
      <th colspan="9"></th>
      <th colspan="4">Yogyakarta, {{$data_dep->tglindo}}</th>
    </tr>
    <tr>
      <th colspan="9"></th>
      <th colspan="4"></th>
    </tr>
    <tr>
      <th colspan="9"></th>
      <th colspan="4">(Nurul Fatimah Juliani, S.Pd)</th>
    </tr>
  </tfoot>
</table>