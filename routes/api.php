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
Route::get('/export-status/{userId}', function ($userId) {
    $latestExport = \Appwrd\NovaDataSync\Export\Models\Export::where('user_id', $userId)
        ->latest()
        ->first();

    $isRecent = false;
    if ($latestExport) {
        $updatedAt = Carbon::parse($latestExport->updated_at);
        $isRecent = $updatedAt->gt(now()->subMinutes(1));
    }

    $cacheKey = 'export_alert_shown_for_user_' . $userId . '_export_' . $latestExport->id;

    if (request()->has('clear')) {
        Cache::forget($cacheKey);
        return response()->json(['cleared' => true]);
    }

    $shouldShowAlert = false;

    if ($latestExport?->status === 'Completed' && $isRecent && !Cache::has($cacheKey)) {
        Cache::put($cacheKey, true, now()->addMinutes(5));
        $shouldShowAlert = true;
    }

    // return response()->json(['done' => $shouldShowAlert]);
    return response()->json([
        'done' => $shouldShowAlert,
        'export_id' => $latestExport?->id,
        'filename' => $latestExport?->filename,
    ]);
});
