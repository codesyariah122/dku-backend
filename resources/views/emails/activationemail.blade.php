@component('mail::message')
# {{$details['title']}}

Dear {{$details['name']}}, Email Anda **{{$details['email']}}**

Registrasi anda berhasil silahkan aktivasi

@component('mail::button', ['url' => $details['url']])
Aktivasi
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
