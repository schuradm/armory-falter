<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trade_requests', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 20)->unique();
            $table->string('type', 20); // consignment (Verkaufsservice/egun) | buyback (Ankauf)
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->json('contact');
            $table->string('weapon_type', 80)->nullable();
            $table->foreignId('manufacturer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('manufacturer_name', 120)->nullable();
            $table->string('model', 160);
            $table->string('caliber', 80)->nullable();
            $table->string('condition_code', 20);
            $table->unsignedInteger('desired_price')->nullable();
            $table->text('description')->nullable();
            $table->string('status', 20)->default('new');
            $table->unsignedInteger('offer_price')->nullable();
            $table->string('egun_listing_id', 60)->nullable();
            $table->string('advisory_pdf_path')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('privacy_accepted_at');
            $table->timestamps();
            $table->index(['type', 'status', 'created_at']);
        });

        Schema::create('trade_request_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trade_request_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->unsignedTinyInteger('sort_order');
        });

        Schema::create('workshop_services', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 80)->unique();
            $table->string('name', 120);
            $table->string('teaser')->nullable();
            $table->longText('description')->nullable();
            $table->unsignedInteger('price_from_gross')->nullable();
            $table->unsignedSmallInteger('duration_minutes')->default(30);
            $table->boolean('is_bookable')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20); // workshop|consultation|pickup
            $table->foreignId('workshop_service_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->json('contact');
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->string('resource', 60)->default('werkstatt');
            $table->string('status', 20)->default('requested');
            $table->string('weapon_description')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['resource', 'starts_at']);
            $table->index(['starts_at', 'status']);
        });

        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 160)->unique();
            $table->string('title', 200);
            $table->longText('body');
            $table->string('template', 40)->default('default');
            $table->string('status', 20)->default('published');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });

        Schema::create('authors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 120);
            $table->string('slug', 140)->unique();
            $table->string('role_title', 120)->nullable();
            $table->text('bio')->nullable();
            $table->string('avatar_path')->nullable();
            $table->json('same_as')->nullable();
            $table->timestamps();
        });

        Schema::create('blog_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80);
            $table->string('slug', 80)->unique();
            $table->text('description')->nullable();
            $table->integer('sort_order')->default(0);
        });

        Schema::create('blog_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('blog_category_id')->constrained();
            $table->foreignId('author_id')->constrained();
            $table->string('title', 200);
            $table->string('slug', 220)->unique();
            $table->text('excerpt')->nullable();
            $table->longText('body');
            $table->string('cover_path')->nullable();
            $table->unsignedTinyInteger('reading_minutes')->nullable();
            $table->boolean('comments_enabled')->default(true);
            $table->string('status', 20)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'published_at']);
        });

        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('blog_post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('comments')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('author_name', 80);
            $table->text('body');
            $table->string('status', 20)->default('pending');
            $table->char('ip_hash', 64)->nullable();
            $table->timestamps();
            $table->index(['status', 'created_at']);
        });

        Schema::create('faqs', function (Blueprint $table) {
            $table->id();
            $table->nullableMorphs('faqable');
            $table->string('group_name', 80)->nullable();
            $table->string('question', 300);
            $table->text('answer');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('seo_meta', function (Blueprint $table) {
            $table->id();
            $table->morphs('seoable');
            $table->string('meta_title', 70)->nullable();
            $table->string('meta_description', 170)->nullable();
            $table->string('canonical_url')->nullable();
            $table->string('robots', 40)->nullable();
            $table->string('og_image_path')->nullable();
            $table->timestamps();
            $table->unique(['seoable_type', 'seoable_id']);
        });

        Schema::create('redirects', function (Blueprint $table) {
            $table->id();
            $table->string('from_path', 500)->unique();
            $table->string('to_path', 500);
            $table->unsignedSmallInteger('status_code')->default(301);
            $table->unsignedInteger('hits')->default(0);
            $table->timestamps();
        });

        Schema::create('newsletter_subscribers', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('first_name', 100)->nullable();
            $table->json('interests')->nullable();
            $table->string('status', 20)->default('pending');
            $table->char('confirm_token', 64)->nullable()->unique();
            $table->char('opt_in_ip_hash', 64)->nullable();
            $table->timestamp('opt_in_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->char('confirmed_ip_hash', 64)->nullable();
            $table->timestamp('unsubscribed_at')->nullable();
            $table->string('source', 60)->nullable();
            $table->timestamps();
            $table->index('status');
        });

        Schema::create('newsletter_segments', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->json('rules');
            $table->timestamps();
        });

        Schema::create('newsletter_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('name', 160);
            $table->string('subject', 200);
            $table->string('preheader', 200)->nullable();
            $table->foreignId('newsletter_segment_id')->nullable()->constrained()->nullOnDelete();
            $table->longText('body');
            $table->string('status', 20)->default('draft');
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });

        Schema::create('newsletter_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('newsletter_campaign_id')->constrained()->cascadeOnDelete();
            $table->foreignId('newsletter_subscriber_id')->constrained()->cascadeOnDelete();
            $table->string('event', 20);
            $table->string('url', 500)->nullable();
            $table->timestamp('occurred_at');
            $table->index(['newsletter_campaign_id', 'event']);
        });

        Schema::create('contact_messages', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('email');
            $table->string('phone', 40)->nullable();
            $table->string('subject', 200);
            $table->text('message');
            $table->string('status', 20)->default('new');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['contact_messages', 'newsletter_events', 'newsletter_campaigns', 'newsletter_segments', 'newsletter_subscribers', 'redirects', 'seo_meta',
            'faqs', 'comments', 'blog_posts', 'blog_categories', 'authors', 'pages', 'appointments', 'workshop_services', 'trade_request_media', 'trade_requests'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
