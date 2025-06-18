<?php
namespace AdevPmftc\NovaDataSync\Import\Nova;

use App\Nova\Resource;
use AdevPmftc\NovaDataSync\Enum\Status as StatusEnum;
use AdevPmftc\NovaDataSync\Import\Nova\Actions\ImportStopAction;
use Ebess\AdvancedNovaMediaLibrary\Fields\Files;
use Flatroy\FieldProgressbar\FieldProgressbar;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\MorphTo;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Status;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\NovaRequest;

class Import extends Resource
{
    public static string $model = \AdevPmftc\NovaDataSync\Import\Models\Import::class;
    public static $title = 'id';
    public static $search = ['id'];
    public static $displayInNavigation = false;

    public function fields(NovaRequest $request): array
    {
        $fields = [
            ID::make()->sortable(),

            Status::make('Status')
                ->loadingWhen([StatusEnum::PENDING->value, StatusEnum::IN_PROGRESS->value])
                ->failedWhen([
                    StatusEnum::STOPPING->value,
                    StatusEnum::STOPPED->value,
                    StatusEnum::FAILED->value,
                ]),

            MorphTo::make('Uploaded By', 'user')
                ->types(config('nova-data-sync.nova_resources.users'))
                ->sortable()
                ->readonly(),

            Text::make('Processor', 'processor_short_name'),
            Text::make('Filename', 'filename')->readonly(),

            Number::make('File Total Rows')->onlyOnDetail(),
            Number::make('Total Rows Processed')->onlyOnDetail(),

            FieldProgressbar::make('Progress', fn ($model) => 
                $model->file_total_rows
                    ? $model->total_rows_processed / $model->file_total_rows
                    : 0
            )->onlyOnDetail(),

            Number::make('Total Rows Failed')->onlyOnDetail(),
            DateTime::make('Created At')->sortable()->readonly(),
            DateTime::make('Started At')->onlyOnDetail()->readonly(),
            DateTime::make('Completed At')->onlyOnDetail()->readonly(),

            Text::make('Duration', function () {
                return $this->started_at && $this->completed_at
                    ? $this->started_at->diffForHumans($this->completed_at, true)
                    : null;
            })->onlyOnDetail(),
        ];

        if ($request->isResourceDetailRequest()) {
            $fields[] = $this->getFailedReportField($request->resourceId);
        }

        return $fields;
    }

    private function getFailedReportField($resourceId)
    {
        $field = Files::make('Failed Report', 'failed')
            ->onlyOnDetail()
            ->readonly();

        $import = \AdevPmftc\NovaDataSync\Import\Models\Import::find($resourceId);
        if (!$import || !$import->hasMedia('failed')) {
            return $field;
        }

        $disk = $import->getFirstMedia('failed')->disk;
        if (config("filesystems.disks.{$disk}.driver") === 's3') {
            $field->temporary(now()->addMinutes(10));
        }

        return $field;
    }

    public function cards(NovaRequest $request): array
    {
        return [];
    }

    public function filters(NovaRequest $request): array
    {
        return [];
    }

    public function lenses(NovaRequest $request): array
    {
        return [];
    }

    public function actions(NovaRequest $request): array
    {
        return [
            (new ImportStopAction())
                ->canRun(fn ($request, $model) =>
                    in_array($model->status, [StatusEnum::PENDING->value, StatusEnum::IN_PROGRESS->value])
                ),
        ];
    }

    public static function authorizedToCreate(Request $request): bool
    {
        return false;
    }

    public function authorizedToUpdate(Request $request): bool
    {
        return false;
    }

    public function authorizedToDelete(Request $request): bool
    {
        return in_array($this->status, [
            StatusEnum::COMPLETED->value,
            StatusEnum::STOPPED->value,
            StatusEnum::FAILED->value,
        ]);
    }

    public function authorizedToReplicate(Request $request): bool
    {
        return false;
    }
}
