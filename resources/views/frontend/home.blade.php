@extends('frontend.master', ['activePage' => 'home'])
@section('title', __('Home'))

@section('content')
@php
    // $featuredEvent is provided by the controller: an admin-marked featured event if one
    // exists, otherwise the soonest upcoming event.
    $latestEvents = $events->take(4);
    $categoriesToShow = $category->take(4);
    $blogsToShow = $blog->take(4);
@endphp
<style>
    .hm-page { font-family: 'Poppins', sans-serif; --hm-accent: var(--primary_color, #dc2626); color: #1f2430; background: #ffffff; }
    .hm-wrap { width: 100%; max-width: 1240px; margin: 0 auto; padding: 0 20px; box-sizing: border-box; }

    /* ---- Hero (default slide + admin banner slides, one carousel) ---- */
    .hm-hero { background: #0d0d12; color: #ffffff; position: relative; overflow: hidden; }
    .hm-hero::before {
        content: ''; position: absolute; top: -200px; right: -160px; width: 560px; height: 560px; border-radius: 50%;
        background: radial-gradient(circle, rgba(220,38,38,0.32) 0%, rgba(220,38,38,0) 70%); z-index: 0;
    }
    .hm-hero::after {
        content: ''; position: absolute; bottom: -240px; left: -160px; width: 460px; height: 460px; border-radius: 50%;
        background: radial-gradient(circle, rgba(220,38,38,0.16) 0%, rgba(220,38,38,0) 70%); z-index: 0;
    }
    .hm-hero-viewport { position: relative; height: 560px; overflow: hidden; }
    .hm-hero-slide-default { padding: 70px 0 90px; height: 100%; box-sizing: border-box; display: flex; align-items: center; }
    .hm-hero-slide-banner { height: 100%; }
    .hm-hero-slide-banner a { display: block; height: 100%; }
    .hm-hero-slide-banner img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .hm-hero-inner { position: relative; z-index: 1; max-width: 720px; }
    .hm-hero h1 { font-size: 44px; font-weight: 800; line-height: 1.18; margin: 0 0 18px; }
    .hm-hero h1 span { color: var(--hm-accent); }
    .hm-hero p.lead { font-size: 16.5px; line-height: 1.65; color: #cbd2e1; margin: 0 0 30px; max-width: 560px; }
    .hm-hero-ctas { display: flex; flex-wrap: wrap; gap: 14px; margin-bottom: 36px; }
    .hm-btn-primary {
        display: inline-flex; align-items: center; gap: 10px; background: var(--hm-accent); color: #ffffff;
        font-weight: 700; font-size: 15px; padding: 14px 28px; border-radius: 10px; text-decoration: none;
        box-shadow: 0 14px 30px rgba(220,38,38,0.35); transition: transform 0.15s ease, box-shadow 0.15s ease;
    }
    .hm-btn-primary:hover { transform: translateY(-2px); box-shadow: 0 18px 36px rgba(220,38,38,0.45); color: #fff; }
    .hm-btn-ghost {
        display: inline-flex; align-items: center; gap: 8px; background: transparent; color: #ffffff;
        font-weight: 600; font-size: 14.5px; padding: 13px 24px; border-radius: 10px; text-decoration: none;
        border: 1px solid rgba(255,255,255,0.28); transition: background 0.15s ease;
    }
    .hm-btn-ghost:hover { background: rgba(255,255,255,0.08); color: #fff; }
    .hm-hero-features { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; max-width: 640px; }
    .hm-hero-feature { display: flex; flex-direction: column; align-items: flex-start; gap: 8px; }
    .hm-hero-feature i {
        width: 38px; height: 38px; border-radius: 10px; background: rgba(220,38,38,0.16); color: var(--hm-accent);
        display: flex; align-items: center; justify-content: center; font-size: 15px;
    }
    .hm-hero-feature strong { font-size: 13px; font-weight: 700; color: #ffffff; }
    .hm-hero-feature span { font-size: 11.5px; color: #9aa2b5; line-height: 1.4; }
    .hm-hero-indicators { display: flex; justify-content: center; gap: 8px; position: relative; z-index: 2; padding: 16px 0; background: #0d0d12; }
    .hm-hero-indicators button { width: 8px; height: 8px; border-radius: 50%; border: none; background: rgba(255,255,255,0.3); cursor: pointer; padding: 0; }
    .hm-hero-indicators button[aria-current="true"] { background: var(--hm-accent); width: 22px; border-radius: 999px; }

    /* Flowbite's carousel JS toggles these utility classes on slides; defined explicitly here
       since this build's compiled Tailwind CSS doesn't include them. Slide + fade combo for a
       smoother, less abrupt crossfade between the default hero and admin banners. */
    .hm-hero-slide.transform {
        transition: transform 0.85s cubic-bezier(0.65, 0, 0.35, 1), opacity 0.85s cubic-bezier(0.65, 0, 0.35, 1);
        opacity: 0;
    }
    .hm-hero-slide.translate-x-full { transform: translateX(4%); opacity: 0; }
    .hm-hero-slide.-translate-x-full { transform: translateX(-4%); opacity: 0; }
    .hm-hero-slide.translate-x-0 { transform: translateX(0); opacity: 1; }
    @media (max-width: 720px) { .hm-hero-features { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 560px) {
        .hm-hero h1 { font-size: 32px; }
        .hm-hero-slide-default { padding: 56px 0 52px; }
        .hm-hero-viewport { height: 780px; }
    }

    /* ---- Search filter card ---- */
    .hm-search-wrap { margin-top: -16px; position: relative; z-index: 2; }
    .hm-search-card {
        background: #ffffff; border-radius: 16px; box-shadow: 0 20px 50px rgba(15,23,42,0.16);
        padding: 26px 28px; display: flex; flex-wrap: wrap; align-items: flex-end; gap: 18px;
    }
    .hm-search-field { flex: 1 1 170px; min-width: 150px; }
    .hm-search-field label { display: block; font-size: 12.5px; font-weight: 700; color: #14161f; margin-bottom: 8px; }
    .hm-search-field select, .hm-search-field input[type="date"] {
        width: 100%; border: 1px solid #e2e8f0; border-radius: 9px; padding: 11px 12px; font-size: 13.5px;
        font-family: 'Poppins', sans-serif; color: #334155; background: #fff;
    }
    .hm-search-submit {
        background: var(--hm-accent); color: #fff; font-weight: 700; font-size: 14.5px; border: none;
        border-radius: 9px; padding: 12px 30px; cursor: pointer; white-space: nowrap;
    }
    .hm-search-submit:hover { opacity: 0.92; }

    /* ---- Section shell ---- */
    .hm-section { padding: 70px 0; }
    .hm-section-alt { background: #f8f9fc; }
    .hm-section-head { display: flex; align-items: flex-end; justify-content: space-between; gap: 20px; margin-bottom: 34px; flex-wrap: wrap; }
    .hm-section-head h2 { font-size: 28px; font-weight: 800; margin: 0 0 6px; color: #14161f; }
    .hm-section-head p { font-size: 14px; color: #667085; margin: 0; }
    .hm-see-all {
        display: inline-flex; align-items: center; gap: 8px; color: var(--hm-accent); font-weight: 700; font-size: 14px;
        text-decoration: none; white-space: nowrap;
    }
    .hm-see-all:hover { color: var(--hm-accent); opacity: 0.8; }
    .hm-empty { color: #94a3b8; font-size: 14px; padding: 20px 0; }

    /* ---- Featured event ---- */
    .hm-featured {
        display: grid; grid-template-columns: 1.1fr 1fr; gap: 0; background: #0d0d12; border-radius: 20px; overflow: hidden;
        box-shadow: 0 20px 50px rgba(15,23,42,0.18);
    }
    .hm-featured-img { position: relative; min-height: 320px; }
    .hm-featured-img img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; }
    .hm-featured-body { padding: 40px; color: #ffffff; display: flex; flex-direction: column; justify-content: center; }
    .hm-featured-eyebrow {
        display: inline-flex; align-items: center; gap: 6px; background: rgba(220,38,38,0.85); color: #fff; font-weight: 700;
        font-size: 11.5px; letter-spacing: 0.05em; text-transform: uppercase; padding: 6px 14px; border-radius: 999px;
        margin-bottom: 16px; width: fit-content;
    }
    .hm-featured-body h3 { font-size: 26px; font-weight: 800; line-height: 1.3; margin: 0 0 16px; }
    .hm-featured-meta { display: flex; flex-direction: column; gap: 10px; margin-bottom: 26px; }
    .hm-featured-meta span { display: flex; align-items: center; gap: 10px; font-size: 13.5px; color: #c2c8d6; }
    .hm-featured-meta i { color: var(--hm-accent); width: 16px; }
    .hm-featured-actions { display: flex; flex-wrap: wrap; gap: 12px; }
    .hm-btn-outline-light {
        display: inline-flex; align-items: center; gap: 8px; background: transparent; color: #fff; font-weight: 600;
        font-size: 14px; padding: 12px 22px; border-radius: 9px; border: 1px solid rgba(255,255,255,0.3); text-decoration: none;
        cursor: pointer;
    }
    .hm-btn-outline-light:hover { background: rgba(255,255,255,0.08); color: #fff; }
    @media (max-width: 860px) { .hm-featured { grid-template-columns: 1fr; } .hm-featured-img { min-height: 220px; } .hm-featured-body { padding: 30px; } }

    /* ---- Event / category / blog cards ---- */
    .hm-grid-4 { display: grid; grid-template-columns: repeat(4, 1fr); gap: 22px; }
    .hm-grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 22px; }
    @media (max-width: 1020px) { .hm-grid-4 { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 640px) { .hm-grid-4, .hm-grid-2 { grid-template-columns: 1fr; } }

    .hm-event-card {
        background: #fff; border: 1px solid #f1f5f9; border-radius: 14px; overflow: hidden;
        box-shadow: 0 10px 26px rgba(15,23,42,0.06); transition: transform 0.15s ease, box-shadow 0.15s ease; text-decoration: none; color: inherit; display: block;
    }
    .hm-event-card:hover { transform: translateY(-4px); box-shadow: 0 16px 36px rgba(15,23,42,0.12); color: inherit; }
    .hm-event-card-img { position: relative; aspect-ratio: 16/10; }
    .hm-event-card-img img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .hm-event-card-tag {
        position: absolute; top: 12px; left: 12px; background: rgba(13,13,18,0.75); color: #fff; font-size: 11px;
        font-weight: 700; padding: 5px 11px; border-radius: 999px;
    }
    .hm-event-card-fav {
        position: absolute; top: 10px; right: 10px; width: 32px; height: 32px; border-radius: 50%; background: #fff;
        display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 10px rgba(0,0,0,0.15);
    }
    .hm-event-card-fav img { width: 15px; height: 15px; object-fit: contain; }
    .hm-event-card-body { padding: 16px 18px 18px; }
    .hm-event-card-body h4 { font-size: 15.5px; font-weight: 700; margin: 0 0 8px; line-height: 1.35; color: #14161f; }
    .hm-event-card-meta { font-size: 12.5px; color: #667085; display: flex; align-items: center; gap: 6px; margin-bottom: 4px; }
    .hm-event-card-meta i { color: var(--hm-accent); width: 12px; }
    .hm-event-card-foot { display: flex; align-items: center; justify-content: space-between; margin-top: 12px; }
    .hm-event-card-seats { font-size: 11.5px; color: #94a3b8; font-weight: 600; }
    .hm-event-card-link { color: var(--hm-accent); font-size: 12.5px; font-weight: 700; display: inline-flex; align-items: center; gap: 5px; }

    .hm-cat-card {
        background: #fff; border: 1px solid #f1f5f9; border-radius: 14px; padding: 24px 20px; text-align: center;
        box-shadow: 0 10px 26px rgba(15,23,42,0.06); transition: transform 0.15s ease, box-shadow 0.15s ease; text-decoration: none; color: inherit; display: block;
    }
    .hm-cat-card:hover { transform: translateY(-4px); box-shadow: 0 16px 36px rgba(15,23,42,0.12); color: inherit; }
    .hm-cat-card img {
        width: 64px; height: 64px; border-radius: 50%; object-fit: cover; margin: 0 auto 14px; display: block;
        border: 3px solid #fff5f5; box-shadow: 0 6px 16px rgba(220,38,38,0.18);
    }
    .hm-cat-card h4 { font-size: 15px; font-weight: 700; margin: 0 0 4px; color: #14161f; }
    .hm-cat-card span { font-size: 12px; color: #94a3b8; }

    .hm-blog-card {
        background: #fff; border: 1px solid #f1f5f9; border-radius: 14px; overflow: hidden; display: flex;
        box-shadow: 0 10px 26px rgba(15,23,42,0.06); transition: transform 0.15s ease, box-shadow 0.15s ease;
    }
    .hm-blog-card:hover { transform: translateY(-4px); box-shadow: 0 16px 36px rgba(15,23,42,0.12); }
    .hm-blog-card-img { flex: 0 0 42%; position: relative; }
    .hm-blog-card-img img { width: 100%; height: 100%; object-fit: cover; display: block; min-height: 160px; }
    .hm-blog-card-body { padding: 18px 20px; flex: 1; display: flex; flex-direction: column; }
    .hm-blog-card-top { display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px; }
    .hm-blog-tag { background: #ecfdf5; color: #059669; font-size: 11px; font-weight: 700; padding: 4px 10px; border-radius: 999px; }
    .hm-blog-date { font-size: 11.5px; color: #94a3b8; }
    .hm-blog-card-body h4 { font-size: 15.5px; font-weight: 700; margin: 0 0 8px; line-height: 1.35; color: #14161f; }
    .hm-blog-card-body p { font-size: 13px; color: #667085; line-height: 1.55; margin: 0; flex: 1; }
    .hm-blog-read { margin-top: 12px; color: var(--hm-accent); font-size: 12.5px; font-weight: 700; display: inline-flex; align-items: center; gap: 5px; text-decoration: none; }
    @media (max-width: 560px) { .hm-blog-card { flex-direction: column; } .hm-blog-card-img img { min-height: 180px; } }

    /* ---- App download band ---- */
    .hm-app-band { background: linear-gradient(120deg, #0d0d12 0%, #2a0a0a 100%); border-radius: 24px; padding: 52px 44px; color: #fff; display: flex; align-items: center; justify-content: space-between; gap: 30px; flex-wrap: wrap; }
    .hm-app-band h2 { font-size: 26px; font-weight: 800; margin: 0 0 12px; max-width: 420px; }
    .hm-app-band p { font-size: 14.5px; color: #cbd2e1; margin: 0 0 22px; max-width: 420px; }
    .hm-app-badges { display: flex; flex-wrap: wrap; gap: 12px; }
    .hm-app-badges img { height: 46px; width: auto; }
</style>

<div class="hm-page">

    {{-- HERO (default slide + admin banners as additional slides, one carousel) --}}
    <section class="hm-hero">
        <div id="homeHeroCarousel" class="hm-hero-viewport" data-carousel="slide">
            <div class="hm-hero-slide hm-hero-slide-default" data-carousel-item>
                <div class="hm-wrap">
                    <div class="hm-hero-inner">
                        <h1>{{ __('Book Or Host Your') }} <span>{{ __('Events') }}</span>, {{ __('Effortlessly') }}</h1>
                        <p class="lead">{{ __('Your all-in-one platform for unforgettable events - discover things to do, or list your own event and start selling tickets in minutes.') }}</p>
                        <div class="hm-hero-ctas">
                            <a href="{{ url('/all-events') }}" class="hm-btn-primary">
                                <i class="fa fa-ticket" aria-hidden="true"></i> {{ __('Explore Events') }}
                            </a>
                            <a href="{{ url('/become-organizer') }}" class="hm-btn-ghost">
                                <i class="fa fa-star" aria-hidden="true"></i> {{ __('Become an Organizer') }}
                            </a>
                        </div>
                        <div class="hm-hero-features">
                            <div class="hm-hero-feature">
                                <i class="fa fa-ticket" aria-hidden="true"></i>
                                <strong>{{ __('Book Tickets') }}</strong>
                                <span>{{ __('Discover and book amazing events') }}</span>
                            </div>
                            <div class="hm-hero-feature">
                                <i class="fa fa-calendar" aria-hidden="true"></i>
                                <strong>{{ __('Host Events') }}</strong>
                                <span>{{ __('Create and manage your events') }}</span>
                            </div>
                            <div class="hm-hero-feature">
                                <i class="fa fa-th-large" aria-hidden="true"></i>
                                <strong>{{ __('All Categories') }}</strong>
                                <span>{{ __('Concerts, sports, workshops & more') }}</span>
                            </div>
                            <div class="hm-hero-feature">
                                <i class="fa fa-shield" aria-hidden="true"></i>
                                <strong>{{ __('Secure & Easy') }}</strong>
                                <span>{{ __('Safe payments, seamless booking') }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @foreach ($banner as $i => $b)
                <div class="hm-hero-slide hm-hero-slide-banner" data-carousel-item>
                    <a href="{{ $b->banner_type === 'general' ? $b->redirect_url : url('/events/' . $b->event_id) }}"
                        @if ($b->banner_type === 'general') target="_blank" rel="noopener" @endif>
                        <picture>
                            <source media="(max-width: 640px)" srcset="{{ asset('images/upload/' . $b->image_for_mobile) }}">
                            <img src="{{ asset('images/upload/' . $b->image) }}" alt="{{ __('Promotion') }} {{ $i + 1 }}">
                        </picture>
                    </a>
                </div>
            @endforeach
        </div>
        @if (count($banner) > 0)
            <div class="hm-hero-indicators">
                <button type="button" aria-current="true" aria-label="{{ __('Slide') }} 1" data-carousel-slide-to="0"></button>
                @foreach ($banner as $i => $b)
                    <button type="button" aria-current="false" aria-label="{{ __('Slide') }} {{ $i + 2 }}" data-carousel-slide-to="{{ $i + 1 }}"></button>
                @endforeach
            </div>
        @endif
    </section>

    {{-- SEARCH FILTER --}}
    <div class="hm-wrap hm-search-wrap">
        <form method="post" action="{{ url('all-events') }}" class="hm-search-card reveal-up">
            @csrf
            <div class="hm-search-field">
                <label for="category">{{ __('Category') }}</label>
                <select id="category" name="category" class="select2 w-full">
                    <option value="">{{ __('All Categories') }}</option>
                    @foreach ($category as $item)
                        <option value="{{ $item->id }}">{{ $item->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="hm-search-field">
                <label for="event">{{ __('Event Type') }}</label>
                <select id="event" name="type" class="select2 w-full">
                    <option value="" selected>{{ __('All Event Types') }}</option>
                    <option value="online">{{ __('Online') }}</option>
                    <option value="offline">{{ __('Venue') }}</option>
                </select>
            </div>
            <div class="hm-search-field">
                <label for="duration">{{ __('When') }}</label>
                <select id="duration" name="duration" class="select2 w-full">
                    <option value="" selected>{{ __('Any Date') }}</option>
                    <option value="Today">{{ __('Today') }}</option>
                    <option value="Tomorrow">{{ __('Tomorrow') }}</option>
                    <option value="ThisWeek">{{ __('This week') }}</option>
                    <option value="date">{{ __('Choose Date') }}</option>
                </select>
            </div>
            <div class="hm-search-field date-section hidden">
                <label for="date">{{ __('Choose date') }}</label>
                <input type="text" class="date" placeholder="{{ __('Choose date') }}" name="date" id="date">
            </div>
            <button type="submit" class="hm-search-submit">{{ __('Search Events') }}</button>
        </form>
    </div>

    {{-- FEATURED EVENT --}}
    @if ($featuredEvent)
        <section class="hm-section" style="padding-top: 70px;">
            <div class="hm-wrap">
                <div class="hm-section-head reveal-up">
                    <div>
                        <h2>{{ __('Featured Event') }}</h2>
                        <p>{{ __('A hand-picked event we think you shouldn\'t miss') }}</p>
                    </div>
                </div>
                <div class="hm-featured reveal-fade">
                    <div class="hm-featured-img">
                        <img src="{{ url('images/upload/' . $featuredEvent->image) }}" alt="{{ $featuredEvent->name }}">
                    </div>
                    <div class="hm-featured-body">
                        <span class="hm-featured-eyebrow"><i class="fa fa-star" aria-hidden="true"></i> {{ __("Don't Miss This") }}</span>
                        <h3>{{ $featuredEvent->name }}</h3>
                        <div class="hm-featured-meta">
                            <span><i class="fa fa-calendar" aria-hidden="true"></i> {{ Carbon\Carbon::parse($featuredEvent->start_time)->format('D, M j, Y · g:i A') }}</span>
                            <span><i class="fa fa-map-marker" aria-hidden="true"></i> {{ $featuredEvent->type == 'online' ? __('Online Event') : $featuredEvent->address }}</span>
                            @if (isset($featuredEvent->available_ticket))
                                <span><i class="fa fa-users" aria-hidden="true"></i> {{ $featuredEvent->available_ticket }} {{ __('Seats Left') }}</span>
                            @endif
                        </div>
                        <div class="hm-featured-actions">
                            <a href="{{ url('event/' . $featuredEvent->id . '/' . Str::slug($featuredEvent->name)) }}" class="hm-btn-primary">
                                <i class="fa fa-ticket" aria-hidden="true"></i> {{ __('Book Tickets') }}
                            </a>
                            <button type="button" class="hm-btn-outline-light" onclick="hmShareEvent('{{ url('event/' . $featuredEvent->id . '/' . Str::slug($featuredEvent->name)) }}', {{ Illuminate\Support\Js::from($featuredEvent->name) }})">
                                <i class="fa fa-share-alt" aria-hidden="true"></i> {{ __('Share Event') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    @endif

    {{-- LATEST EVENTS --}}
    <section class="hm-section {{ $featuredEvent ? '' : 'hm-section-alt' }}">
        <div class="hm-wrap">
            <div class="hm-section-head reveal-up">
                <div>
                    <h2>{{ __('Latest Events') }}</h2>
                    <p>{{ __('Fresh events added to the platform') }}</p>
                </div>
                <a href="{{ url('/all-events') }}" class="hm-see-all">{{ __('View All Events') }} <i class="fa fa-arrow-right" aria-hidden="true"></i></a>
            </div>
            @if ($latestEvents->isEmpty())
                <div class="hm-empty">{{ __('There are no events added yet') }}</div>
            @else
                <div class="hm-grid-4">
                    @foreach ($latestEvents as $item)
                        <a href="{{ url('event/' . $item->id . '/' . Str::slug($item->name)) }}" class="hm-event-card reveal-up">
                            <div class="hm-event-card-img">
                                <img src="{{ url('images/upload/' . $item->image) }}" alt="{{ $item->name }}">
                                @if ($item->category)
                                    <span class="hm-event-card-tag">{{ $item->category->name }}</span>
                                @endif
                                @if (Auth::guard('appuser')->user())
                                    <span class="hm-event-card-fav" onclick="event.preventDefault(); addFavorite('{{ $item->id }}','event')">
                                        <img src="{{ url('images/' . (Str::contains($user->favorite, $item->id) ? 'heart-fill.svg' : 'heart.svg')) }}" alt="{{ __('Favorite') }}">
                                    </span>
                                @endif
                            </div>
                            <div class="hm-event-card-body">
                                <h4>{{ $item->name }}</h4>
                                <div class="hm-event-card-meta"><i class="fa fa-calendar" aria-hidden="true"></i> {{ Carbon\Carbon::parse($item->start_time)->format('d M Y') }}</div>
                                <div class="hm-event-card-meta"><i class="fa fa-map-marker" aria-hidden="true"></i> {{ $item->type == 'online' ? __('Online') : Str::limit($item->address, 30) }}</div>
                                <div class="hm-event-card-foot">
                                    @if (isset($item->available_ticket))
                                        <span class="hm-event-card-seats">{{ $item->available_ticket }} {{ __('left') }}</span>
                                    @else
                                        <span></span>
                                    @endif
                                    <span class="hm-event-card-link">{{ __('View Details') }} <i class="fa fa-arrow-right" aria-hidden="true"></i></span>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    {{-- POPULAR CATEGORIES --}}
    <section class="hm-section {{ $featuredEvent ? 'hm-section-alt' : '' }}">
        <div class="hm-wrap">
            <div class="hm-section-head reveal-up">
                <div>
                    <h2>{{ __('Popular Categories') }}</h2>
                    <p>{{ __('Browse events by category') }}</p>
                </div>
                <a href="{{ url('/all-category') }}" class="hm-see-all">{{ __('View All Categories') }} <i class="fa fa-arrow-right" aria-hidden="true"></i></a>
            </div>
            @if ($categoriesToShow->isEmpty())
                <div class="hm-empty">{{ __('There are no categories added yet') }}</div>
            @else
                <div class="hm-grid-4">
                    @foreach ($categoriesToShow as $item)
                        <a href="{{ url('events-category/' . $item->id) . '/' . Str::slug($item->name) }}" class="hm-cat-card reveal-up">
                            <img src="{{ url('images/upload/' . $item->image) }}" alt="{{ $item->name }}">
                            <h4>{{ $item->name }}</h4>
                            <span>{{ $categoryEventCounts[$item->id] ?? 0 }}+ {{ __('Events') }}</span>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    {{-- LATEST BLOGS --}}
    <section class="hm-section {{ $featuredEvent ? '' : 'hm-section-alt' }}">
        <div class="hm-wrap">
            <div class="hm-section-head reveal-up">
                <div>
                    <h2>{{ __('Latest Blogs') }}</h2>
                    <p>{{ __('Tips, stories and updates from our team') }}</p>
                </div>
                <a href="{{ url('/all-blogs') }}" class="hm-see-all">{{ __('View All Blogs') }} <i class="fa fa-arrow-right" aria-hidden="true"></i></a>
            </div>
            @if ($blogsToShow->isEmpty())
                <div class="hm-empty">{{ __('There are no blogs added yet') }}</div>
            @else
                <div class="hm-grid-2">
                    @foreach ($blogsToShow as $item)
                        <div class="hm-blog-card reveal-up">
                            <div class="hm-blog-card-img">
                                <img src="{{ asset('images/upload/' . $item->image) }}" alt="{{ $item->title }}">
                            </div>
                            <div class="hm-blog-card-body">
                                <div class="hm-blog-card-top">
                                    @if ($item->category)
                                        <span class="hm-blog-tag">{{ $item->category->name }}</span>
                                    @else
                                        <span></span>
                                    @endif
                                    <span class="hm-blog-date">{{ Carbon\Carbon::parse($item->created_at)->format('d M Y') }}</span>
                                </div>
                                <h4>{{ $item->title }}</h4>
                                <p>{{ Illuminate\Support\Str::limit(strip_tags($item->description), 110) }}</p>
                                <a href="{{ url('/blog-detail/' . $item->id . '/' . Str::slug($item->title)) }}" class="hm-blog-read">{{ __('Read More') }} <i class="fa fa-arrow-right" aria-hidden="true"></i></a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    {{-- APP DOWNLOAD --}}
    @if ($showLinkBanner->show_link_banner == 1)
        <section class="hm-section" style="padding-top: 0;">
            <div class="hm-wrap">
                <div class="hm-app-band reveal-fade">
                    <div>
                        <h2>{{ __('Book Your Favorite Events From Anywhere') }}</h2>
                        <p>{{ __('Download the app and start booking in seconds - available on iOS and Android.') }}</p>
                        <div class="hm-app-badges">
                            @if ($showLinkBanner->appstore_link)
                                <a href="{{ $showLinkBanner->appstore_link }}" target="_blank" rel="noopener noreferrer"><img src="{{ asset('images/AppStore.svg') }}" alt="{{ __('Download on the App Store') }}"></a>
                            @endif
                            @if ($showLinkBanner->googleplay_link)
                                <a href="{{ $showLinkBanner->googleplay_link }}" target="_blank" rel="noopener noreferrer"><img src="{{ asset('images/GooglePlay.svg') }}" alt="{{ __('Get it on Google Play') }}"></a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </section>
    @endif

</div>

<script>
    function hmShareEvent(url, title) {
        var shareData = { title: title, url: url };
        if (navigator.share) {
            navigator.share(shareData).catch(function () {});
        } else if (navigator.clipboard) {
            navigator.clipboard.writeText(url).then(function () {
                alert('{{ __("Event link copied to clipboard.") }}');
            });
        }
    }
</script>
@endsection
