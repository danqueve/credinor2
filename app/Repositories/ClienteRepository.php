<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Helpers\Database;
use App\Models\Cliente;
use PDO;

class ClienteRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * @return Cliente[]
     */
    public function findAll(int $limit = 100, int $offset = 0, string $search = ''): array
    {
        $sql = "
            SELECT
                c.*,
                z.nombre                          AS zona_nombre,
                COALESCE(crs.creditos_activos, 0) AS creditos_activos,
                COALESCE(ps.total_pagos,       0) AS total_pagos,
                cr.id_credito,
                cr.codigo                         AS credito_codigo,
                COALESCE(cr.saldo_pendiente,   0) AS credito_saldo,
                COALESCE(cpg.cnt,              0) AS cuotas_pagadas,
                COALESCE(ctot.cnt,             0) AS cuotas_total,
                (SELECT cu.monto_esperado
                 FROM cuotas cu
                 WHERE cu.id_credito = cr.id_credito
                   AND cu.estado IN ('pendiente','vencida','parcial')
                 ORDER BY cu.numero_cuota ASC LIMIT 1)   AS monto_cuota,
                (SELECT cu.fecha_vencimiento
                 FROM cuotas cu
                 WHERE cu.id_credito = cr.id_credito
                   AND cu.estado IN ('pendiente','vencida','parcial')
                 ORDER BY cu.numero_cuota ASC LIMIT 1)   AS proxima_cuota
            FROM clientes c
            LEFT JOIN zonas z ON c.id_zona = z.id_zona
            LEFT JOIN creditos cr
                   ON cr.id_credito = (
                       SELECT MAX(id_credito) FROM creditos
                       WHERE id_cliente = c.id_cliente AND estado = 'activo'
                   )
            LEFT JOIN (
                SELECT id_cliente, COUNT(*) AS creditos_activos
                FROM creditos WHERE estado = 'activo'
                GROUP BY id_cliente
            ) crs ON crs.id_cliente = c.id_cliente
            LEFT JOIN (
                SELECT cr2.id_cliente, COUNT(p.id_pago) AS total_pagos
                FROM pagos p
                JOIN creditos cr2 ON p.id_credito = cr2.id_credito
                WHERE p.anulado = 0
                GROUP BY cr2.id_cliente
            ) ps ON ps.id_cliente = c.id_cliente
            LEFT JOIN (
                SELECT id_credito, COUNT(*) AS cnt
                FROM cuotas WHERE estado = 'pagada'
                GROUP BY id_credito
            ) cpg ON cpg.id_credito = cr.id_credito
            LEFT JOIN (
                SELECT id_credito, COUNT(*) AS cnt
                FROM cuotas
                GROUP BY id_credito
            ) ctot ON ctot.id_credito = cr.id_credito
            WHERE c.deleted_at IS NULL
        ";

        if (!empty($search)) {
            $sql .= " AND (c.nombre LIKE ? OR c.dni LIKE ?)";
        }

        $sql .= " ORDER BY c.nombre ASC LIMIT ? OFFSET ?";

        $stmt       = $this->db->prepare($sql);
        $paramIndex = 1;

        if (!empty($search)) {
            $like = "%$search%";
            $stmt->bindValue($paramIndex++, $like, PDO::PARAM_STR);
            $stmt->bindValue($paramIndex++, $like, PDO::PARAM_STR);
        }
        $stmt->bindValue($paramIndex++, $limit,  PDO::PARAM_INT);
        $stmt->bindValue($paramIndex,   $offset, PDO::PARAM_INT);

        $stmt->execute();

        $clientes = [];
        while ($row = $stmt->fetch()) {
            $clientes[] = $this->hydrate($row);
        }
        return $clientes;
    }

    public function countAll(string $search = ''): int
    {
        $sql = "SELECT COUNT(*) FROM clientes WHERE deleted_at IS NULL";
        $params = [];

        if (!empty($search)) {
            $sql .= " AND (nombre LIKE ? OR dni LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    public function findById(int $id): ?Cliente
    {
        $stmt = $this->db->prepare("
            SELECT c.*, z.nombre as zona_nombre 
            FROM clientes c
            LEFT JOIN zonas z ON c.id_zona = z.id_zona
            WHERE c.id_cliente = ? AND c.deleted_at IS NULL
        ");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? $this->hydrate($row) : null;
    }

    /**
     * Búsqueda completa para la pantalla de consulta mobile (DNI, nombre, dirección, teléfono).
     * @return Cliente[]
     */
    public function searchFull(string $term, int $limit = 20): array
    {
        $like = "%$term%";
        $stmt = $this->db->prepare("
            SELECT c.*, z.nombre AS zona_nombre
            FROM clientes c
            LEFT JOIN zonas z ON c.id_zona = z.id_zona
            WHERE c.deleted_at IS NULL
              AND (c.nombre LIKE ? OR c.dni LIKE ? OR c.direccion LIKE ? OR c.telefono LIKE ?)
            ORDER BY c.nombre ASC
            LIMIT ?
        ");
        $stmt->bindValue(1, $like, PDO::PARAM_STR);
        $stmt->bindValue(2, $like, PDO::PARAM_STR);
        $stmt->bindValue(3, $like, PDO::PARAM_STR);
        $stmt->bindValue(4, $like, PDO::PARAM_STR);
        $stmt->bindValue(5, $limit, PDO::PARAM_INT);
        $stmt->execute();

        $list = [];
        while ($row = $stmt->fetch()) {
            $list[] = $this->hydrate($row);
        }
        return $list;
    }

    /**
     * Todos los clientes con al menos un crédito activo asignado a un cobrador,
     * incluyendo saldo_total y cuotas_vencidas para badges en la lista mobile.
     * @return Cliente[]
     */
    public function findByCobrador(int $idCobrador, string $filtro = ''): array
    {
        $sql = "
            SELECT c.*, z.nombre AS zona_nombre,
                COALESCE(SUM(cr.saldo_pendiente), 0)                  AS saldo_total,
                COALESCE(COUNT(cu.id_cuota), 0)                       AS cuotas_vencidas
            FROM clientes c
            LEFT JOIN zonas z ON c.id_zona = z.id_zona
            JOIN creditos cr ON cr.id_cliente = c.id_cliente
                             AND cr.estado = 'activo'
                             AND cr.deleted_at IS NULL
                             AND cr.id_cobrador = ?
            LEFT JOIN cuotas cu ON cu.id_credito = cr.id_credito
                                AND cu.estado = 'vencida'
            WHERE c.deleted_at IS NULL
        ";

        if ($filtro === 'vencidos') {
            $sql .= " HAVING cuotas_vencidas > 0";
        } elseif ($filtro === 'al-dia') {
            $sql .= " HAVING cuotas_vencidas = 0";
        } elseif ($filtro === 'hoy') {
            $sql .= " AND EXISTS (
                SELECT 1 FROM cuotas cu2
                JOIN creditos cr2 ON cu2.id_credito = cr2.id_credito
                WHERE cr2.id_cliente = c.id_cliente
                  AND cr2.id_cobrador = ?
                  AND cu2.fecha_vencimiento = CURDATE()
                  AND cu2.estado IN ('pendiente','vencida','parcial')
            )";
        }

        $sql .= " GROUP BY c.id_cliente ORDER BY cuotas_vencidas DESC, c.nombre ASC";

        $params = [$idCobrador];
        if ($filtro === 'hoy') {
            $params[] = $idCobrador;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        $list = [];
        while ($row = $stmt->fetch()) {
            $list[] = $this->hydrate($row);
        }
        return $list;
    }

    /**
     * Búsqueda restringida a clientes con crédito activo asignado a un cobrador,
     * incluyendo saldo_total y cuotas_vencidas para badges.
     * @return Cliente[]
     */
    public function searchByCobrador(string $term, int $idCobrador, int $limit = 20): array
    {
        $like = "%$term%";
        $stmt = $this->db->prepare("
            SELECT c.*, z.nombre AS zona_nombre,
                COALESCE(SUM(cr.saldo_pendiente), 0) AS saldo_total,
                COALESCE(COUNT(cu.id_cuota), 0)      AS cuotas_vencidas
            FROM clientes c
            LEFT JOIN zonas z ON c.id_zona = z.id_zona
            JOIN creditos cr ON cr.id_cliente = c.id_cliente
                             AND cr.estado = 'activo'
                             AND cr.deleted_at IS NULL
                             AND cr.id_cobrador = ?
            LEFT JOIN cuotas cu ON cu.id_credito = cr.id_credito
                                AND cu.estado = 'vencida'
            WHERE c.deleted_at IS NULL
              AND (c.nombre LIKE ? OR c.dni LIKE ? OR c.direccion LIKE ? OR c.telefono LIKE ?)
            GROUP BY c.id_cliente
            ORDER BY cuotas_vencidas DESC, c.nombre ASC
            LIMIT ?
        ");
        $stmt->bindValue(1, $idCobrador, PDO::PARAM_INT);
        $stmt->bindValue(2, $like, PDO::PARAM_STR);
        $stmt->bindValue(3, $like, PDO::PARAM_STR);
        $stmt->bindValue(4, $like, PDO::PARAM_STR);
        $stmt->bindValue(5, $like, PDO::PARAM_STR);
        $stmt->bindValue(6, $limit, PDO::PARAM_INT);
        $stmt->execute();

        $list = [];
        while ($row = $stmt->fetch()) {
            $list[] = $this->hydrate($row);
        }
        return $list;
    }

    /**
     * Búsqueda para Autocomplete AJAX
     */
    public function searchByDniOrName(string $term): array
    {
        $stmt = $this->db->prepare("
            SELECT id_cliente, nombre, dni, direccion, barrio
            FROM clientes 
            WHERE deleted_at IS NULL AND (nombre LIKE ? OR dni LIKE ?)
            ORDER BY nombre ASC
            LIMIT 15
        ");
        $likeTerm = "%$term%";
        $stmt->execute([$likeTerm, $likeTerm]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC); // Devolvemos array simple para JSON
    }

    public function insert(Cliente $cliente): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO clientes (nombre, dni, direccion, barrio, telefono, coordenadas_gps, id_zona, referencias) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $cliente->nombre,
            $cliente->dni,
            $cliente->direccion,
            $cliente->barrio,
            $cliente->telefono,
            $cliente->coordenadas_gps,
            $cliente->id_zona,
            $cliente->referencias
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function update(Cliente $cliente): void
    {
        $stmt = $this->db->prepare("
            UPDATE clientes 
            SET nombre = ?, dni = ?, direccion = ?, barrio = ?, telefono = ?, 
                coordenadas_gps = ?, id_zona = ?, referencias = ?
            WHERE id_cliente = ?
        ");
        $stmt->execute([
            $cliente->nombre,
            $cliente->dni,
            $cliente->direccion,
            $cliente->barrio,
            $cliente->telefono,
            $cliente->coordenadas_gps,
            $cliente->id_zona,
            $cliente->referencias,
            $cliente->id_cliente
        ]);
    }

    public function softDelete(int $id): void
    {
        $stmt = $this->db->prepare("UPDATE clientes SET deleted_at = CURRENT_TIMESTAMP WHERE id_cliente = ?");
        $stmt->execute([$id]);
    }

    private function hydrate(array $row): Cliente
    {
        $c = new Cliente();
        $c->id_cliente      = (int)$row['id_cliente'];
        $c->nombre          = $row['nombre'];
        $c->dni             = $row['dni'];
        $c->direccion       = $row['direccion']       ?? null;
        $c->barrio          = $row['barrio']          ?? null;
        $c->telefono        = $row['telefono']        ?? null;
        $c->coordenadas_gps = $row['coordenadas_gps'] ?? null;
        $c->id_zona         = $row['id_zona'] ? (int)$row['id_zona'] : null;
        $c->foto_url        = $row['foto_url']        ?? null;
        $c->referencias     = $row['referencias']     ?? null;
        $c->zona_nombre     = $row['zona_nombre']     ?? null;
        $c->creditos_activos  = (int)($row['creditos_activos']  ?? 0);
        $c->total_pagos       = (int)($row['total_pagos']      ?? 0);
        $c->id_credito        = isset($row['id_credito'])       ? (int)$row['id_credito']  : null;
        $c->credito_codigo    = $row['credito_codigo']          ?? null;
        $c->credito_saldo     = (float)($row['credito_saldo']   ?? 0);
        $c->cuotas_pagadas    = (int)($row['cuotas_pagadas']    ?? 0);
        $c->cuotas_total      = (int)($row['cuotas_total']      ?? 0);
        $c->monto_cuota       = isset($row['monto_cuota'])      ? (float)$row['monto_cuota'] : null;
        $c->proxima_cuota     = $row['proxima_cuota']           ?? null;
        $c->saldo_total       = (float)($row['saldo_total']     ?? 0);
        $c->cuotas_vencidas   = (int)($row['cuotas_vencidas']   ?? 0);
        return $c;
    }
}
