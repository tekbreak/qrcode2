@component('mail::message')
{{ $slot }}

@include('emails.partials.sign-off')
@endcomponent
