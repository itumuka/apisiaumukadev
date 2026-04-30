<table border="0" width='100%'>
  <tr>
    <th colspan="7" style="text-align: center;height: 50px;font-weight: bold ">Template Import Presensi</th>
  </tr>
  <tr>
    <th colspan="2">Fakultas: </th>
    <th >{{$data_dep->nama_fakultas}}</th>
    <th colspan="2">TA: </th>
    <th >{{$data_dep->tahun_akademik}}</th>
  </tr>
  <tr>
    <th colspan="2">Prodi: </th>
    <th >{{$data_dep->nama_program_studi}}</th>
    <th colspan="2">Mata Kuliah: </th>
    <th >{{$data_dep->nama_matakuliah}}</th>
  </tr>
  <tr>
    <th></th>
    <th></th>
    <th></th>
    <th></th>
  </tr>
  <tr>
    <th colspan="7" style="color: darkorange">*Petunjuk Pengisian Kolom Presensi menggunakan H/I/S/A</th>
  </tr>
  <tr>
    <th colspan="7" style="color: darkorange">*Ket. H = Hadir, I = Ijin,  S = Sakit, A = Alpha</th>
  </tr>
</table>

<table class="table table-bordered mb-0" width='100%'>
  <thead>
    <tr>
      <th>No</th>
      <th>NIM</th>
      <th>Nama Mahasiswa</th>
      <th>Presensi</th>                    
      {{-- <th style="color: white;">ID Kelas</th>                    --}}
    </tr>
  </thead>
  <tbody>
    <?php $no = 1; ?>
      @foreach ($data as $row)
      <tr>
        <td>{{ $no++ }}</td>
        <td>{{ $row->nim }}</td>
        <td>{{ $row->nama_mahasiswa }}</td>
        <td></td>
        {{-- <td style="color: white;">{{ $row->id_kelas }}</td> --}}
      </tr>
      @endforeach
  </tbody>
</table>