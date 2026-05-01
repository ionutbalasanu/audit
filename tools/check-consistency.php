<?php
declare(strict_types=1);

require __DIR__ . '/../app/src/bootstrap.php';
require __DIR__ . '/../app/src/Advice.php';
require __DIR__ . '/../app/src/ArticleScorer.php';

$definitions = Advice::definitions();
$ids = ArticleScorer::allCheckIds();
$missing = [];
$incomplete = [];

foreach ($ids as $id) {
    if (!isset($definitions[$id])) {
        $missing[] = $id;
        continue;
    }

    foreach (['label', 'rule', 'tip', 'business_impact_text', 'business_impact_magnitude', 'related_service', 'fix_complexity'] as $field) {
        if (!isset($definitions[$id][$field]) || $definitions[$id][$field] === '') {
            $incomplete[] = $id . ':' . $field;
        }
    }
}

$extra = array_values(array_diff(array_keys($definitions), $ids));

if ($missing === [] && $incomplete === [] && $extra === []) {
    fwrite(STDOUT, "Consistency check passed.\n");
    exit(0);
}

if ($missing !== []) {
    fwrite(STDERR, "Missing definitions:\n - " . implode("\n - ", $missing) . "\n");
}
if ($incomplete !== []) {
    fwrite(STDERR, "Incomplete definitions:\n - " . implode("\n - ", $incomplete) . "\n");
}
if ($extra !== []) {
    fwrite(STDERR, "Extra definitions:\n - " . implode("\n - ", $extra) . "\n");
}

exit(1);
