<?php

namespace MiSocio\Tickets;

use MiSocio\Database;
use Mike42\Escpos\Printer;

/**
 * Ticket / Comprobante de Prestamo
 *
 * Tablas:
 *   prestamos      : id, numero_folio, tenant_id, user_id, cliente_id,
 *                    deposito, estado, fecha_prestamo, fecha_vencimiento,
 *                    fecha_devolucion, created_at
 *   prestamo_items : id, prestamo_id, producto_id, cantidad, precio, subtotal
 *   clientes       : id, nombre, celular, nit
 *   productos      : id, nombre, codigo, medida
 *   users          : id, name
 */
class PrestamoTicket extends BaseTicket
{
    public function print(int $id): void
    {
        $pdo = Database::connect();

        $stmt = $pdo->prepare("
            SELECT
                p.id,
                p.numero_folio,
                p.created_at                            AS fecha,
                p.fecha_prestamo,
                COALESCE(p.fecha_vencimiento, '')       AS fecha_vencimiento,
                COALESCE(p.fecha_devolucion, '')        AS fecha_devolucion,
                COALESCE(p.deposito, 0)                 AS deposito,
                COALESCE(p.estado, 'Prestado')          AS estado,
                COALESCE(c.nombre, 'SIN CLIENTE')       AS cliente_nombre,
                COALESCE(c.celular, '')                 AS cliente_celular,
                COALESCE(c.nit, '')                     AS cliente_nit,
                COALESCE(u.name, '')                    AS usuario
            FROM prestamos p
            LEFT JOIN clientes c ON p.cliente_id = c.id
            LEFT JOIN users    u ON p.user_id    = u.id
            WHERE p.id = :id
            LIMIT 1
        ");
        $stmt->execute([':id' => $id]);
        $prestamo = $stmt->fetch();

        if (!$prestamo) {
            throw new \RuntimeException("Prestamo con id=$id no encontrado.");
        }

        // -- Items del prestamo -------------------------------------------
        $stmt = $pdo->prepare("
            SELECT
                COALESCE(pr.nombre, 'PRODUCTO')     AS descripcion,
                COALESCE(pr.medida, '')             AS medida,
                COALESCE(pi.cantidad, 1)            AS cantidad,
                COALESCE(pi.precio, 0)              AS precio_unitario,
                COALESCE(pi.subtotal, 0)            AS subtotal
            FROM prestamo_items pi
            LEFT JOIN productos pr ON pi.producto_id = pr.id
            WHERE pi.prestamo_id = :id
            ORDER BY pi.id ASC
        ");
        $stmt->execute([':id' => $id]);
        $items = $stmt->fetchAll();

        $this->initPrinter();

        try {
            $this->printHeader();

            // Titulo: PRESTAMO  #00001 en doble alto centrado
            $this->printer->setJustification(Printer::JUSTIFY_CENTER);
            $this->printer->setTextSize(1, 2);
            $this->printer->setEmphasis(true);
            $this->printer->text('PRESTAMO  #' . ($prestamo['numero_folio'] ?? $prestamo['id']) . "\n");
            $this->printer->setTextSize(1, 1);
            $this->printer->setEmphasis(false);
            $this->printer->feed(1);

            // Datos del comprobante
            $this->printer->setJustification(Printer::JUSTIFY_LEFT);
            $this->printDataRow('FECHA:',   $this->formatDate($prestamo['fecha']));
            $this->printDataRow('CLIENTE:', trim($prestamo['cliente_nombre']));
            $this->printDataRow('CELULAR:', $prestamo['cliente_celular'] !== '' ? $prestamo['cliente_celular'] : '-');
            if ($prestamo['cliente_nit'] !== '') {
                $this->printDataRow('NIT:', $prestamo['cliente_nit']);
            }
            if ($prestamo['usuario'] !== '') {
                $this->printDataRow('ATENDIO:', $prestamo['usuario']);
            }
            $this->printer->feed(1);

            // Seccion productos
            $this->printer->setJustification(Printer::JUSTIFY_CENTER);
            $this->printer->setEmphasis(true);
            $this->printer->text("====== P R O D U C T O S ======\n");
            $this->printer->setEmphasis(false);
            $this->printer->feed(1);

            // Items: "3c Coca Cola..................123.00"
            foreach ($items as $item) {
                $qtyLabel = $this->formatQtyLabel((int)$item['cantidad'], (string)$item['medida']);
                $this->printItemLine($qtyLabel, (string)$item['descripcion'], (float)$item['subtotal']);
            }

            $this->printer->feed(1);

            // Totales
            $total   = array_sum(array_column($items, 'subtotal'));
            $deposito = (float)$prestamo['deposito'];

            $this->printMoneyRow('TOTAL:', $total, true);
            if ($deposito > 0) {
                $this->printMoneyRow('DEPOSITO:', $deposito);
            }

            // Nota de devolucion
            $this->printer->feed(1);
            $this->printer->setJustification(Printer::JUSTIFY_CENTER);
            $this->printer->setEmphasis(true);
            $this->printer->text("* Tiene 7 dias para la devolucion\n");
            $this->printer->text("  de los envases *\n");
            $this->printer->setEmphasis(false);

            // Datos para QR
            $qrData = sprintf(
                'PRESTAMO#%s|ESTADO:%s|FECHA:%s',
                $prestamo['numero_folio'] ?? $prestamo['id'],
                $prestamo['estado'],
                $prestamo['fecha_prestamo'] ?? $prestamo['fecha']
            );

            $this->printFooter($qrData);

        } finally {
            $this->closePrinter();
        }
    }
}

