@component('mail::message')
# Update on your data request

We reviewed your {{ \App\Models\DsarRequest::getRequestTypeLabel($dsarRequest->request_type) }} request and could not complete it.

**Reason:** {{ $dsarRequest->rejection_reason }}

@if($dsarRequest->admin_notes)
**Additional notes:** {{ $dsarRequest->admin_notes }}
@endif

If you believe this outcome is incorrect, please contact the site operator and include request ID **#{{ $dsarRequest->id }}**.

Thanks,<br>
{{ config('app.name') }}
@endcomponent