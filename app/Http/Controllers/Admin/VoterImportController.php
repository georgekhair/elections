<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Imports\VotersImport;
use App\Imports\VoterStatusUpdateImport;
use App\Models\VoterImportRun;
use App\Services\CenterResolverService;
use App\Services\VoterImportPreviewService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class VoterImportController extends Controller
{
    public function index()
    {
        $runs = VoterImportRun::latest()->take(10)->get();

        return view('admin.voters.import', compact('runs'));
    }

    public function preview(Request $request, VoterImportPreviewService $previewService)
    {
        $request->validate([
            'file' => 'required|mimes:csv,xlsx,xls',
            'import_type' => 'required|in:safe,full,names_only',
        ]);

        $result = $previewService->preview($request->file('file'), auth()->id());

        if (isset($result['run']) && $result['run'] instanceof VoterImportRun) {
            $result['run']->update([
                'import_type' => $request->import_type,
            ]);

            $result['run']->refresh();
        }

        $result['selected_import_type'] = $request->import_type;

        return view('admin.voters.import-preview', $result);
    }

    public function confirm(VoterImportRun $run, CenterResolverService $centerResolver)
    {
        set_time_limit(0); // 🔥 يمنع timeout
        ini_set('memory_limit', '512M'); // اختياري

        $run->update([
            'status' => 'importing',
            'started_at' => now(),
        ]);

        $path = Storage::disk('local')->path($run->stored_path);

        Excel::import(
            new VotersImport($run, $centerResolver),
            $path
        );


        $run->refresh();

        $run->update([
            'status' => 'completed',
            'finished_at' => now(),
        ]);

        return redirect()
            ->route('admin.voters.import')
            ->with('success', 'تم استيراد الملف بنجاح');
    }

    public function errors(VoterImportRun $run)
    {
        $errors = $run->errors()->latest()->paginate(50);

        return view('admin.voters.import-errors', compact('run', 'errors'));
    }

    public function updateStatusPreview(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:csv,xlsx,xls',
            'import_type' => 'required|in:safe,full,names_only',
        ]);

        $storedPath = $request->file('file')->store('imports', 'local');

        $run = VoterImportRun::create([
            'original_filename' => $request->file('file')->getClientOriginalName(),
            'stored_path' => $storedPath,
            'status' => 'previewed',
            'import_type' => $request->import_type,
            'created_by' => auth()->id(),
        ]);

        return view('admin.voters.import-status-preview', compact('run'));
    }

    public function confirmStatusUpdate(VoterImportRun $run)
    {
        $run->update([
            'status' => 'importing_status_update',
            'started_at' => now(),
        ]);

        $path = Storage::disk('local')->path($run->stored_path);

        Excel::import(
            new VoterStatusUpdateImport($run),
            $path
        );

        $run->refresh();

        $run->update([
            'status' => 'completed_status_update',
            'finished_at' => now(),
        ]);

        return redirect()
            ->route('admin.voters.import')
            ->with('success', 'تم تحديث حالات الناخبين بنجاح');
    }

    public function statusPreview(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:csv,xlsx,xls',
        ]);

        $storedPath = $request->file('file')->store('imports', 'local');

        $run = VoterImportRun::create([
            'original_filename' => $request->file('file')->getClientOriginalName(),
            'stored_path' => $storedPath,
            'status' => 'previewed_status_update',
            'import_type' => 'status_only',
            'created_by' => auth()->id(),
        ]);

        $path = Storage::disk('local')->path($storedPath);
        $rows = Excel::toArray([], $path)[0] ?? [];

        if (empty($rows)) {
            return view('admin.voters.import-status-preview', [
                'run' => $run,
                'headers' => [],
                'preview_rows' => [],
                'summary' => [
                    'total_rows' => 0,
                    'valid_rows' => 0,
                    'error_rows' => 0,
                ],
            ]);
        }

        $headers = array_map(fn($h) => trim((string) $h), $rows[0]);
        $dataRows = array_slice($rows, 1);

        $previewRows = [];
        $validRows = 0;
        $errorRows = 0;

        foreach ($dataRows as $index => $row) {
            $rowNumber = $index + 2;

            $mapped = [];
            foreach ($headers as $i => $header) {
                $mapped[$header] = $row[$i] ?? null;
            }

            $errors = [];

            if (blank($mapped['national_id'] ?? null)) {
                $errors[] = 'رقم الهوية مطلوب';
            }

            if (!array_key_exists('support_status', $mapped)) {
                $errors[] = 'حقل support_status غير موجود';
            }

            if ($errors) {
                $errorRows++;
            } else {
                $validRows++;
            }

            if (count($previewRows) < 25) {
                $previewRows[] = [
                    'row_number' => $rowNumber,
                    'data' => $mapped,
                    'errors' => $errors,
                ];
            }
        }

        $run->update([
            'total_rows' => count($dataRows),
            'valid_rows' => $validRows,
            'error_rows' => $errorRows,
        ]);

        return view('admin.voters.import-status-preview', [
            'run' => $run,
            'headers' => $headers,
            'preview_rows' => $previewRows,
            'summary' => [
                'total_rows' => count($dataRows),
                'valid_rows' => $validRows,
                'error_rows' => $errorRows,
            ],
        ]);
    }
}
