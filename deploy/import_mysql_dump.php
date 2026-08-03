<?php
/**
 * Parses a mysqldump SQL file (CREATE TABLE + INSERT INTO statements only)
 * and inserts the row data into an existing SQLite database whose schema
 * was already created by Laravel migrations. Only columns that exist in
 * both the dump's CREATE TABLE and the live SQLite table are copied.
 */

if ($argc < 3) {
    fwrite(STDERR, "Usage: php import_mysql_dump.php <dump.sql> <database.sqlite>\n");
    exit(1);
}

$dumpPath = $argv[1];
$sqlitePath = $argv[2];

if (!is_file($dumpPath)) {
    fwrite(STDERR, "Dump file not found: $dumpPath\n");
    exit(1);
}
if (!is_file($sqlitePath)) {
    fwrite(STDERR, "SQLite file not found: $sqlitePath\n");
    exit(1);
}

$sql = file_get_contents($dumpPath);
$len = strlen($sql);

// ---- Tokenizing statement splitter -------------------------------------
// Splits the dump into top-level statements terminated by an unquoted ';'
// while stripping -- comments, # comments, and /* ... */ (incl. /*! ... */) comments.
function splitStatements(string $sql): array
{
    $statements = [];
    $buf = '';
    $len = strlen($sql);
    $i = 0;
    while ($i < $len) {
        $c = $sql[$i];

        // line comment: -- or #
        if (($c === '-' && $i + 1 < $len && $sql[$i + 1] === '-') || $c === '#') {
            while ($i < $len && $sql[$i] !== "\n") $i++;
            continue;
        }
        // block comment /* ... */ (also swallows /*!40000 ... */ conditional comments)
        if ($c === '/' && $i + 1 < $len && $sql[$i + 1] === '*') {
            $i += 2;
            while ($i < $len && !($sql[$i] === '*' && $i + 1 < $len && $sql[$i + 1] === '/')) $i++;
            $i += 2;
            continue;
        }
        // single-quoted string
        if ($c === "'") {
            $tok = "'";
            $i++;
            while ($i < $len) {
                if ($sql[$i] === '\\' && $i + 1 < $len) {
                    $tok .= $sql[$i] . $sql[$i + 1];
                    $i += 2;
                    continue;
                }
                if ($sql[$i] === "'") {
                    if ($i + 1 < $len && $sql[$i + 1] === "'") {
                        $tok .= "''";
                        $i += 2;
                        continue;
                    }
                    $tok .= "'";
                    $i++;
                    break;
                }
                $tok .= $sql[$i];
                $i++;
            }
            $buf .= $tok;
            continue;
        }
        // backtick identifier
        if ($c === '`') {
            $tok = '`';
            $i++;
            while ($i < $len && $sql[$i] !== '`') {
                $tok .= $sql[$i];
                $i++;
            }
            $tok .= '`';
            $i++;
            $buf .= $tok;
            continue;
        }
        // statement terminator
        if ($c === ';') {
            $trimmed = trim($buf);
            if ($trimmed !== '') $statements[] = $trimmed;
            $buf = '';
            $i++;
            continue;
        }
        $buf .= $c;
        $i++;
    }
    $trimmed = trim($buf);
    if ($trimmed !== '') $statements[] = $trimmed;
    return $statements;
}

// ---- Value tuple parser for "(...),(...),(...)" in INSERT VALUES -------
function parseValueTuples(string $s): array
{
    $tuples = [];
    $len = strlen($s);
    $i = 0;
    while ($i < $len) {
        while ($i < $len && ($s[$i] === ' ' || $s[$i] === "\n" || $s[$i] === "\t" || $s[$i] === ',' || $s[$i] === "\r")) $i++;
        if ($i >= $len || $s[$i] !== '(') break;
        $i++; // skip (
        $values = [];
        $cur = '';
        $inString = false;
        $started = true;
        while ($i < $len) {
            $c = $s[$i];
            if ($inString) {
                if ($c === '\\' && $i + 1 < $len) {
                    $next = $s[$i + 1];
                    $map = ["n" => "\n", "r" => "\r", "t" => "\t", "0" => "\0", "Z" => "\x1a", "'" => "'", '"' => '"', "\\" => "\\", "b" => "\x08"];
                    $cur .= $map[$next] ?? $next;
                    $i += 2;
                    continue;
                }
                if ($c === "'") {
                    if ($i + 1 < $len && $s[$i + 1] === "'") {
                        $cur .= "'";
                        $i += 2;
                        continue;
                    }
                    $inString = false;
                    $i++;
                    continue;
                }
                $cur .= $c;
                $i++;
                continue;
            }
            if ($c === "'") {
                $inString = true;
                $i++;
                continue;
            }
            if ($c === ')') {
                $values[] = parseScalar($cur);
                $cur = '';
                $i++;
                break;
            }
            if ($c === ',') {
                $values[] = parseScalar($cur);
                $cur = '';
                $i++;
                continue;
            }
            $cur .= $c;
            $i++;
        }
        $tuples[] = $values;
    }
    return $tuples;
}

function parseScalar(string $raw)
{
    $t = trim($raw);
    if (strtoupper($t) === 'NULL') return null;
    if ($t === '') return null;
    if (is_numeric($t)) return $t + 0;
    return $raw; // already unescaped by caller loop (string content)
}

