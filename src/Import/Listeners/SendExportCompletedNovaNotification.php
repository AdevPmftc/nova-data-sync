<?php

namespace AdevPmftc\NovaDataSync\Import\Listeners;

use AdevPmftc\NovaDataSync\Import\Events\ExportCompletedEvent;
use Laravel\Nova\Notifications\NovaNotification;

class SendExportCompletedNovaNotification
{
    public function handle(ExportCompletedEvent $event): void
    {
        if (!$event->export->user) {
            return;
        }

        $event->export->user->notify(
            NovaNotification::make()
                ->message('Your export has completed.')
                ->icon('check-circle')
                ->type('success')
        );
    }
}