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
        return (new MailMessage())
            ->subject($this->payload['title'] ?? 'HRMS Notification')
            ->from(config('mail.from.address'), config('mail.from.name', 'Myagenci'))
            ->view('emails.hrms.approval-flow', [
                'payload' => $this->payload,
                'notifiable' => $notifiable,
            ]);
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
            'previous_approvals' => $this->payload['previous_approvals'] ?? null,
            'status' => $this->payload['status'] ?? null,
            'branch_id' => $this->payload['branch_id'] ?? null,
            'created_at_human' => now()->diffForHumans(),
        ];
    }
}
