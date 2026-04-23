@component('mail::message')
# Verify your data request

We received a {{ \App\Models\DsarRequest::getRequestTypeLabel($dsarRequest->request_type) }} request for **{{ $dsarRequest->requester_email }}**.

To protect the data subject, please verify this request before we process it.

@component('mail::button', ['url' => $verificationUrl])
Verify Request
@endcomponent

This verification link expires in 24 hours.

If you did not submit this request, you can ignore this email.

Thanks,<br>
{{ config('app.name') }}
@endcomponent