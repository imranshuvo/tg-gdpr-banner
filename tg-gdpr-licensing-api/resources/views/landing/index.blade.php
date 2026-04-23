<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }} - GDPR Cookie Consent for WordPress</title>
    <meta name="description" content="GDPR cookie consent for WordPress with Google Consent Mode v2, customizable banner design, and consent analytics.">

    @vite(['resources/js/app.js'])

    <style>
        :root {
            --bg: #f6f8ff;
            --surface: #ffffff;
            --text: #0f172a;
            --muted: #5b6477;
            --line: #e3e8f3;
            --primary: #2563eb;
            --primary-strong: #1d4ed8;
            --accent: #0ea5e9;
            --shadow: 0 10px 30px rgba(31, 41, 55, 0.10);
            --radius: 14px;
            --max: 1160px;
        }

        * { box-sizing: border-box; }

        html, body {
            margin: 0;
            padding: 0;
            font-family: "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            color: var(--text);
            background: var(--bg);
            line-height: 1.55;
        }

        a { color: inherit; text-decoration: none; }

        .wrap {
            width: min(var(--max), calc(100% - 40px));
            margin: 0 auto;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 11px 18px;
            border-radius: 10px;
            border: 1px solid transparent;
            font-weight: 600;
            font-size: 14px;
            transition: 0.2s ease;
            cursor: pointer;
        }

        .btn-primary {
            background: var(--primary);
            color: #fff;
            box-shadow: 0 6px 16px rgba(37, 99, 235, 0.25);
        }

        .btn-primary:hover { background: var(--primary-strong); }

        .btn-ghost {
            background: #fff;
            color: var(--text);
            border-color: var(--line);
        }

        .btn-ghost:hover {
            border-color: #c7d2e8;
            background: #f9fbff;
        }

        .site-nav {
            position: sticky;
            top: 0;
            z-index: 30;
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(8px);
            border-bottom: 1px solid var(--line);
        }

        .site-nav__row {
            height: 68px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
        }

        .brand {
            font-size: 24px;
            font-weight: 800;
            letter-spacing: -0.02em;
            color: #0b1a3a;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 22px;
            color: #32405f;
            font-size: 14px;
            font-weight: 600;
        }

        .nav-links a:hover { color: var(--primary); }

        .hero {
            padding: 70px 0 56px;
        }

        .hero__grid {
            display: grid;
            grid-template-columns: 1.1fr 1fr;
            gap: 42px;
            align-items: center;
        }

        .pill {
            display: inline-flex;
            padding: 6px 12px;
            border-radius: 999px;
            border: 1px solid #bfdbfe;
            background: #eff6ff;
            color: #1d4ed8;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.01em;
        }

        .hero h1 {
            margin: 14px 0 10px;
            font-size: clamp(34px, 4vw, 54px);
            line-height: 1.04;
            letter-spacing: -0.03em;
        }

        .hero p {
            margin: 0;
            color: var(--muted);
            font-size: 18px;
            max-width: 620px;
        }

        .hero__actions {
            margin-top: 24px;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .trust {
            margin-top: 24px;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .trust span {
            font-size: 12px;
            font-weight: 700;
            color: #334155;
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 999px;
            padding: 8px 12px;
        }

        .mock {
            border-radius: 16px;
            background: #dbe5ff;
            padding: 10px;
            box-shadow: var(--shadow);
        }

        .mock-window {
            border-radius: 12px;
            overflow: hidden;
            background: #fff;
            border: 1px solid #ccd6ef;
        }

        .mock-top {
            height: 40px;
            background: #f1f5f9;
            border-bottom: 1px solid #dce4f0;
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 0 12px;
        }

        .mock-top i {
            width: 9px;
            height: 9px;
            border-radius: 50%;
            display: inline-block;
        }

        .mock-body {
            background: #f8fbff;
            min-height: 220px;
            padding: 16px;
            position: relative;
        }

        .banner-preview {
            position: absolute;
            left: 0;
            right: 0;
            bottom: 0;
            background: #fff;
            border-top: 3px solid var(--primary);
            box-shadow: 0 -8px 20px rgba(15, 23, 42, 0.10);
            padding: 10px 12px 8px;
        }

        .banner-preview__row {
            display: grid;
            grid-template-columns: auto 1fr auto;
            align-items: center;
            gap: 10px;
        }

        .banner-preview__icon {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #eff6ff;
            border: 1px solid #bfdbfe;
        }

        .banner-preview__title {
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 1px;
        }

        .banner-preview__msg {
            color: #64748b;
            font-size: 11px;
        }

        .banner-preview__actions {
            display: flex;
            gap: 6px;
        }

        .mini-btn {
            font-size: 10px;
            font-weight: 700;
            border-radius: 6px;
            padding: 5px 8px;
            border: 1px solid #c7d2e8;
            background: #fff;
            color: #334155;
        }

        .mini-btn.primary {
            background: var(--primary);
            border-color: var(--primary);
            color: #fff;
        }

        .section {
            padding: 70px 0;
        }

        .section--white { background: #fff; }

        .section h2 {
            margin: 0;
            text-align: center;
            font-size: clamp(28px, 3vw, 40px);
            letter-spacing: -0.02em;
        }

        .section .sub {
            margin: 8px auto 0;
            text-align: center;
            color: var(--muted);
            max-width: 650px;
        }

        .steps,
        .features,
        .plans,
        .footer-grid {
            margin-top: 28px;
            display: grid;
            gap: 16px;
        }

        .steps { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .features { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .plans { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .footer-grid { grid-template-columns: 1.2fr 1fr 1fr 1fr; }

        .card {
            background: #fff;
            border: 1px solid var(--line);
            border-radius: var(--radius);
            padding: 20px;
        }

        .steps .card h3,
        .features .card h3,
        .plans .card h3 {
            margin: 8px 0 6px;
            font-size: 18px;
        }

        .steps .card p,
        .features .card p,
        .plans .card p,
        .faq details p {
            margin: 0;
            color: var(--muted);
        }

        .step-num {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            color: #fff;
            background: var(--primary);
        }

        .features .icon {
            width: 42px;
            height: 42px;
            border-radius: 10px;
            border: 1px solid #dbe7ff;
            background: #eff6ff;
            margin-bottom: 10px;
        }

        .plans .price {
            margin: 6px 0 10px;
            font-size: 34px;
            font-weight: 800;
            letter-spacing: -0.02em;
        }

        .plans ul {
            margin: 0 0 14px;
            padding-left: 18px;
            color: var(--muted);
        }

        .plans .popular {
            border-color: #93c5fd;
            box-shadow: 0 10px 22px rgba(37, 99, 235, 0.12);
        }

        .faq {
            max-width: 760px;
            margin: 28px auto 0;
            display: grid;
            gap: 10px;
        }

        .faq details {
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 12px;
            padding: 14px 16px;
        }

        .faq summary {
            cursor: pointer;
            list-style: none;
            font-weight: 700;
        }

        .faq summary::-webkit-details-marker { display: none; }

        .faq details p { margin-top: 8px; }

        .cta {
            background: linear-gradient(135deg, #2563eb, #0ea5e9);
            border-radius: 18px;
            color: #fff;
            text-align: center;
            padding: 44px 24px;
        }

        .cta h2 {
            margin: 0;
            font-size: clamp(28px, 3vw, 40px);
        }

        .cta p {
            margin: 10px 0 20px;
            color: #dbeafe;
        }

        .cta .btn-primary {
            background: #fff;
            color: #0b3aa8;
            box-shadow: none;
        }

        .site-footer {
            background: #0f172a;
            color: #94a3b8;
            padding: 48px 0 26px;
            margin-top: 70px;
        }

        .site-footer h4 {
            margin: 0 0 10px;
            color: #e2e8f0;
            font-size: 14px;
        }

        .site-footer ul {
            margin: 0;
            padding: 0;
            list-style: none;
            display: grid;
            gap: 7px;
            font-size: 14px;
        }

        .site-footer a:hover { color: #fff; }

        .footer-bar {
            margin-top: 20px;
            border-top: 1px solid #1e293b;
            padding-top: 14px;
            display: flex;
            justify-content: space-between;
            gap: 10px;
            align-items: center;
            font-size: 12px;
        }

        @media (max-width: 1024px) {
            .hero__grid { grid-template-columns: 1fr; }
            .features { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .plans { grid-template-columns: 1fr; }
            .steps { grid-template-columns: 1fr; }
            .footer-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }

        @media (max-width: 768px) {
            .wrap { width: calc(100% - 24px); }
            .nav-links { display: none; }
            .features { grid-template-columns: 1fr; }
            .footer-grid { grid-template-columns: 1fr; }
            .banner-preview__row { grid-template-columns: 1fr; }
            .banner-preview__actions { justify-content: flex-start; }
            .footer-bar { flex-direction: column; align-items: flex-start; }
        }
    </style>
</head>
<body>
    <header class="site-nav">
        <div class="wrap site-nav__row">
            <a href="/" class="brand">{{ config('app.name') }}</a>
            <nav class="nav-links">
                <a href="#features">Features</a>
                <a href="#pricing">Pricing</a>
                <a href="#faq">FAQ</a>
                @auth
                    <a href="{{ auth()->user()->isAdmin() ? route('admin.dashboard') : route('customer.dashboard') }}">Dashboard</a>
                @else
                    <a href="{{ route('login') }}">Sign in</a>
                    <a class="btn btn-primary" href="{{ route('register') }}">Get Started</a>
                @endauth
            </nav>
        </div>
    </header>

    <main>
        <section class="hero">
            <div class="wrap hero__grid">
                <div>
                    <span class="pill">Google Consent Mode v2 Ready</span>
                    <h1>Privacy-first cookie consent for modern WordPress sites</h1>
                    <p>{{ config('app.name') }} helps you stay compliant with GDPR and ePrivacy while keeping your banner clear, fast, and user-friendly.</p>
                    <div class="hero__actions">
                        <a class="btn btn-primary" href="{{ route('register') }}">Create Account</a>
                        <a class="btn btn-ghost" href="#pricing">View Pricing</a>
                    </div>
                    <div class="trust">
                        <span>GDPR Compliant</span>
                        <span>CCPA Friendly</span>
                        <span>Google Consent v2</span>
                    </div>
                </div>

                <div class="mock">
                    <div class="mock-window">
                        <div class="mock-top">
                            <i style="background:#fb7185"></i>
                            <i style="background:#f59e0b"></i>
                            <i style="background:#10b981"></i>
                        </div>
                        <div class="mock-body">
                            <div class="banner-preview">
                                <div class="banner-preview__row">
                                    <div class="banner-preview__icon"></div>
                                    <div>
                                        <div class="banner-preview__title">We value your privacy</div>
                                        <div class="banner-preview__msg">We use cookies to improve performance and analytics.</div>
                                    </div>
                                    <div class="banner-preview__actions">
                                        <span class="mini-btn">Reject</span>
                                        <span class="mini-btn">Preferences</span>
                                        <span class="mini-btn primary">Accept All</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="section section--white" id="how-it-works">
            <div class="wrap">
                <h2>Live in minutes</h2>
                <p class="sub">Install, activate, and start collecting valid consent quickly.</p>
                <div class="steps">
                    <article class="card">
                        <span class="step-num">1</span>
                        <h3>Install plugin</h3>
                        <p>Add the plugin in WordPress and activate it.</p>
                    </article>
                    <article class="card">
                        <span class="step-num">2</span>
                        <h3>Activate license</h3>
                        <p>Connect your site from your Cookiely dashboard.</p>
                    </article>
                    <article class="card">
                        <span class="step-num">3</span>
                        <h3>Publish banner</h3>
                        <p>Set style, categories, and go live.</p>
                    </article>
                </div>
            </div>
        </section>

        <section class="section" id="features">
            <div class="wrap">
                <h2>Everything you need</h2>
                <p class="sub">A focused set of compliance tools designed for WordPress teams.</p>
                <div class="features">
                    <article class="card">
                        <div class="icon"></div>
                        <h3>GDPR + ePrivacy</h3>
                        <p>Prior consent and granular category controls.</p>
                    </article>
                    <article class="card">
                        <div class="icon"></div>
                        <h3>Customizable Banner</h3>
                        <p>Adapt style, wording, layout, and positioning.</p>
                    </article>
                    <article class="card">
                        <div class="icon"></div>
                        <h3>Consent Mode v2</h3>
                        <p>Consent states mapped to Google signals.</p>
                    </article>
                    <article class="card">
                        <div class="icon"></div>
                        <h3>Performance-first</h3>
                        <p>Lightweight scripts with low runtime overhead.</p>
                    </article>
                    <article class="card">
                        <div class="icon"></div>
                        <h3>DSAR Workflow</h3>
                        <p>Track requests, verification, and completion.</p>
                    </article>
                    <article class="card">
                        <div class="icon"></div>
                        <h3>Consent Analytics</h3>
                        <p>Monitor acceptance and rejection behavior.</p>
                    </article>
                </div>
            </div>
        </section>

        <section class="section section--white" id="pricing">
            <div class="wrap">
                <h2>Simple pricing</h2>
                <p class="sub">Choose the plan that matches your website footprint.</p>
                <div class="plans">
                    <article class="card">
                        <h3>Starter</h3>
                        <div class="price">$49<span style="font-size:14px;font-weight:600;color:var(--muted)"> / year</span></div>
                        <ul>
                            <li>1 site license</li>
                            <li>Consent logs</li>
                            <li>Banner customization</li>
                        </ul>
                        <a class="btn btn-ghost" href="{{ route('register') }}">Get Started</a>
                    </article>
                    <article class="card popular">
                        <h3>Professional</h3>
                        <div class="price">$99<span style="font-size:14px;font-weight:600;color:var(--muted)"> / year</span></div>
                        <ul>
                            <li>Up to 5 sites</li>
                            <li>Google Consent Mode v2</li>
                            <li>DSAR workflow</li>
                        </ul>
                        <a class="btn btn-primary" href="{{ route('register') }}">Get Started</a>
                    </article>
                    <article class="card">
                        <h3>Agency</h3>
                        <div class="price">$199<span style="font-size:14px;font-weight:600;color:var(--muted)"> / year</span></div>
                        <ul>
                            <li>Up to 25 sites</li>
                            <li>Priority support</li>
                            <li>Agency tooling</li>
                        </ul>
                        <a class="btn btn-ghost" href="{{ route('register') }}">Get Started</a>
                    </article>
                </div>
            </div>
        </section>

        <section class="section" id="faq">
            <div class="wrap">
                <h2>Common questions</h2>
                <div class="faq">
                    <details>
                        <summary>Do I need this if my company is outside the EU?</summary>
                        <p>If EU users visit your site, GDPR obligations can still apply.</p>
                    </details>
                    <details>
                        <summary>Does this support Google Analytics and Ads?</summary>
                        <p>Yes. Google Consent Mode v2 signals are handled based on visitor choices.</p>
                    </details>
                    <details>
                        <summary>Can I fully customize the banner?</summary>
                        <p>Yes. Colors, labels, position, and category behavior are configurable.</p>
                    </details>
                </div>
            </div>
        </section>

        <section class="section section--white" id="download">
            <div class="wrap">
                <div class="cta">
                    <h2>Start for free today</h2>
                    <p>Create an account and make your first site compliant in minutes.</p>
                    <a class="btn btn-primary" href="{{ route('register') }}">Create Free Account</a>
                </div>
            </div>
        </section>
    </main>

    <footer class="site-footer">
        <div class="wrap">
            <div class="footer-grid">
                <div>
                    <h4>{{ config('app.name') }}</h4>
                    <p style="margin:0;">GDPR cookie consent for WordPress websites.</p>
                </div>
                <div>
                    <h4>Product</h4>
                    <ul>
                        <li><a href="#features">Features</a></li>
                        <li><a href="#pricing">Pricing</a></li>
                        <li><a href="#faq">FAQ</a></li>
                    </ul>
                </div>
                <div>
                    <h4>Account</h4>
                    <ul>
                        <li><a href="{{ route('login') }}">Sign In</a></li>
                        <li><a href="{{ route('register') }}">Create Account</a></li>
                    </ul>
                </div>
                <div>
                    <h4>Legal</h4>
                    <ul>
                        <li><a href="{{ route('privacy-policy') }}">Privacy Policy</a></li>
                        <li><a href="{{ route('terms-of-service') }}">Terms of Service</a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-bar">
                <span>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</span>
                <span>
                    <a href="{{ route('privacy-policy') }}">Privacy</a>
                    ·
                    <a href="{{ route('terms-of-service') }}">Terms</a>
                </span>
            </div>
        </div>
    </footer>
</body>
</html>
