<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @class(['dark' => ($appearance ?? 'system') == 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        {{-- Inline script to detect system dark mode preference and apply it immediately --}}
        <script>
            (function() {
                const appearance = '{{ $appearance ?? "system" }}';

                if (appearance === 'system') {
                    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

                    if (prefersDark) {
                        document.documentElement.classList.add('dark');
                    }
                }
            })();
        </script>

        {{-- Inline style to set the HTML background color based on our theme in app.css --}}
        <style>
            html {
                background-color: oklch(1 0 0);
            }

            html.dark {
                background-color: oklch(0.145 0 0);
            }
        </style>

        <title inertia>{{ config('app.name', 'BancoSystem') }}</title>

        <link rel="icon" href="{{ asset('adala2-removebg-preview.png') }}" type="image/png">
        <link rel="apple-touch-icon" href="{{ asset('adala2-removebg-preview.png') }}">

        {{-- Font Loading with fallback --}}
        <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
        <link rel="dns-prefetch" href="https://fonts.bunny.net">
        
        {{-- Local fallback fonts --}}
        <link rel="stylesheet" href="/css/fonts-fallback.css">
        
        {{-- Fallback font definition --}}
        <style>
            /* System font stack as fallback */
            body {
                font-family: 'Instrument Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Helvetica Neue', sans-serif;
            }
        </style>
        
        {{-- Load font with error handling and retry logic --}}
        <script>
            (function() {
                let retryCount = 0;
                const maxRetries = 2;
                
                function loadFont() {
                    const fontLink = document.createElement('link');
                    fontLink.rel = 'stylesheet';
                    fontLink.href = 'https://fonts.bunny.net/css?family=instrument-sans:400,500,600&display=swap';
                    
                    // Add timeout for slow connections
                    const timeout = setTimeout(() => {
                        if (retryCount < maxRetries) {
                            retryCount++;
                            console.warn('Font loading timeout, retrying... (attempt ' + retryCount + ')');
                            fontLink.remove();
                            setTimeout(loadFont, 1000); // Retry after 1 second
                        } else {
                            console.warn('Failed to load Instrument Sans font after ' + maxRetries + ' attempts, using fallback');
                        }
                    }, 5000); // 5 second timeout
                    
                    // Add error handler
                    fontLink.onerror = function() {
                        clearTimeout(timeout);
                        if (retryCount < maxRetries) {
                            retryCount++;
                            console.warn('Failed to load font, retrying... (attempt ' + retryCount + ')');
                            setTimeout(loadFont, 1000); // Retry after 1 second
                        } else {
                            console.warn('Failed to load Instrument Sans font, using system fonts as fallback');
                        }
                    };
                    
                    // Add load handler
                    fontLink.onload = function() {
                        clearTimeout(timeout);
                        console.info('Instrument Sans font loaded successfully');
                    };
                    
                    document.head.appendChild(fontLink);
                }
                
                // Start loading the font
                loadFont();
            })();
        </script>

        @routes
        @viteReactRefresh
        @vite(['resources/js/app.tsx', "resources/js/pages/{$page['component']}.tsx"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
