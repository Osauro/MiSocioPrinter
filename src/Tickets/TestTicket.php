<?php

namespace MiSocio\Tickets;

use Mike42\Escpos\Printer;
use Mike42\Escpos\EscposImage;

/**
 * Ticket de prueba: imprime un ticket de muestra usando toda la
 * configuracion actual (logo, QR, corte automatico, etc.).
 */
class TestTicket extends BaseTicket
{
    public function print(int $id = 0): void
    {
        $this->initPrinter();

        try {
            // ── Encabezado completo (logo imagen + nombre + logo texto) ──
            $this->printHeader();

            // ── Titulo ───────────────────────────────────────────────────
            $this->printer->setJustification(Printer::JUSTIFY_CENTER);
            $this->printer->setEmphasis(true);
            $this->printer->text("** TICKET DE PRUEBA **\n");
            $this->printer->setEmphasis(false);
            $this->printDivider('-');

            // ── Informacion del sistema ──────────────────────────────────
            $this->printer->setJustification(Printer::JUSTIFY_LEFT);
            $this->printRow('Impresora:', env('PRINTER_NAME', '-'));
            $this->printRow('Ancho papel:', env('PAPER_WIDTH', '37') . ' chars');
            $this->printRow('Fecha/Hora:', date('d/m/Y H:i:s'));
            $this->printDivider('-');

            // ── Muestra de formatos ──────────────────────────────────────
            $this->printer->setJustification(Printer::JUSTIFY_CENTER);
            $this->printer->text("Muestra de formatos\n");
            $this->printer->setJustification(Printer::JUSTIFY_LEFT);

            $this->printer->setEmphasis(true);
            $this->printer->text("Texto en negrita\n");
            $this->printer->setEmphasis(false);

            $this->printer->setUnderline(Printer::UNDERLINE_SINGLE);
            $this->printer->text("Texto subrayado\n");
            $this->printer->setUnderline(Printer::UNDERLINE_NONE);

            $this->printer->setTextSize(2, 1);
            $this->printer->text("Doble ancho\n");
            $this->printer->setTextSize(1, 2);
            $this->printer->text("Doble alto\n");
            $this->printer->setTextSize(1, 1);

            $this->printDivider('-');

            // ── Muestra de alineacion ────────────────────────────────────
            $this->printer->setJustification(Printer::JUSTIFY_LEFT);
            $this->printer->text("Izquierda\n");
            $this->printer->setJustification(Printer::JUSTIFY_CENTER);
            $this->printer->text("Centro\n");
            $this->printer->setJustification(Printer::JUSTIFY_RIGHT);
            $this->printer->text("Derecha\n");
            $this->printer->setJustification(Printer::JUSTIFY_LEFT);

            $this->printDivider('-');

            // ── Simulacion de ticket de venta ────────────────────────────
            $this->printer->setJustification(Printer::JUSTIFY_LEFT);
            $this->printRow('PRODUCTO EJEMPLO 1', 'Bs 10.00');
            $this->printRow('PRODUCTO EJEMPLO 2', 'Bs 25.50');
            $this->printRow('PRODUCTO EJEMPLO 3', 'Bs  5.00');
            $this->printDivider('-');
            $this->printRow('TOTAL:', 'Bs 40.50', true);

            // ── Estado de opciones activas ───────────────────────────────
            $this->printDivider('-');
            $this->printer->setJustification(Printer::JUSTIFY_LEFT);
            $opts = [
                'Logo imagen'     => $this->config['show_logo'] && $this->config['logo_image'] !== '',
                'Logo texto'      => $this->config['company_logo'] !== '',
                'Codigo QR'       => $this->config['show_qr'],
                'Corte automatico'=> $this->config['auto_cut'],
            ];
            foreach ($opts as $label => $active) {
                $this->printRow($label . ':', $active ? '[ON]' : '[OFF]');
            }

            // ── Pie completo (mensaje, contacto, QR si activo, corte) ────
            $this->printFooter('MISOCIO-PRINTER|TEST|' . date('Ymd-His'));

        } finally {
            $this->closePrinter();
        }
    }
}
