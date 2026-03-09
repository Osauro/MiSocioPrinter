<?php

namespace MiSocio;

use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;
use Mike42\Escpos\PrintConnectors\NetworkPrintConnector;
use Mike42\Escpos\PrintConnectors\FilePrintConnector;

class PrinterManager
{
    /**
     * Detecta las impresoras instaladas en el sistema.
     * En Windows usa wmic (fallback PowerShell).
     *
     * @return string[]
     */
    public static function detectPrinters(): array
    {
        $printers = [];

        if (PHP_OS_FAMILY === 'Windows') {
            // Metodo principal: wmic
            $output = [];
            exec('wmic printer get name /format:list 2>NUL', $output);

            foreach ($output as $line) {
                $line = trim($line);
                if (stripos($line, 'Name=') === 0) {
                    $name = trim(substr($line, 5));
                    if ($name !== '') {
                        $printers[] = $name;
                    }
                }
            }

            // Fallback: PowerShell (Windows 10 21H1+ reemplaza wmic)
            if (empty($printers)) {
                $output = [];
                exec(
                    'powershell -NoProfile -Command "Get-Printer | Select-Object -ExpandProperty Name" 2>NUL',
                    $output
                );
                foreach ($output as $line) {
                    $line = trim($line);
                    if ($line !== '') {
                        $printers[] = $line;
                    }
                }
            }
        }

        return array_values(array_unique($printers));
    }

    /**
     * Crea y retorna el conector adecuado segun la configuracion.
     *
     * @param  string $printerName  Nombre de impresora (usa .env si vacio)
     * @return \Mike42\Escpos\PrintConnectors\PrintConnector
     */
    public static function createConnector(string $printerName = '')
    {
        if ($printerName === '') {
            $printerName = env('PRINTER_NAME', '');
        }

        $type = strtolower(env('PRINTER_TYPE', 'windows'));

        switch ($type) {
            case 'network':
                $host = env('PRINTER_HOST', '192.168.1.100');
                $port = (int) env('PRINTER_PORT', 9100);
                return new NetworkPrintConnector($host, $port);

            case 'file':
                $path = env('PRINTER_FILE', 'php://stdout');
                return new FilePrintConnector($path);

            case 'windows':
            default:
                return new WindowsPrintConnector($printerName);
        }
    }

    /**
     * Verifica si la impresora esta disponible en el sistema.
     */
    public static function isAvailable(string $printerName = ''): bool
    {
        if ($printerName === '') {
            $printerName = env('PRINTER_NAME', '');
        }

        if ($printerName === '') {
            return false;
        }

        if (PHP_OS_FAMILY === 'Windows') {
            $printers = self::detectPrinters();
            foreach ($printers as $p) {
                if (strtolower($p) === strtolower($printerName)) {
                    return true;
                }
            }
            return false;
        }

        return true;
    }
}
