{{-- <script src="{{ asset('/assets/js/Chart.min.js') }}"></script> --}}

<script src="{{ asset('assets/home_page/vendor/aos/aos.js') }}"></script>
<script src="{{ asset('assets/home_page/vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('assets/home_page/vendor/glightbox/js/glightbox.min.js') }}"></script>
<script src="{{ asset('assets/home_page/vendor/isotope-layout/isotope.pkgd.min.js') }}"></script>
<script src="{{ asset('assets/home_page/vendor/swiper/swiper-bundle.min.js') }}"></script>

{{-- <script src="{{ asset('assets/home_page/vendor/waypoints/noframework.waypoints.js') }}"></script> --}}
{{-- <script src="{{ asset('assets/home_page/vendor/php-email-form/validate.js') }}"></script> --}}

<!-- Template Main JS File -->
<script src="{{ asset('assets/home_page/js/main.js') }}"></script>



{{-- <script src="{{ asset('/assets/js/custom/validate.js') }}"></script>
<script src="{{ asset('/assets/js/jquery-additional-methods.min.js')}}"></script>
<script src="{{ asset('/assets/js/custom/function.js') }}"></script>
<script src="{{ asset('/assets/js/custom/common.js') }}"></script>
<script src="{{ asset('/assets/js/custom/custom.js') }}"></script> --}}

<script src="{{ asset('/assets/jquery-toast-plugin/jquery.toast.min.js') }}"></script>


<script type='text/javascript'>
    @if ($errors->any())
    @foreach ($errors->all() as $error)
    $.toast({
        text: '{{ $error }}',
        showHideTransition: 'slide',
        icon: 'error',
        loaderBg: '#f2a654',
        position: 'top-right'
    });
    @endforeach
    @endif

    @if (Session::has('success'))
    $.toast({
        text: '{{ Session::get('success') }}',
        showHideTransition: 'slide',
        icon: 'success',
        loaderBg: '#f96868',
        position: 'top-right'
    });
    @endif

    @if (Session::has('error'))
    $.toast({
        text: '{{ Session::get('error') }}',
        showHideTransition: 'slide',
        icon: 'error',
        loaderBg: '#f2a654',
        position: 'top-right'
    });
    @endif
</script>

<script>
    $('#registration-form').click(function (e) { 
            e.preventDefault();
            $('#editModal').modal('show');
        });

        $('.close').click(function (e) { 
            e.preventDefault();
            $('#editModal').modal('hide');
        });
</script>