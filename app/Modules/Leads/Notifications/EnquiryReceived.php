<?php

namespace App\Modules\Leads\Notifications;

use App\Models\User;
use App\Modules\Leads\Enums\EnquiryStatus;
use App\Modules\Leads\Models\Enquiry;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class EnquiryReceived extends Notification implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Enquiry $enquiry) {}

    /**
     * @return Collection<int, User>
     */
    public static function recipients(): Collection
    {
        return User::query()
            ->whereHas('roles', fn (Builder $query): Builder => $query
                ->whereHas('permissions', fn (Builder $query): Builder => $query->where('name', 'enquiry.view')))
            ->orWhereHas('permissions', fn (Builder $query): Builder => $query->where('name', 'enquiry.view'))
            ->get();
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $subject = $this->enquiry->vehicle_id === null
            ? 'New website enquiry'
            : 'New enquiry about '.$this->enquiry->vehicle->listingTitle();

        $mail = (new MailMessage)
            ->subject($subject)
            ->greeting('Hello '.$notifiable->name.'!')
            ->line('From '.$this->enquiry->name.' <'.$this->enquiry->email.'>'.($this->enquiry->phone ? ' · '.$this->enquiry->phone : ''))
            ->line($this->enquiry->message)
            ->action('Open enquiries', url('/admin/enquiries'));

        if ($this->enquiry->vehicle_id !== null) {
            $mail->line('Listing: '.route('vehicles.show', $this->enquiry->vehicle));
        }

        if ($this->enquiry->status !== EnquiryStatus::New) {
            $mail->line('Status: '.$this->enquiry->status->label());
        }

        return $mail;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'enquiry_id' => $this->enquiry->getKey(),
            'vehicle_id' => $this->enquiry->vehicle_id,
        ];
    }
}