// ---- Extract ordered column names from a CREATE TABLE statement --------
function extractColumns(string $createStmt): array
{
    $start = strpos($createStmt, '(');
    $end = strrpos($createStmt, ')');
    if ($start === false || $end === false || $end <= $start) return [];
    $body = substr($createStmt, $start + 1, $end - $start - 1);

    // split top-level by comma (respecting parens for things like enum(...) or decimal(10,2))
    $parts = [];
    $depth = 0;
    $cur = '';
    for ($i = 0, $n = strlen($body); $i < $n; $i++) {
        $c = $body[$i];
        if ($c === '(') $depth++;
        if ($c === ')') $depth--;
        if ($c === ',' && $depth === 0) {
            $parts[] = $cur;
            $cur = '';
            continue;
        }
        $cur .= $c;
    }
    if (trim($cur) !== '') $parts[] = $cur;

    $columns = [];
    foreach ($parts as $part) {
        $p = ltrim($part);
        if ($p[0] === '`') {
            $end2 = strpos($p, '`', 1);
            if ($end2 !== false) {
                $columns[] = substr($p, 1, $end2 - 1);
            }
        }
        // else: PRIMARY KEY / KEY / CONSTRAINT / UNIQUE KEY lines -> skip
    }
    return $columns;
}

$statements = splitStatements($sql);

$tableColumns = []; // tableName => [colNames in dump order]
$tableRows = [];     // tableName => [ [col=>val, ...], ... ]

$currentCreateTable = null;
foreach ($statements as $stmt) {
    $stmtUpper = strtoupper($stmt);
    if (str_starts_with($stmtUpper, 'CREATE TABLE')) {
        if (preg_match('/CREATE TABLE\s+`([^`]+)`/i', $stmt, $m)) {
            $table = $m[1];
            $tableColumns[$table] = extractColumns($stmt);
        }
        continue;
    }
    if (str_starts_with($stmtUpper, 'INSERT INTO')) {
        if (preg_match('/INSERT INTO\s+`([^`]+)`\s*VALUES\s*(.*)$/is', $stmt, $m)) {
            $table = $m[1];
            $valuesPart = $m[2];
            $cols = $tableColumns[$table] ?? [];
            if (empty($cols)) continue;
            $tuples = parseValueTuples($valuesPart);
            foreach ($tuples as $tuple) {
                $row = [];
                foreach ($cols as $idx => $colName) {
                    $row[$colName] = $tuple[$idx] ?? null;
                }
                $tableRows[$table][] = $row;
            }
        }
        continue;
    }
}

echo "Parsed " . count($tableRows) . " tables with data from dump.\n";
foreach ($tableRows as $t => $rows) {
    echo "  - $t: " . count($rows) . " rows (columns: " . implode(',', $tableColumns[$t]) . ")\n";
}

// ---- Now write into SQLite ---------------------------------------------
$pdo = new PDO('sqlite:' . $sqlitePath);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec('PRAGMA foreign_keys = OFF;');

// get existing sqlite tables
$sqliteTables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN);
$sqliteTables = array_flip($sqliteTables);

// framework/runtime tables must never be imported from an old system's dump
$skipTables = array_flip([
    'migrations', 'cache', 'cache_locks', 'sessions', 'jobs', 'job_batches',
    'failed_jobs', 'password_reset_tokens',
]);

$report = [];
$pdo->beginTransaction();
try {
    foreach ($tableRows as $table => $rows) {
        if (isset($skipTables[$table])) {
            $report[$table] = "SKIPPED (framework table, left untouched)";
            continue;
        }
        if (!isset($sqliteTables[$table])) {
            $report[$table] = "SKIPPED (table not in SQLite schema)";
            continue;
        }
        // get sqlite column list for this table
        $colInfo = $pdo->query("PRAGMA table_info(`$table`)")->fetchAll(PDO::FETCH_ASSOC);
        $sqliteCols = array_column($colInfo, 'name');
        $sqliteColSet = array_flip($sqliteCols);

        // clear existing rows first (idempotent re-run)
        $pdo->exec("DELETE FROM `$table`");

        $inserted = 0;
        foreach ($rows as $row) {
            $useCols = [];
            $useVals = [];
            foreach ($row as $col => $val) {
                if (isset($sqliteColSet[$col])) {
                    $useCols[] = $col;
                    $useVals[] = $val;
                }
            }
            if (empty($useCols)) continue;
            $placeholders = implode(',', array_fill(0, count($useCols), '?'));
            $colList = implode(',', array_map(fn($c) => "`$c`", $useCols));
            $stmt = $pdo->prepare("INSERT INTO `$table` ($colList) VALUES ($placeholders)");
            $stmt->execute($useVals);
            $inserted++;
        }
        $report[$table] = "OK: inserted $inserted rows";
    }
    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    fwrite(STDERR, "ERROR during import: " . $e->getMessage() . "\n");
    exit(1);
}

$pdo->exec('PRAGMA foreign_keys = ON;');

echo "\n--- Import report ---\n";
foreach ($report as $t => $status) {
    echo "$t: $status\n";
}

// Freshness check: surface the most recent activity timestamps so whoever
// runs the cutover can visually confirm this backup is not stale before
// declaring the switch to the local system complete.
echo "\n--- Data freshness check ---\n";
$freshnessQueries = [
    'reservations' => "SELECT MAX(created_at) FROM reservations",
    'payments'     => "SELECT MAX(created_at) FROM payments",
    'audit_logs'   => "SELECT MAX(created_at) FROM audit_logs",
];
foreach ($freshnessQueries as $label => $query) {
    try {
        $latest = $pdo->query($query)->fetchColumn();
        echo "Latest $label: " . ($latest ?: 'no rows') . "\n";
    } catch (Throwable $e) {
        // table may not exist in this schema version - not fatal
    }
}
echo "If these dates look older than expected, this backup is NOT the\n";
echo "current one - re-download it from the online system before going live.\n";

echo "\nDONE.\n";
