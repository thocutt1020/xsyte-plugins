<?php
/**
 * Render an xsyte custom page with a data context, using the platform's own
 * Twig build and real form-helper output.
 *
 *   php render_page.php page.html ds.json [success] > rendered.html
 *   php render_page.php page.html ds.json --fields=values.json
 *   php render_page.php page.html ds.json --vendor=/path/to/aws_xsyte/vendor
 *
 * ds.json is the data-source payload and reaches the template as `ds`:
 *
 *   { "events": [ { "name": "Karaoke", "time_label": "6pm-9pm", ... } ] }
 *
 * Page Fields ({# @field … #}) render with their declared defaults, exactly as
 * a freshly pasted page does. --fields=values.json overrides any of them
 * ({ "motm_name": "Test Person", "motm_photo": "https://…" }) to see the page
 * as a club would have filled it in.
 *
 * Pass the literal word `success` as an argument to render the post-submit
 * branch. Use this instead of editing {% if success %} to {% if true %} — the
 * validator flags leftover previews and it is easy to forget to swap back.
 *
 * The helpers emit real markup, not stubs, so the output can be loaded in a
 * browser and clicked. A CSS-only filter built on form_radio() needs that.
 */

$args   = array_slice($argv, 1);
$page   = null;
$dsFile = null;
$success = false;
$fieldsFile = null;
foreach ($args as $a) {
    if (strpos($a, '--vendor=') === 0)   { $GLOBALS['argv'][] = $a; continue; }
    if (strpos($a, '--fields=') === 0)   { $fieldsFile = substr($a, 9); continue; }
    if ($a === 'success' || $a === '--success') { $success = true; continue; }
    if ($page === null)   { $page = $a;   continue; }
    if ($dsFile === null) { $dsFile = $a; continue; }
}
if (!$page) {
    fwrite(STDERR, "usage: php render_page.php page.html [ds.json] [success] [--vendor=PATH]\n");
    exit(2);
}

define('XSYTE_TWIG_LIB', 1);
require __DIR__ . '/twig_lint.php';

$ds = [];
if ($dsFile && is_file($dsFile)) {
    $ds = json_decode(file_get_contents($dsFile), true);
    if (!is_array($ds)) {
        fwrite(STDERR, "Could not parse $dsFile as JSON\n");
        exit(2);
    }
}

$content = file_get_contents($page);
[, , $fields] = xsyte_page_fields($content);
if ($fieldsFile) {
    $over = is_file($fieldsFile) ? json_decode(file_get_contents($fieldsFile), true) : null;
    if (!is_array($over)) { fwrite(STDERR, "Could not parse $fieldsFile as JSON\n"); exit(2); }
    $fields = array_merge($fields, $over);
}

try {
    echo xsyte_twig_env()
        ->createTemplate($content)
        ->render(['success' => $success, 'ds' => $ds, 'fields' => $fields, 'f' => $fields]);
} catch (\Throwable $e) {
    $line = ($e instanceof \Twig\Error\Error) ? $e->getTemplateLine() : '?';
    fwrite(STDERR, "Render failed at line $line: " . $e->getMessage() . "\n");
    exit(1);
}
