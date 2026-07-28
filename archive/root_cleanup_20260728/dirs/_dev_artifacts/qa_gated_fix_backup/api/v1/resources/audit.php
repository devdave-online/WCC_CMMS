<?php
/**
 * Audit Log Resource (read-only for most users)
 */

function handle_audit($method, $id, $input) {
    global $pdo;

    if ($method !== 'GET') {
        api_error('Audit is read-only', 405);
    }

    require_api_perm('view_history'); // or manage_users for full

    if ($id) {
        $stmt = $pdo->prepare("SELECT * FROM audit_log WHERE log_id = ?");
        $stmt->execute([$id]);
        $log = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$log) api_error('Audit log not found', 404);
        api_response(true, $log);
    } else {
        list($page, $per_page, $offset) = get_pagination();

        $entity_type = $_GET['entity_type'] ?? null;
        $action = $_GET['action'] ?? null;

        $sql = "SELECT * FROM audit_log WHERE 1=1";
        $params = [];

        if ($entity_type) {
            $sql .= " AND entity_type = ?";
            $params[] = $entity_type;
        }
        if ($action) {
            $sql .= " AND action = ?";
            $params[] = $action;
        }

        $sql .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
        $params[] = $per_page;
        $params[] = $offset;

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        api_response(true, $logs);
    }
}
