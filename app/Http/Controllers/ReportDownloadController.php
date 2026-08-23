<?php

namespace App\Http\Controllers;

use App\Models\Report;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportDownloadController extends Controller
{
    use AuthorizesRequests;

    public function download(Report $report): StreamedResponse
    {
        $this->authorize('view', $report);

        abort_unless(Storage::disk('local')->exists($report->file_path), 404);

        return Storage::disk('local')->download($report->file_path, $report->file_name);
    }
}
