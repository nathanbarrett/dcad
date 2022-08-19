<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\SlackAttachment;
use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Notifications\Notification;

class DcadImportSuccessfulNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly int $propertiesCreated,
        private readonly int $ownershipsUpdated
    ) {}

    public function via($notifiable): array
    {
        return ['slack'];
    }

    public function toSlack(): SlackMessage
    {
        return (new SlackMessage)
            ->success()
            ->content('DCAD Import Success!')
            ->attachment(function (SlackAttachment $attachment) {
                $attachment->title('Import Details')
                    ->content(
                        'Properties Created: ' . number_format($this->propertiesCreated) . "\n" .
                                'Ownerships Updated: ' . number_format($this->ownershipsUpdated)
                    );
            });
    }
}
