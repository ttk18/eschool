<!DOCTYPE html>
@php
    $lang = Session::get('language');
@endphp
@if ($lang)
    @if ($lang->is_rtl)
        <html lang="en" dir="rtl">
    @else
        <html lang="en">
    @endif
@else
    <html lang="en">
@endif

<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>@yield('title') {{ config('app.name') }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @include('layouts.home_page.include')
    @include('layouts.include')
    @yield('css')
</head>

<body class="sidebar-fixed">
    <div class="container-scroller">

        @yield('content')

    </div>
    @include('layouts.home_page.footer_js')
    @yield('js')
    @yield('script')
</body>

</html>
