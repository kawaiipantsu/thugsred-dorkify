<?php
// THUGSred Dorkify - sanity checks for src/lib/dorks.php. Exit code 1 on errors.
//   php scripts/lint-dorks.php
//
// Checks every dork for: balanced brackets and quotes, Google's 32-word limit,
// known placeholders, a valid severity, unique titles, and the OR-precedence
// trap - a multi-term alternative inside an OR group, e.g. (a OR b c), which
// Google reads as (a OR b) c.
require __DIR__ . '/../src/lib/dorks.php';

$errors = $warnings = 0;
$titles = [];
$err  = function (string $where, string $msg) use (&$errors) { $errors++; fwrite(STDERR, "ERROR  $where: $msg\n"); };
$warn = function (string $where, string $msg) use (&$warnings) { $warnings++; fwrite(STDERR, "WARN   $where: $msg\n"); };

foreach (dork_library() as $cat) {
    foreach (['id', 'short', 'icon', 'title', 'lead', 'dorks'] as $k) {
        if (!isset($cat[$k])) $err($cat['id'] ?? '?', "category is missing '$k'");
    }
    foreach ($cat['dorks'] as $d) {
        $where = "{$cat['id']} / " . ($d['t'] ?? '?');
        $q = $d['q'] ?? '';
        if ($q === '') { $err($where, 'empty query'); continue; }
        if (!in_array($d['s'] ?? '', ['info', 'low', 'med', 'high', 'crit'], true)) $err($where, "bad severity '" . ($d['s'] ?? '') . "'");
        if (isset($titles[$d['t']])) $err($where, 'duplicate title');
        $titles[$d['t']] = true;

        // Strip quoted phrases before structural checks, so brackets inside
        // "Traceback (most recent call last)" don't count.
        if (substr_count($q, '"') % 2) $err($where, 'unbalanced quotes');
        $bare = preg_replace('/"[^"]*"/', '""', $q);
        if (substr_count($bare, '(') !== substr_count($bare, ')')) $err($where, 'unbalanced brackets');

        $words = preg_match_all('/"[^"]*"|\S+/', $q);
        if ($words > 32) $err($where, "$words words - Google ignores everything after 32");

        preg_match_all('/\{(\w+)\}/', $q, $m);
        foreach ($m[1] as $ph) if (!in_array($ph, ['site', 'd', 'org', 'name', 'user'], true)) $err($where, "unknown placeholder {{$ph}}");

        if (preg_match_all('/\(([^()]*)\)/', $bare, $groups)) {
            foreach ($groups[1] as $g) {
                if (!preg_match('/\bOR\b/', $g)) continue;
                foreach (preg_split('/\s+OR\s+/', trim($g)) as $alt) {
                    if (preg_match_all('/\S+/', trim($alt)) > 1) $warn($where, "OR alternative with several terms: [" . trim($alt) . "]");
                }
            }
        }
    }
}

printf("%d dorks in %d categories - %d error(s), %d warning(s)\n", dork_count(), count(dork_library()), $errors, $warnings);
exit($errors ? 1 : 0);
