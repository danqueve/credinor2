<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Helpers\Database;
use App\Models\Zona;
use PDO;

class ZonaRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * @return Zona[]
     */
    public function findAll(): array
    {
        $sql = "
            SELECT z.*, p.nombre as cobrador_nombre 
            FROM zonas z
            LEFT JOIN personal p ON z.id_cobrador_default = p.id_personal
            ORDER BY z.nombre ASC
        ";
        $stmt = $this->db->query($sql);
        
        $zonas = [];
        while ($row = $stmt->fetch()) {
            $zonas[] = $this->hydrate($row);
        }
        return $zonas;
    }

    public function findById(int $id): ?Zona
    {
        $stmt = $this->db->prepare("SELECT * FROM zonas WHERE id_zona = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? $this->hydrate($row) : null;
    }

    public function insert(Zona $zona): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO zonas (nombre, id_cobrador_default) 
            VALUES (?, ?)
        ");
        $stmt->execute([
            $zona->nombre,
            $zona->id_cobrador_default
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function update(Zona $zona): void
    {
        $stmt = $this->db->prepare("
            UPDATE zonas 
            SET nombre = ?, id_cobrador_default = ?
            WHERE id_zona = ?
        ");
        $stmt->execute([
            $zona->nombre,
            $zona->id_cobrador_default,
            $zona->id_zona
        ]);
    }

    private function hydrate(array $row): Zona
    {
        $zona = new Zona();
        $zona->id_zona = (int)$row['id_zona'];
        $zona->nombre = $row['nombre'];
        $zona->id_cobrador_default = $row['id_cobrador_default'] ? (int)$row['id_cobrador_default'] : null;
        $zona->cobrador_nombre = $row['cobrador_nombre'] ?? null;
        return $zona;
    }
}
