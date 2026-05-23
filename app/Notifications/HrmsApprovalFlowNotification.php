<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class HrmsApprovalFlowNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly array $payload
    ) {
    }

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if (! empty($notifiable->email)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage())
            ->subject($this->payload['title'] ?? 'HRMS Notification')
            ->greeting('Hello ' . ($notifiable->name ?? 'User') . ',')
            ->line($this->payload['message'] ?? 'You have a new HRMS update.');

        if (! empty($this->payload['detail'])) {
            $mail->line($this->payload['detail']);
        }

        if (! empty($this->payload['action_url'])) {
            $mail->action($this->payload['action_label'] ?? 'View Details', $this->payload['action_url']);
        }

        return $mail->line('Thank you.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->payload['title'] ?? 'HRMS Notification',
            'message' => $this->payload['message'] ?? 'You have a new HRMS update.',
            'detail' => $this->payload['detail'] ?? null,
            'action_url' => $this->payload['action_url'] ?? null,
            'action_label' => $this->payload['action_label'] ?? 'View Details',
            'request_type' => $this->payload['request_type'] ?? null,
            'event_type' => $this->payload['event_type'] ?? null,
            'request_id' => $this->payload['request_id'] ?? null,
            'actor_name' => $this->payload['actor_name'] ?? null,
            'requester_name' => $this->payload['requester_name'] ?? null,
            'status' => $this->payload['status'] ?? null,
            'created_at_human' => now()->diffForHumans(),
        ];
    }
}
