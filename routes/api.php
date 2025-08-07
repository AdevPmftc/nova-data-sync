<?php

use AdevPmftc\NovaDataSync\Import\Http\Controllers\ImportSampleController;
use AdevPmftc\NovaDataSync\Export\Models\Export;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Carbon;

/*
|--------------------------------------------------------------------------
| Tool API Routes
|--------------------------------------------------------------------------
|
| Here is where you may register API routes for your tool. These routes
| are loaded by the ServiceProvider of your tool. They are protected
| by your tool's "Authorize" middleware by default. Now, go build!
|
*/

Route::get('/imports/sample', ImportSampleController::class);
Route::get('/export-alerts/{userId}', function ($userId) {
    $shouldMark = request()->boolean('mark');

    // Ambil dari DB: export yang Completed dan belum ditampilkan
    $exports = \AdevPmftc\NovaDataSync\Export\Models\Export::query()
        ->where('user_id', $userId)
        ->where('status', 'Completed')
        ->where('updated_at', '>=', now()->subHour())
        ->orderByDesc('id')
        ->get()
        ->filter(fn ($export) => !Cache::has("export_alert_shown_{$export->id}"));

    // Ambil dari cache: export kosong yang sudah dihapus
    $emptyExport = cache()->get("export_alert_user_{$userId}");

    // Mark as shown
    if ($shouldMark) {
        foreach ($exports as $export) {
            cache()->put("export_alert_shown_{$export->id}", true, now()->addHours(1));
        }

        if ($emptyExport) {
            cache()->forget("export_alert_user_{$userId}");
        }
    }

    // Siapkan response
    $response = $exports->map(fn ($export) => [
        'id' => $export->id,
        'filename' => $export->filename,
    ])->values();

    if ($emptyExport) {
        $response->push([
            'id' => null,
            'filename' => $emptyExport['filename'] ?? null,
        ]);
    }

    return $response;
});
