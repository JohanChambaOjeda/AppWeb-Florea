{{--
    Plantilla de las páginas de error.

    Va en Blade y con los estilos dentro, no en Inertia: una página de error
    tiene que verse aunque la app esté rota o falte el build del frontend,
    que es justo cuando aparece un 500.
--}}
<!doctype html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#7c3aed">
        <title>@yield('title') - Florea</title>
        <style>
            * { box-sizing: border-box; }
            body {
                margin: 0;
                min-height: 100dvh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 24px;
                background: #faf8ff;
                color: #1c1917;
                font-family: system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
                text-align: center;
            }
            .card { max-width: 26rem; }
            img { width: 72px; height: 72px; border-radius: 18px; }
            .code {
                margin-top: 20px;
                font-size: 13px;
                font-weight: 700;
                letter-spacing: .08em;
                text-transform: uppercase;
                color: #7c3aed;
            }
            h1 { margin: 6px 0 10px; font-size: 24px; line-height: 1.25; }
            p { margin: 0 0 24px; font-size: 16px; line-height: 1.55; color: #57534e; }
            .actions { display: flex; flex-direction: column; gap: 10px; }
            a.button, button {
                display: flex;
                align-items: center;
                justify-content: center;
                min-height: 52px;
                padding: 0 24px;
                border: 0;
                border-radius: 12px;
                background: #7c3aed;
                color: #fff;
                font-size: 17px;
                font-weight: 600;
                text-decoration: none;
                cursor: pointer;
            }
            a.secondary {
                background: transparent;
                color: #7c3aed;
                border: 2px solid #ddd6fe;
            }
            @media (prefers-color-scheme: dark) {
                body { background: #1c1917; color: #fafaf9; }
                p { color: #a8a29e; }
                a.secondary { border-color: #4c1d95; color: #c4b5fd; }
            }
        </style>
    </head>
    <body>
        <div class="card">
            <img src="/icons/icon-192.png" alt="Florea">
            <div class="code">@yield('code')</div>
            <h1>@yield('title')</h1>
            <p>@yield('message')</p>
            <div class="actions">
                @yield('actions')
                <a class="button" href="{{ url('/dashboard') }}">Ir al inicio</a>
            </div>
        </div>
    </body>
</html>
