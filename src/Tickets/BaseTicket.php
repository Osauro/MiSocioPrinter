<?php

namespace MiSocio\Tickets;

use Mike42\Escpos\Printer;
use Mike42\Escpos\EscposImage;
use MiSocio\PrinterManager;

/**
 * Clase base para todos los tipos de tickets.
 * Provee metodos comunes de impresion: encabezado, pie, formato de filas.
 */
abstract class BaseTicket
{
    protected Printer $printer;
    protected int     $paperWidth;
    protected array   $config;

    public function __construct()
    {
        $this->paperWidth = (int) env('PAPER_WIDTH', 37);
        $this->config = [
            'company_name'    => env('COMPANY_NAME',    'MI EMPRESA'),
            'company_logo'    => env('COMPANY_LOGO',    ''),
            'footer_message'  => env('FOOTER_MESSAGE',  'GRACIAS POR SU COMPRA'),
            'contact_info'    => env('CONTACT_INFO',    ''),
            'show_logo'       => envBool('SHOW_LOGO',   true),
            'show_qr'         => envBool('SHOW_QR',     false),
            'auto_cut'        => envBool('AUTO_CUT',    true),
            'logo_image'      => env('LOGO_IMAGE',      ''),
        ];
    }

    /**
     * Imprime el ticket del registro indicado.
     */
    abstract public function print(int $id): void;

    // -------------------------------------------------------------------------
    // Metodos de impresion reutilizables
    // -------------------------------------------------------------------------

    protected function initPrinter(): void
    {
        $connector     = PrinterManager::createConnector();
        $this->printer = new Printer($connector);
        $this->printer->initialize();
    }

    /**
     * Encabezado con nombre de empresa, logo texto e imagen si esta configurado.
     */
    protected function printHeader(): void
    {
        $this->printer->setJustification(Printer::JUSTIFY_CENTER);

        $logoImpreso = false;

        // Logo imagen
        if ($this->config['show_logo'] && $this->config['logo_image'] !== '') {
            $logoPath = ROOT_PATH . '/' . basename($this->config['logo_image']);
            if (file_exists($logoPath)) {
                try {
                    $processedPath = $this->prepareLogoForPrinting($logoPath);
                    if ($processedPath && file_exists($processedPath)) {
                        // Cargar imagen SIN optimizaciones para forzar la carga completa
                        $image = EscposImage::load($processedPath, false);
                        
                        // Forzar la carga de datos llamando a toRasterFormat()
                        $image->toRasterFormat();
                        
                        // Ahora verificar que la imagen tenga dimensiones válidas
                        if ($image->getWidth() > 0 && $image->getHeight() > 0) {
                            try {
                                $this->printer->bitImage($image);
                            } catch (\Exception $e2) {
                                try {
                                    $this->printer->graphics($image);
                                } catch (\Exception $e3) {
                                    error_log("Error imprimiendo imagen: " . $e3->getMessage());
                                }
                            }
                            $this->printer->feed(1);
                            $logoImpreso = true;
                        } else {
                            error_log("Logo cargado con dimensiones inválidas: " . $image->getWidth() . "x" . $image->getHeight());
                        }
                    }
                } catch (\Exception $e) {
                    // Log del error para debugging
                    error_log("Error cargando logo: " . $e->getMessage());
                    // Continuar sin imagen si falla la carga
                }
            }
        }

        // Nombre y marca solo si NO se imprimio el logo imagen
        if (!$logoImpreso) {
            $this->printer->setTextSize(1, 2);
            $this->printer->setEmphasis(true);
            $this->printer->text(mb_strtoupper($this->config['company_name']) . "\n");
            $this->printer->setTextSize(1, 1);
            $this->printer->setEmphasis(false);

            if ($this->config['company_logo'] !== '') {
                $this->printer->text($this->config['company_logo'] . "\n");
            }
        }
        $this->printer->text("\n");
        $this->printer->setJustification(Printer::JUSTIFY_LEFT);
    }

