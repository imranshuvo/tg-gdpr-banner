<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Privacy Policy — {{ config('app.name') }}</title>
    <meta name="description" content="{{ config('app.name') }} Privacy Policy — how we collect, use, and protect your data.">
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
        <h1 class="text-4xl font-bold text-gray-900 mb-2">Privacy Policy</h1>
        <p class="text-gray-500 text-sm mb-10">Last updated: {{ now()->format('F j, Y') }}</p>

        <div class="prose prose-gray max-w-none space-y-8 text-gray-700 leading-relaxed">

            <section>
                <h2 class="text-2xl font-semibold text-gray-900 mb-3">1. Who we are</h2>
                <p>{{ config('app.name') }} ("we", "our", "us") is a Software-as-a-Service (SaaS) platform providing GDPR cookie consent management tools. Our registered contact email is <a href="mailto:privacy@cookiely.io" class="text-indigo-600 underline">privacy@cookiely.io</a>.</p>
            </section>

            <section>
                <h2 class="text-2xl font-semibold text-gray-900 mb-3">2. What data we collect</h2>
                <h3 class="text-lg font-medium text-gray-900 mb-2">2.1 Account data</h3>
                <p>When you create an account we collect your name, email address, and an encrypted password. This data is necessary to provide the service.</p>

                <h3 class="text-lg font-medium text-gray-900 mb-2 mt-4">2.2 Usage and analytics data</h3>
                <p>We collect aggregated, anonymised data about how you use the dashboard (page views, feature usage) using our own analytics. No third-party analytics cookies are set on the dashboard.</p>

                <h3 class="text-lg font-medium text-gray-900 mb-2 mt-4">2.3 Consent records (on behalf of your visitors)</h3>
                <p>When visitors to <em>your</em> website interact with the {{ config('app.name') }} consent banner, anonymised consent records (IP address with last octet zeroed, browser fingerprint hash, and consent choices) are transmitted to our servers for storage. We process this data as a <strong>Data Processor</strong> on your behalf under Article 28 GDPR — you remain the Data Controller for your visitors' data.</p>

                <h3 class="text-lg font-medium text-gray-900 mb-2 mt-4">2.4 Billing data</h3>
                <p>Payments are processed by our third-party payment provider. We do not store full card numbers. We retain transaction records (amounts, dates, invoice IDs) for our legal accounting obligations.</p>
            </section>

            <section>
                <h2 class="text-2xl font-semibold text-gray-900 mb-3">3. Legal basis for processing</h2>
                <ul class="list-disc pl-5 space-y-2">
                    <li><strong>Contract performance (Art. 6(1)(b) GDPR)</strong> — processing your account data to deliver the service you signed up for.</li>
                    <li><strong>Legitimate interest (Art. 6(1)(f) GDPR)</strong> — detecting fraud, improving the service, and ensuring security.</li>
                    <li><strong>Legal obligation (Art. 6(1)(c) GDPR)</strong> — retaining billing records as required by law.</li>
                    <li><strong>Consent (Art. 6(1)(a) GDPR)</strong> — sending marketing communications if you opt in.</li>
                </ul>
            </section>

            <section>
                <h2 class="text-2xl font-semibold text-gray-900 mb-3">4. Data retention</h2>
                <ul class="list-disc pl-5 space-y-2">
                    <li><strong>Account data</strong> — retained for the duration of your subscription plus 30 days after deletion to allow account recovery.</li>
                    <li><strong>Consent records</strong> — stored for 36 months as required to demonstrate GDPR compliance, then automatically deleted.</li>
                    <li><strong>Billing records</strong> — retained for 7 years to satisfy accounting obligations.</li>
                </ul>
            </section>

            <section>
                <h2 class="text-2xl font-semibold text-gray-900 mb-3">5. Your rights under GDPR</h2>
                <p>If you are in the EU/EEA, you have the right to:</p>
                <ul class="list-disc pl-5 space-y-2 mt-2">
                    <li><strong>Access</strong> the personal data we hold about you.</li>
                    <li><strong>Rectify</strong> inaccurate data.</li>
                    <li><strong>Erase</strong> your data ("right to be forgotten") where we have no legal obligation to retain it.</li>
                    <li><strong>Restrict</strong> or <strong>object to</strong> processing.</li>
                    <li><strong>Data portability</strong> — receive your data in a machine-readable format.</li>
                    <li><strong>Withdraw consent</strong> at any time where processing is based on consent.</li>
                </ul>
                <p class="mt-3">To exercise any of these rights, email <a href="mailto:privacy@cookiely.io" class="text-indigo-600 underline">privacy@cookiely.io</a>. We respond within 30 days.</p>
            </section>

            <section>
                <h2 class="text-2xl font-semibold text-gray-900 mb-3">6. Data transfers</h2>
                <p>Our servers are located in the European Union. If any sub-processor transfers data outside the EU/EEA, we ensure appropriate safeguards are in place (Standard Contractual Clauses or adequacy decision).</p>
            </section>

            <section>
                <h2 class="text-2xl font-semibold text-gray-900 mb-3">7. Cookies on this website</h2>
                <p>The {{ config('app.name') }} dashboard uses strictly necessary session cookies only. No analytics or advertising cookies are set on our own website without your consent.</p>
            </section>

            <section>
                <h2 class="text-2xl font-semibold text-gray-900 mb-3">8. Changes to this policy</h2>
                <p>We may update this policy. When we do, we update the "Last updated" date above and notify you by email if the changes are material.</p>
            </section>

            <section>
                <h2 class="text-2xl font-semibold text-gray-900 mb-3">9. Contact</h2>
                <p>For privacy-related questions or DSAR requests, contact us at <a href="mailto:privacy@cookiely.io" class="text-indigo-600 underline">privacy@cookiely.io</a>.</p>
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
