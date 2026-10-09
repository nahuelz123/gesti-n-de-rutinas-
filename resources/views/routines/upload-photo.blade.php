<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Importar rutina desde archivo</title>
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
    <h1>Importar rutina desde archivo</h1>
    <p>Elegí una foto, un PDF, un documento Word (.docx) o una planilla Excel (.xlsx), hasta 8 MB. Se envía el archivo o su texto a {{ app(\App\Services\FileAiReader::class)->providerName() }} y VisionFit no conserva el archivo después del procesamiento. Los formatos antiguos .doc y .xls se pueden exportar a PDF. Vas a revisar el borrador y corregir cada ejercicio antes de guardarlo. No se asigna a ningún alumno automáticamente.</p>
    <p role="note">Evitá que el archivo muestre nombres, diagnósticos u otros datos personales que no hagan falta para transcribir la rutina.</p>

    <form method="post" action="{{ route('routines.photo.store') }}" enctype="multipart/form-data" onsubmit="this.querySelector('button').disabled=true; this.querySelector('button').textContent='Leyendo archivo…';">
        @csrf
        <label for="photo">Archivo de la rutina</label>
        <input id="photo" name="photo" type="file" accept=".jpg,.jpeg,.png,.webp,.pdf,.docx,.xlsx,image/jpeg,image/png,image/webp,application/pdf" required>
        <label style="display:flex; align-items:flex-start; gap:9px; margin-top:1rem; font-weight:400; font-size:13px; line-height:1.5;"><input name="photo_processing_consent" type="checkbox" value="1" required style="width:auto; margin-top:3px;">Autorizo que se envíe este archivo a {{ app(\App\Services\FileAiReader::class)->providerName() }} para generar un borrador que voy a revisar.</label>
        @error('photo') <div class="error" role="alert">{{ $message }}</div> @enderror
        <button type="submit">Crear borrador y revisar</button>
    </form>
    @include('partials.legal-links')
</main>
@include('partials.cookie-notice')
</body>
</html>
