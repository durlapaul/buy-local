<?php

namespace App\Mail;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class FavouriteProductMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Product $product,
        public readonly string $changeType,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Update on your favourite: {$this->product->name}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.favourite-product-updated',
            with: [
                'productUrl' => $this->productUrl(),
            ]
        );
    }

    public function attachments(): array
    {
        return [];
    }

    private function productUrl(): string
    {
        $base = config('app.frontend_url', env('FRONTEND_URL'));
        return "{$base}/products/{$this->product->id}";
    }
}