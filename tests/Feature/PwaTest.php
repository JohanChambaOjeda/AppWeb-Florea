<?php

use App\Models\Variety;

test('el manifiesto describe la app instalable', function () {
    $path = public_path('manifest.webmanifest');

    expect($path)->toBeFile();

    $manifest = json_decode((string) file_get_contents($path), true);

    expect($manifest)
        ->toHaveKey('name', 'Florea')
        ->toHaveKey('short_name', 'Florea')
        ->toHaveKey('display', 'standalone')
        ->toHaveKey('lang', 'es-EC')
        ->toHaveKey('start_url', '/dashboard')
        // violet-600, el color de la app.
        ->toHaveKey('theme_color', '#7c3aed');

    $purposes = array_column($manifest['icons'], 'purpose');
    $sizes = array_column($manifest['icons'], 'sizes');

    expect($sizes)->toContain('192x192')->toContain('512x512')
        // Android recorta el ícono: sin maskable sale con bordes feos.
        ->and($purposes)->toContain('maskable');
});

test('los íconos existen y pesan lo que dicen', function (string $file, int $size) {
    $path = public_path($file);

    expect($path)->toBeFile();

    [$width, $height] = getimagesize($path);

    expect($width)->toBe($size)->and($height)->toBe($size);
})->with([
    ['icons/icon-192.png', 192],
    ['icons/icon-512.png', 512],
    ['icons/icon-512-maskable.png', 512],
    ['icons/apple-touch-icon.png', 180],
]);

test('el comando vuelve a generar los íconos', function () {
    $this->artisan('florea:icons')->assertSuccessful();

    expect(public_path('icons/icon-512.png'))->toBeFile();
});

test('la página "Sin conexión" se explica sola, sin depender del build', function () {
    $html = (string) file_get_contents(public_path('offline.html'));

    expect($html)
        ->toContain('Sin conexión')
        ->toContain('lang="es"')
        ->toContain('Reintentar')
        // Los estilos van dentro: si dependiera del CSS compilado, se vería
        // rota justo cuando no hay red para descargarlo.
        ->toContain('<style>')
        ->not->toContain('/build/');
});

test('el service worker no guarda en caché los datos de la app', function () {
    $sw = (string) file_get_contents(public_path('sw.js'));

    // Deja pasar las peticiones de Inertia y todo lo que no sea GET.
    expect($sw)
        ->toContain("request.headers.get('X-Inertia')")
        ->toContain("request.method !== 'GET'")
        // La navegación va primero a la red.
        ->toContain('fetch(request).catch')
        ->toContain('/offline.html');
});

test('la vista raíz enlaza el manifiesto y registra el service worker', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertSee('rel="manifest"', false)
        ->assertSee('/manifest.webmanifest', false)
        ->assertSee('name="theme-color" content="#7c3aed"', false)
        ->assertSee('apple-mobile-web-app-capable', false)
        ->assertSee("navigator.serviceWorker.register('/sw.js')", false);
});

test('la pantalla de instalar se ve sin haber entrado', function () {
    $this->get(route('install'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('install'));
});

test('la pantalla de instalar también se ve con sesión', function () {
    $this->actingAs(growerUser(Variety::factory()->create()))
        ->get(route('install'))
        ->assertOk();
});

/**
 * La vista raíz carga un chunk de Vite por página, así que una pantalla
 * nueva da 500 en el navegador hasta que se corre `npm run build`. Los demás
 * tests no lo ven porque TestCase usa withoutVite().
 *
 * Esto compara las páginas que existen con las que quedaron compiladas.
 */
test('todas las páginas están en el manifiesto de Vite', function () {
    $manifestPath = public_path('build/manifest.json');

    if (! file_exists($manifestPath)) {
        $this->markTestSkipped('No hay build todavía: corre npm run build.');
    }

    $manifest = json_decode((string) file_get_contents($manifestPath), true);

    $pages = collect(File::allFiles(resource_path('js/pages')))
        ->filter(fn ($file) => $file->getExtension() === 'tsx')
        ->map(fn ($file) => str_replace('\\', '/', 'resources/js/pages/'.$file->getRelativePathname()));

    $missing = $pages->reject(fn (string $page) => isset($manifest[$page]))->values();

    expect($missing->all())->toBe([], 'Falta compilar: corre npm run build.');
});
