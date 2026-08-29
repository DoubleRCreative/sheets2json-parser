<!doctype html>
<html>
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <title>{{ config('app.name') }} API Reference</title>
    </head>
    <body>
        <script
            id="api-reference"
            data-url="{{ route('docs.openapi', [], false) }}"
            data-configuration='@json(['theme' => 'default', 'showSidebar' => true])'
        ></script>
        <script src="https://cdn.jsdelivr.net/npm/@scalar/api-reference"></script>
    </body>
</html>