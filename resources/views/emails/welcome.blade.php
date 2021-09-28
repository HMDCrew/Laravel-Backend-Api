@component('mail::message', ['name' => $name, 'email' => $email, 'user_id' => $user_id, 'hash' => $hash])
# Introduction

Hi {{ $name }},
this is your activation e-Mail.

{{ $email }}

Please click the "Verify Account" Button to active your account

@component('mail::button', ['url' => 'http://localhost:3000/user/verify/'. $user_id .'/'. $hash ])
Verify Account 
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
