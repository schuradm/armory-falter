<?php

namespace App\Http\Controllers;

use App\Models\Author;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Support\Seo;
use Illuminate\Support\Str;

class BlogController extends Controller
{
    public function index(Seo $seo, ?BlogCategory $category = null)
    {
        $seo->title($category ? "{$category->name} – Blog" : 'Blog: Jagd, Sportschießen, Waffenrecht & Optik')
            ->description($category?->description ?: 'Fachartikel von Armory Falter zu Jagd, Sportschießen, Waffenrecht, Optiken, Munition und Produktneuheiten.')
            ->breadcrumb('Blog', route('blog.index'));
        if ($category) {
            $seo->breadcrumb($category->name, route('blog.category', $category));
        }

        $posts = BlogPost::published()->with('category', 'author')
            ->when($category, fn ($q) => $q->where('blog_category_id', $category->id))
            ->latest('published_at')->paginate(9, pageName: 'seite');
        $categories = BlogCategory::orderBy('sort_order')->get();

        return view('blog.index', compact('posts', 'categories', 'category'));
    }

    public function author(Author $author, Seo $seo)
    {
        $seo->title("{$author->name} – Autor")->description(Str::limit((string) $author->bio, 150))
            ->breadcrumb('Blog', route('blog.index'))->breadcrumb($author->name, route('blog.author', $author))
            ->schema(['@type' => 'Person', 'name' => $author->name, 'jobTitle' => $author->role_title, 'url' => route('blog.author', $author), 'sameAs' => $author->same_as ?? []]);
        $posts = $author->posts()->published()->with('category', 'author')->latest('published_at')->paginate(9, pageName: 'seite');

        return view('blog.author', compact('author', 'posts'));
    }

    public function show(BlogPost $post, Seo $seo)
    {
        abort_unless($post->status === 'published' && $post->published_at?->isPast(), 404);
        $post->load('category', 'author', 'faqs', 'seo');

        $seo->title($post->title)->description($post->excerpt ?: $post->body)->type('article')
            ->image($post->cover_path ? asset('storage/'.$post->cover_path) : null)
            ->breadcrumb('Blog', route('blog.index'))
            ->breadcrumb($post->category->name, route('blog.category', $post->category))
            ->breadcrumb($post->title, route('blog.show', $post))
            ->schema([
                '@type' => 'Article',
                'headline' => $post->title,
                'datePublished' => $post->published_at->toIso8601String(),
                'dateModified' => $post->updated_at->toIso8601String(),
                'author' => ['@type' => 'Person', 'name' => $post->author->name, 'url' => route('blog.author', $post->author)],
                'publisher' => ['@id' => url('/').'#organization'],
                'image' => $post->cover_path ? asset('storage/'.$post->cover_path) : asset('images/og-default.jpg'),
                'mainEntityOfPage' => route('blog.show', $post),
            ])
            ->applyOverrides($post->seo);

        $comments = $post->approvedComments()->get();
        $related = BlogPost::published()->where('blog_category_id', $post->blog_category_id)->where('id', '!=', $post->id)->with('category', 'author')->latest('published_at')->take(3)->get();

        return view('blog.show', compact('post', 'comments', 'related'));
    }
}
