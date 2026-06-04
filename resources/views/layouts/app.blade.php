<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Thinkra Docs')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        thinkra: {
                            purple: '#9e27b5',
                            navy: '#0f206c',
                            sidebar: '#1b0738',
                            'sidebar-alt': '#21083f',
                            surface: '#f6f7fb',
                        }
                    },
                    fontFamily: {
                        sans: ['Cairo', 'system-ui', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.3/dist/cdn.min.js"></script>
    <style>[x-cloak]{display:none!important}</style>
    @stack('head')
</head>
<body class="min-h-screen bg-[#f6f7fb] font-sans text-slate-800 antialiased">
    @yield('body')
    @stack('scripts')
</body>
</html>
