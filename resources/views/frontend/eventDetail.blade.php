@extends('frontend.master', ['activePage' => 'event'])
@section('title', __('Event Details'))

@php
    $gmapkey = \App\Models\Setting::find(1)->map_key;
@endphp
@section('head_section')
<style>
.modal {
  display: none;
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0,0,0,0.5);
  z-index: 1000;
  align-items: center;
  justify-content: center;
}

.modal:target {
  display: flex;
}

.modal-content {
  background: white;
  padding: 20px;
  border-radius: 8px;
  max-height:800px;
  max-width: 900px;
  width: 90%;
  position: relative;
  overflow-y: auto;
}

.close {
  position: absolute;
  top: 10px;
  right: 15px;
  font-size: 24px;
  text-decoration: none;
  color: #333;
}

.frontend-seat-map-shell {
  width: 100%;
  overflow-x: auto;
}

.frontend-seat-map-stage {
  position: relative;
  width: 100%;
  min-width: 560px;
  aspect-ratio: 16 / 9;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  background: #f8fafc;
  overflow: hidden;
}

.frontend-seat-map-stage img {
  width: 100%;
  height: 100%;
  object-fit: contain;
  display: block;
}

.frontend-seat-map-stage.no-background {
  background:
    linear-gradient(90deg, rgba(15, 23, 42, 0.06) 1px, transparent 1px),
    linear-gradient(0deg, rgba(15, 23, 42, 0.06) 1px, transparent 1px),
    #f8fafc;
  background-size: 40px 40px;
}

.frontend-seat-dot {
    position: absolute;
    min-width: clamp(20px, 2.6vw, 20px);
    height: clamp(18px, 2.2vw, 25px);
    padding: 0 2px !important;
    box-sizing: border-box;
    border: 2px solid #ffffff;
    border-radius: 5px;
    color: #ffffff;
    font-size: clamp(8px, 0.95vw, 9px);
    font-weight: 700;
    line-height: calc(clamp(18px, 2.2vw, 25px) - 4px);
    text-align: center;
    transform: translate(-50%, -50%);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.25);
    cursor: pointer;
    z-index: 3;
    transition: transform 0.15s ease;
}

.frontend-seat-dot.shape-circle {
    border-radius: 50% !important;
    width: clamp(18px, 2.2vw, 30px) !important;
    height: clamp(18px, 2.2vw, 30px) !important;
}

.frontend-seat-dot.shape-square {
    border-radius: 4px !important;
    width: clamp(18px, 2.2vw, 30px) !important;
    height: clamp(18px, 2.2vw, 30px) !important;
}

.frontend-seat-dot.shape-rectangle {
    border-radius: 4px !important;
    width: clamp(24px, 3.0vw, 34px) !important;
    height: clamp(17px, 2.1vw, 22px) !important;
    line-height: calc(clamp(17px, 2.1vw, 22px) - 4px) !important;
}

.frontend-seat-dot.shape-arch,
.frontend-seat-dot.shape-horseshoe {
    border-radius: 14px 14px 4px 4px !important;
    width: clamp(18px, 2.2vw, 30px) !important;
    height: clamp(18px, 2.2vw, 30px) !important;
}

.frontend-row-label {
    position: absolute;
    transform: translate(-50%, -50%) rotate(var(--rotate-deg, 0deg));
    font-size: clamp(9px, 1.05vw, 11px);
    font-weight: 800;
    color: #1e293b;
    background: rgba(255, 255, 255, 0.95);
    border: none !important;
    padding: 2px 7px;
    border-radius: 12px;
    box-shadow: 0 1px 4px rgba(0, 0, 0, 0.15);
    pointer-events: none;
    white-space: nowrap;
    z-index: 10;
    line-height: 1.2;
}

.frontend-seat-dot.available { background: #16a34a; }
.frontend-seat-dot.blocked,
.frontend-seat-dot.held { background: #6b7280; cursor: not-allowed; }
.frontend-seat-dot.booked { background: #dc2626; cursor: not-allowed; }
.accessible-micro-badge {
  position: absolute;
  top: -6px;
  right: -6px;
  width: 13px;
  height: 13px;
  border-radius: 50%;
  background: #0284c7;
  color: #ffffff;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 7px;
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.35);
  border: 1.5px solid #ffffff;
  z-index: 10;
  pointer-events: none;
  line-height: 1;
}

.frontend-seat-map-legend {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
  margin-top: 12px;
  font-size: 13px;
  color: #374151;
}

.frontend-seat-map-legend span {
  display: inline-flex;
  align-items: center;
  gap: 6px;
}

.frontend-seat-map-legend i {
  width: 12px;
  height: 12px;
  border-radius: 50%;
  display: inline-block;
}

@media (max-width: 640px) {
  .frontend-seat-map-stage {
    min-width: 480px;
  }
}
@media (max-width: 768px) {
  .modal-content {
    max-height: 70vh; /* 70% of viewport height */
  }
}

/* Mobile (up to 480px) */
@media (max-width: 480px) {
  .modal-content {
    max-height: 94vh; /* 60% of viewport height */
    padding: 15px;
  }
}
</style>
<script>
    !function(f,b,e,v,n,t,s)
    {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
    n.callMethod.apply(n,arguments):n.queue.push(arguments)};
    if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
    n.queue=[];t=b.createElement(e);t.async=!0;
    t.src=v;s=b.getElementsByTagName(e)[0];
    s.parentNode.insertBefore(t,s)}(window, document,'script',
    'https://connect.facebook.net/en_US/fbevents.js');
    fbq('init', '{{ $data->meta_pixel_id??0 }}');
    fbq('track', 'PageView');
</script>
<noscript>
    <img height="1" width="1" style="display:none"
         src="https://www.facebook.com/tr?id={{ $data->meta_pixel_id??0 }}&ev=PageView&noscript=1"/>
</noscript>
@endsection
@section('content')
@php
    $allTicketsSoldOut = count($data->all_ticket) > 0 && collect($data->all_ticket)->every(function ($ticket) {
        return (int) ($ticket->available_qty ?? 0) <= 0;
    });
@endphp
@php
    $seoTicketPrices = collect($data->all_ticket ?? [])
        ->where('type', 'paid')
        ->pluck('price')
        ->filter(fn ($p) => is_numeric($p))
        ->map(fn ($p) => (float) $p);
    $seoLowestPrice = $seoTicketPrices->count() ? $seoTicketPrices->min() : 0;
    $seoAvailability = ($allTicketsSoldOut ?? false) ? 'https://schema.org/SoldOut' : 'https://schema.org/InStock';
    $seoOrganizerName = (isset($organizations) && $organizations->count() > 0)
        ? $organizations->first()->organization_name
        : (\App\Models\Setting::find(1)->app_name ?? 'TEPTIX');
    $seoDescription = Str::limit(trim(strip_tags((string) $data->description)), 300);
@endphp
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Event",
  "name": {!! json_encode($data->name) !!},
  "startDate": "{{ Carbon\Carbon::parse($data->start_time)->format('Y-m-d\TH:iP') }}",
  "endDate": "{{ Carbon\Carbon::parse($data->end_time)->format('Y-m-d\TH:iP') }}",
  "eventAttendanceMode": "https://schema.org/{{ $data->type === 'online' ? 'OnlineEventAttendanceMode' : 'OfflineEventAttendanceMode' }}",
  "eventStatus": "https://schema.org/EventScheduled",
  @if($data->type === 'online')
  "location": {
    "@type": "VirtualLocation",
    "url": {!! json_encode($data->url ?: url()->current()) !!}
  },
  @else
  "location": {
    "@type": "Place",
    "name": {!! json_encode($data->address) !!},
    "address": {!! json_encode($data->address) !!}
    @if($data->lat && $data->lang)
    ,
    "geo": {
      "@type": "GeoCoordinates",
      "latitude": {{ $data->lat }},
      "longitude": {{ $data->lang }}
    }
    @endif
  },
  @endif
  "image": [
    "{{ url('images/upload/' . $data->image) }}"
  ],
  "description": {!! json_encode($seoDescription) !!},
  "offers": {
    "@type": "Offer",
    "url": "{{ url()->current() }}",
    "price": "{{ $seoLowestPrice }}",
    "priceCurrency": "USD",
    "availability": "{{ $seoAvailability }}",
    "validFrom": "{{ Carbon\Carbon::parse($data->start_time)->format('Y-m-d\TH:iP') }}"
  },
  "organizer": {
    "@type": "Organization",
    "name": {!! json_encode($seoOrganizerName) !!},
    "url": "{{ url('/') }}"
  }
}
</script>
<!-- Swiper CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.css" />

<!-- Swiper JS -->
<script src="https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.js"></script>

    <style>
    .btn1 {
  outline: 0;
  display: inline-flex;
  align-items: center;
  justify-content: space-between;
  background: #ff0000;
  min-width: 200px;
  border: 0;
  border-radius: 4px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, .1);
  box-sizing: border-box;
  padding: 16px 20px;
  color: #fff;
  font-size: 12px;
  font-weight: 600;
  letter-spacing: 1.2px;
  text-transform: uppercase;
  overflow: hidden;
  cursor: pointer;
}



.btn1:hover {
  opacity: .95;
}

.btn1 .animation {
  border-radius: 100%;
  animation: ripple 0.6s linear infinite;
}

