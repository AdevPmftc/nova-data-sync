<?php

namespace AdevPmftc\NovaDataSync\Import\Events;

use AdevPmftc\NovaDataSync\Export\Models\Export;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ExportCompletedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(public Export $export)
    {
        //
    }
}
