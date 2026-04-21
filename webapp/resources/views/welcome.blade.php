<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Exam & Permit Management System') }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        :root {
            --sti-blue: #005aa7;
            --sti-amber: #f3b300;
            --sti-ink: #0c1e33;
        }

        body.sti-landing-body {
            margin: 0;
            min-height: 100vh;
            font-family: "Manrope", "Segoe UI", sans-serif;
            color: #ffffff;
            background: radial-gradient(circle at 10% 10%, #0b5ea5 0%, #05335f 45%, #031c37 100%);
        }

        .sti-transition-fill {
            position: fixed;
            inset: 0;
            background: linear-gradient(120deg, rgba(3, 25, 48, 0.6), rgba(3, 25, 48, 0.2)), url('{{ asset('sti_school.png') }}') center/cover no-repeat;
            clip-path: inset(0 52% 0 0);
            opacity: 0;
            z-index: 70;
            transform: scale(0.98);
            transition: clip-path 560ms cubic-bezier(0.77, 0, 0.175, 1), opacity 340ms ease, transform 560ms ease;
            pointer-events: none;
        }

        .sti-landing-shell {
            position: relative;
            z-index: 10;
            min-height: 100vh;
            display: grid;
            grid-template-columns: minmax(0, 1.2fr) minmax(360px, 0.8fr);
        }

        .sti-landing-media {
            position: relative;
            background: linear-gradient(145deg, rgba(4, 34, 65, 0.4), rgba(4, 34, 65, 0.12)), url('{{ asset('sti_school.png') }}') center/cover no-repeat;
            overflow: hidden;
        }

        .sti-landing-media::after {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(95deg, rgba(1, 24, 47, 0.2) 0%, rgba(1, 24, 47, 0.75) 100%);
        }

        .sti-landing-panel {
            position: relative;
            background: linear-gradient(145deg, rgba(7, 34, 63, 0.95), rgba(3, 21, 40, 0.95));
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2.5rem;
        }

        .sti-hero-content {
            width: min(100%, 480px);
            color: var(--sti-ink);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 1.4rem;
            transition: opacity 260ms ease, transform 260ms ease;
        }

        .sti-logo {
            width: 184px;
            max-width: 78%;
            height: auto;
            object-fit: contain;
            display: block;
            margin-left: auto;
            margin-right: auto;
            margin-bottom: 1rem;
        }

        .sti-title {
            margin: 0;
            font-size: clamp(1.25rem, 2vw, 1.9rem);
            line-height: 1.25;
            font-weight: 800;
            color: #f4f8ff;
        }

        .sti-subtitle {
            margin-top: 0.8rem;
            margin-bottom: 1.4rem;
            color: rgba(228, 239, 255, 0.84);
            font-size: 0.97rem;
        }

        .sti-actions {
            display: grid;
            gap: 0.8rem;
            width: min(100%, 360px);
        }

        .sti-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            padding: 0.78rem 1rem;
            text-decoration: none;
            font-weight: 700;
            border: 1px solid transparent;
            transition: transform 160ms ease, box-shadow 180ms ease, background-color 180ms ease, color 180ms ease;
        }

        .sti-btn-primary {
            background: linear-gradient(90deg, var(--sti-blue), #0b78d7);
            color: #ffffff;
            box-shadow: 0 16px 22px -15px rgba(0, 90, 167, 0.9);
        }

        .sti-btn-secondary {
            background: #ffffff;
            color: var(--sti-blue);
            border-color: rgba(0, 90, 167, 0.35);
        }

        .sti-btn:hover {
            transform: translateY(-1px);
        }

        .sti-btn:focus-visible {
            outline: 3px solid rgba(9, 120, 220, 0.45);
            outline-offset: 2px;
        }

        .sti-dashboard-btn {
            margin-top: 0.6rem;
            background: linear-gradient(90deg, var(--sti-amber), #ffd040);
            color: #072949;
            border-color: rgba(151, 112, 0, 0.2);
        }

        .sti-transitioning .sti-transition-fill {
            clip-path: inset(0 0 0 0);
            opacity: 1;
            transform: scale(1);
        }

        .sti-transitioning .sti-hero-content {
            opacity: 0;
            transform: translateX(40px);
        }

        @media (max-width: 767.98px) {
            .sti-landing-shell {
                grid-template-columns: 1fr;
                background: linear-gradient(180deg, rgba(4, 43, 77, 0.82), rgba(3, 21, 40, 0.94)),
                            url('{{ asset('sti_school.png') }}') center/cover no-repeat;
            }

            .sti-landing-media {
                display: none;
            }

            .sti-landing-panel {
                background: transparent;
                min-height: 100vh;
                padding: 2rem 1.4rem;
            }

            .sti-title {
                font-size: 1.18rem;
            }

            .sti-actions {
                width: 100%;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .sti-transition-fill,
            .sti-hero-content,
            .sti-btn {
                transition: none !important;
            }
        }
    </style>
</head>
<body class="sti-landing-body">
    @php
        $logoFile = file_exists(public_path('sti_logo_full.png')) ? asset('sti_logo_full.png') : asset('sti_logo.png');
    @endphp

    <div class="sti-transition-fill" aria-hidden="true"></div>

    <main class="sti-landing-shell" id="stiLanding">
        <section class="sti-landing-media" aria-hidden="true"></section>

        <section class="sti-landing-panel">
            <div class="sti-hero-content" id="stiLandingCard">
                <img src="{{ $logoFile }}" alt="STI Logo" class="sti-logo">
                <h1 class="sti-title">Welcome to STI College San Jose E-Permit System</h1>
                <p class="sti-subtitle">Choose where you want to continue.</p>

                @if (Route::has('login'))
                    @auth
                        @php
                            $portalRoute = match (auth()->user()->role) {
                                'student' => route('student.dashboard'),
                                'proctor' => route('proctor.dashboard'),
                                'cashier' => route('cashier.dashboard'),
                                'academic_head' => route('academic-head.dashboard'),
                                'admin' => route('admin.dashboard'),
                                default => '/',
                            };
                        @endphp
                        <a href="{{ $portalRoute }}" class="sti-btn sti-dashboard-btn">Go to Dashboard</a>
                    @else
                        <div class="sti-actions">
                            <a href="{{ route('login') }}" data-intent="login" class="sti-btn sti-btn-primary js-auth-link">Login</a>
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" data-intent="register" class="sti-btn sti-btn-secondary js-auth-link">Register</a>
                            @endif
                        </div>
                    @endauth
                @endif
            </div>
        </section>
    </main>

    <script>
        (() => {
            const links = document.querySelectorAll('.js-auth-link');
            if (!links.length) return;

            const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

            links.forEach((link) => {
                link.addEventListener('click', (event) => {
                    if (prefersReducedMotion) return;

                    event.preventDefault();
                    const targetHref = link.getAttribute('href');

                    sessionStorage.setItem('stiAuthIntro', '1');
                    sessionStorage.setItem('stiAuthIntent', link.dataset.intent || 'login');

                    document.documentElement.classList.add('sti-transitioning');

                    window.setTimeout(() => {
                        window.location.href = targetHref;
                    }, 400);
                });
            });
        })();
    </script>
</body>
</html>
