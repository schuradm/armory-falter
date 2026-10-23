<?php

namespace App\Livewire;

use App\Models\BlogPost;
use App\Models\Comment;
use App\Rules\Honeypot;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Locked;
use Livewire\Component;

/** Kommentare werden moderiert (Status pending) und erst nach Freigabe angezeigt. */
class CommentForm extends Component
{
    #[Locked]
    public int $postId;

    public ?int $parentId = null;

    public string $author = '';

    public string $body = '';

    public string $website = '';

    public bool $privacy = false;

    public bool $sent = false;

    public function mount(BlogPost $post): void
    {
        $this->postId = $post->id;
        $this->author = Auth::user()->first_name ?? '';
    }

    public function submit(): void
    {
        $this->validate([
            'author' => ['required', 'string', 'max:80'],
            'body' => ['required', 'string', 'min:5', 'max:3000'],
            'privacy' => ['accepted'],
            'website' => [new Honeypot],
        ]);
        if (! RateLimiter::attempt('comment:'.request()->ip(), 3, fn () => true, 600)) {
            $this->addError('body', 'Bitte warten Sie einen Moment.');

            return;
        }
        $links = preg_match_all('#https?://#i', $this->body);
        Comment::create([
            'blog_post_id' => $this->postId,
            'parent_id' => $this->parentId,
            'user_id' => Auth::id(),
            'author_name' => strip_tags($this->author),
            'body' => strip_tags($this->body),
            'status' => $links > 2 ? 'spam' : 'pending',
            'ip_hash' => hash('sha256', request()->ip().config('app.key')),
        ]);
        $this->reset(['body', 'parentId']);
        $this->sent = true;
    }

    public function render(): View
    {
        return view('livewire.comment-form');
    }
}
