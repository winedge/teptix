@extends('frontend.master', ['activePage' => 'profile'])
@section('title', __('FAQ'))
@section('content')
    <style>
        

        .banner {
            position: relative;
            height: 300px; /* Adjust the height as needed */
            background-image: url('https://teptix.com/images/faq-question-answer-bubble-icon-260nw-1149227447.png');
            background-size: cover;
            background-position: center;
        }

        .overlay {
            position: absolute;
            inset: 0;
            background-color: rgba(0, 0, 0, 0.5); /* Adjust the opacity as needed */
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .banner-text {
            color: white;
            font-size: 2em;
            font-weight: bold;
            padding: 20px;
            border-radius: 8px;
        }

        /* Mobile-friendly styles */
        @media (max-width: 768px) {
            .banner {
                height: 200px; /* Adjust the height for smaller screens */
            }

            .banner-text {
                font-size: 1.5em;
                padding: 10px;
            }
        }
    </style>
<div class="banner">
    <div class="overlay">
        <div class="banner-text">{{ __('FAQ ') }} - Frequently Asked Questions</div>
    </div>
</div>



    <div class="mx-auto w-[60%] ">
        
        <section class="FAQ py-3">
            <div class="container">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-12">
                    @foreach ($data as $item)
                        <div class="col-span-12">
                            <div class="bg-white shadow overflow-hidden sm:rounded-md">
                                <div class="px-4 py-5 sm:px-6">
                                    <h3 class="text-lg  font-medium text-gray-900 text-primary">
                                        <span class="font-bold">Q. </span>{{ $item->question }}
                                    </h3>
                                </div>
                                <div class="px-4 py-1 sm:p-6">
                                    <p class="text-base text-gray-500">
                                        <span class="font-bold">A. </span>{{ $item->answer }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            @if (count($data) == 0)
                <div class="font-poppins font-medium text-lg leading-4 text-black mt-5 capitalize ">
                    {{ __('There are no FAQ added yet') }}
                </div>
            @endif
        </section>

    </div>

@endsection
