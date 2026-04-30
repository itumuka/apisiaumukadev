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
        <th>Jml. Pertemuan: </th>
        <th></th>
        <th>Kode Nilai: </th>
        <th></th>
    </tr>
</table>

<table class="table table-bordered mb-0" width='100%'>
    <thead>
        <tr>
            <th rowspan="2" align="center" valign="center"><b>NIM</b></th>
            <th rowspan="2" align="center" valign="center"><b>Nama Mahasiswa</b></th>
            <th rowspan="2" align="center" valign="center"><b>Nama Matakuliah</b></th>
            <th align="center"><b>Presensi</b></th>
            <th align="center"><b>UTS</b></th>
            <th align="center"><b>UAS</b></th>
            <th align="center"><b>Tugas 1</b></th>
            <th align="center"><b>Tugas 2</b></th>
            <th align="center"><b>Tugas 3</b></th>
            <th align="center"><b>Total</b></th>
            <th rowspan="2" align="center" valign="center"><b>Huruf</b></th>
            <th rowspan="2" align="center" valign="center" style="color: white;">ID Detail KRS</th>
            <th rowspan="2" align="center" valign="center" style="color: white;">ID Matakuliah</th>
            <th rowspan="2" align="center" valign="center" style="color: white;">Tahun Kurikulum</th>
        </tr>
        <tr>
            <th align="center">{{ number_format(0,2) }}</th>
            <th align="center">{{ number_format(0,2) }}</th>
            <th align="center">{{ number_format(0,2) }}</th>
            <th align="center">{{ number_format(0,2) }}</th>
            <th align="center">{{ number_format(0,2) }}</th>
            <th align="center">{{ number_format(0,2) }}</th>
            <th align="center">{{ '=IF(SUM(D8:I8)>1,"Salah",IF(SUM(D8:I8)<0.01,"0%",TEXT(SUM(D8:I8),"0%")))' }}</th>
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
