<table border="0" width='100%'>
    <tr>
        <th colspan="11" style="text-align: center; height: 40px; font-weight: bold; font-size: 14px;">Template Import Jadwal Ujian</th>
    </tr>
    <tr>
        <th colspan="2">Prodi:</th>
        <th colspan="4">{{ $nama_prodi }}</th>
        <th colspan="2">Tahun/Semester:</th>
        <th colspan="3">{{ $tahun }} / {{ $semester }}</th>
    </tr>
    <tr>
        <th></th>
    </tr>
    <tr>
        <th colspan="11" style="color: darkred; font-weight: bold;">* PETUNJUK PENGISIAN JADWAL UJIAN:</th>
    </tr>
    <tr>
        <th colspan="11" style="color: darkblue;">1. Kolom "ID Tawar" dan "ID Kelas" jangan diubah atau dihapus (diperlukan untuk update database).</th>
    </tr>
    <tr>
        <th colspan="11" style="color: darkblue;">2. Kolom Hari Ujian diisi: Senin, Selasa, Rabu, Kamis, Jumat, Sabtu, atau Minggu.</th>
    </tr>
    <tr>
        <th colspan="11" style="color: darkblue;">3. Kolom Tanggal Ujian diisi format teks YYYY-MM-DD (Contoh: 2026-06-25).</th>
    </tr>
    <tr>
        <th colspan="11" style="color: darkblue;">4. Kolom Jam Mulai & Selesai diisi format HH:MM (Contoh: 08:00, 09:30).</th>
    </tr>
    <tr>
        <th colspan="11" style="color: darkblue;">5. Kolom Ruang Ujian diisi dengan kode/nama ruangan (Contoh: R301).</th>
    </tr>
    <tr>
        <th></th>
    </tr>
</table>

<table border="1" width='100%'>
    <thead>
        <tr style="background-color: #333; color: #fff; font-weight: bold; text-align: center;">
            <th>No</th>
            <th style="color: #ffffff;">ID Tawar</th>
            <th style="color: #ffffff;">ID Kelas</th>
            <th>Kode MK</th>
            <th>Nama MK</th>
            <th>Kelas</th>
            <th>Hari Ujian</th>
            <th>Tanggal Ujian</th>
            <th>Jam Mulai</th>
            <th>Jam Selesai</th>
            <th>Ruang Ujian</th>
        </tr>
    </thead>
    <tbody>
        @php $no = 1; @endphp
        @foreach ($data as $row)
            <tr>
                <td style="text-align: center;">{{ $no++ }}</td>
                <td style="color: #ffffff; background-color: #f9f9f9; text-align: center;">{{ $row->id_tawar }}</td>
                <td style="color: #ffffff; background-color: #f9f9f9; text-align: center;">{{ $row->id_kelas }}</td>
                <td>{{ $row->kode_matakuliah }}</td>
                <td>{{ $row->nama_matakuliah }}</td>
                <td style="text-align: center;">{{ $row->nama_kelas }}</td>
                <td style="background-color: #ffffe0;">{{ $row->ujian_hari }}</td>
                <td style="background-color: #ffffe0; text-align: center;">{{ $row->ujian_tanggal }}</td>
                <td style="background-color: #ffffe0; text-align: center;">{{ $row->ujian_jam_mulai ? date('H:i', strtotime($row->ujian_jam_mulai)) : '' }}</td>
                <td style="background-color: #ffffe0; text-align: center;">{{ $row->ujian_jam_selesai ? date('H:i', strtotime($row->ujian_jam_selesai)) : '' }}</td>
                <td style="background-color: #ffffe0;">{{ $row->ujian_kode_ruang }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
