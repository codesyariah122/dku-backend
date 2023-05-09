
    @extends('layouts.global')
    @section('title'){{ $seo['title'] }}@endsection
    {{-- Meta Seo Tag --}}
    @section('canonical'){{ $seo['canonical'] }}@endsection



    @section('content')

        <div id="app">
            <app></app>
        </div>


        <script>
            var context = {!! json_encode($context) !!}
            console.log(context);
        </script>

        <script src="{{ mix('js/app.js') }}" type="text/javascript"></script>
    @endsection
