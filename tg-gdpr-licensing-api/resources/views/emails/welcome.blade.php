@component('mail::message')
# Welcome to {{ config('app.name') }}, {{ $user->name }}!

Your account has been created and you're all set to start making your website GDPR compliant.

---

**Here's what you can do next:**

@component('mail::panel')
**Step 1 — Add your first site**
Log in to your dashboard and click "Add Site". You'll get a unique site token to use in your Cookiely integration settings.
@endcomponent

@component('mail::panel')
**Step 2 — Configure your consent banner**
Choose your banner position, colours, and which cookie categories to show. Geo-targeting lets you restrict the banner to EU visitors only.
@endcomponent

@component('mail::panel')
**Step 3 — Activate your license**
Enter your site token in the integration settings on your site. Cookiely will start syncing consent data within minutes.
@endcomponent

@component('mail::button', ['url' => route('customer.dashboard')])
Go to Dashboard
@endcomponent

If you have any questions, reply to this email — we're happy to help.

Thanks,<br>
The {{ config('app.name') }} Team

---
<small>You're receiving this email because you signed up for {{ config('app.name') }} at {{ config('app.url') }}. If you did not create this account, you can safely ignore this email.</small>
@endcomponent
