<?php
/**
 * Auto Data Seeder
 * - Imports data from an SQL file when the database is largely empty
 * - Prefers any .sql inside the `database` directory; falls back to the root dump file if present
 */

if (!function_exists('seedDatabaseIfEmpty')) {
    /**
     * Seed database if key tables are empty or below thresholds.
     * Returns an associative array with 'seeded' boolean and 'message'.
     */
    function seedDatabaseIfEmpty(mysqli $conn): array {
        try {
            // Check key tables
            $counts = [
                'pharmaceuticals' => getTableCount($conn, 'pharmaceuticals'),
                'clients' => getTableCount($conn, 'clients'),
                'transactions' => getTableCount($conn, 'transactions'),
                'livestock_poultry' => getTableCount($conn, 'livestock_poultry')
            ];

            $isMostlyEmpty = (
                ($counts['pharmaceuticals'] ?? 0) === 0 ||
                ($counts['clients'] ?? 0) === 0 ||
                ($counts['transactions'] ?? 0) < 12 ||
                ($counts['livestock_poultry'] ?? 0) < 12
            );

            if (!$isMostlyEmpty) {
                return ['seeded' => false, 'message' => 'Database already has sufficient data'];
            }

            // Locate SQL file: prefer /database/*.sql then fallback to known dump
            $sqlFile = findSqlFile();
            if (!$sqlFile || !file_exists($sqlFile)) {
                return ['seeded' => false, 'message' => 'No SQL file found for seeding'];
            }

            $sql = file_get_contents($sqlFile);
            if ($sql === false || trim($sql) === '') {
                return ['seeded' => false, 'message' => 'SQL file is empty'];
            }

            // Execute SQL safely (multi_query). Disable FK checks during import
            $conn->query('SET foreign_key_checks = 0');
            $ok = $conn->multi_query($sql);
            if (!$ok) {
                // Some dumps require splitting by delimiter; try a simple split fallback
                $conn->rollback();
                $statements = array_filter(array_map('trim', preg_split('/;\s*\n/', $sql)));
                foreach ($statements as $stmt) {
                    if ($stmt === '') { continue; }
                    if (!$conn->query($stmt)) {
                        // Continue best-effort; collect errors but don't halt entirely
                    }
                }
            } else {
                // drain remaining results for multi_query
                while ($conn->more_results() && $conn->next_result()) { /* drain */ }
            }
            $conn->query('SET foreign_key_checks = 1');

            return ['seeded' => true, 'message' => 'Database seeded from SQL: ' . basename($sqlFile)];
        } catch (Throwable $e) {
            return ['seeded' => false, 'message' => 'Seeding failed: ' . $e->getMessage()];
        }
    }

    function getTableCount(mysqli $conn, string $table): int {
        $result = $conn->query("SELECT COUNT(*) AS c FROM `{$table}`");
        if ($result && ($row = $result->fetch_assoc())) {
            return (int)$row['c'];
        }
        return 0;
    }

    function findSqlFile(): ?string {
        $candidates = [];
        $baseDir = dirname(__DIR__);
        // Prefer any .sql inside /database
        $dbDir = $baseDir . DIRECTORY_SEPARATOR . 'database';
        if (is_dir($dbDir)) {
            $files = glob($dbDir . DIRECTORY_SEPARATOR . '*.sql');
            if (!empty($files)) { $candidates = array_merge($candidates, $files); }
        }
        // Fallback to known dump file in project root
        $rootDump = $baseDir . DIRECTORY_SEPARATOR . 'u520834156_dbBagoVetIMS.sql';
        if (file_exists($rootDump)) { $candidates[] = $rootDump; }

        return $candidates[0] ?? null;
    }
}
?>


