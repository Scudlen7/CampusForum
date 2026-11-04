<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $pageProps['pageTitle'] ?? '华东师范大学校园论坛' }}</title>
    @vite(['resources/css/dhx.css', 'resources/js/pages/' . ($pageScript ?? 'home') . '.js'])
    @stack('head')
</head>
<body>
    <div id="app"></div>
    <script>
        window.__PAGE_PROPS__ = @json($pageProps ?? []);
    </script>
    @stack('scripts')
</body>
</html>
