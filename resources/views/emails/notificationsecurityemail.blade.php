@component('mail::message')
<img src="{{ config('app.url') }}/assets/img/logo-dku.png" style="width:30%" alt="App Logo">

# {{ $details['title'] }}

Dear {{ $details['name'] }}, {{ $details['message'] }}

Device : {{$details['user_agent']}}

@component('mail::button', ['url' => $details['url_reset']])
    Check Your Activity
@endcomponent

@component('mail::button', ['url' => $details['url_force_logout']])
    Force Logout
@endcomponent


Thanks,<br>
{{ config('app.name') }}
@endcomponent
