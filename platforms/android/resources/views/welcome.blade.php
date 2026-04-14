<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'AppGastos') }}</title>
        <link rel="icon" type="image/svg+xml" href="{{ asset('images/logo.svg') }}">
        <link rel="icon" type="image/png" sizes="512x512" href="{{ asset('icon.png') }}">
        <link rel="apple-touch-icon" href="{{ asset('icon.png') }}">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=space-grotesk:400,500,700|dm-sans:400,500,700" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <style>
            :root {
                color-scheme: dark;
            }

            html, body {
                margin: 0;
                min-height: 100%;
                background:
                    radial-gradient(circle at top, rgba(255, 120, 89, 0.18), transparent 34%),
                    radial-gradient(circle at bottom, rgba(91, 240, 196, 0.12), transparent 30%),
                    #08101b;
            }

            body {
                font-family: 'DM Sans', sans-serif;
            }

            #boot-screen {
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 24px;
            }

            .boot-card {
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 18px;
                padding: 28px 24px;
                border: 1px solid rgba(255, 255, 255, 0.08);
                border-radius: 28px;
                background: rgba(12, 22, 36, 0.78);
                box-shadow: 0 24px 60px rgba(0, 0, 0, 0.28);
                backdrop-filter: blur(18px);
            }

            .boot-logo {
                width: 84px;
                height: 84px;
                border-radius: 24px;
                padding: 14px;
                background: rgba(255, 255, 255, 0.04);
                border: 1px solid rgba(255, 255, 255, 0.08);
            }

            .boot-text {
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 6px;
                color: #f5f7fb;
                text-align: center;
            }

            .boot-title {
                margin: 0;
                font-family: 'Space Grotesk', sans-serif;
                font-size: 1.35rem;
                font-weight: 700;
                letter-spacing: 0.04em;
            }

            .boot-subtitle {
                margin: 0;
                font-size: 0.98rem;
                color: rgba(245, 247, 251, 0.7);
            }
        </style>
    </head>
    <body>
        <script>
            window.__APP_DATA = @json($appData ?? ['page' => 'home', 'title' => '']);
        </script>
        <div id="app">
            <div id="boot-screen" aria-live="polite">
                <div class="boot-card">
                    <img class="boot-logo" src="{{ asset('images/logo.svg') }}" alt="Logo de AppGastos">
                    <div class="boot-text">
                        <p class="boot-title">AppGastos</p>
                        <p class="boot-subtitle">Iniciando</p>
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>
