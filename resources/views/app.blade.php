<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
        
        <!-- Tarteaucitron CSS -->
        <link rel="stylesheet" href="{{ asset('tarteaucitron/css/tarteaucitron.min.css') }}" />
        
        <!-- Scripts -->
        @routes
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
        
        <!-- Tarteaucitron JS -->
        <script src="{{ asset('tarteaucitron/tarteaucitron.min.js') }}"></script>
        <script type="text/javascript">
            tarteaucitron.init({
                "privacyUrl": "/privacy-policy",
                "hashtag": "#tarteaucitron",
                "cookieName": "tarteaucitron",
                "orientation": "bottom",
                "groupServices": false,
                "showAlertSmall": false,
                "cookieslist": true,
                "closePopup": false,
                "showIcon": true,
                "iconPosition": "BottomRight",
                "adblocker": false,
                "DenyAllCta": true,
                "AcceptAllCta": true,
                "highPrivacy": true,
                "handleBrowserDNTRequest": false,
                "removeCredit": false,
                "moreInfoLink": true,
                "useExternalCss": false,
                "cookieDomain": "",
                "readmoreLink": "/privacy-policy",
                "mandatory": true,
                "mandatoryCta": true,
                "lang": "fr"
            });
        </script>
    </body>
</html>
