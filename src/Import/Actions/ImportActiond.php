<?php

namespace AdevPmftc\NovaDataSync\Import\Actions;

use AdevPmftc\NovaDataSync\Enum\Status;
use AdevPmftc\NovaDataSync\Import\Jobs\ImportProcessor;
use AdevPmftc\NovaDataSync\Import\Models\Import;
use Illuminate\Contracts\Auth\Authenticatable;
use InvalidArgumentException;
use Spatie\SimpleExcel\SimpleExcelReader;

class ImportAction
{
    public static function make(string $processor, string $filepath, ?Authenticatable $user = null): Import
    {
        $excelReader = SimpleExcelReader::create($filepath, 'csv');

        if (!is_subclass_of($processor, ImportProcessor::class)) {
            throw new InvalidArgumentException('Processor harus extend ' . ImportProcessor::class);
        }

        $import = Import::create([
            'user_id' => $user?->id,
            'filename' => basename($filepath),
            'status' => Status::PENDING,
            'processor' => $processor,
            'file_total_rows' => $excelReader->getRows()->count(),
        ]);

        $import->addMedia($filepath)->toMediaCollection('imports');

        dispatch(new $processor($import, $filepath));

        return $import;
    }
}