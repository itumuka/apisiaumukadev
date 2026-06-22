<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; //untuk raw DB
use Illuminate\Support\Facades\Session; //untuk raw DB
use Illuminate\Support\Facades\Validator;
use App\Imports\PenawaranMakulImport;
use App\Imports\JadwalUjianImport;
use App\Exports\JadwalUjianTemplateExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Response;

class AkademikTools extends Controller
{
    public function import_makul_penawaran(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'fileimport' => 'required|mimes:xls,xlsx'
        ]);
    
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 400);
        }
    
        if ($request->hasFile('fileimport')) {
            try {
                $file = $request->file('fileimport');
                $import = new PenawaranMakulImport();
                Excel::import($import, $file);
    
                if (!empty($import->getFailures())) {
                    $failures = $import->getFailures();
                    $failuresPath = 'failures/failures_' . now()->timestamp . '.json';
                    Storage::disk('public')->put($failuresPath, json_encode($failures));
    
                    return response()->json([
                        'success' => 'Data imported with some errors.',
                        'failures_url' => Storage::disk('public')->url($failuresPath)
                    ], 207);
                }
    
                return response()->json(['success' => 'Data successfully imported!'], 200);
    
            } catch (\Exception $e) {
                return response()->json(['error' => 'Error during import: ' . $e->getMessage()], 500);
            }
        }
    
        return response()->json(['error' => 'Please choose a file before importing'], 400);
    }

    public function export_template_jadwalujian(Request $request)
    {
        $request->validate([
            'tahun' => 'required',
            'semester' => 'required',
            'nama_program_studi' => 'required',
        ]);

        $tahun = $request->tahun;
        $semester = $request->semester;
        $nama_program_studi = $request->nama_program_studi;

        // Clean filename from spaces/slashes
        $safeName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $nama_program_studi);
        $filename = "Template_Jadwal_Ujian_{$safeName}_{$tahun}_{$semester}.xlsx";

        return (new JadwalUjianTemplateExport($tahun, $semester, $nama_program_studi))->download($filename);
    }

    public function import_jadwalujian(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'fileimport' => 'required|mimes:xls,xlsx'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'messages' => $validator->errors()->all()
            ], 400);
        }

        if ($request->hasFile('fileimport')) {
            try {
                $file = $request->file('fileimport');
                $import = new JadwalUjianImport();
                Excel::import($import, $file);

                $failures = $import->getFailures();
                $successCount = $import->getSuccessCount();

                if (!empty($failures)) {
                    return response()->json([
                        'status' => 'error',
                        'message' => "Berhasil memproses $successCount baris, namun terdapat beberapa kesalahan.",
                        'messages' => $failures
                    ], 200);
                }

                return response()->json([
                    'status' => 'success',
                    'message' => "Seluruh data ($successCount baris) jadwal ujian berhasil di-import!"
                ], 200);

            } catch (\Exception $e) {
                return response()->json([
                    'status' => 'error',
                    'messages' => ['Terjadi kesalahan saat menguraikan file: ' . $e->getMessage()]
                ], 500);
            }
        }

        return response()->json([
            'status' => 'error',
            'messages' => ['File Excel wajib dipilih sebelum melakukan upload.']
        ], 400);
    }
}