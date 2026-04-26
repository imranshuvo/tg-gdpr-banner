<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Terms of Service — {{ config('app.name') }}</title>
    <meta name="description" content="{{ config('app.name') }} Terms of Service.">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="alternate icon" href="{{ asset('favicon.ico') }}">
    <meta name="theme-color" content="#4f46e5">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased bg-white text-gray-900">

    {{-- Nav --}}
    <nav class="border-b border-gray-200 bg-white sticky top-0 z-50">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 py-4 flex items-center justify-between">
            <a href="{{ route('landing') }}" class="text-xl font-bold text-indigo-600">{{ config('app.name') }}</a>
            <a href="{{ route('landing') }}" class="text-sm text-gray-600 hover:text-indigo-600">← Back to home</a>
        </div>
    </nav>

    <main class="max-w-4xl mx-auto px-4 sm:px-6 py-12">
        <h1 class="text-4xl font-bold text-gray-900 mb-2">Terms of Service</h1>
        <p class="text-gray-500 text-sm mb-10">Last updated: {{ now()->format('F j, Y') }}</p>

        <div class="prose prose-gray max-w-none space-y-8 text-gray-700 leading-relaxed">

            <section>
                <h2 class="text-2xl font-semibold text-gray-900 mb-3">1. Acceptance of Terms</h2>
                <p>By accessing or using {{ config('app.name') }} (the "Service"), you agree to be bound by these Terms of Service. If you do not agree, do not use the Service.</p>
            </section>

            <section>
                <h2 class="text-2xl font-semibold text-gray-900 mb-3">2. Description of Service</h2>
                <p>{{ config('app.name') }} provides a cookie consent management platform including a site integration, a compliance dashboard, automated cookie scanning, consent record storage, Data Subject Access Request (DSAR) management, and related features. The Service is offered on a subscription basis.</p>
            </section>

            <section>
                <h2 class="text-2xl font-semibold text-gray-900 mb-3">3. Your Account</h2>
                <p>You are responsible for maintaining the confidentiality of your account credentials and for all activity that occurs under your account. Notify us immediately at <a href="mailto:support@cookiely.io" class="text-indigo-600 underline">support@cookiely.io</a> if you suspect unauthorised access.</p>
            </section>

            <section>
                <h2 class="text-2xl font-semibold text-gray-900 mb-3">4. Acceptable Use</h2>
                <p>You agree not to:</p>
                <ul class="list-disc pl-5 space-y-2 mt-2">
                    <li>Use the Service to violate any applicable law or regulation.</li>
                    <li>Attempt to reverse-engineer, decompile, or extract the source code of the Service.</li>
                    <li>Use the Service to transmit spam, malware, or any harmful content.</li>
                    <li>Resell or sublicense the Service without our written permission.</li>
                    <li>Attempt to overload, disrupt, or gain unauthorised access to our infrastructure.</li>
                </ul>
            </section>

            <section>
                <h2 class="text-2xl font-semibold text-gray-900 mb-3">5. License</h2>
                <p>Subject to your payment of applicable fees, we grant you a limited, non-exclusive, non-transferable, revocable licence to use the Service for your own websites, up to the number of sites included in your subscription plan.</p>
            </section>

            <section>
                <h2 class="text-2xl font-semibold text-gray-900 mb-3">6. Subscription & Billing</h2>
                <p>Subscriptions are billed annually in advance. All fees are non-refundable except as required by applicable law or as stated in our refund policy. We reserve the right to change pricing with 30 days' written notice.</p>
            </section>

            <section>
                <h2 class="text-2xl font-semibold text-gray-900 mb-3">7. Data Processing</h2>
                <p>You remain the Data Controller for the personal data of your website visitors. We act as your Data Processor under Article 28 GDPR. By using the Service you agree to our <a href="{{ route('privacy-policy') }}" class="text-indigo-600 underline">Privacy Policy</a>, which forms part of these Terms. You are solely responsible for ensuring your own use of the Service is compliant with applicable data protection laws.</p>
            </section>

            <section>
                <h2 class="text-2xl font-semibold text-gray-900 mb-3">8. Uptime & Service Level</h2>
                <p>We aim for 99.5% monthly uptime for the API. Planned maintenance is announced in advance. Downtime does not entitle you to a refund but may be credited at our discretion.</p>
            </section>

            <section>
                <h2 class="text-2xl font-semibold text-gray-900 mb-3">9. Limitation of Liability</h2>
                <p>To the maximum extent permitted by law, {{ config('app.name') }} shall not be liable for any indirect, incidental, special, consequential, or punitive damages. Our total aggregate liability to you shall not exceed the fees paid by you in the 12 months preceding the claim.</p>
            </section>

            <section>
                <h2 class="text-2xl font-semibold text-gray-900 mb-3">10. Disclaimer of Warranties</h2>
                <p>The Service is provided "as is" without warranty of any kind. We do not warrant that the Service will be error-free, uninterrupted, or that it will satisfy any specific legal compliance requirement in your jurisdiction. You should seek independent legal advice regarding your GDPR obligations.</p>
            </section>

            <section>
                <h2 class="text-2xl font-semibold text-gray-900 mb-3">11. Termination</h2>
                <p>Either party may terminate the subscription at any time. Upon termination you lose access to the dashboard. Consent records stored on your behalf will be retained for the legally required period before being deleted.</p>
            </section>

            <section>
                <h2 class="text-2xl font-semibold text-gray-900 mb-3">12. Governing Law</h2>
                <p>These Terms are governed by and construed in accordance with the laws of the European Union and the jurisdiction in which we are incorporated. Any disputes shall be submitted to the courts of that jurisdiction.</p>
            </section>

            <section>
                <h2 class="text-2xl font-semibold text-gray-900 mb-3">13. Changes to Terms</h2>
                <p>We may update these Terms. Continued use of the Service after receiving notice of changes constitutes acceptance. Material changes will be communicated by email at least 14 days in advance.</p>
            </section>

            <section>
                <h2 class="text-2xl font-semibold text-gray-900 mb-3">14. Contact</h2>
                <p>For questions about these Terms, contact us at <a href="mailto:legal@cookiely.io" class="text-indigo-600 underline">legal@cookiely.io</a>.</p>
            </section>

        </div>
    </main>

    <footer class="border-t border-gray-200 mt-16 py-8 text-center text-sm text-gray-500">
        <div class="space-x-4">
            <a href="{{ route('landing') }}" class="hover:text-indigo-600">Home</a>
            <a href="{{ route('privacy-policy') }}" class="hover:text-indigo-600">Privacy Policy</a>
            <a href="{{ route('terms-of-service') }}" class="hover:text-indigo-600">Terms of Service</a>
        </div>
        <p class="mt-4">&copy; {{ now()->year }} {{ config('app.name') }}. All rights reserved.</p>
    </footer>

</body>
</html>
