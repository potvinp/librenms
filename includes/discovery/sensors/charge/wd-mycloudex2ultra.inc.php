<?php
/*
 * WD My Cloud EX2 Ultra - UPS Battery Charge
 */

global $valid;
if (!isset($valid) || !is_array($valid)) { $valid = []; }
if (!isset($valid['sensor']) || !is_array($valid['sensor'])) { $valid['sensor'] = []; }

$ups_table = snmpwalk_cache_oid($device, 'mycloudex2ultraUPSEntry', [], 'MYCLOUDEX2ULTRA-MIB');

foreach ((array)$ups_table as $index => $entry) {
    $oid = ".1.3.6.1.4.1.5127.1.1.1.8.1.11.1.5.$index"; // UPSBatteryCharge

    $raw = $entry['mycloudex2ultraUPSBatteryCharge'] ?? null;
    if ($raw === null || $raw === '') {
        continue;
    }
    if (!preg_match('/([0-9]+(?:\.[0-9]+)?)/', (string)$raw, $m)) {
        continue;
    }
    $value = (float)$m[1];

    discover_sensor($valid['sensor'], 'charge', $device, $oid, $index, 'wd-mycloudex2ultra', 'UPS Battery Charge', 1, 1, $value);
}
