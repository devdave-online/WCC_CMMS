<?php
/**
 * Optional DB reads for audit verification (uses app inc/db.php).
 */
final class WccAuditDbProbe
{
    private ?PDO $pdo = null;

    public function pdo(): ?PDO
    {
        if ($this->pdo) {
            return $this->pdo;
        }
        $root = dirname(__DIR__, 3);
        $db = $root . '/inc/db.php';
        if (!is_file($db)) {
            return null;
        }
        require_once $db;
        try {
            $this->pdo = get_wcc_db_connection();
        } catch (Throwable $e) {
            return null;
        }
        return $this->pdo;
    }

    public function one(string $sql, array $params = []): mixed
    {
        $pdo = $this->pdo();
        if (!$pdo) {
            return null;
        }
        $st = $pdo->prepare($sql);
        $st->execute($params);
        return $st->fetchColumn();
    }

    public function all(string $sql, array $params = []): array
    {
        $pdo = $this->pdo();
        if (!$pdo) {
            return [];
        }
        $st = $pdo->prepare($sql);
        $st->execute($params);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    public function firstEquipId(): ?int
    {
        $id = $this->one('SELECT equip_id FROM equipment ORDER BY equip_id ASC LIMIT 1');
        return $id !== false && $id !== null ? (int)$id : null;
    }

    public function firstToolingId(): ?int
    {
        try {
            $id = $this->one('SELECT tooling_id FROM toolings WHERE deleted_at IS NULL ORDER BY tooling_id ASC LIMIT 1');
            return $id !== false && $id !== null ? (int)$id : null;
        } catch (Throwable $e) {
            return null;
        }
    }
}
