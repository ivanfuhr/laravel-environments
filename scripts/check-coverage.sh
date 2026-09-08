#!/usr/bin/env bash
set -euo pipefail

CLOVER="${1:-coverage/clover.xml}"

if [[ ! -f "$CLOVER" ]]; then
  echo "Missing coverage report at ${CLOVER}"
  exit 1
fi

php -r '
$xml = simplexml_load_file($argv[1]);
$statements = 0;
$covered = 0;
foreach ($xml->xpath("//file/metrics") as $metrics) {
    $statements += (int) $metrics["statements"];
    $covered += (int) $metrics["coveredstatements"];
}
if ($statements === 0) {
    fwrite(STDERR, "No statements found in coverage report.\n");
    exit(1);
}
$percent = round(($covered / $statements) * 100, 2);
echo "Coverage: {$covered}/{$statements} lines ({$percent}%)\n";
if ($covered < $statements) {
    fwrite(STDERR, "Coverage must be 100%.\n");
    exit(1);
}
' "$CLOVER"
