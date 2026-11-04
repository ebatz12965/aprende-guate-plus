<?php

namespace App\Support;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;

/**
 * Discos de archivos de la plataforma. En local y en las pruebas son los discos del servidor;
 * en producción, el bucket privado de S3 (ver config/filesystems.php).
 */
class Archivos
{
    /** CV, evidencias, adjuntos de entrevistas y narraciones de la biblioteca. */
    public static function disco(): Filesystem
    {
        return Storage::disk(self::nombre());
    }

    public static function nombre(): string
    {
        return (string) config('filesystems.archivos', 'local');
    }

    /** Fotos de perfil. */
    public static function fotos(): Filesystem
    {
        return Storage::disk(self::nombreFotos());
    }

    public static function nombreFotos(): string
    {
        return (string) config('filesystems.fotos', 'public');
    }

    /**
     * Ejecuta $usar con una ruta local del archivo (los extractores de texto leen del disco).
     * Si el archivo está en S3, se descarga a un temporal que se borra al terminar.
     *
     * @template T
     *
     * @param  callable(string): T  $usar
     * @return T|null
     */
    public static function conRutaLocal(string $ruta, callable $usar): mixed
    {
        $disco = self::disco();
        if (! $disco->exists($ruta)) {
            return null;
        }

        if (config('filesystems.disks.'.self::nombre().'.driver') === 'local') {
            return $usar($disco->path($ruta));
        }

        $temporal = tempnam(sys_get_temp_dir(), 'ag-archivo-');
        try {
            $origen = $disco->readStream($ruta);
            $destino = fopen($temporal, 'wb');
            stream_copy_to_stream($origen, $destino);
            fclose($destino);
            is_resource($origen) && fclose($origen);

            return $usar($temporal);
        } finally {
            @unlink($temporal);
        }
    }
}
