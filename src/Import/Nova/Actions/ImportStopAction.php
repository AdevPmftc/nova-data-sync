<?php
namespace AdevPmftc\NovaDataSync\Import\Nova\Actions;

use AdevPmftc\NovaDataSync\Enum\Status;
use AdevPmftc\NovaDataSync\Import\Models\Import;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Bus\BatchRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Actions\ActionResponse;
use Laravel\Nova\Fields\ActionFields;
use Illuminate\Support\Facades\DB;

class ImportStopAction extends Action
{
    public $onlyOnDetail = true;

    public function name(): string
    {
        return 'Stop Import';
    }

    public function handle(ActionFields $fields, Collection $models): ActionResponse
    {
        /** @var Export $import */
        $import = $models->first();

        $batch = Bus::findBatch($import->batch_id);


        if ($batch) {
            $batch->cancel();
            $message = "Stopped batch {$import->batch_id}";
            $import->update([
                    'status' => Status::STOPPED->value,
            ]);
            Log::info("Stopped batch {$import->batch_id}", []);
            $shell_run = shell_exec(base_path('kill-job.sh'));
        } else {
            $success = false;
            $message = "Batch {$import->batch_id} not found";
            Log::warning("Batch not found", []);
        }

        return Action::message('Attempt to stop the export started..');
    }
}
