<?php
/**
 * WCC CMMS — demo BOM + lifecycle map (shared).
 *
 * Used by demo/demo_seed.php (fresh seed) and demo/apply_bom.php (apply to an
 * already-seeded database without a full reseed). Keeping it in one place means the
 * two can never drift.
 *
 * Keys are stable business identifiers — equipment NAME and part internal CODE —
 * not auto-increment ids, so this survives a reseed regardless of id ordering.
 */

/** equipment name => [part internal_code => qty fitted]. Class-A machines earn the ★. */
function wcc_demo_bom(): array
{
    return [
        'DMG Mori NHX 5000 Machining Ctr' => [
            'BRG-6205' => 2, 'BRG-6308' => 2, 'MEC-BK15' => 2, 'MEC-HGH25' => 4,
            'HYD-GRS2' => 1, 'HYD-WL68' => 1, 'FLT-CB25' => 4, 'TOL-EM12' => 6,
            'TOL-CN120' => 10, 'ELC-VFD75' => 1,
        ],
        'Mazak VTC-800 Vertical Center' => [
            'BRG-6205' => 2, 'MEC-BK15' => 2, 'MEC-HGH25' => 4, 'HYD-GRS2' => 1,
            'FLT-CB25' => 4, 'TOL-EM12' => 6, 'TOL-ER32' => 2, 'ELC-VFD75' => 1,
        ],
        'Okuma LB3000 EX II Lathe' => [
            'BRG-6308' => 2, 'BRG-22215' => 1, 'HYD-GRS2' => 1, 'HYD-WL68' => 1,
            'TOL-CN120' => 10, 'ELC-VFD75' => 1, 'ELC-3RT25' => 2,
        ],
        'KUKA KR 60 Weld Robot' => [
            'ELC-1FK7' => 1, 'ELC-VFD75' => 1, 'ELC-3RT25' => 2, 'ELC-PNOZ' => 1,
            'TOL-WCT10' => 20, 'TOL-WTN8' => 4,
        ],
        'Trumpf TruLaser 3030 Cutter' => [
            'MEC-HGH25' => 4, 'MEC-BK15' => 2, 'ELC-PRX12' => 2, 'ELC-PNOZ' => 1,
        ],
        'Leak Test Bench A1' => [
            'SEA-ORK1' => 1, 'PNU-SV52' => 2, 'PNU-FRL12' => 1, 'PNU-32100' => 1,
        ],
        'Servo Press Station A2' => [
            'ELC-1FK7' => 1, 'ELC-VFD75' => 1, 'ELC-PNOZ' => 1, 'MEC-HGH25' => 2,
        ],
        'Atlas Copco GA 55 Compressor' => [
            'FLT-GA55' => 2, 'ELC-3RT25' => 1, 'MEC-SPZ16' => 2,
        ],
        'Chiller Unit CH-1' => [
            'ELC-3RT25' => 1, 'PNU-SV52' => 1, 'ELC-PNOZ' => 1,
        ],
    ];
}

/** part internal_code => lifecycle_status to override (demonstrates the obsolete/phasing states). */
function wcc_demo_lifecycle(): array
{
    return [
        'BRG-22215'  => 'Phasing Out',  // a critical spare being phased out — below min, so it
                                        // shows "obsolete" rather than "reorder" (precedence demo)
        'FAC-LED150' => 'Obsolete',     // healthy stock but discontinued
    ];
}

/**
 * Write the BOM and lifecycle overrides into the database.
 *
 * @param PDO $pdo
 * @param bool $wipeBom truncate equipment_bom first (fresh seed) or leave it (apply)
 * @return array counts
 */
function wcc_demo_apply_bom(PDO $pdo, bool $wipeBom = false): array
{
    // Resolve stable keys to ids.
    $eqId = $pdo->query("SELECT equip_name, equip_id FROM equipment")->fetchAll(PDO::FETCH_KEY_PAIR);
    $ptId = $pdo->query("SELECT internal_code, part_id FROM inventory_parts")->fetchAll(PDO::FETCH_KEY_PAIR);

    if ($wipeBom) $pdo->exec("TRUNCATE TABLE equipment_bom");

    $ins  = $pdo->prepare("INSERT INTO equipment_bom (equip_id, part_id, quantity) VALUES (?,?,?)");
    $rows = 0; $skipped = 0;
    foreach (wcc_demo_bom() as $machine => $parts) {
        if (!isset($eqId[$machine])) { $skipped++; continue; }
        foreach ($parts as $code => $qty) {
            if (!isset($ptId[$code])) { $skipped++; continue; }
            // Skip if this pairing already exists (apply mode, re-runnable).
            $exists = $pdo->prepare("SELECT 1 FROM equipment_bom WHERE equip_id=? AND part_id=? LIMIT 1");
            $exists->execute([$eqId[$machine], $ptId[$code]]);
            if ($exists->fetchColumn()) continue;
            $ins->execute([$eqId[$machine], $ptId[$code], $qty]);
            $rows++;
        }
    }

    $life = $pdo->prepare("UPDATE inventory_parts SET lifecycle_status = ? WHERE internal_code = ?");
    $lifeN = 0;
    foreach (wcc_demo_lifecycle() as $code => $status) {
        if (!isset($ptId[$code])) continue;
        $life->execute([$status, $code]);
        $lifeN++;
    }

    return ['bom_rows' => $rows, 'lifecycle_set' => $lifeN, 'skipped' => $skipped];
}
