<table border="0" width='100%'>
  <tr>
    <th colspan="4" style="text-align: center;height: 50px;font-weight: bold ">Template Input Nilai UTS</th>
  </tr>
  <tr>
    <th >Fakultas: </th>
    <th >{{$data_dep->nama_fakultas}}</th>
    <th >TA: </th>
    <th >{{$data_dep->tahun_akademik}}</th>
  </tr>
  <tr>
    <th >Prodi: </th>
    <th >{{$data_dep->nama_program_studi}}</th>
    <th >Mata Kuliah: </th>
    <th >{{$data_dep->nama_matakuliah}}</th>
  </tr>
  <tr>
    <th >Dosen: </th>
    <th >{{$data_dep->fullname}}</th>
    <th >Kelas: </th>
    <th >{{$data_dep->nama_kelas}}</th>
  </tr>
  <tr>
    <th></th>
    <th></th>
    <th></th>
    <th></th>
  </tr>
</table>

<table class="table table-bordered mb-0" width='100%'>
  <thead>
    <tr>
      <th>NIM</th>
      <th>Nama Mahasiswa</th>
      <th>Nama Matakuliah</th>
      <th>Nilai UTS (Angka)</th>                      
      <th style="color: white;">ID Detail KRS</th>                    
    </tr>
  </thead>
  <tbody>
    <?php $no = 0; ?>
      @foreach ($data as $row)
      <tr>
        <td>{{ $row->nim }}</td>
        <td>{{ $row->nama_mahasiswa }}</td>
        <td>{{ $row->nama_matakuliah }}</td>
        <td></td>
        <td style="color: white;">{{ $row->id_detail_krs }}</td>
      </tr>
      @endforeach
  </tbody>
</table>