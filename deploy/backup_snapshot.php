<?php
/**
 * Takes a safe, consistent snapshot of the live SQLite database using
 * SQLite's own VACUUM INTO command, which is atomic and does not corrupt
 * data even if the app is actively reading/writing at the time it runs.
 *
 * Usage: php backup_snapshot.php <source-database.sqlite> <destination-snapshot.sqlite>
 */

if ($argc < 3) {
    fwrite(STDERR, "Usage: php backup_snapshot.php <source.sqlite> <destination.sqlite>\n");
    exit(1);
}

$source = $argv[1];
$destination = $argv[2];

if (!is_file($source)) {
    fwrite(STDERR, "Source database not found: $source\n");
    exit(1);
}

if (is_file($destination)) {
    unlink($destination);
}

try {
    $pdo = new PDO('sqlite:' . $source);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Forward slashes are required inside the VACUUM INTO literal even on Windows.
    $destForSql = str_replace('\\', '/', $destination);
    $escaped = str_replace("'", "''", $destForSql);
    $pdo->exec("VACUUM INTO '{$escaped}'");
} catch (Throwable $e) {
    fwrite(STDERR, "Snapshot failed: " . $e->getMessage() . "\n");
    exit(1);
}

if (!is_file($destination)) {
    fwrite(STDERR, "Snapshot did not produce an output file.\n");
    exit(1);
}

echo "OK: snapshot written to $destination (" . round(filesize($destination) / 1024, 1) . " KB)\n";
