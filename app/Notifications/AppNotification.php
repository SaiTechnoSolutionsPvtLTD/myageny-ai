<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AppNotification extends Notification
{
    use Queueable;

    /**
     * notification_type values that should also send mail (in addition to
     * database), when the notifiable has an email. Kept intentionally short —
     * most CRM/Projects notifications are in-app only, unlike HRMS approval
     * flows which already email on every step.
     */
    private const MAIL_ENABLED_TYPES = [
        'production_approval_pending',
        'price_request_pending',
    ];

    public function __construct(
        private readonly array $payload
    ) {
    }

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        $type = $this->payload['notification_type'] ?? null;
        if ($type && in_array($type, self::MAIL_ENABLED_TYPES, true) && ! empty($notifiable->email)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject($this->payload['title'] ?? 'Notification')
            ->from(config('mail.from.address'), config('mail.from.name', 'Myagenci'))
            ->view('emails.app.generic-notification', [
                'payload' => $this->payload,
                'notifiable' => $notifiable,
            ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'module' => $this->payload['module'] ?? null,
            'notification_type' => $this->payload['notification_type'] ?? null,
            'priority' => $this->payload['priority'] ?? 'medium',
            'title' => $this->payload['title'] ?? 'Notification',
            'message' => $this->payload['message'] ?? '',
            'detail' => $this->payload['detail'] ?? null,
            'action_url' => $this->payload['action_url'] ?? null,
            'action_label' => $this->payload['action_label'] ?? 'View Details',
            'request_type' => $this->payload['request_type'] ?? null,
            'request_id' => $this->payload['request_id'] ?? null,
            'actor_name' => $this->payload['actor_name'] ?? null,
            'requester_name' => $this->payload['requester_name'] ?? null,
            'status' => $this->payload['status'] ?? null,
            'created_at_human' => now()->diffForHumans(),
        ];
    }
}