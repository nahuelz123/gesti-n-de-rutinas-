<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cargar rutina desde foto</title>
    <style>
        :root { color-scheme: dark; font-family: system-ui, sans-serif; background: #111827; color: #f9fafb; }
        body { margin: 0; padding: 2rem 1rem; }
        main { max-width: 34rem; margin: 3rem auto; padding: 2rem; background: #1f2937; border-radius: 1rem; }
        h1 { font-size: 1.5rem; }
        p { line-height: 1.5; color: #d1d5db; }
        label { display: block; margin: 1.5rem 0 .6rem; font-weight: 600; }
        input { box-sizing: border-box; width: 100%; padding: .8rem; border: 1px solid #6b7280; border-radius: .5rem; }
        button { margin-top: 1.5rem; padding: .85rem 1.2rem; border: 0; border-radius: .5rem; background: #f59e0b; color: #111827; font-weight: 700; cursor: pointer; }
        button:disabled { opacity: .65; }
        a { color: #fbbf24; }
        .error { margin-top: 1rem; color: #fca5a5; }
    </style>
</head>
<body>
<main>
    <a href="{{ \App\Filament\Resources\Routines\RoutineResource::getUrl('create') }}">← Volver a crear rutina</a>
    <h1>Cargar rutina desde foto</h1>
    <p>Elegí una foto nítida (JPG, PNG o WebP, hasta 8 MB). Vas a revisar el borrador y corregir cada ejercicio antes de guardarlo. No se asigna a ningún alumno automáticamente.</p>

    <form method="post" action="{{ route('routines.photo.store') }}" enctype="multipart/form-data" onsubmit="this.querySelector('button').disabled=true; this.querySelector('button').textContent='Leyendo foto…';">
        @csrf
        <label for="photo">Foto de la rutina</label>
        <input id="photo" name="photo" type="file" accept="image/jpeg,image/png,image/webp" required>
        @error('photo') <div class="error" role="alert">{{ $message }}</div> @enderror
        <button type="submit">Leer foto y revisar</button>
    </form>
</main>
</body>
</html>
