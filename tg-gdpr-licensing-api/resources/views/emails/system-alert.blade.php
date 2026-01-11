@component('mail::message')
# {{ $alert->title }}

**Type:** {{ strtoupper($alert->type) }}  
**Category:** {{ ucfirst($alert->category) }}  
**Time:** {{ $alert->created_at->format('Y-m-d H:i:s') }}

---

## Message

{{ $alert->message }}

@if($alert->context)
## Additional Details

@foreach($alert->context as $key => $value)
- **{{ ucfirst(str_replace('_', ' ', $key)) }}:** {{ is_array($value) ? json_encode($value) : $value }}
@endforeach
@endif

---

@component('mail::button', ['url' => $url])
View Alert Details
@endcomponent

@if($alert->type === 'critical')
**⚠️ This is a critical alert that requires immediate attention.**
@endif

Thanks,<br>
{{ config('app.name') }} Monitoring System
@endcomponent
