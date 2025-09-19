<?php
/*
 * WD My Cloud EX2 Ultra - State Sensors
 * - UPS Status (table) .1.3.6.1.4.1.5127.1.1.1.8.1.11.1.6.index  (your device returns "N/A": this will auto-skip) * - Fan Status (scalar) .1.3.6.1.4.1.5127.1.1.1.8.1.8.0          ("fan0: stop    " or "fan0: running")
 */

global $valid;
if (!isset($valid) || !is_array($valid)) { $valid = []; }
if (!isset($valid['sensor']) || !is_array($valid['sensor'])) { $valid['sensor'] = []; }

/* ---- UPS state (table) ---- */
$ups_table = snmpwalk_cache_oid($device, 'mycloudex2ultraUPSEntry', [], 'MYCLOUDEX2ULTRA-MIB');

$ups_states = [
    'online' => 0, 'on line' => 0,
    'onbattery' => 1, 'on battery' => 1, 'charging' => 1, 'discharging' => 1,
    'bypass' => 1, 'calibration' => 1,
    'lowbattery' => 2, 'low battery' => 2, 'replacebattery' => 2, 'replace battery' => 2,
    'overload' => 2, 'shutdown' => 2,
];

$ups_type = 'wd-mycloudex2ultra-ups-status';
$ups_state_index_id = create_state_index($ups_type);
if ($ups_state_index_id !== null) {
    foreach ([
        ['online',0,0], ['on line',0,0], ['on battery',1,1], ['charging',1,1], ['discharging',1,1],
        ['bypass',1,1], ['calibration',1,1], ['low battery',2,2], ['replace battery',2,2],
        ['overload',2,2], ['shutdown',2,2], ['unknown',3,3],
    ] as [$descr,$val,$gen]) {
        create_state_translation($ups_state_index_id, $val, $gen, $descr);
    }
}

foreach ((array)$ups_table as $index => $entry) {
    $oid = ".1.3.6.1.4.1.5127.1.1.1.8.1.11.1.6.$index"; // mycloudex2ultraUPSStatus
    $raw = strtolower(trim((string)($entry['mycloudex2ultraUPSStatus'] ?? '')));
    if ($raw === '' || $raw === 'n/a' || $raw === '--') { continue; } // your device returns N/A → skip
    $current = $ups_states[$raw] ?? 3; // unknown
    discover_sensor($valid['sensor'], 'state', $device, $oid, $index, $ups_type, 'UPS Status', 1, 1, $current);
    if ($ups_state_index_id !== null) { set_entity_state($device, $ups_type, $index, $ups_state_index_id); }
}

/* ---- Fan status (scalar) ---- */
$fan_oid = '.1.3.6.1.4.1.5127.1.1.1.8.1.8.0'; // mycloudex2ultraFanStatus.0
$raw = snmp_get($device, $fan_oid, '-Oqv', 'MYCLOUDEX2ULTRA-MIB');
if ($raw !== '' && $raw !== false) {
    $s = strtolower(trim((string)$raw));          // e.g. "fan0: stop"
    $ok = (str_contains($s, 'run'));             // running / runner / running…
    $stopped = (str_contains($s, 'stop') || str_contains($s, 'stopped'));

    $current = 3;                                 // unknown
    if     ($ok)      { $current = 0; }           // OK
    elseif ($stopped) { $current = 2; }           // Critical

    $type = 'wd-mycloudex2ultra-fan';
    discover_sensor($valid['sensor'], 'state', $device, $fan_oid, 0, $type, 'Fan Status', 1, 1, $current);

    $fan_state_index_id = create_state_index($type);
    if ($fan_state_index_id !== null) {
        foreach ([['OK',0,0], ['Fail/Stopped',2,2], ['Unknown',3,3]] as [$descr,$val,$gen]) {
            create_state_translation($fan_state_index_id, $val, $gen, $descr);
        }
        set_entity_state($device, $type, 0, $fan_state_index_id);
    }
}
