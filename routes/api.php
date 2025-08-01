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
Route::get('/export-ongoing/{userId}', function ($userId) {
    return \Appwrd\NovaDataSync\Export\Models\Export::query()
        ->where('user_id', $userId)
        ->where('status', 'Processing') // atau status != 'Completed'
        ->pluck('id');
});
