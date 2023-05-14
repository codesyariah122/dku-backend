@component('mail::message')
<img src="{{config('app.url')}}/assets/img/logo-dku.png" style="width:30%" alt="App Logo">

# {{$details['title']}}

Dear {{$details['name']}}, Email Anda **{{$details['email']}}**

Registrasi anda berhasil silahkan aktivasi

@component('mail::button', ['url' => $details['url']])
Aktivasi
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
