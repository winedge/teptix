@extends('frontend.master', ['activePage' => 'become-organizer'])
@section('title', __('Become an Organizer'))
@section('content')
@php
    $orgRegisterUrl = url('user/register') . '?type=organizer';
    $appStoreUrl = $setting->appstore_link ?? '#';
    $playStoreUrl = $setting->googleplay_link ?? '#';

    $galleryImages = [
        '65aa1750e4e2a.jpg', '65aa1860b9ab5.jpg', '65ab67cc2cbb0.jpg',
        '65ab687d21729.jpg', '65ab68b6b15d4.jpg', '65bb10c288235.jpg',
        '65bb178f5dd36.jpg', '65bb1eef1bec0.jpg',
    ];
    $galleryVideos = ['BDUyrYgiuHE', 'HmnG35THPeA', 'n5YFC59iYIE'];

    // Interleave images/videos into a single feed so the masonry mixes media types.
    $masonryItems = [];
    $imgI = 0; $vidI = 0;
    for ($i = 0; $i < count($galleryImages) + count($galleryVideos); $i++) {
        if ($i % 4 === 3 && $vidI < count($galleryVideos)) {
            $masonryItems[] = ['type' => 'video', 'id' => $galleryVideos[$vidI++]];
        } elseif ($imgI < count($galleryImages)) {
            $masonryItems[] = ['type' => 'image', 'file' => $galleryImages[$imgI++]];
        } elseif ($vidI < count($galleryVideos)) {
            $masonryItems[] = ['type' => 'video', 'id' => $galleryVideos[$vidI++]];
        }
    }
