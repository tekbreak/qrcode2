@component('mail::message')
# {{ __('billing.account_deletion_greeting', ['name' => $user->name]) }}

{{ __('billing.account_deletion_body', ['days' => config('qrcode.account_deletion_grace_days', 7)]) }}

**{{ __('billing.account_deletion_date_label') }}:** {{ $deletionDate->timezone(config('app.timezone'))->format('F j, Y') }}

{{ __('billing.account_deletion_data_warning') }}

@component('mail::button', ['url' => route('billing.index')])
{{ __('billing.account_deletion_resubscribe') }}
@endcomponent

{{ __('billing.account_deletion_resubscribe_note') }}

{{ __('common.thanks') }},<br>
{{ config('app.name') }}
@endcomponent
