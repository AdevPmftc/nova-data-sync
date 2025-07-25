<?php

namespace Appwrd\NovaDataSync\Import\Actions;

use Appwrd\NovaDataSync\Enum\Status;
use Appwrd\NovaDataSync\Import\Jobs\BulkImportProcessor;
use Appwrd\NovaDataSync\Import\Jobs\ImportProcessor;
use Appwrd\NovaDataSync\Import\Models\Import;
use Illuminate\Contracts\Auth\Authenticatable;
use InvalidArgumentException;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileDoesNotExist;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig;
use Spatie\SimpleExcel\SimpleExcelReader;
use Illuminate\Support\Facades\Log;

class ImportAction
{
    /**
     * @throws FileDoesNotExist
     * @throws FileIsTooBig
     * @throws InvalidArgumentException
     */
    public static function make(string $processor, string $filepath, ?Authenticatable $user = null): Import
    {
        // Ensure the file exists
        if (!file_exists($filepath)) {
            throw new InvalidArgumentException("File does not exist at path: {$filepath}");
        }

        $excelReader = SimpleExcelReader::create($filepath, 'csv');

        // Validate that the processor is a subclass of ImportProcessor
        if (!is_subclass_of($processor, ImportProcessor::class) && $processor !== ImportProcessor::class) {
            throw new InvalidArgumentException('Class name must be a subclass of ' . ImportProcessor::class);
        }

        // Validate that file headers match the expected headers
        if (static::checkHeaders($processor::expectedHeaders(), $excelReader->getHeaders()) === false) {
            throw new InvalidArgumentException('File headers do not match the expected headers.');
        }

        // Additional validation: Ensure the file contains at least one data row
        $rows = $excelReader->getRows();
        if ($rows->count() === 0) {
            throw new InvalidArgumentException('The file only contains headers and no data rows.');
        }

        // Create the import model
        $import = Import::query()->create([
            'user_id' => $user?->id ?? null,
            'user_type' => !empty($user) ? get_class($user) : null,
            'filename' => basename($filepath),
            'status' => Status::PENDING,
            'processor' => $processor,
            'file_total_rows' => $rows->count(),
        ]);

        // Attach the file as media
        $import->addMedia($filepath)->toMediaCollection('file');

        // Dispatch the bulk import job with error handling
        try {
            dispatch(new BulkImportProcessor($import));
        } catch (\Throwable $e) {
            Log::error("Failed to dispatch BulkImportProcessor job for Import ID {$import->id}: {$e->getMessage()}");

            // Optionally update status to FAILED
            $import->update(['status' => Status::FAILED]);
            throw $e;
        }

        return $import;
    }

    /**
     * Optional setter if you need to use an instance of ImportAction
     */
    public function setUser(Authenticatable $user): self
    {
        $this->user = $user;
        return $this;
    }

    /**
     * Validate that the file headers match the expected headers
     */
    public static function checkHeaders(array $expectedHeaders, array $headers): bool
    {
        // Ensure all expected headers are present in the file
        foreach ($expectedHeaders as $expectedHeader) {
            if (!in_array($expectedHeader, $headers)) {
                return false;
            }
        }

        // Ensure no expected header is missing
        return count(array_diff($expectedHeaders, $headers)) === 0;
    }
}