    /**
     * Pie del ticket: mensaje de cierre, contacto, QR y corte.
     */
    protected function printFooter(?string $qrData = null): void
    {
        $this->printDivider('-');
        $this->printer->setJustification(Printer::JUSTIFY_CENTER);

        if ($this->config['show_qr'] && $qrData !== null && $qrData !== '') {
            $this->printer->feed(1);
            $this->printer->qrCode(
                $qrData,
                Printer::QR_ECLEVEL_L,
                6,
                Printer::QR_MODEL_2
            );
            $this->printer->feed(1);
        }

        if ($this->config['footer_message'] !== '') {
            $this->printer->setEmphasis(true);
            $this->printer->text($this->config['footer_message'] . "\n");
            $this->printer->setEmphasis(false);
        }

        if ($this->config['contact_info'] !== '') {
            $this->printer->text($this->config['contact_info'] . "\n");
        }

        $this->printer->feed(3);

        if ($this->config['auto_cut']) {
            $this->printer->cut();
        }
    }

    /**
     * Imprime una linea divisora del ancho del papel.
     */
    protected function printDivider(string $char = '-'): void
    {
        $this->printer->setJustification(Printer::JUSTIFY_LEFT);
        $this->printer->text(str_repeat($char, $this->paperWidth) . "\n");
    }

    /**
     * Imprime una fila con etiqueta alineada a la izquierda y valor a la derecha.
     */
    protected function printRow(string $label, string $value, bool $bold = false): void
    {
        $this->printer->setJustification(Printer::JUSTIFY_LEFT);

        $spaces = $this->paperWidth - mb_strlen($label) - mb_strlen($value);
        if ($spaces < 1) {
            // Si no caben, truncar la etiqueta
            $label  = mb_substr($label, 0, $this->paperWidth - mb_strlen($value) - 1);
            $spaces = 1;
        }

        $line = $label . str_repeat(' ', $spaces) . $value;

        if ($bold) {
            $this->printer->setEmphasis(true);
        }
        $this->printer->text(mb_substr($line, 0, $this->paperWidth) . "\n");
        if ($bold) {
            $this->printer->setEmphasis(false);
        }
    }

    /**
     * Formatea un numero como moneda (2 decimales).
     */
    protected function formatMoney(float $amount): string
    {
        return number_format($amount, 2, '.', ',');
    }

    /**
     * Formatea una fecha a dd/mm/YYYY HH:MM.
     */
    protected function formatDate(string $date): string
    {
        try {
            $dt = new \DateTime($date);
            return $dt->format('d/m/Y H:i:s');
        } catch (\Exception $e) {
            return $date;
        }
    }

    protected function closePrinter(): void
    {
        if (isset($this->printer)) {
            $this->printer->close();
        }
    }

    /**
     * Formatea cantidad + medida a abreviatura corta.
     * CJ=c (Caja), PQ=p (Paquete), UN=u (Unidad), BT=b (Botella), etc.
     */
    protected function formatQtyLabel(int $qty, string $medida): string
    {
        $map = [
            'CJ' => 'c', 'CAJA'    => 'c', 'CAJAS'    => 'c',
            'PQ' => 'p', 'PAQUETE' => 'p', 'PAQUETES' => 'p',
            'UN' => 'u', 'UNIDAD'  => 'u', 'UNIDADES' => 'u',
            'BT' => 'b', 'BOTELLA' => 'b', 'BOTELLAS' => 'b',
            'LT' => 'l', 'LITRO'   => 'l', 'LITROS'   => 'l',
            'KG' => 'k',
            'GR' => 'g',
        ];
        $key    = strtoupper(trim($medida));
        $abbrev = $map[$key] ?? (mb_strlen($key) > 0 ? mb_strtolower(mb_substr($key, 0, 1)) : 'u');
        return $qty . $abbrev;
    }

    /**
     * Imprime una linea de item con puntos de separacion:
     * "3c Coca Cola, Fanta y Sprite 1.5L.........123.00"
     */
    protected function printItemLine(string $qtyLabel, string $name, float $price): void
    {
        $priceStr  = number_format($price, 2, '.', '');
        $prefix    = $qtyLabel . ' ';
        $available = $this->paperWidth - mb_strlen($prefix) - mb_strlen($priceStr);

        if ($available < 2) {
            $maxLeft = $this->paperWidth - mb_strlen($priceStr);
            $this->printer->text(mb_substr($prefix . $name, 0, $maxLeft) . $priceStr . "\n");
            return;
        }

        $maxName = $available - 1;
        $name    = mb_substr($name, 0, $maxName);
        $dots    = str_repeat('.', $available - mb_strlen($name));

        $this->printer->setJustification(Printer::JUSTIFY_LEFT);
        $this->printer->text($prefix . $name . $dots . $priceStr . "\n");
    }

