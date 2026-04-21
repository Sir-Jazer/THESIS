<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Exam & Permit Management System') }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body.sti-auth-body {
            margin: 0;
            min-height: 100vh;
            font-family: "Manrope", "Segoe UI", sans-serif;
            color: #ffffff;
            background: #072949;
        }

        .sti-auth-wrap {
            position: relative;
            min-height: 100vh;
            overflow: hidden;
        }

        .sti-auth-bg {
            position: absolute;
            inset: 0;
            background: linear-gradient(115deg, rgba(5, 33, 62, 0.72), rgba(5, 33, 62, 0.38)), url('{{ asset('sti_school.png') }}') center/cover no-repeat;
            transform: scale(1);
            transition: transform 620ms ease, filter 620ms ease;
        }

        .sti-auth-mask {
            position: absolute;
            inset: 0;
            background: radial-gradient(circle at 84% 18%, rgba(5, 88, 161, 0.36), rgba(2, 26, 49, 0.78));
        }

        .sti-auth-shell {
            position: relative;
            z-index: 5;
            min-height: 100vh;
            display: grid;
            grid-template-columns: minmax(280px, 0.95fr) minmax(360px, 1fr);
            align-items: center;
            gap: 2rem;
            padding: 2.4rem;
        }

        .sti-auth-brand {
            max-width: 500px;
            padding: 1rem;
        }

        .sti-auth-brand h1 {
            margin: 1rem 0 0.7rem;
            font-size: clamp(1.6rem, 2.7vw, 2.8rem);
            line-height: 1.15;
            font-weight: 800;
            text-wrap: balance;
        }

        .sti-auth-brand p {
            margin: 0;
            color: rgba(235, 244, 255, 0.95);
            max-width: 45ch;
        }

        .sti-back-home {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-top: 1rem;
            padding: 0.55rem 1.1rem;
            border-radius: 999px;
            border: 1px solid rgba(255, 255, 255, 0.45);
            color: #ffffff;
            text-decoration: none;
            font-weight: 600;
            transition: background-color 160ms ease;
        }

        .sti-back-home:hover {
            background: rgba(255, 255, 255, 0.14);
        }

        .sti-auth-card-wrap {
            display: flex;
            justify-content: flex-end;
        }

        .sti-auth-card {
            width: min(100%, 380px);
            background: rgba(255, 255, 255, 0.96);
            color: #0e2641;
            border-radius: 1.2rem;
            border: 1px solid rgba(0, 90, 167, 0.2);
            box-shadow: 0 34px 70px -40px rgba(2, 20, 39, 0.8);
            padding: 1.35rem 1.4rem 1.25rem;
            transition: opacity 340ms ease, transform 340ms ease;
        }

        .sti-auth-card-logo {
            display: flex;
            justify-content: center;
            margin-bottom: 0.85rem;
        }

        .sti-auth-card-logo img {
            width: 165px;
            max-width: 72%;
            height: auto;
            object-fit: contain;
        }

        .sti-auth-card-title {
            margin: 0 0 1rem;
            text-align: center;
            font-size: 1.05rem;
            font-weight: 800;
            color: #07345f;
        }

        html.sti-auth-intro .sti-auth-bg {
            transform: scale(1.18);
            filter: saturate(1.15);
        }

        html.sti-auth-intro .sti-auth-card {
            opacity: 0;
            transform: translateY(22px);
        }

        html.sti-auth-intro.sti-auth-intro-active .sti-auth-bg {
            transform: scale(1);
            filter: saturate(1);
        }

        html.sti-auth-intro.sti-auth-intro-active .sti-auth-card {
            opacity: 1;
            transform: translateY(0);
            transition-delay: 280ms;
        }

        @media (max-width: 767.98px) {
            .sti-auth-shell {
                display: flex;
                flex-direction: column;
                align-items: center;
                padding: 1.5rem 1rem 2.5rem;
                gap: 1.5rem;
                min-height: 100vh;
                justify-content: flex-start;
            }

            .sti-auth-card-wrap {
                justify-content: center;
                width: 100%;
                order: 1;
            }

            .sti-auth-card {
                width: 100%;
                max-width: 400px;
                border-radius: 1rem;
                padding: 1.15rem;
            }

            .sti-auth-brand {
                order: 2;
                max-width: 400px;
                width: 100%;
                padding: 0;
                text-align: center;
                display: flex;
                flex-direction: column;
                align-items: center;
            }

            .sti-auth-brand > :first-child {
                display: none;
            }

            .sti-auth-brand h1 {
                font-size: 1.1rem;
                margin: 0 0 0.5rem;
            }

            .sti-auth-brand p {
                font-size: 0.88rem;
                max-width: 34ch;
            }
        }

        .sti-page-out {
            position: fixed;
            inset: 0;
            background: rgba(3, 21, 40, 1);
            opacity: 0;
            z-index: 100;
            pointer-events: none;
            transition: opacity 360ms ease;
        }

        .sti-leaving .sti-page-out {
            opacity: 1;
        }

        @media (prefers-reduced-motion: reduce) {
            .sti-auth-bg,
            .sti-auth-card,
            .sti-page-out {
                transition: none !important;
            }
        }
    </style>
</head>
<body class="sti-auth-body">
    @php
        $routeLabel = request()->routeIs('login') ? 'Login' : (request()->routeIs('register*') ? 'Register' : 'Account');
    @endphp

    <div class="sti-auth-wrap">
        <div class="sti-page-out" aria-hidden="true"></div>
        <div class="sti-auth-bg" aria-hidden="true"></div>
        <div class="sti-auth-mask" aria-hidden="true"></div>

        <main class="sti-auth-shell">
            <section class="sti-auth-brand">
                <x-application-logo class="h-14 w-auto" />
                <h1>Exam &amp; Permit Management System</h1>
                <p>Secure access for students and staff. Continue with your account to manage registrations, schedules, and exam permits.</p>
                <a href="{{ url('/') }}" class="sti-back-home">Back to Home</a>
            </section>

            <section class="sti-auth-card-wrap">
                <div class="sti-auth-card" id="stiAuthCard">
                    <div class="sti-auth-card-logo">
                        <x-application-logo class="h-12 w-auto" />
                    </div>
                    <p class="sti-auth-card-title">{{ $routeLabel }}</p>
                    {{ $slot }}
                </div>
            </section>
        </main>
    </div>

    <script>
        (() => {
            const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            const isDesktop = window.matchMedia('(min-width: 768px)').matches;
            const shouldAnimate = isDesktop && !prefersReducedMotion && sessionStorage.getItem('stiAuthIntro') === '1';

            if (shouldAnimate) {
                sessionStorage.removeItem('stiAuthIntro');
                const root = document.documentElement;
                root.classList.add('sti-auth-intro');
                requestAnimationFrame(() => root.classList.add('sti-auth-intro-active'));
                window.setTimeout(() => {
                    root.classList.remove('sti-auth-intro', 'sti-auth-intro-active');
                }, 900);
            }

            // Back to Home transition
            const backLink = document.querySelector('.sti-back-home');
            if (backLink && !prefersReducedMotion) {
                backLink.addEventListener('click', (e) => {
                    e.preventDefault();
                    const href = backLink.getAttribute('href');
                    document.documentElement.classList.add('sti-leaving');
                    window.setTimeout(() => {
                        window.location.href = href;
                    }, 380);
                });
            }
        })();
    </script>

    @stack('scripts')
</body>
</html>
