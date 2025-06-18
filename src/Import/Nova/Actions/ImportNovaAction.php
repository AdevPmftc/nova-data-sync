<?php
namespace AdevPmftc\NovaDataSync\Import\Nova\Actions;

use AdevPmftc\NovaDataSync\Import\Actions\ImportAction;
use AdevPmftc\NovaDataSync\Import\Jobs\ImportProcessor;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Http\UploadedFile;
use Illuminate\Queue\InteractsWithQueue;
use InvalidArgumentException;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Actions\ActionResponse;
use Laravel\Nova\Fields\ActionFields;
use Laravel\Nova\Fields\File;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Nova;

abstract class ImportNovaAction extends Action
{
    use InteractsWithQueue, Queueable;

    public $onlyOnIndex = true;
    public $standalone = true;
    public $withoutConfirmation = false;

    public string $processor;

    public function __construct()
    {
        $this->onQueue(config('nova-data-sync.imports.queue', 'default'));

        if (!is_subclass_of($this->processor, ImportProcessor::class) && $this->processor !== ImportProcessor::class) {
            throw new InvalidArgumentException('Class name must be a subclass of ' . ImportProcessor::class);
        }
    }

    public function helpText(): string
    {
        $params = http_build_query(['class' => $this->processor]);
        $url = url('nova-vendor/nova-data-sync/imports/sample?' . $params);
        return '<a href="' . $url . '">Download sample file</a>';
    }

    public function handle(ActionFields $fields): ActionResponse|Action
    {
        $file = $fields->get('file');

        try {
            $import = ImportAction::make($this->processor, $file->path(), request()->user());
        } catch (Exception $e) {
            return Action::danger($e->getMessage());
        }

        return Action::redirect(url(Nova::path() . '/resources/imports/' . $import->id));
    }

    public function fields(NovaRequest $request): array
    {
        return [
            File::make('File', 'file')
                ->rules(['required', 'mimes:txt,csv'])
                ->help($this->helpText()),
        ];
    }
}
