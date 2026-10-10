<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NewsletterSubscriber extends Model
{
    protected $fillable = ['email', 'user_id', 'first_name', 'interests', 'status', 'confirm_token', 'opt_in_ip_hash', 'opt_in_at',
        'confirmed_at', 'confirmed_ip_hash', 'unsubscribed_at', 'source'];

    protected $casts = ['interests' => 'array', 'opt_in_at' => 'datetime', 'confirmed_at' => 'datetime', 'unsubscribed_at' => 'datetime'];

    public function unsubscribeUrl(): string
    {
        return \URL::signedRoute('newsletter.unsubscribe', ['subscriber' => $this->id]);
    }
}
