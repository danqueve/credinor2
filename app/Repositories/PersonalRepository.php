<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Helpers\Database;
use App\Models\Personal;
use PDO;

class PersonalRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * @return Personal[]
     */
    public function findAllActive(): array
    {
        $sql = "
            SELECT p.*, z.nombre as zona_nombre 
            FROM personal p
            LEFT JOIN zonas z ON p.id_zona = z.id_zona
            WHERE p.deleted_at IS NULL AND p.estado = 'activo'
            ORDER BY p.nombre ASC
        ";
        $stmt = $this->db->query($sql);
        
        $personalList = [];
        while ($row = $stmt->fetch()) {
            $personalList[] = $this->hydrate($row);
        }
        return $personalList;
    }

    public function findById(int $id): ?Personal
    {
        $stmt = $this->db->prepare("SELECT * FROM personal WHERE id_personal = ? AND deleted_at IS NULL");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? $this->hydrate($row) : null;
    }

    public function insert(Personal $personal): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO personal (nombre, dni, telefono, rol_operativo, id_zona, comision_pct, estado) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $personal->nombre,
            $personal->dni,
            $personal->telefono,
            $personal->rol_operativo,
            $personal->id_zona,
            $personal->comision_pct,
            $personal->estado
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function update(Personal $personal): void
    {
        $stmt = $this->db->prepare("
            UPDATE personal 
            SET nombre = ?, dni = ?, telefono = ?, rol_operativo = ?, 
                id_zona = ?, comision_pct = ?, estado = ?
            WHERE id_personal = ?
        ");
        $stmt->execute([
            $personal->nombre,
            $personal->dni,
            $personal->telefono,
            $personal->rol_operativo,
            $personal->id_zona,
            $personal->comision_pct,
            $personal->estado,
            $personal->id_personal
        ]);
    }

    public function softDelete(int $id): void
    {
        $stmt = $this->db->prepare("UPDATE personal SET deleted_at = CURRENT_TIMESTAMP WHERE id_personal = ?");
        $stmt->execute([$id]);
    }

    private function hydrate(array $row): Personal
    {
        $p = new Personal();
        $p->id_personal = (int)$row['id_personal'];
        $p->nombre = $row['nombre'];
        $p->dni = $row['dni'];
        $p->telefono = $row['telefono'];
        $p->rol_operativo = $row['rol_operativo'];
        $p->id_zona = $row['id_zona'] ? (int)$row['id_zona'] : null;
        $p->comision_pct = (float)$row['comision_pct'];
        $p->estado = $row['estado'];
        $p->zona_nombre = $row['zona_nombre'] ?? null;
        return $p;
    }
}