@endphp
<style>
    .bo-page { font-family: 'Poppins', sans-serif; --bo-accent: var(--primary_color, #dc2626); color: #1f2430; background: #ffffff; }
    .bo-wrap { max-width: 1180px; margin: 0 auto; padding: 0 20px; }
    .bo-eyebrow {
        display: inline-flex; align-items: center; gap: 8px; background: rgba(220,38,38,0.12);
        color: #ff8a80; font-weight: 700; font-size: 12.5px; letter-spacing: 0.06em; text-transform: uppercase;
        padding: 7px 16px; border-radius: 999px; margin-bottom: 20px;
    }

    /* ---- Hero ---- */
    .bo-hero { background: #0d0d12; color: #ffffff; padding: 96px 0 110px; position: relative; overflow: hidden; }
    .bo-hero::before {
        content: ''; position: absolute; top: -180px; right: -160px; width: 520px; height: 520px; border-radius: 50%;
        background: radial-gradient(circle, rgba(220,38,38,0.35) 0%, rgba(220,38,38,0) 70%);
    }
    .bo-hero::after {
        content: ''; position: absolute; bottom: -220px; left: -140px; width: 460px; height: 460px; border-radius: 50%;
        background: radial-gradient(circle, rgba(220,38,38,0.18) 0%, rgba(220,38,38,0) 70%);
    }
    .bo-hero-inner { position: relative; z-index: 1; max-width: 760px; }
    .bo-hero h1 { font-size: 46px; font-weight: 800; line-height: 1.15; margin: 0 0 20px; }
    .bo-hero h1 span { color: var(--bo-accent); }
    .bo-hero p.lead { font-size: 17px; line-height: 1.65; color: #cbd2e1; margin: 0 0 34px; max-width: 600px; }
    .bo-hero-ctas { display: flex; flex-wrap: wrap; align-items: center; gap: 16px; margin-bottom: 44px; }
    .bo-btn-primary {
        display: inline-flex; align-items: center; gap: 10px; background: var(--bo-accent); color: #ffffff;
        font-weight: 700; font-size: 15.5px; padding: 15px 30px; border-radius: 10px; text-decoration: none;
        box-shadow: 0 14px 30px rgba(220,38,38,0.35); transition: transform 0.15s ease, box-shadow 0.15s ease;
    }
    .bo-btn-primary:hover { transform: translateY(-2px); box-shadow: 0 18px 36px rgba(220,38,38,0.45); color: #fff; }
    .bo-btn-ghost {
        display: inline-flex; align-items: center; gap: 8px; background: transparent; color: #ffffff;
        font-weight: 600; font-size: 15px; padding: 14px 26px; border-radius: 10px; text-decoration: none;
        border: 1px solid rgba(255,255,255,0.28); transition: background 0.15s ease;
    }
    .bo-btn-ghost:hover { background: rgba(255,255,255,0.08); color: #fff; }
    .bo-hero-chips { display: flex; flex-wrap: wrap; gap: 12px 28px; }
    .bo-hero-chip { display: flex; align-items: center; gap: 8px; font-size: 13.5px; color: #b9c0d1; font-weight: 500; }
    .bo-hero-chip i { color: var(--bo-accent); font-size: 14px; }

    /* ---- Section shell ---- */
    .bo-section { padding: 84px 0; }
    .bo-section-alt { background: #f8f9fc; }
    .bo-section-head { text-align: center; max-width: 620px; margin: 0 auto 52px; }
    .bo-section-head h2 { font-size: 32px; font-weight: 800; margin: 0 0 14px; color: #14161f; }
    .bo-section-head p { font-size: 15.5px; color: #667085; line-height: 1.6; margin: 0; }

    /* ---- Benefits grid ---- */
    .bo-benefits-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; }
    .bo-benefit-card {
        background: #ffffff; border: 1px solid #f1f5f9; border-radius: 14px; padding: 30px 26px;
        box-shadow: 0 12px 32px rgba(15,23,42,0.06); transition: transform 0.15s ease, box-shadow 0.15s ease;
    }
    .bo-benefit-card:hover { transform: translateY(-4px); box-shadow: 0 18px 40px rgba(15,23,42,0.1); }
    .bo-benefit-card.bo-highlight { background: #fff5f5; border-color: #fecaca; }
    .bo-benefit-icon {
        width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center;
        background: rgba(220,38,38,0.1); color: var(--bo-accent); font-size: 20px; margin-bottom: 18px;
    }
    .bo-benefit-card h3 { font-size: 17px; font-weight: 700; margin: 0 0 10px; color: #14161f; }
    .bo-benefit-card p { font-size: 14px; line-height: 1.6; color: #667085; margin: 0; }

    @media (max-width: 980px) { .bo-benefits-grid { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 640px) { .bo-benefits-grid { grid-template-columns: 1fr; } .bo-hero h1 { font-size: 34px; } .bo-hero { padding: 72px 0 88px; } }

    /* ---- App showcase ---- */
    .bo-app-row { display: grid; grid-template-columns: 1fr 1fr; gap: 32px; }
    .bo-app-card {
        background: #0d0d12; color: #ffffff; border-radius: 18px; padding: 38px 34px; position: relative; overflow: hidden;
    }
    .bo-app-card::before {
        content: ''; position: absolute; inset: 0; background: radial-gradient(circle at 85% 15%, rgba(220,38,38,0.28), transparent 55%);
    }
    .bo-app-card > * { position: relative; z-index: 1; }
    .bo-app-icon {
        width: 56px; height: 56px; border-radius: 14px; background: rgba(220,38,38,0.18); color: var(--bo-accent);
        display: flex; align-items: center; justify-content: center; font-size: 24px; margin-bottom: 22px;
    }
    .bo-app-card h3 { font-size: 21px; font-weight: 800; margin: 0 0 10px; }
    .bo-app-card p { font-size: 14px; line-height: 1.65; color: #c2c8d6; margin: 0 0 24px; }
    .bo-app-badges { display: flex; flex-wrap: wrap; gap: 10px; }
    .bo-app-badges a { display: inline-flex; }
    .bo-app-badges img { height: 42px; width: auto; }

    @media (max-width: 820px) { .bo-app-row { grid-template-columns: 1fr; } }

    /* ---- Masonry gallery ---- */
    .bo-masonry { column-count: 3; column-gap: 18px; }
    .bo-masonry-item {
        break-inside: avoid; margin-bottom: 18px; border-radius: 14px; overflow: hidden; position: relative;
        box-shadow: 0 10px 26px rgba(15,23,42,0.1); background: #0d0d12;
    }
    .bo-masonry-item img { display: block; width: 100%; height: auto; }
    .bo-video-tile { cursor: pointer; }
    .bo-video-tile .bo-play-overlay {
        position: absolute; inset: 0; display: flex; align-items: center; justify-content: center;
        background: rgba(13,13,18,0.28); transition: background 0.15s ease;
    }
    .bo-video-tile:hover .bo-play-overlay { background: rgba(13,13,18,0.42); }
    .bo-play-btn {
        width: 58px; height: 58px; border-radius: 50%; background: rgba(255,255,255,0.94); color: var(--bo-accent);
        display: flex; align-items: center; justify-content: center; font-size: 20px; box-shadow: 0 8px 20px rgba(0,0,0,0.35);
    }
    .bo-masonry-item iframe { display: block; width: 100%; aspect-ratio: 16/9; border: 0; }

    @media (max-width: 900px) { .bo-masonry { column-count: 2; } }
    @media (max-width: 560px) { .bo-masonry { column-count: 1; } }

    /* ---- Final CTA band ---- */
    .bo-final-cta {
        background: linear-gradient(120deg, #0d0d12 0%, #2a0a0a 100%); color: #ffffff; text-align: center;
        border-radius: 24px; padding: 64px 32px; margin: 0 20px;
    }
    .bo-final-cta h2 { font-size: 30px; font-weight: 800; margin: 0 0 14px; }
    .bo-final-cta p { font-size: 15.5px; color: #cbd2e1; margin: 0 0 30px; }
</style>

<div class="bo-page">

    {{-- HERO --}}
    <section class="bo-hero">
        <div class="bo-wrap">
            <div class="bo-hero-inner">
                <span class="bo-eyebrow"><i class="fa fa-star" aria-hidden="true"></i> {{ __('For Event Organizers') }}</span>
                <h1>{{ __('Turn Your Events Into') }} <span>{{ __('Unforgettable Experiences') }}</span></h1>
                <p class="lead">{{ __('List your event in minutes, sell tickets instantly, and keep 100% of what you earn - no commission, ever.') }}</p>
                <div class="bo-hero-ctas">
                    <a href="{{ $orgRegisterUrl }}" class="bo-btn-primary">
                        <i class="fa fa-arrow-right" aria-hidden="true"></i> {{ __('Register Now') }}
                    </a>
                    <a href="#bo-benefits" class="bo-btn-ghost">{{ __('See the Benefits') }}</a>
                </div>
                <div class="bo-hero-chips">
                    <span class="bo-hero-chip"><i class="fa fa-check-circle" aria-hidden="true"></i> {{ __('0% commission on ticket sales') }}</span>
                    <span class="bo-hero-chip"><i class="fa fa-check-circle" aria-hidden="true"></i> {{ __('Organizer & Scanner apps on iOS & Android') }}</span>
                    <span class="bo-hero-chip"><i class="fa fa-check-circle" aria-hidden="true"></i> {{ __('Go live in minutes') }}</span>
                </div>
            </div>
        </div>
    </section>

    {{-- BENEFITS --}}
    <section class="bo-section" id="bo-benefits">
        <div class="bo-wrap">
            <div class="bo-section-head reveal-up">
                <h2>{{ __('Why Host With Us') }}</h2>
                <p>{{ __('Everything you need to plan, sell out, and run your event - without giving up a cut of your revenue.') }}</p>
            </div>
            <div class="bo-benefits-grid">
                <div class="bo-benefit-card bo-highlight reveal-up">
                    <div class="bo-benefit-icon"><i class="fa fa-percent" aria-hidden="true"></i></div>
                    <h3>{{ __('0% Commission') }}</h3>
                    <p>{{ __('Whatever your event earns is completely yours. We don\'t take a cut of your ticket sales - no hidden fees, no surprises.') }}</p>
                </div>
                <div class="bo-benefit-card reveal-up">
                    <div class="bo-benefit-icon"><i class="fa fa-mobile" aria-hidden="true"></i></div>
                    <h3>{{ __('Organizer Mobile App') }}</h3>
                    <p>{{ __('Create events, track sales in real time, and manage your team from your phone - available on iOS and Android.') }}</p>
                </div>
                <div class="bo-benefit-card reveal-up">
                    <div class="bo-benefit-icon"><i class="fa fa-qrcode" aria-hidden="true"></i></div>
                    <h3>{{ __('Scanner Mobile App') }}</h3>
                    <p>{{ __('Check guests in instantly at the door with our dedicated scanner app - fast lines, zero paperwork.') }}</p>
                </div>
                <div class="bo-benefit-card reveal-up">
                    <div class="bo-benefit-icon"><i class="fa fa-bolt" aria-hidden="true"></i></div>
                    <h3>{{ __('Go Live in Minutes') }}</h3>
                    <p>{{ __('Set up your event page, ticket types, and seating in minutes - no technical setup required.') }}</p>
                </div>
                <div class="bo-benefit-card reveal-up">
                    <div class="bo-benefit-icon"><i class="fa fa-users" aria-hidden="true"></i></div>
                    <h3>{{ __('Reach More Buyers') }}</h3>
                    <p>{{ __('Your event gets discovered by ticket buyers already browsing our platform for things to do.') }}</p>
                </div>
                <div class="bo-benefit-card reveal-up">
                    <div class="bo-benefit-icon"><i class="fa fa-shield" aria-hidden="true"></i></div>
                    <h3>{{ __('Secure Payments') }}</h3>
                    <p>{{ __('Payments are processed securely end-to-end, so you and your buyers can focus on the event.') }}</p>
                </div>
            </div>
        </div>
    </section>

    {{-- APP SHOWCASE --}}
    <section class="bo-section bo-section-alt">
        <div class="bo-wrap">
            <div class="bo-section-head reveal-up">
                <h2>{{ __('Run Your Event From Your Pocket') }}</h2>
                <p>{{ __('Two purpose-built apps, so you and your door staff always have what you need.') }}</p>
            </div>
            <div class="bo-app-row">
                <div class="bo-app-card reveal-left">
                    <div class="bo-app-icon"><i class="fa fa-mobile" aria-hidden="true"></i></div>
                    <h3>{{ __('Organizer App') }}</h3>
                    <p>{{ __('Manage your events, monitor ticket sales as they happen, add team members, and stay on top of your event - wherever you are.') }}</p>
                    <div class="bo-app-badges">
                        <a href="{{ $appStoreUrl }}" target="_blank" rel="noopener noreferrer"><img src="{{ asset('images/AppStore.svg') }}" alt="{{ __('Download on the App Store') }}"></a>
                        <a href="{{ $playStoreUrl }}" target="_blank" rel="noopener noreferrer"><img src="{{ asset('images/GooglePlay.svg') }}" alt="{{ __('Get it on Google Play') }}"></a>
                    </div>
                </div>
                <div class="bo-app-card reveal-right">
                    <div class="bo-app-icon"><i class="fa fa-qrcode" aria-hidden="true"></i></div>
                    <h3>{{ __('Scanner App') }}</h3>
                    <p>{{ __('Scan tickets at the door in an instant. Built for speed, so your guests walk right in - no long lines, no manual lists.') }}</p>
                    <div class="bo-app-badges">
                        <a href="{{ $appStoreUrl }}" target="_blank" rel="noopener noreferrer"><img src="{{ asset('images/AppStore.svg') }}" alt="{{ __('Download on the App Store') }}"></a>
                        <a href="{{ $playStoreUrl }}" target="_blank" rel="noopener noreferrer"><img src="{{ asset('images/GooglePlay.svg') }}" alt="{{ __('Get it on Google Play') }}"></a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- MASONRY GALLERY --}}
    <section class="bo-section">
        <div class="bo-wrap">
            <div class="bo-section-head reveal-up">
                <h2>{{ __('Events Hosted On Our Platform') }}</h2>
                <p>{{ __('A glimpse at the concerts, screenings, and celebrations organizers like you have brought to life with us.') }}</p>
            </div>
            <div class="bo-masonry">
                @foreach ($masonryItems as $item)
                    @if ($item['type'] === 'image')
                        <div class="bo-masonry-item">
                            <img src="{{ url('images/upload/' . $item['file']) }}" alt="{{ __('Event photo') }}" loading="lazy">
                        </div>
                    @else
                        <div class="bo-masonry-item bo-video-tile" data-video-id="{{ $item['id'] }}" onclick="boPlayVideo(this)">
                            <img src="https://img.youtube.com/vi/{{ $item['id'] }}/hqdefault.jpg" alt="{{ __('Event video') }}" loading="lazy">
                            <div class="bo-play-overlay"><span class="bo-play-btn"><i class="fa fa-play" aria-hidden="true"></i></span></div>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    </section>

    {{-- FINAL CTA --}}
    <section class="bo-section" style="padding-top: 0;">
        <div class="bo-final-cta reveal-fade">
            <h2>{{ __('Ready to host your first event?') }}</h2>
            <p>{{ __('It takes just a few minutes to get started - and every dollar you earn stays yours.') }}</p>
            <a href="{{ $orgRegisterUrl }}" class="bo-btn-primary">
                <i class="fa fa-arrow-right" aria-hidden="true"></i> {{ __('Register Now') }}
            </a>
        </div>
    </section>

</div>

<script>
    function boPlayVideo(tile) {
        var videoId = tile.getAttribute('data-video-id');
        if (!videoId) return;
        var iframe = document.createElement('iframe');
        iframe.src = 'https://www.youtube.com/embed/' + videoId + '?autoplay=1';
        iframe.setAttribute('allow', 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture');
        iframe.setAttribute('allowfullscreen', '');
        tile.innerHTML = '';
        tile.classList.remove('bo-video-tile');
        tile.removeAttribute('onclick');
        tile.appendChild(iframe);
    }
</script>
@endsection
