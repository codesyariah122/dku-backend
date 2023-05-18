@component('mail::message')
<img src="{{ config('app.url') }}/assets/img/logo-dku.png" style="width:30%" alt="App Logo">

# {{ $details['title'] }}

Dear {{ $details['name'] }}, {{ $details['message'] }}

@component('mail::button', ['url' => $details['url']])
    Check Your Activity
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
