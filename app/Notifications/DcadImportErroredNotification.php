<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\SlackAttachment;
use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Notifications\Notification;

class DcadImportErroredNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $errorMessage = 'unknown',
        private readonly int $errorCode = 0
    ) {}

    public function via($notifiable): array
    {
        return ['slack'];
    }

    public function toSlack(): SlackMessage
    {
        return (new SlackMessage)
            ->error()
            ->content('DCAD Import Error')
            ->attachment(function (SlackAttachment $attachment) {
                $attachment->title('Error Details')
                    ->content(
                        'Message: ' . $this->errorMessage . "\n" .
                        'Error Code: ' . $this->errorCode
                    );
            });
    }
}
