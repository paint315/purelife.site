#!/usr/bin/php
<?php
$backupDir = __DIR__ . '/backups/';
if (!is_dir($backupDir)) mkdir($backupDir, 0755, true);

$dbHost = 'localhost';
$dbUser = 'cm903759_purelife';
$dbPass = 'KsJZ7bEM';
$dbName = 'cm903759_purelife';
$backupFile = $backupDir . 'db_backup_' . date('Y-m-d_H-i-s') . '.sql';

$command = "mysqldump --host=$dbHost --user=$dbUser --password=$dbPass --no-tablespaces $dbName > $backupFile";
system($command, $output);

// Оставляем только последние 15 копий
$files = glob($backupDir . '*.sql');
usort($files, function($a, $b) { return filemtime($a) - filemtime($b); });
while (count($files) > 15) {
    unlink(array_shift($files));
}