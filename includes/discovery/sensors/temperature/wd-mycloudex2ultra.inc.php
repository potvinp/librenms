<?php
/*
 * WD My Cloud EX2 Ultra - Temperatures
 * - Disk temperatures: table  .1.3.6.1.4.1.5127.1.1.1.8.1.10.1.5.index
 * - System temperature: scalar .1.3.6.1.4.1.5127.1.1.1.8.1.7.0
 */

global $valid;
if (!isset($valid) || !is_array($valid)) { $valid = []; }
if (!isset($valid['sensor']) || !is_array($valid['sensor'])) { $valid['sensor'] = []; }

/* ---- Disk temperatures (table) ---- */
$disk_table = snmpwalk_cache_oid($device, 'mycloudex2ultraDiskEntry', [], 'MYCLOUDEX2ULTRA-MIB');

foreach ((array)$disk_table as $index => $entry) {
    $oid = ".1.3.6.1.4.1.5127.1.1.1.8.1.10.1.5.$index"; // mycloudex2ultraDiskTemperature

    $raw = $entry['mycloudex2ultraDiskTemperature'] ?? null;
    if ($raw === null || $raw === '' || !preg_match('/([0-9]+(?:\.[0-9]+)?)/', (string)$raw, $m)) {
        continue;
    }
    $value = (float)$m[1];

    $num   = $entry['mycloudex2ultraDiskNum']   ?? $index;
    $model = $entry['mycloudex2ultraDiskModel'] ?? '';
    $descr = trim("Disk $num $model");

    discover_sensor($valid['sensor'], 'temperature', $device, $oid, $index, 'wd-mycloudex2ultra', $descr, 1, 1, $value);
}

/* ---- System temperature (scalar) ---- */
$sys_temp_oid = '.1.3.6.1.4.1.5127.1.1.1.8.1.7.0'; // mycloudex2ultraTemperature.0
$raw = snmp_get($device, $sys_temp_oid, '-Oqv', 'MYCLOUDEX2ULTRA-MIB');
if ($raw !== '' && $raw !== false) {
    if (preg_match('/Centigrade:\s*([0-9]+(?:\.[0-9]+)?)/i', (string)$raw, $m)) {
        $value = (float)$m[1];
        discover_sensor($valid['sensor'], 'temperature', $device, $sys_temp_oid, 0, 'wd-mycloudex2ultra', 'System Temperature', 1, 1, $value);
    }
}
