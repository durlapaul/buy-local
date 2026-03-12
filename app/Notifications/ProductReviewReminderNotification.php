<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProductReviewReminderNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly Order $order) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $base = config('app.frontend_url', env('FRONTEND_URL'));

        return (new MailMessage)
            ->subject('How was your order?')
            ->line("Your order #{$this->order->order_number} has been delivered!")
            ->line('You have 5 days to rate the products you received.')
            ->action('Leave a Review', "{$base}/orders/{$this->order->id}/review")
            ->line('If you do not leave a review, we will automatically give it 5 stars.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'order_id'     => $this->order->id,
            'order_number' => $this->order->order_number,
            'message'      => "Rate the products from order #{$this->order->order_number}",
            'action_url'   => "/orders/{$this->order->id}/review",
        ];
    }
}
