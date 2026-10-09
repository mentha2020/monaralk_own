<?php

namespace App\Modules\Submissions\Notifications;

use App\Modules\Settings\Models\Setting;
use App\Modules\Submissions\Models\VehicleSubmission;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;

class SubmissionRejected extends Notification implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public VehicleSubmission $submission, public string $reason) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your listing submission was not approved')
            ->greeting('Hello '.$notifiable->name.'!')
            ->line('Thanks for submitting your car to '.Setting::get('site.name', 'Monaralk').'.')
            ->line('Unfortunately we cannot publish this submission as it is.')
            ->line('Reason: '.$this->reason)
            ->line('Reference: '.$this->submission->reference())
            ->action('Start a new submission', url('/submit'))
            ->line('Fix the details and send it again — every submission is reviewed by hand.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'submission_id' => $this->submission->getKey(),
            'reason' => $this->reason,
        ];
    }
}
