<table border="0" width='100%'>
    <tr>
        <th colspan="5" style="text-align: center;height: 50px;font-weight: bold ">Template Input Nilai KHS</th>
    </tr>
    <tr>
        <th>Fakultas: </th>
        <th>{{ $data_dep->nama_fakultas }}</th>
        <th>TA: </th>
        <th>{{ $data_dep->tahun_akademik }}</th>
    </tr>
    <tr>
        <th>Prodi: </th>
        <th>{{ $data_dep->nama_program_studi }}</th>
        <th>Mata Kuliah: </th>
        <th>{{ $data_dep->nama_matakuliah }}</th>
    </tr>
    <tr>
        <th>Dosen: </th>
        <th>{{ $data_dep->fullname }}</th>
        <th>Kelas: </th>
        <th>{{ $data_dep->nama_kelas }}</th>
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
            <th><b>NIM</b></th>
            <th><b>Nama Mahasiswa</b></th>
            <th><b>Nama Matakuliah</b></th>
            <th align="center"><b>Kehadiran Kuliah</b></th>
            <th align="center"><b>Tugas</b></th>
            <th align="center"><b>Praktek</b></th>
            <th align="center"><b>Ujian Sisipan/Kuis</b></th>
            <th align="center"><b>Nilai UTS</b></th>
            <th align="center"><b>Nilai UAS</b></th>
            <th align="center"><b>Nilai Akhir Angka</b></th>
            <th align="center"><b>Nilai Akhir Huruf</b></th>
            <th style="color: white;">ID Detail KRS</th>
            <th style="color: white;">ID Matakuliah</th>
            <th style="color: white;">Tahun Kurikulum</th>
        </tr>
    </thead>
    <tbody>
        <?php $no = 0; ?>
        @foreach ($data as $row)
            <tr>
                <td>{{ $row->nim }}</td>
                <td>{{ $row->nama_mahasiswa }}</td>
                <td>{{ $row->nama_matakuliah }}</td>
                <td align="center"></td>
                <td align="center"></td>
                <td align="center"></td>
                <td align="center"></td>
                <td align="center"></td>
                <td align="center"></td>
                <td align="center"></td>
                <td align="center"></td>
                <td style="color: white;">{{ $row->id_detail_krs }}</td>
                <td style="color: white;">{{ $row->id_matakuliah }}</td>
                <td style="color: white;">{{ $row->tahun_kurikulum }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
