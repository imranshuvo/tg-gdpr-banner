@component('mail::message')
# Your data request has been completed

Your {{ \App\Models\DsarRequest::getRequestTypeLabel($dsarRequest->request_type) }} request has been completed.

**Request ID:** #{{ $dsarRequest->id }}  
**Completed at:** {{ optional($dsarRequest->completed_at)->format('Y-m-d H:i:s') }}

@if($downloadUrl)
Your export file is ready. It will remain available until **{{ optional($dsarRequest->export_expires_at)->format('Y-m-d H:i:s') }}**.

@component('mail::button', ['url' => $downloadUrl])
Download Export
@endcomponent
@elseif($dsarRequest->request_type === 'erasure')
The matching consent records associated with this request have been deleted.
@elseif(in_array($dsarRequest->request_type, ['restriction', 'objection'], true))
The matching consent records associated with this request have been restricted from further processing.
@else
The request has been recorded as completed.
@endif

If you have questions about this request, reply to this email or contact the site operator.

Thanks,<br>
{{ config('app.name') }}
@endcomponent