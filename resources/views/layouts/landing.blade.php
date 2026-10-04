<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aprende Guate</title>
    <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('images/aprende-guate-app-icon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/aprende-guate-app-icon.png') }}">
    <script>
        if (localStorage.getItem('platform-theme') === 'dark') document.documentElement.classList.add('dark');
        document.addEventListener('DOMContentLoaded', () => {
            const isDark = document.documentElement.classList.contains('dark');
            document.querySelectorAll('[data-theme-label]').forEach((el) => {
                el.textContent = isDark ? 'Tema claro' : 'Tema oscuro';
            });
        });

        function toggleLandingTheme() {
            const isDark = document.documentElement.classList.toggle('dark');
            localStorage.setItem('platform-theme', isDark ? 'dark' : 'light');
            document.querySelectorAll('[data-theme-label]').forEach((el) => {
                el.textContent = isDark ? 'Tema claro' : 'Tema oscuro';
            });
        }
    </script>
    <style>
        :root {
            color-scheme: light;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            color: #0f172a;
            background: #f8fafc
        }

        * {
            box-sizing: border-box
        }

        html {
            scroll-behavior: smooth
        }

        body {
            margin: 0;
            min-width: 320px;
            overflow-x: hidden
        }

        a {
            color: inherit;
            text-decoration: none
        }

        .topbar {
            min-height: 72px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
            padding: 12px clamp(20px, 4vw, 72px);
            background: rgba(255, 255, 255, .96);
            border-bottom: 1px solid #e2e8f0
        }

        .brand {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            font-weight: 800;
            color: #172554;
            font-size: 18px
        }

        .brand-mark {
            width: 40px;
            height: 40px;
            display: grid;
            place-items: center;
            background: #1d4ed8;
            border-radius: 12px;
            color: #fff;
            box-shadow: 0 10px 20px rgba(29, 78, 216, .2)
        }

        .top-actions {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 14px;
            font-weight: 700;
            color: #1e40af
        }

        .button {
            padding: 11px 17px;
            border-radius: 11px;
            border: 1px solid #bfdbfe;
            white-space: nowrap
        }

        .button-primary {
            color: #fff;
            background: #1d4ed8;
            border-color: #1d4ed8;
            box-shadow: 0 8px 16px rgba(29, 78, 216, .16)
        }

        main {
            width: min(100% - 32px, 1500px);
            margin: 0 auto;
            padding: clamp(28px, 4vw, 64px) 0 56px
        }

        .hero {
            overflow: hidden;
            position: relative;
            padding: clamp(32px, 5vw, 84px);
            border-radius: 28px;
            color: #fff;
            background: linear-gradient(135deg, #172554 0%, #1d4ed8 58%, #2563eb 100%);
            box-shadow: 0 20px 45px rgba(30, 64, 175, .22)
        }

        .hero:before {
            content: "";
            position: absolute;
            inset: 0;
            background: radial-gradient(circle at 87% 18%, rgba(125, 211, 252, .48), transparent 24%), radial-gradient(circle at 72% 90%, rgba(147, 197, 253, .28), transparent 22%)
        }

        .hero-content {
            position: relative;
            max-width: 850px
        }

        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            padding: 10px 15px;
            border: 1px solid rgba(191, 219, 254, .4);
            border-radius: 999px;
            background: rgba(255, 255, 255, .1);
            color: #dbeafe;
            font-size: 14px;
            font-weight: 700
        }

        .dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #5eead4
        }

        .hero h1 {
            margin: 22px 0 0;
            max-width: 870px;
            font-size: clamp(40px, 4.5vw, 70px);
            line-height: 1.04;
            letter-spacing: -.055em
        }

        .hero p {
            max-width: 710px;
            margin: 22px 0 0;
            color: #dbeafe;
            font-size: clamp(16px, 1.35vw, 20px);
            line-height: 1.6
        }

        .hero-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 14px;
            margin-top: 30px
        }

        .hero-actions .button {
            font-size: 15px
        }

        .button-light {
            color: #1e3a8a;
            background: #fff;
            border-color: #fff
        }

        .button-outline {
            color: #fff;
            border-color: rgba(255, 255, 255, .45);
            background: rgba(255, 255, 255, .06)
        }

        .scroll-hint {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin: 18px auto 0;
            color: #64748b;
            font-size: 13px;
            font-weight: 700;
            text-align: center
        }

        .scroll-hint-arrow {
            display: grid;
            place-items: center;
            width: 24px;
            height: 24px;
            border: 1px solid #bfdbfe;
            border-radius: 50%;
            color: #1d4ed8;
            font-size: 17px;
            line-height: 1;
            animation: hint-bounce 1.8s ease-in-out infinite
        }

        @keyframes hint-bounce {

            0%,
            100% {
                transform: translateY(0)
            }

            50% {
                transform: translateY(4px)
            }
        }

        .features {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 20px;
            margin-top: 30px
        }

        .feature {
            padding: 25px;
            border: 1px solid #e2e8f0;
            border-radius: 20px;
            background: #fff;
            box-shadow: 0 2px 6px rgba(15, 23, 42, .04)
        }

        .number {
            display: grid;
            width: 40px;
            height: 40px;
            place-items: center;
            border-radius: 12px;
            color: #075985;
            background: #e0f2fe;
            font-size: 12px;
            font-weight: 800
        }

        .feature h2 {
            margin: 20px 0 0;
            font-size: 18px
        }

        .feature p {
            margin: 9px 0 0;
            color: #64748b;
            font-size: 14px;
            line-height: 1.65
        }

        @media(max-width:840px) {
            .features {
                grid-template-columns: repeat(2, minmax(0, 1fr))
            }
        }

        @media(max-height:760px) and (min-width:561px) {
            main {
                padding-top: 24px
            }

            .hero {
                padding: 34px 6vw
            }

            .hero h1 {
                font-size: clamp(38px, 4.2vw, 58px)
            }

            .hero p {
                margin-top: 16px
            }

            .hero-actions {
                margin-top: 22px
            }

            .scroll-hint {
                margin-top: 12px
            }
        }

        @media(max-width:560px) {
            .topbar {
                min-height: 64px;
                padding: 10px 16px
            }

            .brand {
                font-size: 16px
            }

            .brand-mark {
                width: 36px;
                height: 36px
            }

            .top-actions .login {
                display: none
            }

            main {
                width: min(100% - 24px, 1500px);
                padding-top: 24px
            }

            .hero {
                border-radius: 22px;
                padding: 30px 24px
            }

            .hero h1 {
                font-size: clamp(36px, 11vw, 52px)
            }

            .hero p {
                font-size: 16px;
                line-height: 1.5
            }

            .hero-actions {
                margin-top: 24px
            }

            .features {
                grid-template-columns: 1fr;
                gap: 14px;
                margin-top: 24px
            }

            .feature {
                padding: 20px
            }

            .scroll-hint {
                font-size: 12px
            }
        }

        .brand-mark-image {
            width: 40px;
            height: 40px;
            display: block;
            border-radius: 12px;
            object-fit: cover;
            box-shadow: 0 10px 20px rgba(33, 150, 243, .22)
        }

        .scroll-hint {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 7px;
            margin: 16px auto 0;
            height: 16px;
            color: transparent;
            font-size: 0
        }

        .scroll-hint-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: #93c5fd;
            opacity: .35;
            animation: hint-pulse 1.8s ease-in-out infinite
        }

        .scroll-hint-dot:nth-child(2) {
            animation-delay: .2s
        }

        .scroll-hint-dot:nth-child(3) {
            animation-delay: .4s
        }

        @keyframes hint-pulse {

            0%,
            100% {
                opacity: .25;
                transform: translateY(0) scale(.85)
            }

            50% {
                opacity: 1;
                transform: translateY(5px) scale(1.15)
            }
        }

        .theme-toggle {
            padding: 9px 12px;
            border: 1px solid #bfdbfe;
            border-radius: 11px;
            background: #fff;
            color: #1e40af;
            font: inherit;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer
        }

        .theme-toggle:hover {
            background: #eff6ff
        }

        .dark body {
            background: #020617;
            color: #e2e8f0
        }

        .dark .topbar {
            background: rgba(15, 23, 42, .96);
            border-color: #1e293b
        }

        .dark .brand {
            color: #f8fafc
        }

        .dark .top-actions {
            color: #bfdbfe
        }

        .dark .theme-toggle {
            border-color: #334155;
            background: #1e293b;
            color: #dbeafe
        }

        .dark .theme-toggle:hover {
            background: #334155
        }

        .dark .feature {
            border-color: #334155;
            background: #0f172a;
            color: #e2e8f0
        }

        .dark .feature h2 {
            color: #f8fafc
        }

        .dark .feature p {
            color: #cbd5e1
        }

        .dark .scroll-hint-dot {
            background: #81d4fa
        }
    </style>
    @vite(['resources/css/app.css'])
</head>

<body>
    <header class="topbar"><a href="{{ route('home') }}" class="brand"><img src="{{ asset('images/aprende-guate-app-icon.png') }}" alt=""
                class="brand-mark-image"><span>Aprende Guate</span></a>
        <nav class="top-actions" aria-label="Acciones de cuenta"><button type="button" class="theme-toggle" onclick="toggleLandingTheme()"
                aria-label="Cambiar tema"><span data-theme-label>Tema oscuro</span></button>@auth <a class="button" href="{{ route('adaptive.dashboard') }}">Ir
                    a mi panel</a>
            @else
                <a class="login" href="{{ route('login') }}">Iniciar sesión</a>
                @if (Route::has('register'))
                    <a class="button button-primary" href="{{ route('register') }}">Crear cuenta</a>
                @endif @endauth
            </nav>
        </header>
        <main>@yield('content')</main>
        @include('components.platform-footer')
    </body>

    </html>
