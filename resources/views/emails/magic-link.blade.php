<x-emails.transactional>
# {{ __('auth.magic_link_greeting') }}

{{ __('auth.magic_link_body') }}

@component('mail::button', ['url' => $url, 'color' => 'primary'])
{{ __('auth.magic_link_button') }}
@endcomponent

{{ __('auth.magic_link_expiry') }}

{{ __('auth.magic_link_ignore') }}
</x-emails.transactional>
