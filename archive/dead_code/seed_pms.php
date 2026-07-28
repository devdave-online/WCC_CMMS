<?php
$host = 'localhost'; $db = 'workshop_db'; $user = 'root'; $pass = '';
try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    
    // Fetch dependencies
    $equipment = $pdo->query("SELECT equip_id, equip_name FROM equipment")->fetchAll(PDO::FETCH_ASSOC);
    $users = $pdo->query("SELECT user_id FROM users WHERE role_level >= 2")->fetchAll(PDO::FETCH_ASSOC);
    $parts = $pdo->query("SELECT part_id FROM inventory_parts")->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($equipment) || empty($users)) {
        die("Not enough data to seed.");
    }
    
    // Arrays for realistic generation
    $pm_types = [
        'Conveyor' => ['Belt Tension Check', 'Roller Lubrication', 'Motor Amperage Analysis'],
        'Robot' => ['Axis Calibration', 'Joint Greasing', 'End-Effector Sensor Check'],
        'Pump' => ['Seal Inspection', 'Vibration Analysis', 'Impeller Cleaning'],
        'CNC' => ['Spindle Runout Test', 'Coolant Flush', 'Way Lube Top-Off'],
        'Sensor' => ['Lens Cleaning', 'Signal Calibration'],
        'Default' => ['General Inspection', 'Electrical Panel Check', 'Deep Cleaning']
    ];
    
    $descriptions = [
        "Please follow the standard operating procedure for this maintenance task. Ensure LOTO is applied.",
        "Check for any abnormal wear and tear. Report findings to the supervisor.",
        "Perform a thorough cleaning and lubrication. Do not overtighten fittings.",
        "Verify all sensors are responding within normal parameters."
    ];
    
    echo "Seeding PM Schedules...\n";
    $pm_count = 0;
    
    // Create 75 PM Schedules
    for ($i = 0; $i < 75; $i++) {
        $eq = $equipment[array_rand($equipment)];
        $tech_id = rand(0, 1) ? $users[array_rand($users)]['user_id'] : null;
        
        $eq_name = strtolower($eq['equip_name']);
        $category = 'Default';
        foreach ($pm_types as $key => $titles) {
            if ($key !== 'Default' && strpos($eq_name, strtolower($key)) !== false) {
                $category = $key; break;
            }
        }
        
        $title = $pm_types[$category][array_rand($pm_types[$category])] . " - " . $eq['equip_name'];
        $desc = $descriptions[array_rand($descriptions)];
        $freq = [7, 14, 30, 90, 180, 365][array_rand([7, 14, 30, 90, 180, 365])];
        
        // Parts
        $num_parts = rand(0, 3);
        $req_parts = [];
        for ($p=0; $p<$num_parts; $p++) {
            $req_parts[] = $parts[array_rand($parts)]['part_id'];
        }
        $parts_json = json_encode(array_unique($req_parts));
        
        $stmt = $pdo->prepare("INSERT INTO pm_schedules (title, description, equipment_id, assigned_to, parts_list, frequency_days, next_run_date) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $next_run = date('Y-m-d', strtotime("+" . rand(1, 45) . " days"));
        $stmt->execute([$title, $desc, $eq['equip_id'], $tech_id, $parts_json, $freq, $next_run]);
        $pm_count++;
    }
    
    echo "Created $pm_count PM Schedules.\n";
    
    echo "Seeding Work Orders for Calendar testing...\n";
    $wo_count = 0;
    $today = new DateTime('today');
    
    // We need 10 closed, but let's make 40 total to cover all states
    $offsets = [
        -10, -5, -4, -3, -2, -1, 0, 1, 2, 3, 5, 7, 10, 14
    ];
    
    foreach ($offsets as $offset) {
        // Create 2-3 WOs per offset day
        $daily_count = rand(2, 4);
        $date = clone $today;
        $date->modify("$offset days");
        $date_str = $date->format('Y-m-d');
        
        for ($j = 0; $j < $daily_count; $j++) {
            $eq = $equipment[array_rand($equipment)];
            $tech_id = $users[array_rand($users)]['user_id'];
            $title = "Scheduled PM: " . $eq['equip_name'];
            $desc = "Auto-generated test WO for offset $offset.";
            
            $status = 'Scheduled';
            $completed_date = null;
            
            // Logic for statuses
            if ($offset < -2) {
                // Far in past, probably completed or missed
                $status = rand(0, 3) > 0 ? 'Completed' : 'Cancelled';
            } elseif ($offset >= -2 && $offset <= 0) {
                // Recent past or today, might be completed or overdue/open
                $status = rand(0, 2) > 0 ? 'Completed' : (rand(0,1) ? 'In Progress' : 'Scheduled');
            } else {
                // Future, mostly scheduled
                $status = rand(0, 5) == 0 ? 'Cancelled' : 'Scheduled';
            }
            
            if ($status === 'Completed') {
                $comp = clone $date;
                $comp->modify("+" . rand(0, 1) . " days");
                $completed_date = $comp->format('Y-m-d H:i:s');
            }
            
            $stmt = $pdo->prepare("INSERT INTO work_orders (title, description, equipment_id, assigned_to, parts_list, scheduled_date, status, completed_date, completed_by) VALUES (?, ?, ?, ?, '[]', ?, ?, ?, ?)");
            
            $comp_by = $status === 'Completed' ? $tech_id : null;
            $stmt->execute([$title, $desc, $eq['equip_id'], $tech_id, $date_str, $status, $completed_date, $comp_by]);
            $wo_count++;
        }
    }
    
    echo "Created $wo_count Work Orders.\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
