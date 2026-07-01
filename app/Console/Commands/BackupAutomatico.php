<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class BackupAutomatico extends Command
{
    /**
     * Nombre del comando Artisan para ejecutarlo manualmente o desde el scheduler.
     */
    protected $signature = 'backup:automatico';

    protected $description = 'Genera una copia de seguridad automática de la base de datos PostgreSQL (CU-11)';

    public function handle(): int
    {
        $nombreArchivo = 'auto_backup_'.date('Y-m-d_H-i-s').'.sql';

        $dbDatabase = config('database.connections.pgsql.database');
        $dbUser     = config('database.connections.pgsql.username');
        $dbPassword = config('database.connections.pgsql.password');
        $dbHost     = config('database.connections.pgsql.host');
        $dbPort     = config('database.connections.pgsql.port');

        $dirBackups  = storage_path('app/public/backups');
        $rutaDestino = $dirBackups.DIRECTORY_SEPARATOR.$nombreArchivo;

        if (! file_exists($dirBackups)) {
            mkdir($dirBackups, 0755, true);
        }

        // Detectar pg_dump en versiones conocidas de PostgreSQL en Windows
        $pgBin  = $this->getPgBinPath();
        $pgDump = $pgBin ? '"'.$pgBin.'\\pg_dump.exe"' : 'pg_dump';

        putenv('PGPASSWORD='.$dbPassword);

        $comando = "{$pgDump} -h {$dbHost} -p {$dbPort} -U {$dbUser} -d {$dbDatabase} -F p --no-password -f \"{$rutaDestino}\" 2>&1";

        exec($comando, $output, $resultCode);

        putenv('PGPASSWORD='); // Limpiar variable de entorno por seguridad

        if ($resultCode !== 0) {
            $detalle = implode("\n", $output);
            $this->error("Error al generar backup automático: {$detalle}");
            \Illuminate\Support\Facades\Log::error("BackupAutomatico falló: {$detalle}");

            return self::FAILURE;
        }

        $this->info("Backup automático generado: {$nombreArchivo}");
        \Illuminate\Support\Facades\Log::info("BackupAutomatico exitoso: {$nombreArchivo}");

        // ZIP de comprobantes (sincroniza archivos físicos con el backup SQL)
        $this->zipComprobantes($dirBackups, $nombreArchivo);

        // Limpiar backups automáticos antiguos — mantener solo los últimos 30
        $this->limpiarBackupsAntiguos($dirBackups, 30);

        return self::SUCCESS;
    }

    /**
     * Elimina backups automáticos más allá del límite permitido (los más viejos primero).
     */
    private function limpiarBackupsAntiguos(string $directorio, int $limite): void
    {
        $archivos = glob($directorio.DIRECTORY_SEPARATOR.'auto_backup_*.sql');

        if (! $archivos || count($archivos) <= $limite) {
            return;
        }

        // Ordenar por fecha de modificación: más antiguos primero
        usort($archivos, fn ($a, $b) => filemtime($a) - filemtime($b));

        $aEliminar = array_slice($archivos, 0, count($archivos) - $limite);

        foreach ($aEliminar as $archivo) {
            @unlink($archivo);
            \Illuminate\Support\Facades\Log::info("BackupAutomatico: archivo antiguo eliminado: ".basename($archivo));
        }
    }

    /**
     * Detecta el directorio bin de PostgreSQL instalado en Windows.
     */
    private function getPgBinPath(): string
    {
        foreach ([17, 16, 15, 14, 13] as $version) {
            $path = "C:\\Program Files\\PostgreSQL\\{$version}\\bin";
            if (file_exists($path.'\\pg_dump.exe')) {
                return $path;
            }
        }

        return '';
    }

    /**
     * Crea un ZIP de la carpeta comprobantes junto al archivo SQL generado.
     */
    private function zipComprobantes(string $dirBackups, string $nombreSql): void
    {
        $dirComprobantes = storage_path('app/public/comprobantes');
        if (! file_exists($dirComprobantes) || ! class_exists('ZipArchive')) {
            return;
        }

        $nombreZip = 'comprobantes_'.str_replace('.sql', '.zip', $nombreSql);
        $rutaZip   = $dirBackups.DIRECTORY_SEPARATOR.$nombreZip;

        $zip = new \ZipArchive();
        if ($zip->open($rutaZip, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            return;
        }

        $archivos = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dirComprobantes, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($archivos as $archivo) {
            $relativo = substr($archivo->getPathname(), strlen($dirComprobantes) + 1);
            $zip->addFile($archivo->getPathname(), $relativo);
        }

        $zip->close();
        \Illuminate\Support\Facades\Log::info("BackupAutomatico: ZIP comprobantes generado: {$nombreZip}");
    }
}
