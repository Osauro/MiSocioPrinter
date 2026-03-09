<?php

namespace MiSocio\Tickets;

use MiSocio\Database;
use Mike42\Escpos\Printer;

/**
 * Ticket de Venta
 *
 * Tablas:
 *   ventas      : id, numero_folio, tenant_id, user_id, cliente_id,
 *                 efectivo, online, credito, cambio, estado, created_at
 *   venta_items : id, venta_id, producto_id, cantidad, precio, subtotal
 *   clientes    : id, nombre, celular, nit
 *   productos   : id, nombre, codigo, medida
 *   users       : id, name
 */
class VentaTicket extends BaseTicket
{
    public function print(int $id): void
    {
        $pdo = Database::connect();

        // -- Cabecera de la venta ------------------------------------------
        $stmt = $pdo->prepare("
            SELECT
                v.id,
                v.numero_folio,
                v.created_at                            AS fecha,
                COALESCE(v.efectivo, 0)                 AS efectivo,
                COALESCE(v.online, 0)                   AS online,
                COALESCE(v.credito, 0)                  AS credito,
                COALESCE(v.cambio, 0)                   AS cambio,
                COALESCE(v.estado, '')                  AS estado,
                COALESCE(c.nombre, 'CLIENTE GENERAL')   AS cliente_nombre,
                COALESCE(c.celular, '')                 AS cliente_celular,
                COALESCE(c.nit, '')                     AS cliente_nit,
                COALESCE(u.name, '')                    AS cajero
            FROM ventas v
            LEFT JOIN clientes c ON v.cliente_id = c.id
            LEFT JOIN users    u ON v.user_id    = u.id
            WHERE v.id = :id
            LIMIT 1
        ");
        $stmt->execute([':id' => $id]);
        $venta = $stmt->fetch();

        if (!$venta) {
            throw new \RuntimeException("Venta con id=$id no encontrada.");
        }

        // -- Items de la venta ---------------------------------------------
        $stmt = $pdo->prepare("
            SELECT
                COALESCE(p.nombre, 'PRODUCTO')  AS descripcion,
                COALESCE(p.medida, '')          AS medida,
                COALESCE(vi.cantidad, 1)        AS cantidad,
                COALESCE(vi.precio, 0)          AS precio_unitario,
                COALESCE(vi.subtotal, 0)        AS subtotal
            FROM venta_items vi
            LEFT JOIN productos p ON vi.producto_id = p.id
            WHERE vi.venta_id = :id
            ORDER BY vi.id ASC
        ");
        $stmt->execute([':id' => $id]);
        $detalles = $stmt->fetchAll();

        // -- Imprimir -------------------------------------------------------
        $this->initPrinter();

        try {
            $this->printHeader();

            // Titulo: VENTA  #21891 en doble alto centrado
            $this->printer->setJustification(Printer::JUSTIFY_CENTER);
            $this->printer->setTextSize(1, 2);
            $this->printer->setEmphasis(true);
            $this->printer->text('VENTA  #' . ($venta['numero_folio'] ?? $venta['id']) . "\n");
            $this->printer->setTextSize(1, 1);
            $this->printer->setEmphasis(false);
            $this->printer->feed(1);

            // Datos del comprobante
            $this->printer->setJustification(Printer::JUSTIFY_LEFT);
            $this->printDataRow('FECHA:',   $this->formatDate($venta['fecha']));
            $this->printDataRow('CLIENTE:', trim($venta['cliente_nombre']));
            $this->printDataRow('CELULAR:', $venta['cliente_celular'] !== '' ? $venta['cliente_celular'] : '-');
            if ($venta['cliente_nit'] !== '') {
                $this->printDataRow('NIT:', $venta['cliente_nit']);
            }
            if ($venta['cajero'] !== '') {
                $this->printDataRow('CAJERO:', $venta['cajero']);
            }
            $this->printer->feed(1);

            // Seccion productos
            $this->printer->setJustification(Printer::JUSTIFY_CENTER);
            $this->printer->setEmphasis(true);
            $this->printer->text("====== P R O D U C T O S ======\n");
            $this->printer->setEmphasis(false);
            $this->printer->feed(1);

            // Items: "3c Coca Cola, Fanta y Sprite 1.5L.........123.00"
            foreach ($detalles as $item) {
                $qtyLabel = $this->formatQtyLabel((int)$item['cantidad'], (string)$item['medida']);
                $this->printItemLine($qtyLabel, (string)$item['descripcion'], (float)$item['subtotal']);
            }

            $this->printer->feed(1);

            // Totales alineados a la derecha
            $total    = array_sum(array_column($detalles, 'subtotal'));
            $efectivo = (float)$venta['efectivo'];
            $online   = (float)$venta['online'];
            $credito  = (float)$venta['credito'];
            $cambio   = (float)$venta['cambio'];

            $this->printMoneyRow('TOTAL:', $total, true);
            if ($efectivo > 0) $this->printMoneyRow('EFECTIVO:',  $efectivo);
            if ($online   > 0) $this->printMoneyRow('ONLINE/QR:', $online);
            if ($credito  > 0) $this->printMoneyRow('CREDITO:',   $credito);
            if ($cambio   > 0) $this->printMoneyRow('CAMBIO:',    $cambio);

            // Datos para QR
            $qrData = sprintf(
                'VENTA#%s|TOTAL:%.2f|FECHA:%s',
                $venta['numero_folio'] ?? $venta['id'],
                $total,
                $venta['fecha']
            );

            $this->printFooter($qrData);

        } finally {
            $this->closePrinter();
        }
    }
}
