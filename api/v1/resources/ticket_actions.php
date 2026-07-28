<?php
/**
 * Ticket Actions Resource Handler
 */

function handle_ticket_actions($method, $id, $input) {
    global $pdo;

    switch ($method) {
        case 'GET':
            require_api_perm('view_tickets');
            if ($id) {
                $stmt = $pdo->prepare("SELECT * FROM ticket_actions WHERE action_id = ?");
                $stmt->execute([$id]);
                $action = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$action) api_error('Action not found', 404);
                api_response(true, $action);
            } else {
                $ticket_id = $_GET['ticket_id'] ?? null;
                if (!$ticket_id) api_error('ticket_id query param required for list');
                $stmt = $pdo->prepare("SELECT * FROM ticket_actions WHERE ticket_id = ? ORDER BY action_start ASC");
                $stmt->execute([$ticket_id]);
                $actions = $stmt->fetchAll(PDO::FETCH_ASSOC);
                api_response(true, $actions);
            }
            break;

        case 'POST':
            require_api_perm('takeover_tickets');
            if (empty($input['ticket_id']) || empty($input['tech_name'])) {
                api_error('ticket_id and tech_name required');
            }
            // Schema: fault_type, root_cause, action_taken, parts_used, escalated_to (no `notes` column)
            $actionTaken = $input['action_taken'] ?? $input['notes'] ?? '';
            $stmt = $pdo->prepare("INSERT INTO ticket_actions (ticket_id, tech_name, action_start, action_end, fault_type, root_cause, action_taken, parts_used, escalated_to) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $input['ticket_id'],
                $input['tech_name'],
                $input['action_start'] ?? date('Y-m-d H:i:s'),
                $input['action_end'] ?? null,
                $input['fault_type'] ?? 'Other',
                $input['root_cause'] ?? 'Other',
                $actionTaken,
                $input['parts_used'] ?? 'None',
                $input['escalated_to'] ?? 'None',
            ]);
            api_response(true, ['action_id' => $pdo->lastInsertId()], 'Action logged', 201);
            break;

        default:
            api_error('Method not allowed', 405);
    }
}
