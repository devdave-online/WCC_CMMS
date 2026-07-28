<?php
/**
 * Vendors / Suppliers Resource Handler
 * Table: vendors_suppliers
 */

function handle_vendors($method, $id, $input) {
    global $pdo;

    switch ($method) {
        case 'GET':
            if ($id) {
                require_api_perm('view_vendors');
                $stmt = $pdo->prepare("SELECT * FROM vendors_suppliers WHERE vendor_id = ?");
                $stmt->execute([$id]);
                $item = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$item) api_error('Vendor not found', 404);
                api_response(true, $item);
            } else {
                require_api_perm('view_vendors');
                list($page, $per_page, $offset) = get_pagination();
                $search = $_GET['search'] ?? '';
                $sql = "SELECT * FROM vendors_suppliers WHERE 1=1";
                $params = [];
                if ($search) {
                    $sql .= " AND (vendor_name LIKE ? OR primary_contact_name LIKE ?)";
                    $params[] = "%$search%";
                    $params[] = "%$search%";
                }
                $sql .= " ORDER BY vendor_name ASC LIMIT ? OFFSET ?";
                $params[] = $per_page;
                $params[] = $offset;
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $meta = ['page' => $page, 'per_page' => $per_page, 'returned' => count($items)];
                api_response(true, $items, '', 200, $meta);
            }
            break;

        case 'POST':
            require_api_perm('manage_vendors');
            if (empty($input['vendor_name'])) api_error('vendor_name is required');
            $stmt = $pdo->prepare("INSERT INTO vendors_suppliers (vendor_name, primary_contact_name, contact_email, contact_phone, address) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([
                $input['vendor_name'],
                $input['primary_contact_name'] ?? null,
                $input['contact_email'] ?? null,
                $input['contact_phone'] ?? null,
                $input['address'] ?? null
            ]);
            api_response(true, ['vendor_id' => $pdo->lastInsertId()], 'Vendor created', 201);
            break;

        case 'PUT':
        case 'PATCH':
            require_api_perm('manage_vendors');
            if (!$id) api_error('Vendor ID required');
            $fields = [];
            $params = [];
            $allowed = ['vendor_name', 'primary_contact_name', 'contact_email', 'contact_phone', 'address', 'notes'];
            foreach ($allowed as $f) {
                if (isset($input[$f])) {
                    $fields[] = "$f = ?";
                    $params[] = $input[$f];
                }
            }
            if (empty($fields)) api_error('No fields to update');
            $params[] = $id;
            $stmt = $pdo->prepare("UPDATE vendors_suppliers SET " . implode(', ', $fields) . " WHERE vendor_id = ?");
            $stmt->execute($params);
            api_response(true, null, 'Vendor updated');
            break;

        case 'DELETE':
            require_api_perm('manage_vendors');
            if (!$id) api_error('Vendor ID required');
            $stmt = $pdo->prepare("DELETE FROM vendors_suppliers WHERE vendor_id = ?");
            $stmt->execute([$id]);
            api_response(true, null, 'Vendor deleted');
            break;

        default:
            api_error('Method not allowed', 405);
    }
}
