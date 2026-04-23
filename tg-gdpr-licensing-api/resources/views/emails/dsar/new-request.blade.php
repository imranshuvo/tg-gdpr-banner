@component('mail::message')
# New verified DSAR request

A new {{ \App\Models\DsarRequest::getRequestTypeLabel($dsarRequest->request_type) }} request has been verified and is ready for review.

**Request ID:** #{{ $dsarRequest->id }}  
**Requester:** {{ $dsarRequest->requester_name ?: 'Unspecified' }}  
**Email:** {{ $dsarRequest->requester_email }}  
**Site:** {{ $dsarRequest->site?->domain ?? 'Customer-wide request' }}  
**Due date:** {{ optional($dsarRequest->due_date)->format('Y-m-d') }}

@component('mail::button', ['url' => $adminUrl])
Review Request
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent