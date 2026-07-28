<?php
/**
 * Toolings REST resource (v1).
 *
 * Companion continues to use /api/companion/toolings.php — this is the full REST surface
 * for integrations: list/read/create/update/soft-delete + BOM + document metadata.
 *
 * Routes (via index.php):
 *   GET    /toolings
 *   GET    /toolings/{id}
 *   POST   /toolings
 *   PUT    /toolings/{id}
 *   DELETE /toolings/{id}              soft-delete (sets deleted_at)
 *   GET    /toolings/{id}/bom
 *   POST   /toolings/{id}/bom          { part_id, quantity?, notes? }
 *   PUT    /toolings/{id}/bom/{bom_id}
 *   DELETE /toolings/{id}/bom/{bom_id}
 *   GET    /toolings/{id}/documents
 *   POST   /toolings/{id}/documents    metadata only { doc_title, doc_type?, file_path }
 *   DELETE /toolings/{id}/documents/{doc_id}
 *
 * Perms: view_toolings (read), manage_toolings (write).
 */

function handle_toolings($method, $id, $input, $subResource = null, $subId = null)
{
    global $pdo;

    // Nested: /toolings/{id}/bom[/{bom_id}]
    if ($id && $subResource === 'bom') {
        handle_tooling_bom($method, (int)$id, $input, $subId);
        return;
    }
    // Nested: /toolings/{id}/documents[/{doc_id}]
    if ($id && ($subResource === 'documents' || $subResource === 'docs')) {
        handle_tooling_documents($method, (int)$id, $input, $subId);
        return;
    }
    if ($id && $subResource) {
        api_error('Unknown tooling sub-resource. Use bom or documents.', 404);
    }

    switch ($method) {
        case 'GET':
            require_api_perm('view_toolings');
            if ($id) {
                $stmt = $pdo->prepare(
                    "SELECT * FROM toolings WHERE tooling_id = ? AND deleted_at IS NULL"
                );
                $stmt->execute([(int)$id]);
                $item = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$item) {
                    api_error('Tooling not found', 404);
                }
                api_response(true, $item);
            }

            list($page, $per_page, $offset) = get_pagination();
            $sql = "SELECT * FROM toolings WHERE deleted_at IS NULL";
            $params = [];

            $category = $_GET['category'] ?? null;
            $status = $_GET['status'] ?? null;
            $active = $_GET['is_active'] ?? null;
            $search = trim((string)($_GET['search'] ?? ''));
            $barcode = trim((string)($_GET['barcode'] ?? ''));
            $assetTag = trim((string)($_GET['asset_tag'] ?? ''));
            $code = trim((string)($_GET['tooling_code'] ?? ''));
            $linked = $_GET['linked_equip_id'] ?? null;
            $workshop = $_GET['workshop_id'] ?? null;

            // Exact scan-style lookup (companion parity)
            if ($barcode !== '') {
                $sql .= " AND (barcode = ? OR asset_tag = ? OR tooling_code = ?)";
                $params[] = $barcode;
                $params[] = $barcode;
                $params[] = $barcode;
            }
            if ($assetTag !== '') {
                $sql .= " AND asset_tag = ?";
                $params[] = $assetTag;
            }
            if ($code !== '') {
                $sql .= " AND tooling_code = ?";
                $params[] = $code;
            }
            if ($category) {
                $sql .= " AND category = ?";
                $params[] = $category;
            }
            if ($status) {
                $sql .= " AND status = ?";
                $params[] = $status;
            }
            if ($active !== null && $active !== '') {
                $sql .= " AND is_active = ?";
                $params[] = (int)$active;
            }
            if ($linked !== null && $linked !== '') {
                $sql .= " AND linked_equip_id = ?";
                $params[] = (int)$linked;
            }
            if ($workshop !== null && $workshop !== '') {
                $sql .= " AND workshop_id = ?";
                $params[] = (int)$workshop;
            }
            if ($search !== '') {
                $sql .= " AND (
                    tooling_name LIKE ? OR tooling_code LIKE ? OR barcode LIKE ?
                    OR asset_tag LIKE ? OR oem_brand LIKE ? OR oem_model LIKE ?
                    OR serial_number LIKE ? OR location LIKE ?
                )";
                $like = '%' . $search . '%';
                for ($i = 0; $i < 8; $i++) {
                    $params[] = $like;
                }
            }

            $sql .= " ORDER BY tooling_id DESC LIMIT ? OFFSET ?";
            $params[] = $per_page;
            $params[] = $offset;

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            api_response(true, $items, '', 200, [
                'page' => $page,
                'per_page' => $per_page,
                'returned' => count($items),
            ]);
            break;

        case 'POST':
            require_api_perm('manage_toolings');
            $name = trim((string)($input['tooling_name'] ?? ''));
            if ($name === '') {
                api_error('tooling_name is required');
            }
            $codeIn = trim((string)($input['tooling_code'] ?? ''));
            if ($codeIn === '') {
                $codeIn = 'TL-' . strtoupper(bin2hex(random_bytes(4)));
            }
            // Unique code check
            $chk = $pdo->prepare("SELECT tooling_id FROM toolings WHERE tooling_code = ? LIMIT 1");
            $chk->execute([$codeIn]);
            if ($chk->fetch()) {
                api_error('tooling_code already exists', 409);
            }

            $status = $input['status'] ?? 'Available';
            $allowedStatus = ['Available', 'In Use', 'Maintenance', 'Calibration Due', 'Retired'];
            if (!in_array($status, $allowedStatus, true)) {
                api_error('Invalid status');
            }
            $cond = $input['condition_rating'] ?? 'Good';
            $allowedCond = ['New', 'Good', 'Fair', 'Poor'];
            if (!in_array($cond, $allowedCond, true)) {
                api_error('Invalid condition_rating');
            }

            $stmt = $pdo->prepare(
                "INSERT INTO toolings (
                    tooling_code, tooling_name, category, tooling_type, barcode, asset_tag,
                    oem_brand, oem_model, serial_number, status, condition_rating, location,
                    workshop_id, line_id, linked_equip_id, owner_dept,
                    calibration_due, last_calibration, purchase_date, cost, notes, is_active
                ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)"
            );
            $stmt->execute([
                $codeIn,
                $name,
                $input['category'] ?? null,
                $input['tooling_type'] ?? null,
                $input['barcode'] ?? null,
                $input['asset_tag'] ?? null,
                $input['oem_brand'] ?? null,
                $input['oem_model'] ?? null,
                $input['serial_number'] ?? null,
                $status,
                $cond,
                $input['location'] ?? null,
                isset($input['workshop_id']) ? (int)$input['workshop_id'] : null,
                isset($input['line_id']) ? (int)$input['line_id'] : null,
                isset($input['linked_equip_id']) ? (int)$input['linked_equip_id'] : null,
                $input['owner_dept'] ?? null,
                $input['calibration_due'] ?? null,
                $input['last_calibration'] ?? null,
                $input['purchase_date'] ?? null,
                isset($input['cost']) ? $input['cost'] : null,
                $input['notes'] ?? null,
                isset($input['is_active']) ? (int)$input['is_active'] : 1,
            ]);
            api_response(true, [
                'tooling_id' => (int)$pdo->lastInsertId(),
                'tooling_code' => $codeIn,
            ], 'Tooling created', 201);
            break;

        case 'PUT':
        case 'PATCH':
            require_api_perm('manage_toolings');
            if (!$id) {
                api_error('Tooling ID required');
            }
            $exists = $pdo->prepare(
                "SELECT tooling_id FROM toolings WHERE tooling_id = ? AND deleted_at IS NULL"
            );
            $exists->execute([(int)$id]);
            if (!$exists->fetch()) {
                api_error('Tooling not found', 404);
            }

            $allowed = [
                'tooling_code', 'tooling_name', 'category', 'tooling_type', 'barcode', 'asset_tag',
                'oem_brand', 'oem_model', 'serial_number', 'status', 'condition_rating', 'location',
                'workshop_id', 'line_id', 'linked_equip_id', 'owner_dept',
                'calibration_due', 'last_calibration', 'purchase_date', 'cost', 'notes', 'is_active',
            ];
            $fields = [];
            $params = [];
            foreach ($allowed as $f) {
                if (array_key_exists($f, $input)) {
                    if ($f === 'status') {
                        $allowedStatus = ['Available', 'In Use', 'Maintenance', 'Calibration Due', 'Retired'];
                        if (!in_array($input[$f], $allowedStatus, true)) {
                            api_error('Invalid status');
                        }
                    }
                    if ($f === 'condition_rating') {
                        $allowedCond = ['New', 'Good', 'Fair', 'Poor'];
                        if (!in_array($input[$f], $allowedCond, true)) {
                            api_error('Invalid condition_rating');
                        }
                    }
                    if ($f === 'tooling_code') {
                        $code = trim((string)$input[$f]);
                        if ($code === '') {
                            api_error('tooling_code cannot be empty');
                        }
                        $dup = $pdo->prepare(
                            "SELECT tooling_id FROM toolings WHERE tooling_code = ? AND tooling_id <> ? LIMIT 1"
                        );
                        $dup->execute([$code, (int)$id]);
                        if ($dup->fetch()) {
                            api_error('tooling_code already exists', 409);
                        }
                        $input[$f] = $code;
                    }
                    $fields[] = "$f = ?";
                    $params[] = $input[$f];
                }
            }
            if ($fields === []) {
                api_error('No fields to update');
            }
            $params[] = (int)$id;
            $pdo->prepare(
                "UPDATE toolings SET " . implode(', ', $fields) . " WHERE tooling_id = ?"
            )->execute($params);
            api_response(true, null, 'Tooling updated');
            break;

        case 'DELETE':
            require_api_perm('manage_toolings');
            if (!$id) {
                api_error('Tooling ID required');
            }
            // Soft-delete — preserves BOM/docs FKs and audit history
            $stmt = $pdo->prepare(
                "UPDATE toolings SET deleted_at = NOW(), is_active = 0, status = 'Retired'
                 WHERE tooling_id = ? AND deleted_at IS NULL"
            );
            $stmt->execute([(int)$id]);
            if ($stmt->rowCount() === 0) {
                api_error('Tooling not found or already deleted', 404);
            }
            api_response(true, null, 'Tooling soft-deleted');
            break;

        default:
            api_error('Method not allowed', 405);
    }
}

