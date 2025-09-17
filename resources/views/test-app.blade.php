<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title inertia>{{ config('app.name', 'Laravel') }}</title>
        @inertiaHead
    </head>
    <body>
        <h1>Basic Test View</h1>
        @inertia
        <script>
            console.log('Basic test view loaded');
        </script>
    </body>
</html>