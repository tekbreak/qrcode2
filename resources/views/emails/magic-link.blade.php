@component('mail::message')
# {{ __('auth.magic_link_greeting') }}

{{ __('auth.magic_link_body') }}

@component('mail::button', ['url' => $url])
{{ __('auth.magic_link_button') }}
@endcomponent

{{ __('auth.magic_link_expiry') }}

{{ __('auth.magic_link_ignore') }}

{{ __('common.thanks') }},<br>
{{ config('app.name') }}
@endcomponent
