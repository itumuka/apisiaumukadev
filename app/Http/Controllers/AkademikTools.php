<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; //untuk raw DB
use Illuminate\Support\Facades\Session; //untuk raw DB
use Illuminate\Support\Facades\Validator;
use App\Imports\PenawaranMakulImport;
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

}