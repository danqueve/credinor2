<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Helpers\Database;
use PDO;

class VisitaCobranzaRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function insert(
        int $idCuota,
        int $idCobrador,
        string $resultado,
        ?string $observaciones,
        ?float $geoLat,
        ?float $geoLng
    ): int {
        $stmt = $this->db->prepare("
            INSERT INTO visitas_cobranza
                (id_cuota, id_cobrador, resultado, observaciones, geo_lat, geo_lng, fecha)
            VALUES (?, ?, ?, ?, ?, ?, CURDATE())
        ");
        $stmt->execute([$idCuota, $idCobrador, $resultado, $observaciones, $geoLat, $geoLng]);
        return (int)$this->db->lastInsertId();
    }

    /** Última visita de cada cuota para un crédito dado. */
    public function getUltimasPorCredito(int $idCredito): array
    {
        $stmt = $this->db->prepare("
            SELECT v.id_cuota, v.resultado, v.fecha, v.observaciones
            FROM visitas_cobranza v
            INNER JOIN (
                SELECT id_cuota, MAX(id_visita) AS max_id
                FROM visitas_cobranza
                WHERE id_cuota IN (SELECT id_cuota FROM cuotas WHERE id_credito = ?)
                GROUP BY id_cuota
            ) latest ON v.id_visita = latest.max_id
        ");
        $stmt->execute([$idCredito]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $map = [];
        foreach ($rows as $r) {
            $map[(int)$r['id_cuota']] = $r;
        }
        return $map;
    }
}