function tooling_must_exist(int $toolingId): void
{
    global $pdo;
    $st = $pdo->prepare(
        "SELECT tooling_id FROM toolings WHERE tooling_id = ? AND deleted_at IS NULL"
    );
    $st->execute([$toolingId]);
    if (!$st->fetch()) {
        api_error('Tooling not found', 404);
    }
}

function handle_tooling_bom(string $method, int $toolingId, $input, $bomId = null): void
{
    global $pdo;
    tooling_must_exist($toolingId);

    switch ($method) {
        case 'GET':
            require_api_perm('view_toolings');
            $stmt = $pdo->prepare(
                "SELECT b.bom_id, b.tooling_id, b.part_id, b.quantity, b.notes, b.created_at,
                        p.part_name, p.internal_code AS sku, p.stock_level
                 FROM tooling_bom b
                 JOIN inventory_parts p ON b.part_id = p.part_id
                 WHERE b.tooling_id = ?
                 ORDER BY p.part_name ASC"
            );
            $stmt->execute([$toolingId]);
            api_response(true, $stmt->fetchAll(PDO::FETCH_ASSOC));
            break;

        case 'POST':
            require_api_perm('manage_toolings');
            $partId = (int)($input['part_id'] ?? 0);
            if ($partId <= 0) {
                api_error('part_id is required');
            }
            $qty = max(1, (int)($input['quantity'] ?? 1));
            $notes = $input['notes'] ?? null;
            // Validate part
            $p = $pdo->prepare("SELECT part_id FROM inventory_parts WHERE part_id = ?");
            $p->execute([$partId]);
            if (!$p->fetch()) {
                api_error('part_id not found', 404);
            }
            try {
                $ins = $pdo->prepare(
                    "INSERT INTO tooling_bom (tooling_id, part_id, quantity, notes) VALUES (?,?,?,?)"
                );
                $ins->execute([$toolingId, $partId, $qty, $notes]);
            } catch (PDOException $e) {
                if ((int)$e->errorInfo[1] === 1062) {
                    api_error('Part already on this tooling BOM', 409);
                }
                throw $e;
            }
            api_response(true, ['bom_id' => (int)$pdo->lastInsertId()], 'BOM line created', 201);
            break;

        case 'PUT':
        case 'PATCH':
            require_api_perm('manage_toolings');
            if (!$bomId) {
                api_error('bom_id required in path');
            }
            $fields = [];
            $params = [];
            if (isset($input['quantity'])) {
                $fields[] = 'quantity = ?';
                $params[] = max(1, (int)$input['quantity']);
            }
            if (array_key_exists('notes', $input)) {
                $fields[] = 'notes = ?';
                $params[] = $input['notes'];
            }
            if (isset($input['part_id'])) {
                $fields[] = 'part_id = ?';
                $params[] = (int)$input['part_id'];
            }
            if ($fields === []) {
                api_error('No fields to update');
            }
            $params[] = (int)$bomId;
            $params[] = $toolingId;
            $u = $pdo->prepare(
                "UPDATE tooling_bom SET " . implode(', ', $fields) .
                " WHERE bom_id = ? AND tooling_id = ?"
            );
            $u->execute($params);
            if ($u->rowCount() === 0) {
                // might be same values — verify exists
                $c = $pdo->prepare(
                    "SELECT bom_id FROM tooling_bom WHERE bom_id = ? AND tooling_id = ?"
                );
                $c->execute([(int)$bomId, $toolingId]);
                if (!$c->fetch()) {
                    api_error('BOM line not found', 404);
                }
            }
            api_response(true, null, 'BOM line updated');
            break;

        case 'DELETE':
            require_api_perm('manage_toolings');
            if (!$bomId) {
                api_error('bom_id required in path');
            }
            $d = $pdo->prepare(
                "DELETE FROM tooling_bom WHERE bom_id = ? AND tooling_id = ?"
            );
            $d->execute([(int)$bomId, $toolingId]);
            if ($d->rowCount() === 0) {
                api_error('BOM line not found', 404);
            }
            api_response(true, null, 'BOM line deleted');
            break;

        default:
            api_error('Method not allowed', 405);
    }
}

