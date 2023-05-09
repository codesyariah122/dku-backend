<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>

    @include('layouts.partials.meta')

    @include('layouts.partials.head')

</head>

<body>
{{-- Page Content --}}
@yield('content')
{{-- End Page Content --}}

@include('layouts.partials.footer')
</body>
</html>