    /**
     * Fila de datos: label padded a ancho fijo + valor inmediato.
     * Ejemplo: "CLIENTE:   SN"
     */
    protected function printDataRow(string $label, string $value, int $labelWidth = 10): void
    {
        $line = str_pad($label, $labelWidth) . $value;
        $this->printer->setJustification(Printer::JUSTIFY_LEFT);
        $this->printer->text(mb_substr($line, 0, $this->paperWidth) . "\n");
    }

    /**
     * Fila de monto alineada a la derecha del papel.
     * Ejemplo: "                        TOTAL:    177.00"
     */
    protected function printMoneyRow(string $label, float $amount, bool $bold = false): void
    {
        $priceStr = number_format($amount, 2, '.', '');
        $priceW   = max(8, mb_strlen($priceStr));
        $labelW   = $this->paperWidth - $priceW;
        $line     = str_pad($label, $labelW, ' ', STR_PAD_LEFT)
                  . str_pad($priceStr, $priceW, ' ', STR_PAD_LEFT);
        $this->printer->setJustification(Printer::JUSTIFY_LEFT);
        if ($bold) $this->printer->setEmphasis(true);
        $this->printer->text(mb_substr($line, 0, $this->paperWidth) . "\n");
        if ($bold) $this->printer->setEmphasis(false);
    }

    /**
     * Pre-procesa el logo con GD: aplana transparencia y redimensiona.
     * Guarda una version temporal lista para EscposImage.
     * Compatible con PHP 7 (resource) y PHP 8+ (GdImage).
     */
    protected function prepareLogoForPrinting(string $srcPath): string
    {
        // Ancho max en pixels - reducido para evitar problemas de memoria en impresoras
        $maxWidth = 200;

        // Detectar el tipo de imagen
        $imageInfo = @getimagesize($srcPath);
        if (!$imageInfo) {
            error_log("prepareLogoForPrinting: No se pudo obtener info de imagen: " . $srcPath);
            return $srcPath;
        }

        // Crear la imagen source según el tipo
        $src = null;
        switch ($imageInfo[2]) {
            case IMAGETYPE_PNG:
                $src = @imagecreatefrompng($srcPath);
                break;
            case IMAGETYPE_JPEG:
                $src = @imagecreatefromjpeg($srcPath);
                break;
            case IMAGETYPE_GIF:
                $src = @imagecreatefromgif($srcPath);
                break;
            default:
                error_log("prepareLogoForPrinting: Tipo de imagen no soportado: " . $imageInfo[2]);
                return $srcPath;
        }

        if (!$src) {
            error_log("prepareLogoForPrinting: No se pudo crear imagen desde: " . $srcPath);
            return $srcPath; // no se pudo abrir, devolver original
        }

        $origW = imagesx($src);
        $origH = imagesy($src);

        // Calcular nuevas dimensiones manteniendo la proporción
        $scale = ($origW > $maxWidth) ? $maxWidth / $origW : 1.0;
        $newW  = max(1, (int)($origW * $scale));
        $newH  = max(1, (int)($origH * $scale));

        // Crear imagen destino con fondo blanco
        $dst = imagecreatetruecolor($newW, $newH);
        if (!$dst) {
            imagedestroy($src);
            error_log("prepareLogoForPrinting: No se pudo crear imagen destino");
            return $srcPath;
        }

        // Fondo blanco
        $white = imagecolorallocate($dst, 255, 255, 255);
        imagefilledrectangle($dst, 0, 0, $newW - 1, $newH - 1, $white);
        
        // Copiar y redimensionar con suavizado
        imagealphablending($dst, false);
        imagesavealpha($dst, false);
        
        if (!imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $origW, $origH)) {
            imagedestroy($src);
            imagedestroy($dst);
            error_log("prepareLogoForPrinting: Error al redimensionar imagen");
            return $srcPath;
        }
        
        imagedestroy($src);

        // Guardar con compresión mínima para mejor calidad
        $tmpPath = sys_get_temp_dir() . '/misocio_logo_print_' . uniqid() . '.png';
        
        // Comprimir la imagen para que sea más pequeña
        if (!imagepng($dst, $tmpPath, 9)) { // Nivel 9 = máxima compresión
            imagedestroy($dst);
            error_log("prepareLogoForPrinting: No se pudo guardar imagen temporal en: " . $tmpPath);
            return $srcPath;
        }
        
        imagedestroy($dst);

        error_log("prepareLogoForPrinting: Imagen procesada: {$newW}x{$newH} -> " . $tmpPath);
        return $tmpPath;
    }
}
