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
            SELECT c.*, z.nombre as zona_nombre 
            FROM clientes c
            LEFT JOIN zonas z ON c.id_zona = z.id_zona
            WHERE c.deleted_at IS NULL
        ";
        $params = [];

        if (!empty($search)) {
            $sql .= " AND (c.nombre LIKE ? OR c.dni LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        $sql .= " ORDER BY c.id_cliente DESC LIMIT ? OFFSET ?";
        
        $stmt = $this->db->prepare($sql);
        
        // Bind params para LIKE si existen
        $paramIndex = 1;
        if (!empty($search)) {
            $stmt->bindValue($paramIndex++, "%$search%", PDO::PARAM_STR);
            $stmt->bindValue($paramIndex++, "%$search%", PDO::PARAM_STR);
        }
        $stmt->bindValue($paramIndex++, $limit, PDO::PARAM_INT);
        $stmt->bindValue($paramIndex, $offset, PDO::PARAM_INT);
        
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
        $c->id_cliente = (int)$row['id_cliente'];
        $c->nombre = $row['nombre'];
        $c->dni = $row['dni'];
        $c->direccion = $row['direccion'] ?? null;
        $c->barrio = $row['barrio'] ?? null;
        $c->telefono = $row['telefono'] ?? null;
        $c->coordenadas_gps = $row['coordenadas_gps'] ?? null;
        $c->id_zona = $row['id_zona'] ? (int)$row['id_zona'] : null;
        $c->foto_url = $row['foto_url'] ?? null;
        $c->referencias = $row['referencias'] ?? null;
        $c->zona_nombre = $row['zona_nombre'] ?? null;
        return $c;
    }
}