@keyframes ripple {
  0% {
    box-shadow: 0 0 0 0 rgba(255, 255, 255, 0.1), 0 0 0 20px rgba(255, 255, 255, 0.1), 0 0 0 40px rgba(255, 255, 255, 0.1), 0 0 0 60px rgba(255, 255, 255, 0.1);
  }

  100% {
    box-shadow: 0 0 0 20px rgba(255, 255, 255, 0.1), 0 0 0 40px rgba(255, 255, 255, 0.1), 0 0 0 60px rgba(255, 255, 255, 0.1), 0 0 0 80px rgba(255, 255, 255, 0);
  }
}
 .slider-container {
    max-width: 100%;
    overflow: hidden;
    position: relative;
  }

  .slider {
    display: flex;
    transition: transform 0.5s ease-in-out;
  }

  .slide {
    flex: 0 0 calc(100% - 40px); /* Adjust the space between images here */
    max-width: calc(100% - 40px); /* Adjust the space between images here */
    margin: 0 20px; /* Adjust the space between images here */
    opacity: 0;
    transition: opacity 0.5s ease-in-out;
  }

  .slide.active {
    opacity: 1;
  }
  .no-cursor {
    pointer-events: none;
    cursor: not-allowed;
  }

  .seat-selection-section {
    border-top: 1px solid #e5e7eb;
    padding-top: 1rem;
    margin-top: 1rem;
  }

  .seat-dropdown {
    font-family: 'Poppins', sans-serif;
    background-color: #f9fafb;
    border: 1px solid #d1d5db;
    color: #374151;
    font-size: 0.9rem;
    transition: all 0.2s ease-in-out;
  }

  .seat-dropdown:focus {
    outline: none;
    border-color: #ff0000;
    box-shadow: 0 0 0 3px rgba(255, 0, 0, 0.1);
  }

  .seat-dropdown:hover {
    border-color: #9ca3af;
  }

  .seat-warning {
    background-color: #fef2f2;
    border: 1px solid #fecaca;
    border-radius: 4px;
    padding: 8px;
  }

  .seat-unavailable-warning {
    background-color: #fef2f2;
    border: 1px solid #fecaca;
    border-radius: 4px;
    padding: 8px;
    color: #ff0000;
    font-size: 0.9em;
    text-align: center;
    margin-bottom: 10px;
  }

  .seat-dropdown option:disabled {
    color: #999;
    background-color: #f5f5f5;
    font-style: italic;
  }

  .seat-dropdown option[disabled] {
    color: #999 !important;
    background-color: #f5f5f5 !important;
  }

    .ticket-option-card {
      display: flex;
      flex-direction: column;
    }

    .ticket-option-body {
      margin-bottom: 0 !important;
    }

    .ticket-option-action {
      bottom: auto !important;
      margin-top: 1rem;
      position: static !important;
      width: 100% !important;
    }

    .ticket-option-action > .mt-7 {
      margin-top: 0 !important;
    }
    </style>

    <style>
    :root { --ed-accent: var(--primary_color, #dc2626); }

    .event-page-wrap { max-width: 1280px; margin: 0 auto; padding: 0 24px; display: flex; flex-direction: column; }

    /* ---- Hero: dark, poster + info ---- */
    .event-hero-v2 { background: linear-gradient(135deg, #0d0d12 0%, #2b1215 55%, #4a161b 100%); padding: 32px 0 64px; }
    .event-hero-inner { display: grid; grid-template-columns: 260px minmax(0, 1fr); gap: 32px; align-items: center; }
    .event-hero-poster {
        border-radius: 14px;
        overflow: hidden;
        box-shadow: 0 16px 40px rgba(0,0,0,0.5), 0 0 70px 6px rgba(220,38,38,0.35), 0 0 130px 30px rgba(220,38,38,0.18);
        border: 1px solid rgba(255,255,255,0.08);
        background: #14161f;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .event-hero-poster img {
        display: block;
        width: 100%;
        height: auto;
        max-height: 380px;
        object-fit: contain;
    }
    .event-hero-badge { display: inline-block; background: var(--ed-accent); color: #ffffff; font-family: 'Poppins', sans-serif; font-weight: 600; font-size: 12px; padding: 6px 14px; border-radius: 6px; margin-bottom: 14px; }
    .event-hero-title { color: #ffffff; font-family: 'Poppins', sans-serif; font-weight: 700; font-size: clamp(24px, 3vw, 36px); line-height: 1.25; margin: 0 0 14px; }
    .event-hero-desc { color: rgba(255,255,255,0.72); font-size: 15px; line-height: 1.6; margin: 0 0 22px; max-width: 640px; }
    .event-hero-meta { display: flex; flex-wrap: wrap; gap: 24px; margin-bottom: 26px; }
    .event-hero-meta-item { display: flex; align-items: center; gap: 10px; color: rgba(255,255,255,0.85); font-size: 14px; }
    .event-hero-meta-item i { color: var(--ed-accent); font-size: 16px; width: 18px; text-align: center; }
    .event-hero-actions { display: flex; gap: 12px; flex-wrap: wrap; }
    .event-hero-btn-primary, .event-hero-btn-secondary {
        display: inline-flex; align-items: center; gap: 8px; font-family: 'Poppins', sans-serif; font-weight: 600; font-size: 14px;
        padding: 12px 22px; border-radius: 8px; text-decoration: none; cursor: pointer; border: none;
    }
    .event-hero-btn-primary { background: var(--ed-accent); color: #ffffff; }
    .event-hero-btn-primary:hover { opacity: 0.92; color: #ffffff; }
    .event-hero-btn-secondary { background: transparent; color: #ffffff; border: 1px solid rgba(255,255,255,0.35); }
    .event-hero-btn-secondary:hover { background: rgba(255,255,255,0.08); color: #ffffff; }

    @media (max-width: 768px) {
        .event-hero-inner { grid-template-columns: 1fr; }
        .event-hero-poster { max-width: 280px; margin: 0 auto; }
        .event-hero-badge { display: block; width: fit-content; margin-left: auto; margin-right: auto; }
        .event-hero-title, .event-hero-desc, .event-hero-meta, .event-hero-actions { text-align: center; }
        .event-hero-title { font-size: clamp(15px, 5.6vw, 22px); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .event-hero-meta { justify-content: center; }
        .event-hero-actions { justify-content: center; }
    }

    /* ---- Tickets section: two-column, pulled up over hero ---- */
    .tickets-section { display: grid; grid-template-columns: minmax(0, 1fr) 340px; gap: 20px; margin-top: -44px; margin-bottom: 24px; align-items: start; }
    .tickets-main, .tickets-summary-card { background: #ffffff; border-radius: 14px; box-shadow: 0 12px 32px rgba(15,23,42,0.12); border: 1px solid #f1f5f9; }
    .tickets-main { padding: 20px; }
    .tickets-summary-card { padding: 20px; position: sticky; top: 20px; }
    .tickets-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; margin-bottom: 14px; flex-wrap: wrap; }
    .tickets-header-title { display: flex; align-items: center; gap: 10px; }
    .tickets-header-icon { width: 34px; height: 34px; border-radius: 8px; background: rgba(220,38,38,0.08); color: var(--ed-accent); display: flex; align-items: center; justify-content: center; font-size: 15px; flex: none; }
    .tickets-header h2 { font-family: 'Poppins', sans-serif; font-weight: 700; font-size: 19px; color: #0f172a; margin: 0; }
    .tickets-header p { font-family: 'Poppins', sans-serif; font-size: 13px; color: #64748b; margin: 2px 0 0; }

    .ticket-list-flex { display: flex; flex-direction: column; gap: 12px; }

    .ticket-row {
        display: flex; align-items: center; gap: 14px; padding: 14px; border: 1.5px solid #e2e8f0; border-radius: 10px; margin-bottom: 10px;
        transition: border-color 0.15s ease, background 0.15s ease;
    }
    .ticket-row.is-selected { border-color: var(--ed-accent); background: rgba(220,38,38,0.03); }
    .ticket-row-checkbox-hidden { position: absolute; width: 1px; height: 1px; opacity: 0; pointer-events: none; }
    .ticket-row-action { display: flex; align-items: center; flex: none; }
    .ticket-row-icon { width: 38px; height: 38px; border-radius: 50%; background: #f1f5f9; color: #64748b; display: flex; align-items: center; justify-content: center; font-size: 15px; flex: none; }
    .ticket-row-body { flex: 1 1 auto; min-width: 0; }
    .ticket-row-name { font-family: 'Poppins', sans-serif; font-weight: 600; font-size: 15px; color: #0f172a; margin: 0; }
    .ticket-row-desc { font-family: 'Poppins', sans-serif; font-size: 12.5px; color: #94a3b8; margin: 2px 0 0; }
    .ticket-row-price { text-align: right; flex: none; padding: 0 4px; white-space: nowrap; }
    .ticket-row-price strong { font-family: 'Poppins', sans-serif; font-weight: 700; font-size: 15px; color: #0f172a; }
    .ticket-row-price span { font-size: 11px; color: #94a3b8; margin-left: 4px; }
    .ticket-stepper { display: flex; align-items: center; gap: 10px; flex: none; }
    .ticket-stepper button {
        width: 28px; height: 28px; border-radius: 8px; border: 1px solid #cbd5e1; background: #ffffff; color: #334155;
        font-size: 16px; line-height: 1; cursor: pointer; display: flex; align-items: center; justify-content: center;
    }
    .ticket-stepper button:disabled { opacity: 0.35; cursor: not-allowed; }
    .ticket-stepper button:not(:disabled):hover { border-color: var(--ed-accent); color: var(--ed-accent); }
    .ticket-stepper .ticket-qty-value { min-width: 18px; text-align: center; font-family: 'Poppins', sans-serif; font-weight: 600; font-size: 14px; }
    .ticket-row-status { font-family: 'Poppins', sans-serif; font-size: 13px; font-weight: 600; padding: 6px 12px; border-radius: 999px; flex: none; }

    .ticket-select-btn {
        flex: none; font-family: 'Poppins', sans-serif; font-weight: 600; font-size: 13px; padding: 9px 18px;
        border-radius: 8px; border: 1.5px solid #cbd5e1; background: #ffffff; color: #334155; cursor: pointer;
        transition: border-color 0.15s ease, background 0.15s ease, color 0.15s ease;
    }
    .ticket-select-btn:hover:not(:disabled):not(.is-active) { border-color: var(--ed-accent); color: var(--ed-accent); }
    .ticket-select-btn.is-active { background: var(--ed-accent); border-color: var(--ed-accent); color: #ffffff; }
    .ticket-select-btn:disabled { opacity: 0.5; cursor: not-allowed; }

    .tickets-trust-strip { display: flex; flex-wrap: wrap; justify-content: center; gap: 16px 28px; margin-top: 16px; padding-top: 16px; border-top: 1px solid #f1f5f9; }
    .tickets-trust-item { display: flex; align-items: center; gap: 8px; font-size: 12.5px; color: #64748b; }
    .tickets-trust-item i { color: var(--ed-accent); font-size: 15px; }
    .tickets-trust-item strong { display: block; color: #334155; font-size: 12.5px; }

    .tickets-summary-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px; }
    .tickets-summary-head h3 { font-family: 'Poppins', sans-serif; font-weight: 700; font-size: 16px; color: #0f172a; margin: 0; }
    .tickets-summary-badge { width: 30px; height: 30px; border-radius: 8px; background: rgba(220,38,38,0.08); color: var(--ed-accent); display: flex; align-items: center; justify-content: center; font-size: 13px; }
    .tickets-summary-empty { text-align: center; padding: 22px 8px; color: #94a3b8; }
    .tickets-summary-empty i { font-size: 30px; color: #e2e8f0; margin-bottom: 8px; display: block; }
    .tickets-summary-empty p { font-size: 12.5px; margin: 2px 0 0; }
    .tickets-summary-list { display: none; flex-direction: column; gap: 8px; margin-bottom: 12px; }
    .tickets-summary-row { display: flex; justify-content: space-between; font-size: 13px; color: #334155; }
    .tickets-summary-row span:last-child { font-weight: 600; color: #0f172a; }

    #form_book { border-radius: 10px !important; letter-spacing: 0.2px; margin-top: 14px; transition: transform 0.1s ease, box-shadow 0.15s ease; }
    #form_book:not(:disabled):hover { box-shadow: 0 6px 16px rgba(220, 38, 38, 0.25); }
    .tickets-summary-secure { display: flex; align-items: center; gap: 6px; justify-content: center; font-size: 11.5px; color: #94a3b8; margin-top: 10px; }

    @media (max-width: 1024px) {
        .tickets-section { grid-template-columns: 1fr; margin-top: -28px; }
        .tickets-summary-card { position: static; }
    }

    @media (max-width: 520px) {
        .ticket-row { gap: 8px; padding: 12px; }
        .ticket-row-name { font-size: 13.5px; }
        .ticket-row-desc { display: none; }
        .ticket-row-price { padding: 0; }
        .ticket-row-price strong { font-size: 13.5px; }
        .ticket-row-price span { display: none; }
        .ticket-select-btn { padding: 8px 14px; font-size: 12.5px; }
    }

    /* ---- Info cards row + venue/organizer row ---- */
    .info-cards-row, .venue-organizer-row { display: grid; gap: 20px; margin-bottom: 24px; align-items: start; }
    .info-cards-row { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    .venue-organizer-row { grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); }
    .info-card { background: #ffffff; border-radius: 14px; box-shadow: 0 4px 16px rgba(15,23,42,0.06); border: 1px solid #f1f5f9; padding: 18px; min-width: 0; }
    .info-card-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px; }
    .organizer-logo { width: 48px; height: 48px; border-radius: 50%; object-fit: cover; flex: none; }
    .info-card-head h3 { font-family: 'Poppins', sans-serif; font-weight: 700; font-size: 16px; color: #0f172a; margin: 0; }
    .info-card-head a { font-size: 12.5px; color: var(--ed-accent); font-weight: 600; text-decoration: none; }
    .info-card p { font-size: 13.5px; color: #64748b; line-height: 1.65; }

    .info-card-about-text { margin: 0; }
    .info-card-readmore {
        background: none; border: none; padding: 0; margin-top: 8px; font-family: 'Poppins', sans-serif;
        font-size: 12.5px; font-weight: 600; color: var(--ed-accent); cursor: pointer;
    }
    .info-card-readmore:hover { text-decoration: underline; }

    /* ---- About the Event: "Read More" modal ---- */
    .about-modal {
        display: none; position: fixed; inset: 0; z-index: 1200; align-items: center; justify-content: center;
        background: rgba(15, 23, 42, 0.6); padding: 20px;
    }
    .about-modal.is-open { display: flex; }
    .about-modal-dialog {
        background: #ffffff; border-radius: 16px; max-width: 640px; width: 100%; max-height: 85vh;
        overflow-y: auto; padding: 32px; position: relative; box-shadow: 0 24px 60px rgba(0,0,0,0.35);
    }
    .about-modal-close {
        position: absolute; top: 14px; right: 14px; width: 34px; height: 34px; border-radius: 50%;
        border: none; background: rgba(15, 23, 42, 0.08); color: #334155; font-size: 20px; line-height: 1;
        cursor: pointer; display: flex; align-items: center; justify-content: center;
    }
    .about-modal-close:hover { background: rgba(15, 23, 42, 0.15); }
    .about-modal-title {
        font-family: 'Poppins', sans-serif; font-weight: 700; font-size: 20px; color: #0f172a;
        margin: 0 0 16px; padding-right: 30px; line-height: 1.35;
    }
    .about-modal-body { font-size: 14px; color: #475569; line-height: 1.75; text-align: justify; }
    @media (max-width: 640px) { .about-modal-dialog { padding: 24px; max-height: 90vh; } }

    .swiper-single .swiper-button-next,
    .swiper-single .swiper-button-prev,
    .swiper-single .swiper-pagination { display: none !important; }

    .gallery-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 8px; }
    .gallery-tile { border-radius: 10px; overflow: hidden; aspect-ratio: 1 / 1; cursor: pointer; background: #f1f5f9; }
    .gallery-tile img { width: 100%; height: 100%; object-fit: cover; display: block; transition: transform 0.2s ease; }
    .gallery-tile:hover img { transform: scale(1.05); }

    @media (max-width: 1024px) { .info-cards-row { grid-template-columns: 1fr 1fr; } }
    @media (max-width: 640px) { .info-cards-row, .venue-organizer-row { grid-template-columns: 1fr; } }

    /* ---- Floating quick-access ticket button ---- */
    .quick-tickets-fab {
        position: fixed; right: 20px; bottom: 20px; z-index: 40; display: inline-flex; align-items: center; gap: 8px;
        background: var(--ed-accent); color: #ffffff; font-family: 'Poppins', sans-serif; font-weight: 600; font-size: 14px;
        padding: 12px 18px; border-radius: 999px; box-shadow: 0 8px 20px rgba(220, 38, 38, 0.35); text-decoration: none;
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }
    .quick-tickets-fab:hover { transform: translateY(-2px); box-shadow: 0 10px 24px rgba(220, 38, 38, 0.4); color: #ffffff; }
    @media (min-width: 1025px) { .quick-tickets-fab { display: none; } }
    @media (max-width: 640px) {
        .quick-tickets-fab { left: 16px; right: 16px; bottom: 16px; width: auto; justify-content: center; padding: 14px 18px; font-size: 15px; border-radius: 12px; }
    }

    /* ---- In-page ticket flow modal (seat selection / checkout) ---- */
    .ticket-flow-modal { display: none; position: fixed; inset: 0; z-index: 100; }
    .ticket-flow-modal.is-open { display: block; }
    .ticket-flow-modal-backdrop { position: absolute; inset: 0; background: rgba(15, 23, 42, 0.6); }
    .ticket-flow-modal-panel {
        position: absolute; top: 3vh; left: 50%; transform: translateX(-50%);
        width: min(1100px, 94vw); max-height: 94vh; background: #ffffff; border-radius: 16px;
        box-shadow: 0 24px 60px rgba(0,0,0,0.35); overflow: hidden; display: flex; flex-direction: column;
    }
    .ticket-flow-modal-close {
        position: absolute; top: 12px; right: 12px; z-index: 2; width: 34px; height: 34px; border-radius: 50%;
        border: none; background: rgba(15, 23, 42, 0.08); color: #334155; font-size: 20px; line-height: 1;
        cursor: pointer; display: flex; align-items: center; justify-content: center;
    }
    .ticket-flow-modal-close:hover { background: rgba(15, 23, 42, 0.15); }
    .ticket-flow-modal-body { flex: 1 1 auto; width: 100%; min-height: 0; overflow-y: auto; }
    .ticket-flow-modal-loading {
        display: flex; flex-direction: column; align-items: center; justify-content: center;
        gap: 12px; height: 100%; min-height: 260px; color: #64748b; font-family: 'Poppins', sans-serif; font-size: 14px;
    }
    .ticket-flow-modal-spinner {
        width: 32px; height: 32px; border-radius: 50%; border: 3px solid #e2e8f0; border-top-color: var(--ed-accent, #dc2626);
        animation: ticketFlowSpin 0.8s linear infinite;
    }
    @keyframes ticketFlowSpin { to { transform: rotate(360deg); } }

    /* ---- Booking failed / cancelled state ---- */
    .booking-failed-wrap {
        display: flex; flex-direction: column; align-items: center; justify-content: center;
        text-align: center; padding: 48px 24px; background: #ffffff; min-height: 100%;
    }
    .booking-failed-lottie { width: 180px; height: 160px; }
    .booking-failed-title {
        color: #dc2626; font-family: 'Poppins', sans-serif; font-weight: 700; font-size: 24px; margin: 8px 0 8px;
    }
    .booking-failed-text {
        color: #64748b; font-family: 'Poppins', sans-serif; font-size: 15px; max-width: 420px; margin: 0 0 28px;
    }
    .booking-failed-close-btn {
        background: var(--ed-accent, #dc2626); color: #ffffff; font-family: 'Poppins', sans-serif; font-weight: 600;
        font-size: 15px; padding: 12px 32px; border: none; border-radius: 10px; cursor: pointer;
    }
    .booking-failed-close-btn:hover { opacity: 0.92; }

    @media (max-width: 768px) {
        .ticket-flow-modal-panel { top: 0; left: 0; transform: none; width: 100vw; height: 100vh; max-height: 100vh; border-radius: 0; }
    }
    </style>

@php
    $heroStartTime = Carbon\Carbon::parse($data->start_time);
    $heroEndTime = Carbon\Carbon::parse($data->end_time);
    $heroOrganizerName = isset($organizations) && $organizations->count() > 0 ? $organizations->first()->organization_name : null;
    $heroDescriptionExcerpt = Str::limit(trim(strip_tags($data->description)), 180);
@endphp
<section class="event-hero-v2">
    <div class="event-page-wrap">
        <div class="event-hero-inner">
            <div class="event-hero-poster">
                <img src="{{ url('images/upload/' . $data->image) }}" alt="{{ $data->name }}">
            </div>
            <div>
                @if($heroOrganizerName)
                    <span class="event-hero-badge">{{ $heroOrganizerName }} {{ __('Presents') }}</span>
                @endif
                <p class="event-hero-title">{{ $data->name }}</p>
                @if($heroDescriptionExcerpt)
                    <p class="event-hero-desc">{{ $heroDescriptionExcerpt }}</p>
                @endif
                <div class="event-hero-meta">
                    <div class="event-hero-meta-item">
                        <i class="fa fa-calendar" aria-hidden="true"></i>
                        <span>{{ $heroStartTime->format('D, M d Y') }} &middot; {{ $heroStartTime->format('h:i A') }} - {{ $heroEndTime->format('h:i A') }}</span>
                    </div>
                    <div class="event-hero-meta-item">
                        <i class="fa fa-map-marker" aria-hidden="true"></i>
                        <span>{{ $data->type == 'online' ? __('Online Event') : $data->address }}</span>
                    </div>
                    
                </div>
                <div class="event-hero-actions">
                    <a href="#tickets" class="event-hero-btn-primary">
                        <i class="fa fa-ticket" aria-hidden="true"></i> {{ __('Book Tickets') }}
                    </a>
                    <button type="button" class="event-hero-btn-secondary" onclick="shareEventPage()">
                        <i class="fa fa-share-alt" aria-hidden="true"></i> {{ __('Share Event') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</section>

<a href="#tickets" class="quick-tickets-fab">
    <i class="fa fa-ticket"></i> {{ __('Get Tickets') }}
</a>

<div class="ticket-flow-modal" id="ticketFlowModal">
    <div class="ticket-flow-modal-backdrop" onclick="closeTicketFlowModal()"></div>
    <div class="ticket-flow-modal-panel">
        <button type="button" class="ticket-flow-modal-close" onclick="closeTicketFlowModal()" aria-label="{{ __('Close') }}">&times;</button>
        <div class="ticket-flow-modal-body" id="ticketFlowModalBody">
            <div class="ticket-flow-modal-loading">
                <span class="ticket-flow-modal-spinner"></span>
                <span>{{ __('Loading seat map...') }}</span>
            </div>
        </div>
    </div>
</div>

<script>
    function openTicketFlowModal() {
        document.getElementById('ticketFlowModal').classList.add('is-open');
        document.body.style.overflow = 'hidden';
    }

    function reallyCloseTicketFlowModal() {
        document.getElementById('ticketFlowModal').classList.remove('is-open');
        document.body.style.overflow = '';
        if (window.__venueSeatMapPoll) {
            clearInterval(window.__venueSeatMapPoll);
            window.__venueSeatMapPoll = null;
        }
        document.getElementById('ticketFlowModalBody').innerHTML =
            '<div class="ticket-flow-modal-loading"><span class="ticket-flow-modal-spinner"></span><span>{{ __("Loading...") }}</span></div>';
    }

    function closeTicketFlowModal() {
        var body = document.getElementById('ticketFlowModalBody');
        var isMidBooking = body && body.querySelector('.checkout-modal-scope, .venue-seat-modal-inner');

        if (isMidBooking) {
            showBookingFailedInModal();
            return;
        }

        reallyCloseTicketFlowModal();
    }

    function showBookingFailedInModal() {
        if (window.__venueSeatMapPoll) {
            clearInterval(window.__venueSeatMapPoll);
            window.__venueSeatMapPoll = null;
        }

        var body = document.getElementById('ticketFlowModalBody');
        body.innerHTML =
            '<div class="booking-failed-wrap">' +
                '<div class="booking-failed-lottie" id="bookingFailedLottie"></div>' +
                '<div class="booking-failed-title">{{ __("Booking Failed") }}</div>' +
                '<div class="booking-failed-text">{{ __("Your booking was not completed and no payment was taken. Feel free to try again whenever you\'re ready.") }}</div>' +
                '<button type="button" class="booking-failed-close-btn" onclick="reallyCloseTicketFlowModal()">{{ __("Close") }}</button>' +
            '</div>';

        var container = document.getElementById('bookingFailedLottie');

        function startLottie() {
            if (typeof lottie === 'undefined' || !container) return;
            try {
                lottie.loadAnimation({
                    container: container,
                    renderer: 'svg',
                    loop: false,
                    autoplay: true,
                    path: '{{ asset('animations/booking-failed.json') }}'
                });
            } catch (e) {}
        }

        if (typeof lottie !== 'undefined') {
            startLottie();
        } else {
            var script = document.createElement('script');
            script.src = '{{ asset('js/lottie.min.js') }}';
            script.onload = startLottie;
            document.head.appendChild(script);
        }
    }

    function loadContentIntoModal(url, loadingText, errorText) {
        openTicketFlowModal();

        const modalBody = document.getElementById('ticketFlowModalBody');
        modalBody.innerHTML = '<div class="ticket-flow-modal-loading"><span class="ticket-flow-modal-spinner"></span><span>' + loadingText + '</span></div>';

        return fetch(url, { headers: { 'Accept': 'text/html' } })
            .then(function (response) {
                if (!response.ok) {
                    return response.json()
                        .catch(function () { return {}; })
                        .then(function (data) {
                            throw new Error((data && data.message) || errorText);
                        });
                }
                return response.text();
            })
            .then(function (html) {
                modalBody.innerHTML = html;

                // innerHTML-injected <script> tags do not auto-execute - recreate them manually.
                Array.from(modalBody.querySelectorAll('script')).forEach(function (oldScript) {
                    const newScript = document.createElement('script');
                    Array.from(oldScript.attributes).forEach(function (attr) {
                        newScript.setAttribute(attr.name, attr.value);
                    });
                    newScript.textContent = oldScript.textContent;
                    oldScript.parentNode.replaceChild(newScript, oldScript);
                });
            })
            .catch(function (error) {
                modalBody.innerHTML = '<div class="ticket-flow-modal-loading"><span>' +
                    (error.message || errorText) + '</span></div>';
                throw error;
            });
    }

    function loadSeatMapIntoModal(seatMapPartialUrl) {
        loadContentIntoModal(
            seatMapPartialUrl,
            '{{ __("Loading seat map...") }}',
            '{{ __("Unable to load seat selection for this event.") }}'
        );
    }

    function loadCheckoutIntoModal() {
        return loadContentIntoModal(
            '{{ route("checkout.contentPartial") }}',
            '{{ __("Loading checkout...") }}',
            '{{ __("Unable to load checkout. Please try again.") }}'
        );
    }
    window.loadCheckoutIntoModal = loadCheckoutIntoModal;

    function loadOrderSuccessIntoModal(orderId) {
        const url = '{{ url("order-success-partial") }}/' + encodeURIComponent(orderId);
        return loadContentIntoModal(
            url,
            '{{ __("Finishing up...") }}',
            '{{ __("Unable to load your order confirmation. Please check your email for details.") }}'
        );
    }
    window.loadOrderSuccessIntoModal = loadOrderSuccessIntoModal;

    // Returning from hosted Stripe Checkout: reopen the modal here and show the
    // success celebration inline instead of landing on a separate page.
    (function () {
        const params = new URLSearchParams(window.location.search);
        const orderId = params.get('order_success');
        if (!orderId) {
            return;
        }

        params.delete('order_success');
        const cleanedSearch = params.toString();
        const cleanedUrl = window.location.pathname + (cleanedSearch ? '?' + cleanedSearch : '') + window.location.hash;
        window.history.replaceState({}, document.title, cleanedUrl);

        loadOrderSuccessIntoModal(orderId);
    })();

    function shareEventPage() {
        const shareData = { title: @json($data->name), url: window.location.href };
        if (navigator.share) {
            navigator.share(shareData).catch(function () {});
        } else if (navigator.clipboard) {
            navigator.clipboard.writeText(window.location.href).then(function () {
                alert('{{ __("Event link copied to clipboard.") }}');
            });
        }
    }
</script>
    {{-- content --}}
    <div class="pb-20 bg-scroll min-h-screen" style="background-image: url('https://teptix.com/images/events.png')">
        {{-- scroll --}}
        <div class="mr-4 flex justify-end z-30">
            <a type="button" href="{{ url('#') }}"
                class="scroll-up-button bg-primary rounded-full p-4 fixed z-20  2xl:mt-[49%] xl:mt-[59%] xlg:mt-[68%] lg:mt-[75%] xxmd:mt-[83%] md:mt-[90%]
                xmd:mt-[90%] sm:mt-[117%] msm:mt-[125%] xsm:mt-[160%]">
                <img src="{{ asset('images/downarrow.png') }}" alt="" class="w-3 h-3 z-20">
            </a>
        </div>
        <div
            class="container mt-5 3xl:mx-52 2xl:mx-28 1xl:mx-28 xl:mx-36 xlg:mx-32 lg:mx-36 xxmd:mx-24 xmd:mx-32 md:mx-28 sm:mx-20 msm:mx-16 xsm:mx-10 xxsm:mx-5 z-10 relative">
            <div class="event-page-wrap">
                @if (Auth::guard('appuser')->user())
                    <div class="hidden">
                        @if (Str::contains($appUser->favorite, $data->id))
                            <a href="javascript:void(0);" class="like" onclick="addFavorite('{{ $data->id }}','{{ 'event' }}')">
                                <img src="{{ url('images/heart-fill.svg') }}" alt="" class="object-cover bg-cover fillLike bg-white-light p-2 rounded-lg">
                            </a>
                        @else
                            <a href="javascript:void(0);" class="like" onclick="addFavorite('{{ $data->id }}','{{ 'event' }}')">
                                <img src="{{ url('images/heart.svg') }}" alt="" class="object-cover bg-cover fillLike bg-white-light p-2 rounded-lg">
                            </a>
                        @endif
                    </div>
                @endif
                <div class="hidden">
                    <div class="">
                        <p class="font-poppins font-semibold text-3xl leading-9 text-black">{{ $data->name }}</p>
                                @if ($data->rate > 1)
                                    <div class="flex space-x-2 pt-3 ">
                                        @for ($i = 1; $i <= $data->rate; $i++)
                                            <img src="{{ asset('images/star-fill.png') }}" alt="">
                                        @endfor
                                        @for ($i = 5; $i > $data->rate; $i--)
                                            <img src="{{ asset('images/star.png') }}" alt="">
                                        @endfor
                                    </div>
                                @endif
                            </div>
                    </div>
                <div class="trailing-info-wrapper" style="order:2;">
                    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.css" />
                    @php
                        $eventVideos = $data->videos->first();
                        $videoLinks = $eventVideos && $eventVideos->links ? $eventVideos->links : [];
                    @endphp
                    <section class="info-cards-row">
                        @php
                            $aboutPlainText = trim(preg_replace('/\s+/', ' ', strip_tags($data->description ?? '')));
                            $aboutIsLong = strlen($aboutPlainText) > 220;
                            $aboutExcerpt = $aboutIsLong ? Str::limit($aboutPlainText, 220) : $aboutPlainText;
                        @endphp
                        <div class="info-card reveal-up">
                            <div class="info-card-head"><h3>{{ __('About the Event') }}</h3></div>
                            <p class="info-card-about-text" id="aboutExcerptText">{{ $aboutExcerpt }}</p>
                            @if($aboutIsLong)
                                <button type="button" class="info-card-readmore" onclick="openAboutModal()">{{ __('Read More') }}</button>
                            @endif
                            @if(count($tags) > 0)
                            <div class="flex flex-wrap gap-2 mt-3">
                                @foreach ($tags as $item)
                                    <a href="{{ url('/user/tag/' . $item) }}"
                                        class="px-3 py-1 text-success bg-success-light rounded-full font-poppins font-normal text-xs">{{ $item }}
                                    </a>
                                @endforeach
                            </div>
                            @endif
                        </div>

                        @if($aboutIsLong)
                            <div class="about-modal" id="aboutEventModal" role="dialog" aria-modal="true" aria-labelledby="aboutEventModalTitle" onclick="if(event.target===this)closeAboutModal()">
                                <div class="about-modal-dialog">
                                    <button type="button" class="about-modal-close" onclick="closeAboutModal()" aria-label="{{ __('Close') }}">&times;</button>
                                    <h3 id="aboutEventModalTitle" class="about-modal-title">{{ $data->name }}</h3>
                                    <div class="about-modal-body">{!! $data->description !!}</div>
                                </div>
                            </div>
                        @endif

                        <div class="info-card reveal-up">
                            <div class="info-card-head"><h3>{{ __('Trailer & Videos') }}</h3></div>
                            @if(!empty($videoLinks) && count($videoLinks) > 0)
                                <div class="swiper mySwiper {{ count($videoLinks) <= 1 ? 'swiper-single' : '' }}">
                                    <div class="swiper-wrapper">
                                        @foreach($videoLinks as $index => $videoLink)
                                            @if(!empty($videoLink))
                                                @php
                                                    $embedUrl = $videoLink;
                                                    if (strpos($videoLink, 'youtube.com') !== false || strpos($videoLink, 'youtu.be') !== false) {
                                                        preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/ ]{11})/i', $videoLink, $match);
                                                        if (isset($match[1])) {
                                                            $embedUrl = 'https://www.youtube.com/embed/' . $match[1];
                                                        }
                                                    } elseif (strpos($videoLink, 'vimeo.com') !== false) {
                                                        preg_match('/vimeo\.com\/(\d+)/i', $videoLink, $match);
                                                        if (isset($match[1])) {
                                                            $embedUrl = 'https://player.vimeo.com/video/' . $match[1];
                                                        }
                                                    }
                                                @endphp
                                                <div class="swiper-slide">
                                                    <iframe class="w-full rounded-md" style="height: 180px;"
                                                        src="{{ $embedUrl }}"
                                                        title="Video {{ $index + 1 }}"
                                                        frameborder="0"
                                                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                                        allowfullscreen></iframe>
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>
                                    @if(count($videoLinks) > 1)
                                        <div class="swiper-button-next"></div>
                                        <div class="swiper-button-prev"></div>
                                        <div class="swiper-pagination"></div>
                                    @endif
                                </div>
                            @else
                                <p>{{ __('No videos added for this event yet.') }}</p>
                            @endif
                        </div>

                        <div class="info-card reveal-up">
                            <div class="info-card-head"><h3>{{ __('Image Gallery') }}</h3></div>
                            <div class="gallery-grid">
                                <div class="gallery-tile" onclick="imagegallery('{{ $data->image }}')">
                                    <img src="{{ url('images/upload/' . $data->image) }}" alt="">
                                </div>
                                @foreach ($images as $item)
                                    @if (strlen($item) > 0)
                                        <div class="gallery-tile" onclick="imagegallery('{{ $item }}')">
                                            <img src="{{ url('images/upload/' . $item) }}" alt="{{ 'Event Image' }}">
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    </section>

                    <script>
                        function openAboutModal() {
                            const modal = document.getElementById('aboutEventModal');
                            if (!modal) {
                                return;
                            }
                            modal.classList.add('is-open');
                            document.body.style.overflow = 'hidden';
                        }

                        function closeAboutModal() {
                            const modal = document.getElementById('aboutEventModal');
                            if (!modal) {
                                return;
                            }
                            modal.classList.remove('is-open');
                            document.body.style.overflow = '';
                        }

                        document.addEventListener('keydown', function (e) {
                            if (e.key === 'Escape') {
                                closeAboutModal();
                            }
                        });
                    </script>

                    <section class="venue-organizer-row">
                        <div class="info-card reveal-up">
                            <div class="info-card-head">
                                <h3>{{ __('Venue') }}</h3>
                                @if($data->id != 42 && !empty($data->lat) && !empty($data->lang))
                                    <a href="javascript:void(0);" onclick="openGoogleMaps()">{{ __('Get Directions') }} &rarr;</a>
                                @endif
                            </div>
                            @if($data->id == 42)
                                <p class="text-red-500 font-bold">{{ __('Location To Be Coming Soon.') }}</p>
                            @elseif (!empty($data->lat) && !empty($data->lang))
                                <iframe
                                    width="100%"
                                    height="220"
                                    frameborder="0"
                                    style="border:0; border-radius: 10px;"
                                    loading="lazy"
                                    allowfullscreen
                                    referrerpolicy="no-referrer-when-downgrade"
                                    src="https://maps.google.com/maps?q={{ $data->lat }},{{ $data->lang }}&z=15&output=embed">
                                </iframe>
                            @else
                                <p class="text-red-500">{{ __('Location not available.') }}</p>
                            @endif
                        </div>

                        <div class="info-card reveal-up">
                            <div class="info-card-head"><h3>{{ __('Organized By') }}</h3></div>
                            @if(isset($organizations) && $organizations->count() > 0)
                                @foreach($organizations as $org)
                                    <a href="{{ route('organizationDetails', ['id' => $org->id]) }}" class="flex items-center gap-3 {{ !$loop->last ? 'mb-3' : '' }}">
                                        <img src="{{ url('images/upload/' . $org->image) }}" class="organizer-logo" alt="">
                                        <span class="font-poppins font-medium text-sm text-black">{{ $org->organization_name }}</span>
                                    </a>
                                @endforeach
                            @else
                                <p>{{ __('Organizer details not available.') }}</p>
                            @endif
                        </div>
                    </section>

                    @php $eventLogos = array_filter(explode(',', $data->event_logos ?? '')); @endphp
                    @if(count($eventLogos) > 0)
                    <div class="info-card reveal-up" style="margin-bottom:24px;">
                        <div class="info-card-head"><h3>{{ __('Sponsors & Partners') }}</h3></div>
                        <div class="flex flex-wrap gap-4 items-center justify-start">
                            @foreach($eventLogos as $logo)
                                @if(strlen(trim($logo)) > 0)
                                <div class="flex items-center justify-center p-2 border border-gray-200 rounded-lg bg-gray-50" style="width:100px;height:100px;">
                                    <img src="{{ url('images/upload/' . trim($logo)) }}"
                                         alt="{{ __('Sponsor Logo') }}"
                                         class="max-w-full max-h-full object-contain"
                                         style="max-width:88px;max-height:88px;">
                                </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                    @endif

                    @if($data->id == 19)
                    <div class="info-card reveal-up" style="margin-bottom:24px;">
                        <div class="info-card-head"><h3>{{ __('Venue Layout') }}</h3></div>
                        <iframe src="https://teptix.com/seat-representation.html" style="width:100%; height:500px; border:none; box-shadow: rgba(0, 0, 0, 0.4) 0px 2px 4px, rgba(0, 0, 0, 0.3) 0px 7px 13px -3px, rgba(0, 0, 0, 0.2) 0px -3px 0px inset;"></iframe>
                    </div>
                    @endif
                    @if($data->id == 54)
                    <div class="info-card reveal-up" style="margin-bottom:24px;">
                        <div class="info-card-head"><h3>{{ __('Venue Layout') }}</h3></div>
                        <img src="{{ asset('images/seatmaps.jpg') }}" alt="{{ __('Venue seat map') }}" class="w-full rounded-md" style="height:auto; box-shadow: rgba(0, 0, 0, 0.24) 0px 3px 8px;">
                    </div>
                    @endif
                </div>

                <script>
                  document.addEventListener('DOMContentLoaded', function () {
                    if (document.querySelector('.mySwiper') && typeof Swiper !== 'undefined') {
                      new Swiper('.mySwiper', {
                        slidesPerView: 1,
                        spaceBetween: 15,
                        loop: true,
                        autoplay: { delay: 3000, disableOnInteraction: false },
                        pagination: { el: '.swiper-pagination', clickable: true },
                        navigation: { nextEl: '.swiper-button-next', prevEl: '.swiper-button-prev' }
                      });
                    }
                  });
                </script>

            {{-- tickets --}}
            <form method="post" class="require-validation" action="{{ url('checkout') }}" style="order:1;"
                    data-cc-on-file="false" id="event-tickets-form" onsubmit="return handleFormSubmission()">
                    @csrf
                    <!-- Hidden inputs for seat selections -->
                    <div id="seat-data-container"></div>
            <section class="tickets-section" id="tickets">
                <div class="tickets-main">
                    <div class="tickets-header">
                        <div class="tickets-header-title">
                            <span class="tickets-header-icon"><i class="fa fa-ticket" aria-hidden="true"></i></span>
                            <div>
                                <h2>{{ __('Choose Your Tickets') }}</h2>
                                <p>{{ __('Select tickets from below') }}</p>
                            </div>
                        </div>
                    </div>
                <div class="ticket-list-flex">
                    {{-- Donation Card for Event 46 --}}
                    @if($data->id == 46)
                    <div class="relative rounded-lg border-2 p-5" style="border-color:#f59e0b;background:linear-gradient(135deg,#fff5eb 0%,#fed7aa 100%);box-shadow:0 8px 16px rgba(245,158,11,0.2);">
                        <div class="!h-auto" style="height:auto;margin-bottom:100px;">
                            <div style="display:flex;align-items:center;justify-content:center;gap:6px;margin-bottom:8px;flex-wrap:wrap;">
                                <span style="font-size:1.2rem;flex-shrink:0;">🙏</span>
                                <p class="font-poppins font-bold text-sm leading-5 text-center" style="color:#92400e;margin:0;">
                                    {{ __('Support Our Divine Celebration') }}
                                </p>
                                <span style="font-size:1.2rem;flex-shrink:0;">🙏</span>
                            </div>
                            <p class="font-poppins text-sm text-center mt-2" style="color:#1f2937;line-height:1.6;display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden;">
                                We warmly invite you to be part of this divine community celebration through your generous sponsorship and donations. Since this is a <strong>FREE event</strong> for all devotees and families, your support plays a vital role in helping us manage event production, décor, prasadam, food service, venue arrangements, audio-visual setup, hospitality, and overall event operations.
                            </p>
                            <div class="text-center mt-2">
                                <button type="button" onclick="document.getElementById('donationReadMoreModal').style.display='flex'" class="font-poppins text-sm font-semibold underline" style="background:none;border:none;cursor:pointer;color:#92400e;">
                                    read more...
                                </button>
                            </div>
                        </div>
                        <div class="absolute bottom-5" style="width:85%;bottom:2.4rem !important;">
                            <a href="https://donate.stripe.com/00w14oflc2Xt9POcbU77O02" style="text-decoration:none;">
                                <button type="button"
                                    class="font-poppins font-medium text-base leading-6 text-white w-full rounded-md py-3"
                                    style="background:#f59e0b;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:6px;">
                                    <svg xmlns="http://www.w3.org/2000/svg" style="width:16px;height:16px;flex-shrink:0;" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd"/>
                                    </svg>
                                    {{ __('Make a Donation') }}
                                </button>
                            </a>
                        </div>
                    </div>

                    {{-- Donation Read More Modal --}}
                    <div id="donationReadMoreModal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:9999;align-items:center;justify-content:center;">
                        <div style="background:#fff5eb;border:2px solid #f59e0b;border-radius:12px;max-width:520px;width:90%;padding:28px;position:relative;max-height:90vh;overflow-y:auto;">
                            <button type="button" onclick="document.getElementById('donationReadMoreModal').style.display='none'" style="position:absolute;top:12px;right:16px;background:none;border:none;font-size:1.5rem;cursor:pointer;color:#92400e;">&times;</button>
                            <div style="display:flex;align-items:center;justify-content:center;gap:8px;margin-bottom:14px;">
                                <span style="font-size:1.5rem;">🙏</span>
                                <h3 style="font-family:'Poppins',sans-serif;font-size:1.1rem;font-weight:700;color:#92400e;margin:0;">{{ __('Support Our Divine Celebration') }}</h3>
                                <span style="font-size:1.5rem;">🙏</span>
                            </div>
                            <p style="font-family:'Poppins',sans-serif;font-size:0.95rem;color:#1f2937;margin:0 0 20px;line-height:1.8;text-align:justify;">
                                We warmly invite you to be part of this divine community celebration through your generous sponsorship and donations. Since this is a <strong>FREE event</strong> for all devotees and families, your support plays a vital role in helping us manage event production, décor, prasadam, food service, venue arrangements, audio-visual setup, hospitality, and overall event operations. Together, let us create a spiritually uplifting and unforgettable experience for the community. 🙏
                            </p>
                            <a href="https://donate.stripe.com/00w14oflc2Xt9POcbU77O02" style="text-decoration:none;">
                                <button type="button"
                                    style="width:100%;background:#f59e0b;color:#fff;border:none;border-radius:8px;padding:12px 20px;font-size:1rem;font-weight:600;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px;">
                                    <svg xmlns="http://www.w3.org/2000/svg" style="width:18px;height:18px;flex-shrink:0;" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd"/>
                                    </svg>
                                    {{ __('Make a Donation') }}
                                </button>
                            </a>
                        </div>
                    </div>
                    @endif

                    {{-- Manual Dandiya Event-16 Ticket (if event ID is 16) --}}
                    @if($data->id == 16)
                        <div class="ticket-card relative rounded-lg border border-gray-light p-5">
                            <div class="!h-auto mb-5" style="height: auto;margin-bottom:100px;">
                                <div class="badge flex justify-center">
                                    <p class="font-poppins font-medium text-sm leading-4 text-danger text-center rounded-full bg-danger-light w-16 py-1">{{ __('Paid') }}</p>
                                </div>
                                <div class="ticket-title">
                                    <p class="font-poppins font-medium text-xl leading-7 text-primary text-center py-4">{{ __('Book Now') }}</p>
                                </div>

                                <div class="text-center mb-4">
                                    <span class="date-range font-poppins font-normal text-base leading-6 text-gray">
                                        {{ __('8 Sept 2025 - 4 Oct 2025') }}
                                    </span>
                                </div>
                            </div>

                            <div class="absolute bottom-5" style="width: 85%;bottom: 2.4rem !important;">
                                <div class="select-ticket w-full">
                                    <button type="button" onclick="handleDandiyaBookNow()"
                                            class="font-poppins font-medium text-lg leading-6 text-white w-full bg-primary hover:bg-primary-dark transition-colors duration-300 rounded-md py-3">
                                        <i class="fa fa-ticket mr-2"></i>{{ __('Book Now') }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Database Tickets --}}
                    @if (count($data->all_ticket) != 0)
                        @foreach ($data->all_ticket as $item)
                        @php
                            $nameLower = strtolower($item->name);
                            $ticketIcon = 'fa-ticket';
                            if (str_contains($nameLower, 'vip')) { $ticketIcon = 'fa-crown'; }
                            elseif (str_contains($nameLower, 'premium')) { $ticketIcon = 'fa-star'; }
                            elseif (str_contains($nameLower, 'family') || str_contains($nameLower, 'group') || str_contains($nameLower, 'pack')) { $ticketIcon = 'fa-users'; }
                            $priceUnitLabel = str_contains($nameLower, 'pack') ? __('per pack') : __('per ticket');
                            $ticketTagline = trim(strip_tags($item->description ?? ''));
                            $ticketTagline = $ticketTagline !== '' ? Str::limit($ticketTagline, 48) : ($item->type === 'paid' ? __('Standard seating') : __('Free admission'));
                        @endphp
                        <div class="ticket-row" id="ticket-row-{{ $item->id }}">
                            <span class="ticket-row-icon"><i class="fa {{ $ticketIcon }}" aria-hidden="true"></i></span>
                            <div class="ticket-row-body">
                                <p class="ticket-row-name">{!! $item->name!!}</p>
                                <p class="ticket-row-desc">{{ $ticketTagline }}</p>
                @php
                    $now = Carbon\Carbon::now();
                    $isSaleStarted = Carbon\Carbon::parse($item->start_time)->lte($now);
                    $isSaleEnded = Carbon\Carbon::parse($item->end_time)->lt($now);
                @endphp

                                @if($item->SeatTable_id && $isSaleStarted && !$isSaleEnded)
                                    <!-- Seat Selection Section -->
                                    <div class="mt-4 seat-selection-section" id="seat-section-{{ $item->id }}">
                                        <p class="font-poppins font-medium text-base leading-6 text-gray text-center mb-2">
                                            <b style="color:#ff0000;">{{ __('Select Your Table') }}</b>
                                        </p>

                                        @php
                                            // Get seat tables directly to avoid relationship issues
                                            $seatTables = collect([]);
                                            if (!empty($item->SeatTable_id)) {
                                                $seatTableIds = explode(',', $item->SeatTable_id);
                                                $seatTableIds = array_filter($seatTableIds); // Remove empty values
                                                $seatTables = \App\Models\SeatTable::whereIn('id', $seatTableIds)->get();
                                            }
                                        @endphp

                                        @if($seatTables->count() > 0)
                                            <!--<p class="font-poppins font-normal text-sm leading-5 text-gray text-center mb-3">-->
                                            <!--    {{ $seatTables->pluck('name_of_table')->join(', ') }}-->
                                            <!--</p>-->

                                            <div class="seat-selection-container">
                                                <select name="selected_seat_{{ $item->id }}" id="seat-select-{{ $item->id }}" class="seat-dropdown" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; margin-bottom: 10px;">
                                                    <option value="">{{ __('Choose a table...') }}</option>
                                                    @foreach($seatTables as $seatTable)
                                                        @php
                                                            $existingOrders = \App\Models\OrderChild::where('SeatDetails_id', $seatTable->sponsership_id)
                                                                ->where('seat_id', $seatTable->id)
                                                                ->count();

                                                            $availableSeats = $seatTable->number_seat - $existingOrders;

                                                            // Check for user_id == 8 (assumes each seatTable has user_id field)
                                                            if ($seatTable->sponsership_id == 8) {
                                                                $availableSeats *= 2;
                                                                $seatTable->number_seat *= 2;
                                                            }
                                                        @endphp

                                                        <option value="{{ $seatTable->id }}"
                                                            @if($availableSeats <= 0) disabled @endif
                                                            data-available-seats="{{ $availableSeats }}"
                                                            data-sponser-id="{{ $seatTable->sponsership_id }}">

                                                            {{ $seatTable->name_of_table }}
                                                            @if($seatTable->number_seat)
                                                                @if($availableSeats > 0)
                                                                    ({{ $availableSeats }}/{{ $seatTable->number_seat }} {{ __('available') }})
                                                                @else
                                                                    ({{ __('Sold Out') }})
                                                                @endif
                                                            @endif
                                                        </option>
                                                    @endforeach

                                                </select>

                                                <div class="seat-warning" id="seat-warning-{{ $item->id }}" style="display: none; color: #ff0000; font-size: 0.9em; text-align: center; margin-bottom: 10px;">
                                                    <i class="fa fa-exclamation-triangle"></i> {{ __('Please select a table before proceeding') }}
                                                </div>

                                                <div class="seat-unavailable-warning" id="seat-unavailable-warning-{{ $item->id }}" style="display: none; color: #ff0000; font-size: 0.9em; text-align: center; margin-bottom: 10px;">
                                                    <i class="fa fa-exclamation-triangle"></i> {{ __('All seats for this ticket are sold out') }}
                                                </div>
                                            </div>
                                        @else
                                            <p class="font-poppins font-normal text-sm leading-5 text-gray text-center mb-3">
                                                {{ __('Seat table information not available') }}
                                            </p>
                                        @endif
                                    </div>
                                @endif
                                <!-- <p class="font-poppins font-normal text-base leading-6 text-gray text-center" style="color:#ff0000;">
                                    <b>{{ __('Ticket Sales Onwards') }}</b>
                                </p> -->
                                <!-- <p class="font-poppins font-normal text-base leading-6 text-gray text-center">
                                    {{ Carbon\Carbon::parse($item->start_time)->format('d M Y') }} {{__('-')}}
                                    {{ Carbon\Carbon::parse($item->end_time)->format('d M Y') }}
                                </p> -->
                            </div>
                            <div class="ticket-row-price">
                                @if($data->id != 46)
                                    <strong>{{ $item->type=="paid" ? ($currency->currency_sybmol ?? '$') . number_format($item->price, 2) : __('Free') }}</strong>
                                    <span>{{ $item->type=="paid" ? $priceUnitLabel : '' }}</span>
                                @endif
                            </div>
                            <div class="ticket-row-action">
                                @php
                                    $now = Carbon\Carbon::now();
                                    $isSaleStarted = Carbon\Carbon::parse($item->start_time)->lte($now);
                                    $isSaleEnded = Carbon\Carbon::parse($item->end_time)->lt($now);
                                    $remainingDays = $now->diffInDays(Carbon\Carbon::parse($item->start_time), false);
                                @endphp
                                @if ($item->available_qty == 0)
                                    <div class="ticket-row-status" style="color:#dc2626;background:#fef2f2;">
                                        <span class="font-poppins font-medium text-base leading-6 text-red-600 py-3">
                                            <i class="fa fa-check-circle"></i> {{ __('Sold Out') }}
                                        </span>
                                    </div>
                                    <!-- @if($data->id == 46)
                                        <a href="https://forms.gle/VMmow2m8Zr9EpkHS9" target="_blank" rel="noopener"
                                            class="mt-3 w-full rounded-lg flex justify-center bg-primary text-white font-poppins font-medium text-base leading-6 py-3">
                                            {{ __('Join Waitlist') }}
                                        </a>
                                    @endif -->
                                @elseif ($isSaleEnded)
                                    <div class="ticket-row-status" style="color:#64748b;background:#f1f5f9;">
                                        <span class="font-poppins font-medium text-base leading-6 text-gray-600 py-3">
                                            {{ __('Sales End') }}
                                        </span>
                                    </div>
                                @elseif (!$isSaleStarted)
                                    <div class="ticket-row-status" style="color:#c2410c;background:#fff7ed;flex-direction:column;">
                                        <div class="font-poppins font-medium text-base leading-6 text-orange-600">
                                            <i class="fa fa-clock-o"></i> {{ $remainingDays }} {{ $remainingDays > 1 ? __('days to go') : __('day to go') }}
                                        </div>
                                        <div id="countdown-{{ $item->id }}" class="font-poppins font-normal text-sm text-orange-600 mt-1 countdown-timer">
                                            <span class="inline-block h-4 w-28 bg-orange-200 rounded animate-pulse"></span>
                                        </div>
                                    </div>
                                    <script>
                                        // Countdown timer for ticket {{ $item->id }}
                                        function updateCountdown{{ $item->id }}() {
                                            const startTime = new Date('{{ $item->start_time }}').getTime();
                                            const now = new Date().getTime();
                                            const distance = startTime - now;

                                            if (distance > 0) {
                                                const days = Math.floor(distance / (1000 * 60 * 60 * 24));
                                                const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                                                const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                                                const seconds = Math.floor((distance % (1000 * 60)) / 1000);

                                                document.getElementById('countdown-{{ $item->id }}').innerHTML =
                                                    hours.toString().padStart(2, '0') + 'h:' +
                                                    minutes.toString().padStart(2, '0') + 'm';
                                            } else {
                                                document.getElementById('countdown-{{ $item->id }}').innerHTML = '00h:00m';
                                            }
                                        }

                                        // Update immediately and then every second
                                        updateCountdown{{ $item->id }}();
                                        setInterval(updateCountdown{{ $item->id }}, 1000);
                                    </script>
                                @else
                                    <input type="checkbox" id="multitickets-{{ $item->id }}" class="ticket-row-checkbox-hidden" name="multitickets[]" value="{{$item->id}}"
                                           data-available-qty="{{ (int) ($item->available_qty ?? 0) }}"
                                           data-ticket-name="{{ $item->name }}"
                                           data-ticket-price="{{ $item->type === 'paid' ? number_format((float) $item->price, 2) : '0.00' }}"
                                           data-ticket-price-label="{{ $item->type === 'paid' ? ($currency->currency_sybmol ?? '$') . number_format($item->price, 2) : __('Free') }}"
                                           onchange="handleTicketSelection(this, {{$item->id}}, {{$item->SeatTable_id ? 'true' : 'false'}}); syncTicketRowStepper({{ $item->id }});" {{ $isSaleEnded || (int) ($item->available_qty ?? 0) <= 0 ? 'disabled' : '' }}>
                                    <button type="button" class="ticket-select-btn" id="ticket-select-btn-{{ $item->id }}"
                                            onclick="toggleTicketSelect({{ $item->id }})"
                                            {{ $isSaleEnded || (int) ($item->available_qty ?? 0) <= 0 ? 'disabled' : '' }}>
                                        {{ __('Select') }}
                                    </button>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    @endif

                    {{-- Show "No Tickets found" only if there are no database tickets AND it's not event 16 --}}
                    @if (count($data->all_ticket) == 0 && $data->id != 16 && (empty($liveVenueMap) || $venueSeatMapSeats->count() == 0))
                    <div class="mx-auro w-full">
                        <div class="px-5">
                            <img src="{{ url('frontend/images/empty.png') }}">
                            <h6 class="font-poopins  font-light  text-3xl leading-9 text-black px-5">
                                {{ __('No Tickets found') }}!
                            </h6>
                        </div>
                    </div>
                    @endif
                </div>

                    <div class="tickets-trust-strip">
                        <div class="tickets-trust-item"><i class="fa fa-shield" aria-hidden="true"></i><div><strong>{{ __('Secure Booking') }}</strong>{{ __('Your data is protected') }}</div></div>
                        <div class="tickets-trust-item"><i class="fa fa-credit-card" aria-hidden="true"></i><div><strong>{{ __('Safe Payments') }}</strong>{{ __('Trusted transactions') }}</div></div>
                        <div class="tickets-trust-item"><i class="fa fa-bolt" aria-hidden="true"></i><div><strong>{{ __('Instant Confirmation') }}</strong>{{ __('Get your tickets instantly') }}</div></div>
                    </div>
                </div>

                <aside class="tickets-summary-card">
                    <div class="tickets-summary-head">
                        <h3>{{ __('Your Selection') }}</h3>
                        <span class="tickets-summary-badge"><i class="fa fa-ticket" aria-hidden="true"></i></span>
                    </div>
                    <div class="tickets-summary-empty" id="ticketsSummaryEmpty">
                        <i class="fa fa-ticket" aria-hidden="true"></i>
                        <p>{{ __('Select your tickets to see the summary here.') }}</p>
                    </div>
                    <div class="tickets-summary-list" id="ticketsSummaryList"></div>
                    <button type="submit" id="form_book"
                        class="font-poppins font-medium text-lg ml-1 leading-6 text-white w-full rounded-md py-3 {{ $allTicketsSoldOut ? 'cursor-not-allowed no-cursor' : 'bg-primary' }}"
                        style="{{ $allTicketsSoldOut ? 'background-color:#9ca3af;' : '' }}"
                        disabled>
                        <div id="formtext">
                            <i class="fa pr-2 fa-check-square"></i>{{ !empty($liveVenueMap) && $venueSeatMapSeats->count() > 0 ? __('Continue to Seat Selection') : __('Buy Tickets') }}
                        </div>
                        <div id="formloader"
                            class="hidden mx-auto animate-spin rounded-full border-t-2 border-blue-500 border-solid h-7 w-7">
                        </div>
                    </button>
                    <div class="tickets-summary-secure"><i class="fa fa-lock" aria-hidden="true"></i> {{ __('Your booking is secure and safe') }}</div>
                </aside>
            </section>
            </form>
            </div>

            {{-- review --}}
            {{-- <div class="bg-white shadow-lg rounded-md p-4 mt-10">
                <div class="flex">
                    <p class="font-poppins font-semibold text-2xl leading-7 text-black">{{ __('Reviews') }}</p>&nbsp;
                    <p class="font-poppins font-medium text-base leading-8 text-black">({{ count($data->review) }})</p>
                </div>
                @if (count($data->review) != 0)
                    @foreach ($data->review as $item)
                        <div>
                            <div class="flex justify-between mt-4 sm:flex-wrap xxsm:flex-wrap">
                                <div class="flex sm:flex-wrap xxsm:flex-wrap">
                                    <div class="">
                                        @php
                                            $user = \App\Models\AppUser::find($item->user_id);
                                        @endphp
                                        <img src="{{ asset('images/upload/' . $user->image) }}"
                                            class="w-10 h-10 bg-cover object-cover" alt="">
                                    </div>
                                    <div class="ml-3 ">
                                        <p class="font-poppins font-medium text-lg leading-6 text-black-100">
                                            {{ $user->name }}</p>

                                    </div>
                                </div>
                                <div class="flex">
                                    <p class="font-poppins font-medium text-base leading-4 text-gray-200 pt-1 mr-3">
                                        {{ __('Rating : ' . $item->rate) }}</p>
                                    <div class="flex space-x-1">
                                        @for ($i = 1; $i <= $item->rate; $i++)
                                            <img src="{{ asset('images/star-fill.png') }}"
                                                class="h-5 w-5 bg-cover object-cover" alt="">
                                        @endfor

                                    </div>
                                </div>
                            </div>
                            <div class="ml-12 mt-4">
                                <p class="font-poppins font-normal text-base leading-6 text-gray">
                                    {{ $item->message }}
                                </p>
                            </div>
                        </div>
                    @endforeach
                @else
                @endif

            </div> --}}
            {{-- Report Event --}}
            {{-- <div class="bg-white shadow-lg rounded-md p-4 mt-10">
                <p class="font-poppins font-semibold text-2xl leading-8 text-black">{{ __('Report Event') }}</p>
                <form class="form-a" method="post" action="{{ url('report-event') }}">
                    @csrf
                    <div class="">
                        <div class="grid md:grid-cols-2 sm:grid-cols-1 xxsm:grid-cols-1 mt-5 gap-3">
                            <div class=" ">
                                <label for="name"
                                    class="font-poppins font-normal text-lg leading-7 text-gray-100 pb-2">{{ __('Name') }}</label>
                                <input type="text" name="name"
                                    class="focus:outline-none text-base leading-4 font-poppins font-normal text-gray-100 block p-3 rounded-md z-20
                            border border-gray-light w-full"
                                    placeholder="{{ __('Name *') }}">
                            </div>
                            <div class="">
                                <label for="name"
                                    class="font-poppins font-normal text-lg leading-7 text-gray-100 pb-2">{{ __('Email address') }}</label>
                                <input type="text" name="email"
                                    class="focus:outline-none text-base leading-4 font-poppins font-normal text-gray-100 block p-3 rounded-md z-20
                            border border-gray-light w-full"
                                    placeholder="{{ __('Email *') }}">
                            </div>
                        </div>
                        <div class="grid md:grid-cols-2 sm:grid-cols-1 xxsm:grid-cols-1 mt-5 gap-3">
                            <div class="w-full">
                                <label for="report_reason"
                                    class="font-poppins font-normal text-lg leading-7 text-gray-100 pb-2">{{ __('Report Reason') }}</label>
                                <select id="report_reason" name="reason"
                                    class="w-full focus:outline-none text-base leading-4 font-poppins font-normal text-gray-100 block p-3 rounded-md z-20
                            border border-gray-light">
                                    <option class="font-poppins font-normal text-base leading-4 text-gray-100" selected
                                        disabled>
                                        {{ __('Select Reason') }}</option>
                                    <option class="font-poppins font-normal text-base leading-4 text-gray-100"
                                        value="Canceled Event">
                                        {{ __('Canceled Event') }}</option>
                                    <option class="font-poppins font-normal text-base leading-4 text-gray-100"
                                        value="Copyright or Trademark Infringement">
                                        {{ __('Copyright or Trademark Infringement') }}</option>
                                    <option class="font-poppins font-normal text-base leading-4 text-gray-100"
                                        value="Fraudulent of Unauthorized Event">
                                        {{ __('Fraudulent of Unauthorized Event') }}</option>
                                    <option class="font-poppins font-normal text-base leading-4 text-gray-100"
                                        value="Offensive or Illegal Event">
                                        {{ __('Offensive or Illegal Event') }}</option>
                                    <option class="font-poppins font-normal text-base leading-4 text-gray-100"
                                        value="Spam">
                                        {{ __('Spam') }}</option>
                                    <option class="font-poppins font-normal text-base leading-4 text-gray-100"
                                        value="Other">
                                        {{ __('Other') }}</option>
                                </select>
                            </div>
                        </div>
                        <div class="w-full mt-5">
                            <textarea id="message" rows="4" required name="message"
                                class="block p-2.5 w-full focus:outline-none text-base leading-4 font-poppins font-normal text-gray-100
                        border border-gray-light rounded-md"
                                placeholder="{{ __('Describe your message...') }}"></textarea>

                        </div>
                        <input type="hidden" name="event_id" id="" value="{{ $data->id }}">
                        <div class="mt-5 flex justify-end">
                            <button
                                class="bg-primary text-white text-right font-poppins font-medium text-lg leading-7 px-5 py-2 rounded-md">{{ __('Send Message') }}</button>
                        </div>
                    </div>
                </form>
            </div> --}}
        </div>
    </div>
 <script>
  document.addEventListener("DOMContentLoaded", function() {
    const slider = document.querySelector('.slider');
    const slides = document.querySelectorAll('.slide');
    const slideWidth = slides[0].clientWidth + parseInt(window.getComputedStyle(slides[0]).marginLeft) + parseInt(window.getComputedStyle(slides[0]).marginRight);
    let currentIndex = 0;

    setInterval(() => {
      currentIndex = (currentIndex + 1) % slides.length;
      slider.style.transform = `translateX(-${slideWidth * currentIndex}px)`;

      slides.forEach((slide, index) => {
        if (index === currentIndex) {
          slide.classList.add('active');
        } else {
          slide.classList.remove('active');
        }
      });
    }, 4000); // Change image every 4 seconds (4000 milliseconds)
  });
</script>
    <script>
    function openGoogleMaps() {
        // Replace latitude and longitude with your actual coordinates
        var latitude = {{ $data->lat }};
        var longitude = {{ $data->lang }};

        // Construct the Google Maps URL
        var mapsUrl = 'https://www.google.com/maps?q=' + latitude + ',' + longitude;

        // Open a new window or tab with the Google Maps URL
        window.open(mapsUrl, '_blank');
    }
</script>
    <script>
        function initMap() {
            var map = new google.maps.Map(document.getElementById('map'), {
                center: {
                    lat: {{ $data->lat }},
                    lng: {{ $data->lang }}
                },
                zoom: 13
            });
            let marker = new google.maps.Marker({
                position: {
                    lat: {{ $data->lat }},
                    lng: {{ $data->lang }}
                },
                map: map
            });
        }
    </script>
    <script>
        const isAllTicketsSoldOut = @json($allTicketsSoldOut);

        document.addEventListener('DOMContentLoaded', (event) => {
            const checkboxes = document.querySelectorAll('input[name="multitickets[]"]');
            const button = document.getElementById('form_book');

            // Set initial button state
            updateButtonState();

            checkboxes.forEach((checkbox) => {
                checkbox.addEventListener('change', () => {
                    updateButtonState();
                });
            });
        });

        function handleTicketSelection(checkbox, ticketId, hasSeatTable) {
            const seatSelect = document.getElementById('seat-select-' + ticketId);
            const seatWarning = document.getElementById('seat-warning-' + ticketId);
            const seatUnavailableWarning = document.getElementById('seat-unavailable-warning-' + ticketId);

            if (checkbox.checked) {
                if (hasSeatTable) {
                    // Check if any seats are available for this ticket
                    const availableOptions = Array.from(seatSelect.options).filter(option =>
                        option.value !== '' && !option.disabled
                    );

                    if (availableOptions.length === 0) {
                        checkbox.checked = false;
                        checkbox.dataset.selectedQty = '0';
                        const qtySelect = document.getElementById('ticket-qty-select-' + ticketId);
                        if (qtySelect) qtySelect.value = '0';
                        const row = document.getElementById('ticket-row-' + ticketId);
                        if (row) row.classList.remove('is-selected');
                        if (seatUnavailableWarning) {
                            seatUnavailableWarning.style.display = 'block';
                        }
                        alert('{{ __("All seats for this ticket are sold out. Please try another ticket.") }}');
                        return false;
                    }

                    // If ticket has seat table, allow selection without requiring pre-selection
                    if (!seatSelect || seatSelect.value === '') {
                        // Just show warning but allow the ticket to be selected
                        if (seatWarning) {
                            seatWarning.style.display = 'block';
                        }
                        if (seatUnavailableWarning) {
                            seatUnavailableWarning.style.display = 'none';
                        }
                        // Don't prevent ticket selection, just require seat selection before checkout
                    } else {
                        // Check if selected seat is available
                        const selectedOption = seatSelect.options[seatSelect.selectedIndex];
                        if (selectedOption && selectedOption.disabled) {
                            checkbox.checked = false;
                            checkbox.dataset.selectedQty = '0';
                            const qtySelect = document.getElementById('ticket-qty-select-' + ticketId);
                            if (qtySelect) qtySelect.value = '0';
                            const row = document.getElementById('ticket-row-' + ticketId);
                            if (row) row.classList.remove('is-selected');
                            if (seatUnavailableWarning) {
                                seatUnavailableWarning.style.display = 'block';
                            }
                            alert('{{ __("The selected seat is no longer available. Please choose another seat.") }}');
                            return false;
                        }

                        // Hide warnings if seat is selected and available
                        if (seatWarning) {
                            seatWarning.style.display = 'none';
                        }
                        if (seatUnavailableWarning) {
                            seatUnavailableWarning.style.display = 'none';
                        }
                        // Add seat data to form
                        addSeatDataToForm(ticketId, seatSelect.value);
                    }
                }
            } else {
                checkbox.dataset.selectedQty = '0';
                const qtySelect = document.getElementById('ticket-qty-select-' + ticketId);
                if (qtySelect) qtySelect.value = '0';
                const row = document.getElementById('ticket-row-' + ticketId);
                if (row) row.classList.remove('is-selected');
                // Remove seat data when unchecked
                removeSeatDataFromForm(ticketId);
                // Hide warnings when unchecked
                if (seatWarning) {
                    seatWarning.style.display = 'none';
                }
                if (seatUnavailableWarning) {
                    seatUnavailableWarning.style.display = 'none';
                }
            }

            // Update button state
            updateButtonState();
        }

        function handleSeatSelection(ticketId) {
            const seatSelect = document.getElementById('seat-select-' + ticketId);
            const seatWarning = document.getElementById('seat-warning-' + ticketId);
            const seatUnavailableWarning = document.getElementById('seat-unavailable-warning-' + ticketId);
            const checkbox = document.querySelector('input[name="multitickets[]"][value="' + ticketId + '"]');

            if (seatSelect && seatSelect.value !== '') {
                const selectedOption = seatSelect.options[seatSelect.selectedIndex];

                // Check if selected seat is available
                if (selectedOption && selectedOption.disabled) {
                    seatSelect.value = ''; // Reset selection
                    if (seatUnavailableWarning) {
                        seatUnavailableWarning.style.display = 'block';
                    }
                    if (checkbox && checkbox.checked) {
                        checkbox.checked = false;
                        checkbox.dataset.selectedQty = '0';
                        const qtySelect = document.getElementById('ticket-qty-select-' + ticketId);
                        if (qtySelect) qtySelect.value = '0';
                        const row = document.getElementById('ticket-row-' + ticketId);
                        if (row) row.classList.remove('is-selected');
                        removeSeatDataFromForm(ticketId);
                        updateButtonState();
                    }
                    alert('{{ __("The selected seat is no longer available. Please choose another seat.") }}');
                    return;
                }

                // Hide warnings when valid seat is selected
                if (seatWarning) {
                    seatWarning.style.display = 'none';
                }
                if (seatUnavailableWarning) {
                    seatUnavailableWarning.style.display = 'none';
                }

                // Enable checkbox if it was disabled
                if (checkbox) {
                    checkbox.disabled = false;
                }

                // Update seat data if ticket is already selected
                if (checkbox && checkbox.checked) {
                    addSeatDataToForm(ticketId, seatSelect.value);
                }
            } else {
                // Show warning if no seat selected but ticket is checked
                if (checkbox && checkbox.checked) {
                    if (seatWarning) {
                        seatWarning.style.display = 'block';
                    }
                }
            }
        }

        function validateSeatSelections() {
            const selectedTickets = document.querySelectorAll('input[name="multitickets[]"]:checked');

            for (let checkbox of selectedTickets) {
                const ticketId = checkbox.value;
                const seatSelect = document.getElementById('seat-select-' + ticketId);

                // Check if this ticket has a seat table requirement
                if (seatSelect) {
                    if (!seatSelect.value || seatSelect.value === '') {
                        alert('{{ __("Please select a table for all tickets that require table selection.") }}');
                        // Scroll to the problematic ticket
                        seatSelect.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        return false;
                    }

                    // Check if selected seat is still available
                    const selectedOption = seatSelect.options[seatSelect.selectedIndex];
                    if (selectedOption && selectedOption.disabled) {
                        alert('{{ __("One or more selected tables are no longer available. Please refresh the page and try again.") }}');
                        seatSelect.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        return false;
                    }
                }
            }

            return true;
        }

        function addSeatDataToForm(ticketId, seatValue) {
            const container = document.getElementById('seat-data-container');

            // Remove existing seat data for this ticket
            removeSeatDataFromForm(ticketId);

            // Add new seat data
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'seat_selection[' + ticketId + ']';
            input.value = seatValue;
            input.id = 'seat-data-' + ticketId;
            container.appendChild(input);
        }

        function removeSeatDataFromForm(ticketId) {
            const existingInput = document.getElementById('seat-data-' + ticketId);
            if (existingInput) {
                existingInput.remove();
            }
        }



        const isAdminOrOrganizer = @json(Auth::check() && Auth::user() && (Auth::user()->hasRole('Organizer') || Auth::user()->hasRole('admin')));
        const hasVenueSeatMap = @json(!empty($liveVenueMap) && $venueSeatMapSeats->count() > 0);
        let venueSeatStepActive = false;
        // Seat selection for venue-map events happens inside the ticket-flow modal (see loadSeatMapIntoModal);
        // this stays an empty placeholder so the non-seat-map submission payload below has a consistent shape.
        const selectedVenueSeats = [];

        function handleFormSubmission() {
            console.log('Form submission started');

            if (isAllTicketsSoldOut) {
                return false;
            }

            // If organizer or admin is logged in, redirect to order creation page
            if (isAdminOrOrganizer) {
                window.location.href = '{{ url('/orders-create-for-user') }}';
                return false;
            }

            // First validate seat selections
            if (!validateSeatSelections()) {
                console.log('Seat validation failed');
                return false;
            }

            const selectedTickets = document.querySelectorAll('input[name="multitickets[]"]:checked');
            const selectedAvailableTickets = Array.from(selectedTickets).filter(function (ticket) {
                const qty = parseInt(ticket.dataset.selectedQty || (ticket.checked ? '1' : '0'), 10);
                return qty > 0 && parseInt(ticket.dataset.availableQty || 0, 10) > 0 && !ticket.disabled;
            });
            if (hasVenueSeatMap && !venueSeatStepActive) {
                if (selectedAvailableTickets.length === 0) {
                    alert('{{ __("Please select at least one available ticket.") }}');
                    return false;
                }

                const seatMapPartialUrl = new URL(@json(route('event.venueSeatMapPartial', $data->id)), window.location.origin);
                selectedAvailableTickets.forEach(function (ticket) {
                    seatMapPartialUrl.searchParams.append('tickets[]', ticket.value);
                });
                loadSeatMapIntoModal(seatMapPartialUrl.toString());

                return false;
            }

            // Check if any tickets are selected
            if (selectedTickets.length === 0 || selectedAvailableTickets.length === 0) {
                alert('{{ __("Please select at least one ticket.") }}');
                return false;
            }

            if (hasVenueSeatMap && selectedVenueSeats.length < selectedTickets.length) {
                alert('{{ __("Please select one available seat for each selected ticket.") }}');
                return false;
            }

            console.log('Found selected tickets:', selectedTickets.length);

            // Show loading state
            const formText = document.getElementById('formtext');
            const formLoader = document.getElementById('formloader');
            const submitButton = document.getElementById('form_book');

            if (formText && formLoader) {
                formText.classList.add('hidden');
                formLoader.classList.remove('hidden');
            }

            if (submitButton) {
                submitButton.disabled = true;
            }

            // Store seat selections and ticket data
            const multitickets = [];
            const seatSelections = {};

            selectedTickets.forEach(checkbox => {
                const ticketId = checkbox.value;
                const qty = parseInt(checkbox.dataset.selectedQty || '1', 10) || 1;
                if (qty <= 0) return;

                for (let i = 0; i < qty; i++) {
                    multitickets.push(ticketId);
                }

                const seatSelect = document.getElementById('seat-select-' + ticketId);
                if (seatSelect && seatSelect.value) {
                    // Get the selected option to extract sponser_id
                    const selectedOption = seatSelect.options[seatSelect.selectedIndex];
                    const sponserId = selectedOption ? selectedOption.getAttribute('data-sponser-id') : null;

                    // Format seat selection data properly for the backend
                    seatSelections[ticketId] = {
                        selectionSeat_id: seatSelect.value,
                        sponser_id: sponserId || seatSelect.value  // Fallback to seat_table_id if sponser_id not available
                    };

                    console.log('Seat selection for ticket ' + ticketId + ':', seatSelections[ticketId]);
                }
            });

            console.log('Final data to send:', {
                multitickets: multitickets,
                seat_selections: seatSelections,
                venue_seat_ids: selectedVenueSeats.map(function (seat) { return seat.id; }),
                venue_seat_details: selectedVenueSeats
            });

            // Show debug info in console
            console.log('=== FORM SUBMISSION DEBUG ===');
            console.log('Selected Tickets:', multitickets);
            console.log('Seat Selections:', seatSelections);
            console.log('Form Action:', document.getElementById('event-tickets-form').action);
            console.log('============================');

            // Check if CSRF token exists
            const csrfToken = document.querySelector('meta[name="csrf-token"]');
            if (!csrfToken) {
                console.error('CSRF token not found');
                alert('{{ __("Security token missing. Please refresh the page and try again.") }}');
                return false;
            }

            console.log('Sending AJAX request to store session data...');
            console.log('Request payload:', JSON.stringify({
                multitickets: multitickets,
                seat_selections: seatSelections,
                venue_seat_ids: selectedVenueSeats.map(function (seat) { return seat.id; }),
                venue_seat_details: selectedVenueSeats
            }, null, 2));

            // Send AJAX request to store session data before form submission
            fetch('{{ route("storeSessionTickets") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken.getAttribute('content')
                },
                body: JSON.stringify({
                    multitickets: multitickets,
                    seat_selections: seatSelections,
                    venue_seat_ids: selectedVenueSeats.map(function (seat) { return seat.id; }),
                    venue_seat_details: selectedVenueSeats
                })
            })
            .then(response => {
                console.log('Response status:', response.status);
                console.log('Response ok:', response.ok);

                if (!response.ok) {
                    console.error('HTTP error! status: ' + response.status);
                    throw new Error('HTTP error! status: ' + response.status);
                }

                return response.json();
            })
            .then(data => {
                console.log('Session data stored successfully:', data);

                if (data.success) {
                    console.log('Loading checkout into modal...');
                    // Clear any existing seat data inputs to avoid conflicts
                    const seatDataContainer = document.getElementById('seat-data-container');
                    seatDataContainer.innerHTML = '';
                    loadCheckoutIntoModal().catch(function () {}).finally(function () {
                        resetFormButton(formText, formLoader, submitButton);
                    });
                } else {
                    console.error('Failed to store session data:', data);
                    // Reset button state
                    resetFormButton(formText, formLoader, submitButton);
                    alert('{{ __("An error occurred. Please try again.") }}');
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);

                // Try direct form submission as fallback with seat data in hidden inputs
                console.log('Attempting direct form submission as fallback...');

                // Create hidden inputs for seat selections in the form
                const seatDataContainer = document.getElementById('seat-data-container');
                seatDataContainer.innerHTML = ''; // Clear existing

                // Add seat selections in the same format as the AJAX request
                const seatSelectionsInput = document.createElement('input');
                seatSelectionsInput.type = 'hidden';
                seatSelectionsInput.name = 'seat_selections_json';
                seatSelectionsInput.value = JSON.stringify(seatSelections);
                seatDataContainer.appendChild(seatSelectionsInput);

                const venueSeatIdsInput = document.createElement('input');
                venueSeatIdsInput.type = 'hidden';
                venueSeatIdsInput.name = 'venue_seat_ids';
                venueSeatIdsInput.value = JSON.stringify(selectedVenueSeats.map(function (seat) { return seat.id; }));
                seatDataContainer.appendChild(venueSeatIdsInput);

                const venueSeatDetailsInput = document.createElement('input');
                venueSeatDetailsInput.type = 'hidden';
                venueSeatDetailsInput.name = 'venue_seat_details';
                venueSeatDetailsInput.value = JSON.stringify(selectedVenueSeats);
                seatDataContainer.appendChild(venueSeatDetailsInput);

                // Also add individual seat selections for backward compatibility
                Object.keys(seatSelections).forEach(ticketId => {
                    const seatData = seatSelections[ticketId];
                    if (typeof seatData === 'object') {
                        // New format - create separate inputs for seat_table_id and sponser_id
                        const seatTableInput = document.createElement('input');
                        seatTableInput.type = 'hidden';
                        seatTableInput.name = 'seat_selections[' + ticketId + '][seat_table_id]';
                        seatTableInput.value = seatData.seat_table_id;
                        seatDataContainer.appendChild(seatTableInput);

                        const sponserInput = document.createElement('input');
                        sponserInput.type = 'hidden';
                        sponserInput.name = 'seat_selections[' + ticketId + '][sponser_id]';
                        sponserInput.value = seatData.sponser_id;
                        seatDataContainer.appendChild(sponserInput);
                    } else {
                        // Old format - single value
                        const seatInput = document.createElement('input');
                        seatInput.type = 'hidden';
                        seatInput.name = 'seat_selections[' + ticketId + ']';
                        seatInput.value = seatData;
                        seatDataContainer.appendChild(seatInput);
                    }
                });

                // Ensure multitickets are added according to selected quantity in fallback form submit
                selectedTickets.forEach(checkbox => {
                    const ticketId = checkbox.value;
                    const qty = parseInt(checkbox.dataset.selectedQty || '1', 10) || 1;
                    if (qty <= 0) return;
                    for (let i = 0; i < qty; i++) {
                        const ticketInput = document.createElement('input');
                        ticketInput.type = 'hidden';
                        ticketInput.name = 'multitickets[]';
                        ticketInput.value = ticketId;
                        seatDataContainer.appendChild(ticketInput);
                    }
                });

                // Disable original hidden checkboxes so they don't submit once in addition to hidden inputs
                document.querySelectorAll('input.ticket-row-checkbox-hidden').forEach(cb => {
                    cb.disabled = true;
                });

                // Reset button state before submitting
                resetFormButton(formText, formLoader, submitButton);

                // Submit the form directly
                document.getElementById('event-tickets-form').submit();
            });

            return false; // Prevent immediate form submission
        }

        function resetFormButton(formText, formLoader, submitButton) {
            if (formText && formLoader) {
                formText.classList.remove('hidden');
                formLoader.classList.add('hidden');
            }
            if (submitButton) {
                if (isAllTicketsSoldOut) {
                    submitButton.disabled = true;
                    submitButton.style.backgroundColor = '#9ca3af';
                    submitButton.classList.add('cursor-not-allowed', 'no-cursor');
                    submitButton.classList.remove('bg-primary');
                    return;
                }
                submitButton.disabled = false;
            }
        }

        function updateButtonState() {
            const checkboxes = document.querySelectorAll('input[name="multitickets[]"]');
            const button = document.getElementById('form_book');
            if (!button) return;

            const isAnyChecked = [...checkboxes].some(checkbox => {
                const qty = parseInt(checkbox.dataset.selectedQty || (checkbox.checked ? '1' : '0'), 10);
                return checkbox.checked && qty > 0;
            });
            const hasCheckedAvailableTicket = [...checkboxes].some(checkbox => {
                const qty = parseInt(checkbox.dataset.selectedQty || (checkbox.checked ? '1' : '0'), 10);
                return checkbox.checked && qty > 0 && !checkbox.disabled && parseInt(checkbox.dataset.availableQty || 0, 10) > 0;
            });

            // Check if this is event 16 with no database tickets
            const isEvent16 = {{ $data->id == 16 ? 'true' : 'false' }};
            const hasDbTickets = {{ count($data->all_ticket) > 0 ? 'true' : 'false' }};

            // If it's event 16 and no database tickets, always disable the button
            if (isEvent16 && !hasDbTickets) {
                button.disabled = true;
                button.classList.add('no-cursor');
                button.style.display = 'none'; // Hide the button completely
                return;
            }

            if (isAllTicketsSoldOut) {
                button.disabled = true;
                button.style.backgroundColor = '#9ca3af';
                button.classList.remove('bg-primary');
                button.classList.add('cursor-not-allowed', 'no-cursor');
                button.style.display = 'block';
                return;
            }

            if (hasVenueSeatMap && !venueSeatStepActive) {
                button.disabled = !hasCheckedAvailableTicket;
                button.style.display = 'block';
                button.style.backgroundColor = hasCheckedAvailableTicket ? '' : '#9ca3af';
                button.classList.toggle('cursor-not-allowed', !hasCheckedAvailableTicket);
                button.classList.toggle('no-cursor', !hasCheckedAvailableTicket);
                button.classList.toggle('bg-primary', hasCheckedAvailableTicket);
                return;
            }

            // Normal logic for other cases
            button.disabled = !isAnyChecked;
            button.style.display = 'block'; // Show the button
            if (isAnyChecked) {
                button.style.backgroundColor = '';
                button.classList.remove('cursor-not-allowed', 'no-cursor');
                button.classList.add('bg-primary');
            } else {
                button.style.backgroundColor = '#9ca3af';
                button.classList.add('cursor-not-allowed', 'no-cursor');
                button.classList.remove('bg-primary');
            }
        }

        // Add event listeners to seat dropdowns
        document.addEventListener('DOMContentLoaded', function() {
            const seatDropdowns = document.querySelectorAll('.seat-dropdown');
            seatDropdowns.forEach(dropdown => {
                dropdown.addEventListener('change', function() {
                    const ticketId = this.id.replace('seat-select-', '');
                    handleSeatSelection(ticketId);
                });
            });
        });
    </script>

    <script>
        function toggleTicketSelect(ticketId) {
            const checkbox = document.getElementById('multitickets-' + ticketId);
            if (!checkbox || checkbox.disabled) {
                return;
            }

            checkbox.checked = !checkbox.checked;
            checkbox.dataset.selectedQty = checkbox.checked ? '1' : '0';
            checkbox.dispatchEvent(new Event('change', { bubbles: true }));
        }

        function syncTicketRowStepper(ticketId) {
            const checkbox = document.getElementById('multitickets-' + ticketId);
            const row = document.getElementById('ticket-row-' + ticketId);
            const selectBtn = document.getElementById('ticket-select-btn-' + ticketId);
            if (!checkbox || !row) {
                return;
            }

            const isSelected = checkbox.checked;
            const qty = parseInt(checkbox.dataset.selectedQty || (isSelected ? '1' : '0'), 10) || 0;

            if (selectBtn) {
                selectBtn.textContent = isSelected ? @json(__('Selected')) : @json(__('Select'));
                selectBtn.classList.toggle('is-active', isSelected);
            }

            row.classList.toggle('is-selected', isSelected && qty > 0);
        }

        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.ticket-row-checkbox-hidden').forEach(function (checkbox) {
                const ticketId = checkbox.value;
                checkbox.dataset.selectedQty = checkbox.checked ? '1' : '0';
                syncTicketRowStepper(ticketId);
            });
            updateButtonState();
        });
    </script>

    <script>
        // Live "Your Selection" summary panel - reflects selected quantities & computes line/grand totals
        function refreshTicketsSummary() {
            const checkboxes = document.querySelectorAll('input[name="multitickets[]"]:checked');
            const emptyEl = document.getElementById('ticketsSummaryEmpty');
            const listEl = document.getElementById('ticketsSummaryList');

            if (!emptyEl || !listEl) {
                return;
            }

            listEl.innerHTML = '';

            const activeCheckboxes = Array.from(checkboxes).filter(function (cb) {
                const qty = parseInt(cb.dataset.selectedQty || (cb.checked ? '1' : '0'), 10);
                return qty > 0;
            });

            if (activeCheckboxes.length === 0) {
                emptyEl.style.display = '';
                listEl.style.display = 'none';
                return;
            }

            emptyEl.style.display = 'none';
            listEl.style.display = 'flex';

            activeCheckboxes.forEach(function (checkbox) {
                const name = checkbox.dataset.ticketName || '';
                const price = parseFloat(checkbox.dataset.ticketPrice || '0') || 0;
                const qty = parseInt(checkbox.dataset.selectedQty || '1', 10) || 1;
                const lineTotal = price * qty;

                const row = document.createElement('div');
                row.className = 'tickets-summary-row';

                const nameSpan = document.createElement('span');
                nameSpan.textContent = qty > 1 ? `${name} × ${qty}` : name;

                const priceSpan = document.createElement('span');
                if (price === 0) {
                    priceSpan.textContent = @json(__('Free'));
                } else {
                    priceSpan.textContent = @json($currency->currency_sybmol ?? '$') + lineTotal.toFixed(2);
                }

                row.appendChild(nameSpan);
                row.appendChild(priceSpan);
                listEl.appendChild(row);
            });
        }

        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('input[name="multitickets[]"]').forEach(function (checkbox) {
                checkbox.addEventListener('change', refreshTicketsSummary);
            });
            refreshTicketsSummary();
        });
    </script>

    <script>
        // Handle Dandiya Event-16 Book Now button
        function handleDandiyaBookNow() {
            // Redirect to ticket purchase link or handle as needed
            window.open('https://www.tixr.com/groups/azgrounds/events/dandiya-dhoom-2025-falguni-pathak-153503', '_blank');
            // Or you can add your custom logic here
            console.log('Dandiya Event-16 Book Now clicked');
        }
    </script>

    @if(!($data->id == 42) && !empty($data->lat) && !empty($data->lang))
    <script async defer src="https://maps.googleapis.com/maps/api/js?key={{ $gmapkey }}&callback=initMap"></script>
    @endif

@endsection
