@extends('layouts.home_page.master')

@section('content')
    <style>
        :root {
            --front-site-theme-color: {{ $settings['front_site_theme_color'] ?? '#e9f9f3' }};
            --primary-color: {{ $settings['primary_color'] ?? '#0cae74' }};
            --secondary-color: {{ $settings['secondary_color'] ?? '#245a7f' }};

            --preloader-img: url({{ $settings['horizontal_logo'] ?? asset('assets/home_page/img/Logo.svg') }});

        }
    </style>

    <!-- ======= Header ======= -->
    <header id="header" class="fixed-top autohide">
        <div class="container d-flex align-items-center">

            <h1 class="logo me-auto"><a href="{{ url('/') }}"><img src="{{ $settings['horizontal_logo'] ?? asset('assets/home_page/img/Logo.svg') }}" alt=""></a></h1>

            <nav id="navbar" class="navbar">
                <ul>
                    <li><a class="nav-link scrollto active" href="#hero">{{ __('home') }}</a></li>
                    <li><a class="nav-link scrollto" href="#feature">{{ __('feature') }}</a></li>
                    <li><a class="nav-link scrollto" href="#pricing">{{ __('pricing') }}</a></li>
                    <li><a class="nav-link scrollto" href="#faq">{{ __('faq') }}</a></li>
                    <li><a class="nav-link scrollto" href="#contact">{{ __('contact') }}</a></li>

                    @if (count($guidances))
                        <li class="dropdown"><a href="#"><span>{{ __('guidance') }}</span> <i class="bi bi-chevron-down"></i></a>
                            <ul>
                                @foreach ($guidances as $guidance)
                                    <li><a href="{{ $guidance->link }}">{{ $guidance->name }}</a></li>
                                @endforeach
                            </ul>
                        </li>
                    @endif

                    @if (count($languages))
                        <li class="dropdown"><a href="#"><span>{{ __('language') }}</span> <i class="bi bi-chevron-down"></i></a>
                            <ul>
                                @foreach ($languages as $language)
                                    <li><a href="{{ url('set-language') . '/' . $language->code }}">{{ $language->name }}</a></li>
                                @endforeach
                            </ul>
                        </li>
                    @endif

                    @if (Auth::user())
                        <li><a class="login scrollto" href="{{ route('auth.logout') }}">{{ __('logout') }}</a></li>
                        <li><a class="register scrollto" href="/dashboard">{{ __('hello') }}
                                {{ Auth::user()->first_name }}</a></li>
                    @else
                        <li><a class="login scrollto" href="{{ url('login') }}">{{ __('login') }}</a></li>
                        <li><a class="register" id="registration-form" href="javascript:void(0)">{{ __('register') }}</a></li>
                    @endif


                </ul>
                <i class="bi bi-list mobile-nav-toggle"></i>
            </nav><!-- .navbar -->

        </div>
    </header><!-- End Header -->

    <!-- ======= Hero Section ======= -->
    <section id="hero" class="d-flex align-items-center">
        <img src="{{ asset('assets/home_page/img/book.png') }}" class="book-img d-none d-md-block" alt="">
        <img src="{{ asset('assets/home_page/img/calc.png') }}" class="calc-img d-none d-md-block" alt="">
        <img src="{{ asset('assets/home_page/img/cap.png') }}" class="cap-img d-none d-md-block" alt="">
        <img src="{{ asset('assets/home_page/img/glass.png') }}" class="glass-img d-none d-md-block" alt="">
        <img src="{{ asset('assets/home_page/img/idea.svg') }}" class="idea-img d-none d-md-block" alt="">
        <img src="{{ asset('assets/home_page/img/rocket.svg') }}" class="rocket-img d-none d-md-block" alt="">
        <img src="{{ asset('assets/home_page/img/scale.svg') }}" class="scale-img d-none d-md-block" alt="">
        <div class="container">
            <div class="row">
                <div class="col-lg-6 d-flex flex-column justify-content-center pt-4 pt-lg-0 order-2 order-lg-1"
                     data-aos="fade-up" data-aos-delay="200">
                    <!-- <p class="saas">eSchool SaaS</p> -->
                    <label class="saas" for="">{{ $settings['system_name'] }}</label>
                    <!-- <div class="col-md-4"> -->
                    <h1 class="title"> {{ $settings['tag_line'] ?? 'eSchool-Saas - Manage Your School' }} </h1>
                    <label for="">

                    </label>
                    <!-- </div> -->

                    <h2></h2>
                    <div class="d-flex justify-content-center justify-content-lg-start">
                        <a href="#feature" class="btn-get-started scrollto">{{ __('get_started') }}</a>
                    </div>
                </div>
                {{-- <div class="col-lg-2 d-flex flex-column justify-content-center pt-4 pt-lg-0 order-2 order-lg-1"> --}}

                {{-- </div> --}}
                <div class="col-lg-6 order-1 order-lg-2 hero-img" data-aos="zoom-in" data-aos-delay="200">
                    <img src="{{ $settings['home_image'] ?? asset('assets/home_page/img/main_image-rbg.png') }}" class="img-fluid animated"
                         alt="">
                </div>
            </div>
        </div>

    </section><!-- End Hero -->

    @include('register')

    <main id="main">

        <!-- ======= Services Section ======= -->
        <section id="feature" class="services section-bg">
            <div class="container" data-aos="fade-up">

                <div class="section-title">
                    <h2>{{ __('our_features') }}</h2>
                    {{-- <p class="feature-tag text-center mx-auto">You don't have to struggle alone, you've got our
                        assistance and help.</p> --}}
                </div>

                <div class="row">
                    @php
                        $i = 1;
                    @endphp



                    @foreach ($features as $feature)
                        <div class="col-md-3 col-sm-6 grid-margin stretch-card" data-aos="zoom-in" data-aos-delay="100">
                            <div class="card">
                                <div class="card-body feature-div">

                                    <h4 class="card-title">
                                        {{ __($feature->name) }}
                                    </h4>
                                </div>
                            </div>
                        </div>

                        {{-- <div class="col-md-3 col-sm-6 grid-margin stretch-card" data-aos="zoom-in" data-aos-delay="100">
                        <div class="card @if ($i % 3 == 0) feature-div @elseif($i%2 == 0) feature-div-2 @else feature-div-1 @endif ">
                            <div class="card-body">
                                <img src="{{ url('images/onlineexam.svg') }}" class="mb-3" height="30" alt="">
                                <h4 class="card-title">
                                    {{ $feature->name }}
                                </h4>
                                {{ $feature->description }}
                            </div>
                        </div>
                    </div> --}}

                        @php
                            $i++;
                        @endphp
                    @endforeach


                </div>

            </div>
        </section><!-- End Services Section -->


    @if ($packages)
        <!-- ======= Pricing Section ======= -->
            <section id="pricing" class="pricing">
                <div class="container" data-aos="fade-up">

                    <div class="section-title">
                        <h2>{{ __('flexible_pricing_packages') }}</h2>
                    </div>

                    <div class="row justify-content-around">
                        @php
                            $delay = 0;
                        @endphp
                        @foreach ($packages as $package)
                            <div class="col-lg-4 mb-4" data-aos="fade-up" data-aos-delay="{{ $delay += 100 }}">
                                <div class="box @if ($package->highlight) featured @endif">
                                    <h2>{{ $package->name }}</h2>
                                    <h6>
                                        @if ($package->is_trial == 1)
                                            <div class="">
                                                <label for="">{{ $settings['student_limit'] }}</label>
                                                <label class="price">{{__("Student Limit")}}</label>
                                            </div>
                                            <div>
                                                <label class="">{{ $settings['staff_limit'] }}</label>
                                                <label class="price">{{__("Staff Limit")}}</label>
                                            </div>
                                        @else
                                            <div class="">
                                                <label for="">{{ $settings['currency_symbol'] }} {{ $package->student_charge }}</label>
                                                <label class="price">{{__("Per Student Charges")}}</label>
                                            </div>
                                            <div>
                                                <label class="">{{ $settings['currency_symbol'] }} {{ $package->staff_charge }}</label>
                                                <label class="price">{{__("Per Staff Charges")}}</label>
                                            </div>
                                        @endif

                                    </h6>
                                    <h4>
                                        @if ($package->is_trial == 1)
                                            <span class="">{{ $systemSettings['trial_days'] }} / {{__("Days")}}</span>
                                        @else
                                            <span class="">{{ $settings['billing_cycle_in_days'] }} / {{__("Days")}}</span>
                                        @endif

                                    </h4>

                                    <ul>

                                        {{-- Package features --}}
                                        @foreach ($features as $feature)
                                            @if (str_contains($package->package_feature->pluck('feature_id'), $feature->id))
                                                <li><i class="bx bx-check"></i>{{ __($feature->name) }}</li>
                                            @else
                                                <li class="na"><i class="bx bx-x"></i><span>{{ __($feature->name) }}</span></li>
                                            @endif
                                        @endforeach
                                    </ul>
                                    <a href="{{ url('login') }}" class="buy-btn ml-4">{{ __('get_started') }}</a>
                                </div>
                            </div>
                        @endforeach
                    </div>

                </div>
            </section><!-- End Pricing Section -->
    @endif
    <!-- ======= Frequently Asked Questions Section ======= -->
        <section id="faq" class="faq section-bg">
            <div class="container" data-aos="fade-up">

                <div class="section-title">
                    <h2>{{ __('frequently_asked_questions') }}</h2>
                </div>

                <div class="faq-list">
                    <ul>
                        @foreach ($faqs as $faq)
                            <li data-aos="fade-up" class="faq-question" data-aos-delay="100">
                                <i class="bx bx-help-circle icon-help"></i>
                                <a data-bs-toggle="collapse" class="collapsed" data-bs-target="#faq-list-{{ $faq->id }}">{{ $faq->title }}
                                    <i class="bx bx-chevron-down icon-show"></i>
                                    <i class="bx bx-chevron-up icon-close"></i>
                                </a>
                                <div id="faq-list-{{ $faq->id }}" class="collapse" data-bs-parent=".faq-list">
                                    <p>{{ $faq->description }}</p>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>

            </div>
        </section><!-- End Frequently Asked Questions Section -->

        <!-- ======= Contact Section ======= -->
        <section id="contact" class="contact">
            <div class="container" data-aos="fade-up">

                <div class="section-title">
                    <h2>{{ __('lets_get_in_touch') }}</h2>
                </div>

                <div class="row">
                    <div class="col-md-7 stretch-card">
                        {{-- <div class="pb-3"> --}}
                        <form action="{{ url('contact') }}" method="post" role="form" class="php-email-form mb-5 create-form">
                            @csrf
                            <div class="form-group">
                                <input type="text" name="title" class="form-control" placeholder="{{ __('name') }}" id="name" required>
                            </div>
                            <div class="form-group">
                                <input type="email" class="form-control" placeholder="{{ __('enter_email') }}" name="email" id="email" required>
                            </div>

                            <div class="form-group">
                                <textarea class="form-control" name="message" placeholder="{{ __('message') }}" rows="10" required></textarea>
                            </div>
                            <div class="my-3">
                                <div class="loading">{{ __('loading') }}</div>
                                <div class="error-message"></div>
                                <div class="sent-message">{{ __('Your message has been sent. Thank you') }}!</div>
                            </div>
                            <div class="text-left">
                                <button type="submit">{{ __('send_your_message') }}</button>
                            </div>
                        </form>
                        {{-- </div> --}}
                    </div>
                    <div class="col-md-5 mb-5 stretch-card">
                        <div class="info php-email-form">
                            <h3 class="contact-form">{{ __('support_contact') }}</h3>
                            @if (isset($settings['mobile']))
                                <div class="address">
                                    <div class="row">
                                        <div class="col-md-1">
                                            <img src="assets/home_page/img/phone.png" class="mt-3 contact-img" alt="">
                                        </div>
                                        <div class="col-md-10">
                                            <h5 class="mt-3 contact-title">{{ __('phone') }}:</h5>
                                        </div>
                                        <div class="col-md-1">
                                        </div>
                                        <div class="col-md-10">
                                            <p>{{ __('mobile') }} : {{ $settings['mobile'] ?? '' }}</p>
                                        </div>
                                    </div>
                                </div>
                            @endif
                            @if (isset($settings['mail_username']))
                                <div class="address">
                                    <div class="row">
                                        <div class="col-md-1">
                                            <img src="assets/home_page/img/gmail.png" class="mt-3 contact-img" alt="">
                                        </div>
                                        <div class="col-md-10">
                                            <h5 class="mt-3 contact-title">{{ __('email') }}:</h5>
                                        </div>
                                        <div class="col-md-1">
                                        </div>
                                        <div class="col-md-10">
                                            <p>{{ $settings['mail_username'] ?? '' }}</p>
                                        </div>
                                    </div>
                                </div>
                            @endif
                            @if (isset($settings['address']))
                                <div class="address">
                                    <div class="row">
                                        <div class="col-md-1">
                                            <img src="assets/home_page/img/location.png" class="mt-3 contact-img" alt="">
                                        </div>
                                        <div class="col-md-10">
                                            <h5 class="mt-3 contact-title">{{ __('location') }}:</h5>
                                        </div>
                                        <div class="col-md-1">
                                        </div>
                                        <div class="col-md-10">
                                            <p>{{ $settings['address'] }}</p>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>

                    </div>
                </div>
            </div>
        </section><!-- End Contact Section -->

    </main><!-- End #main -->

    {{-- MOBILE DEVICE --}}
    <div data-aos="zoom-out-up" data-aos-delay="100"
         class="d-sm-block d-md-none mt-n3 text-center mobile-footer">
        <div class="mobile-app-download-title">
            <div class="mx-auto text-light">
                <h2>{{ __('start_learning_by') }}<br>{{ __('downloading_apps') }}.</h2>
            </div>
        </div>
        <div class="mobile-app-download-button">
            <div class="mx-auto text-light">
                <a href="#" class="btn-apple mx-2 mb-2"><img src="{{ asset('assets/home_page/img/apple.svg') }}"
                                                             class="mx-2" alt="">{{ __('apple_store') }}</a>
                <a href="#" class="btn-play mx-2 mb-2"><img src="{{ asset('assets/home_page/img/playstore.svg') }}" class="mx-2" alt="">{{ __('play_store') }}</a>
            </div>
        </div>
    </div>
    {{-- END MOBILE DEVICE --}}

    {{-- PC DEVICE --}}
    <div data-aos="zoom-out-up" data-aos-delay="200"
         class="col-md-9 d-none d-md-block col-sm-8 mx-auto justify-content-center align-items-center footer-1">
        <div class="row">
            <div class="col-md-7" style="padding:3%">
                <div class="mx-auto text-light">
                    <h2>{{ __('start_learning_by') }}<br>{{ __('downloading_apps') }}.</h2>
                </div>
            </div>

            <div class="col-md-5 download-app text-center">
                <div class="mx-auto text-light">
                    <a href="{{ $settings['ios_app_link'] ?? '/' }}" target="_blank" class="btn-apple mx-2"> <img src="{{ asset('assets/home_page/img/apple.svg') }}" class="mx-2" alt="">{{ __('apple_store') }}</a>
                    <a href="{{ $settings['app_link'] ?? '/' }}" target="_blank" class="btn-play mx-2"> <img src="{{ asset('assets/home_page/img/playstore.svg') }}" class="mx-2" alt="">{{ __('play_store') }}</a>
                </div>
            </div>

        </div>
    </div>
    {{-- END PC DEVICE --}}


    <!-- ======= Footer ======= -->
    <footer id="footer">
        <div class="footer-top">
            <div class="container">
                <div class="row">

                    <div class="col-lg-4 col-md-6 footer-contact">
                        <a href="{{ url('/') }}"><img src="{{ $settings['horizontal_logo'] ?? asset('assets/home_page/img/Logo.svg') }}" alt=""></a>

                        <h4><strong>{{ $settings['system_name'] ?? 'eSchool Virtual Education' }}</strong></h4>
                        <p class="mt-4">{{ $settings['short_description'] ?? '' }}</p>
                    </div>

                    <div class="col-lg-4 col-md-6 footer-links">
                        <h4>{{ __('links') }}</h4>
                        <ul>
                            <li><i class="bx bx-chevron-right"></i> <a href="{{ url('/') }}"
                                                                       class="scrollto">{{ __('home') }}</a></li>
                            <li><i class="bx bx-chevron-right"></i> <a href="#feature" class="scrollto">{{ __('features') }}</a>
                            </li>
                            <li><i class="bx bx-chevron-right"></i> <a href="#pricing" class="scrollto">{{ __('pricing') }}</a>
                            </li>
                            <li><i class="bx bx-chevron-right"></i> <a href="#faq" class="scrollto">{{ __('faq') }}</a></li>
                            <li><i class="bx bx-chevron-right"></i> <a href="#contact" class="scrollto">{{ __('contact') }}</a>
                            </li>
                        </ul>
                    </div>

                    <div class="col-lg-4 col-md-6 footer-links">
                        @if (!empty($settings['facebook']) && !empty($settings['instagram']) && !empty($settings['linkedin']))
                            <h4 class="mx-2">{{ __('follow_us') }}</h4>
                        @endif
                        <div class="social-links mt-3">
                            @if (!empty($settings['facebook']))
                                <a href="{{ $settings['facebook'] }}" target="_blank" class="twitter mx-2"><img src="{{ asset('assets/home_page/img/facebook.png') }}" alt=""></a>
                            @endif

                            @if (!empty($settings['instagram']))
                                <a href="{{ $settings['instagram'] }}" target="_blank" class="facebook mx-2"><img src="{{ asset('assets/home_page/img/instagram.png') }}" alt=""></a>
                            @endif

                            @if (!empty($settings['linkedin']))
                                <a href="{{ $settings['linkedin'] }}" target="_blank" class="instagram mx-2"><img src="{{ asset('assets/home_page/img/linkedIn.png') }}" alt=""></a>
                            @endif
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <hr>
        <div class="container footer-bottom clearfix text-center">
            {!! $settings['footer_text'] ?? '<p>&copy; {{ date("Y") }} <strong><span><a href="https://wrteam.in/" target="_blank"
                rel="noopener noreferrer">WRTeam</a></span></strong>. All Rights Reserved</p>' !!}
            {{-- &copy; {{ date('Y') }} <strong><span><a href="https://wrteam.in/" target="_blank"
                        rel="noopener noreferrer">WRTeam</a></span></strong>. All Rights Reserved --}}
        </div>
    </footer><!-- End Footer -->

    <div id="preloader"></div>
    <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i
            class="bi bi-arrow-up-short"></i></a>
@endsection
