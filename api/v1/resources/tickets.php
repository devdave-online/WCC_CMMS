<?php
/**
 * Tickets Resource Handler
 */

function handle_tickets($method, $id, $input) {
    global $pdo;

    switch ($method) {
        case 'GET':
            if ($id) {
                require_api_perm('view_tickets');
                $stmt = $pdo->prepare("SELECT * FROM active_tickets WHERE ticket_id = ?");
                $stmt->execute([$id]);
                $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$ticket) api_error('Ticket not found', 404);
                api_response(true, $ticket);
            } else {
                require_api_perm('view_tickets');
                list($page, $per_page, $offset) = get_pagination();
                $status = $_GET['status'] ?? null;
                $equip_id = $_GET['equip_id'] ?? null;
                $sql = "SELECT * FROM active_tickets WHERE 1=1";
                $params = [];
                if ($status) {
                    $sql .= " AND status = ?";
                    $params[] = strtoupper($status);
                }
                if ($equip_id) {
                    $sql .= " AND equip_id = ?";
                    $params[] = $equip_id;
                }
                // History (CLOSED): most recently closed first. Live board: newest opened first.
                $orderStatus = strtoupper((string)($status ?? ''));
                if ($orderStatus === 'CLOSED') {
                    $sql .= " ORDER BY COALESCE(closed_at, created_at) DESC LIMIT ? OFFSET ?";
                } else {
                    $sql .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
                }
                $params[] = $per_page;
                $params[] = $offset;
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $meta = ['page' => $page, 'per_page' => $per_page, 'returned' => count($tickets)];
                api_response(true, $tickets, '', 200, $meta);
            }
            break;

        case 'POST':
            require_api_perm('create_tickets');
            if (empty($input['equip_id']) || empty($input['fault_desc'])) {
                api_error('equip_id and fault_desc are required');
            }
            // Was 'TK-API-' . date('YmdHis') — second-resolution, and ticket_id is the
            // primary key, so two API calls in the same second collided. Now uses the
            // shared allocator (retries on contention) and the same TK-YYMMDD-NNN
            // format as the web app, so IDs are indistinguishable by origin.
            //
            // announced_by previously fell back to $_SESSION['user_id'] — an integer —
            // which matched neither a username nor a full name, so those tickets were
            // orphaned from every "my work" stat. It now carries the display name.
            $stmt = $pdo->prepare("INSERT INTO active_tickets (ticket_id, equip_id, report_date, report_time, priority, fault_desc, announced_by, status) VALUES (?, ?, CURDATE(), CURTIME(), ?, ?, ?, 'OPEN')");
            $reporter = trim((string)($input['announced_by'] ?? '')) ?: wcc_tech_name();
            $ticketId = wcc_insert_ticket($pdo, function (string $id) use ($stmt, $input, $reporter) {
                $stmt->execute([
                    $id,
                    $input['equip_id'],
                    $input['priority'] ?? 'normal',
                    $input['fault_desc'],
                    $reporter
                ]);
            });
            // Parity with web submit_ticket.php
            require_once __DIR__ . '/../../../inc/notifications.php';
            $priority = $input['priority'] ?? 'normal';
            $actor = (int)($_SESSION['user_id'] ?? 0);
            wcc_notify_perm(
                'takeover_tickets',
                'ticket_new',
                'New event logged: ' . mb_substr((string)$input['fault_desc'], 0, 60) . ' (' . $ticketId . ')',
                '/_maint/active_tickets.php',
                ($priority === 'high' ? 'warning' : 'info'),
                $actor
            );
            api_response(true, ['ticket_id' => $ticketId], 'Ticket created', 201);
            break;

        case 'PUT':
            require_api_perm('takeover_tickets');
            if (!$id) api_error('Ticket ID required');
            $fields = [];
            $params = [];
            $allowed = ['status', 'priority', 'fault_desc', 'pic'];
            foreach ($allowed as $f) {
                if (isset($input[$f])) {
                    $fields[] = "$f = ?";
                    $params[] = $input[$f];
                }
            }
            if (empty($fields)) api_error('No fields to update');
            $params[] = $id;
            $stmt = $pdo->prepare("UPDATE active_tickets SET " . implode(', ', $fields) . " WHERE ticket_id = ?");
            $stmt->execute($params);
            api_response(true, null, 'Ticket updated');
            break;

        case 'DELETE':
            require_api_perm('closeout_tickets');
            if (!$id) api_error('Ticket ID required');
            $stmt = $pdo->prepare("DELETE FROM active_tickets WHERE ticket_id = ?");
            $stmt->execute([$id]);
            api_response(true, null, 'Ticket deleted');
            break;

        default:
            api_error('Method not allowed', 405);
    }
}
