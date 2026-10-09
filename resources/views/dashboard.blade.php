<!DOCTYPE html>
<html lang="">

<head>
    <meta charset="UTF-8">
    <link rel="icon" href="{{ asset('/logo_jateng.png') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') }}</title>
    <link rel="stylesheet" href="{{ asset('/codebase/css/codebase.min-5.11.css') }}">
</head>

<body>
    <div id="app"></div>
    @vite(['resources/js/app.js'])
    <script src="{{ asset('/js-loading-overlay/js-loading-overlay-custom.js') }}"></script>
    <script src="{{ asset('/codebase/js/codebase.app.min-5.11.js') }}"></script>
    <script>
        window.appConfig = {
            appName: "{{ config('app.name') }}",
            year: "{{ date('Y') }}",
            kabkota: "{{ config('wilayah.active') }}",
            winnersPerPage: {{ (int) config('wilayah.winners_per_page', 5) }},
            platJateng: `{!! json_encode(config('plat_jateng')) !!}`
        };
    </script>
</body>

</html>