function handle_tooling_documents(string $method, int $toolingId, $input, $docId = null): void
{
    global $pdo;
    tooling_must_exist($toolingId);

    switch ($method) {
        case 'GET':
            require_api_perm('view_toolings');
            $stmt = $pdo->prepare(
                "SELECT doc_id, tooling_id, doc_title, doc_type, file_path, uploaded_by, uploaded_at
                 FROM tooling_documents
                 WHERE tooling_id = ?
                 ORDER BY uploaded_at DESC"
            );
            $stmt->execute([$toolingId]);
            api_response(true, $stmt->fetchAll(PDO::FETCH_ASSOC));
            break;

        case 'POST':
            // Metadata registration only — binary upload remains on /api/upload_document.php
            require_api_perm('manage_toolings');
            $title = trim((string)($input['doc_title'] ?? ''));
            $path = trim((string)($input['file_path'] ?? ''));
            if ($title === '' || $path === '') {
                api_error('doc_title and file_path are required (multipart upload uses /api/upload_document.php)');
            }
            $type = $input['doc_type'] ?? 'SOP';
            $user = current_api_user();
            $by = $user['username'] ?? ($user['full_name'] ?? 'api');
            if (!empty($input['uploaded_by'])) {
                $by = (string)$input['uploaded_by'];
            }
            $ins = $pdo->prepare(
                "INSERT INTO tooling_documents (tooling_id, doc_title, doc_type, file_path, uploaded_by)
                 VALUES (?,?,?,?,?)"
            );
            $ins->execute([$toolingId, $title, $type, $path, $by]);
            api_response(true, ['doc_id' => (int)$pdo->lastInsertId()], 'Document metadata created', 201);
            break;

        case 'DELETE':
            require_api_perm('manage_toolings');
            if (!$docId) {
                api_error('doc_id required in path');
            }
            $d = $pdo->prepare(
                "DELETE FROM tooling_documents WHERE doc_id = ? AND tooling_id = ?"
            );
            $d->execute([(int)$docId, $toolingId]);
            if ($d->rowCount() === 0) {
                api_error('Document not found', 404);
            }
            api_response(true, null, 'Document deleted');
            break;

        default:
            api_error('Method not allowed', 405);
    }
}
