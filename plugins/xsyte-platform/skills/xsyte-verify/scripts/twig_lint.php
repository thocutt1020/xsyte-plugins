<?php
/**
 * xsyte custom page linter — run it BEFORE pasting anything.
 *
 *   php twig_lint.php page.html [more.html ...]
 *   php twig_lint.php --vendor=/path/to/aws_xsyte/vendor *.html
 *
 * What it is: a local stand-in for Admin's "Check page content", built on the
 * SAME Twig the platform runs (autoloads out of the aws_xsyte vendor dir, so
 * it is 3.21.1 or whatever you have upgraded to — never a different parser).
 * It mirrors Page_validator::check_twig_syntax + check_mock_render:
 *
 *   1  parse   — tokenize + parse, reporting getRawMessage() and the line,
 *                which is the exact string the admin report prints
 *   2  render  — createTemplate()->render() twice, success=false and true,
 *                with an empty ds and the page's field defaults, to catch
 *                runtime-only errors
 *   3  fields  — Page Fields ({# @field name type "Label" … #}): declarations
 *                must parse, every fields.x the template reads must be
 *                declared (error), a declaration nothing reads is a warning,
 *                richtext output without |raw is a warning — the same checks
 *                Page_validator::check_fields runs in admin
 *
 * Why it exists: a Jinja or hand-rolled harness will happily accept Twig 1
 * syntax that Twig 3 rejects — `{% for x in y if cond %}` is the one that bit
 * us, and it costs a WSOD to find out. Only the real parser can tell you.
 *
 * The form helpers are registered under the same names, is_safe => html, and
 * they emit real markup rather than stubs, so a rendered page can also be
 * screenshotted or clicked in a browser harness.
 *
 * Exit code 0 = every file clean, 1 = something to fix.
 */

$paths = [];
$vendor = null;
foreach (array_slice($argv, 1) as $arg) {
    if (strpos($arg, '--vendor=') === 0) { $vendor = substr($arg, 9); continue; }
    $paths[] = $arg;
}

foreach ([$vendor, __DIR__.'/vendor', getcwd().'/vendor',
          getenv('HOME').'/Sites/aws_xsyte/vendor'] as $cand) {
    if ($cand && is_file($cand.'/twig/twig/src/Environment.php')) { $vendor = $cand; break; }
    $vendor = null;
}
if (!$vendor) {
    fwrite(STDERR, "Cannot find Twig. Pass --vendor=/path/to/aws_xsyte/vendor\n");
    exit(2);
}

spl_autoload_register(function ($class) use ($vendor) {
    if (strpos($class, 'Twig\\') !== 0) return;
    $file = $vendor.'/twig/twig/src/'.str_replace('\\', '/', substr($class, 5)).'.php';
    if (is_file($file)) require $file;
});
foreach (glob($vendor.'/symfony/polyfill-*/bootstrap.php') as $f) require_once $f;

function _x($extra) { return $extra ? ' '.trim(is_array($extra) ? implode(' ', $extra) : $extra) : ''; }

function xsyte_twig_env() {
    $env  = new \Twig\Environment(new \Twig\Loader\ArrayLoader([]), ['autoescape' => 'html', 'cache' => false]);
    $html = ['is_safe' => ['html']];

    $env->addFunction(new \Twig\TwigFunction('form_radio', function ($n = '', $v = '', $c = false, $e = '') {
        return '<input type="radio" name="'.$n.'" value="'.$v.'"'.($c ? ' checked="checked"' : '')._x($e).' />'; }, $html));
    $env->addFunction(new \Twig\TwigFunction('form_checkbox', function ($n = '', $v = '', $c = false, $e = '') {
        return '<input type="checkbox" name="'.$n.'" value="'.$v.'"'.($c ? ' checked="checked"' : '')._x($e).' />'; }, $html));
    $env->addFunction(new \Twig\TwigFunction('form_input', function ($n = '', $v = '', $e = '') {
        return '<input type="text" name="'.$n.'" value="'.$v.'"'._x($e).' />'; }, $html));
    $env->addFunction(new \Twig\TwigFunction('form_hidden', function ($n = '', $v = '') {
        return '<input type="hidden" name="'.$n.'" value="'.$v.'" />'; }, $html));
    $env->addFunction(new \Twig\TwigFunction('form_textarea', function ($n = '', $v = '', $e = '') {
        return '<textarea name="'.$n.'"'._x($e).'>'.$v.'</textarea>'; }, $html));
    $env->addFunction(new \Twig\TwigFunction('form_dropdown', function ($n = '', $o = [], $s = '', $e = '') {
        $out = ''; foreach ((array) $o as $k => $v) $out .= '<option value="'.$k.'">'.$v.'</option>';
        return '<select name="'.$n.'"'._x($e).'>'.$out.'</select>'; }, $html));
    $env->addFunction(new \Twig\TwigFunction('form_submit', function ($n = '', $v = 'Submit', $e = '') {
        return '<input type="submit" name="'.$n.'" value="'.$v.'"'._x($e).' />'; }, $html));
    $env->addFunction(new \Twig\TwigFunction('form_open', function ($a = '', $at = '', $h = []) {
        return '<form action="'.$a.'" method="post"'._x(is_array($at) ? '' : $at).'>'; }, $html));
    $env->addFunction(new \Twig\TwigFunction('form_close', function ($e = '') { return '</form>'.$e; }, $html));
    foreach (['form_error', 'validation_errors', 'set_value', 'form_label',
              'form_fieldset', 'form_fieldset_close', 'form_password', 'form_button'] as $fn)
        $env->addFunction(new \Twig\TwigFunction($fn, function () { return ''; }, $html));

    $env->addFunction(new \Twig\TwigFunction('anchor', function ($u = '', $t = '') {
        return '<a href="'.$u.'">'.$t.'</a>'; }, $html));
    foreach (['base_url', 'site_url', 'get_graphic', 'get_graphic_path'] as $fn)
        $env->addFunction(new \Twig\TwigFunction($fn, function ($p = '') { return '/'.ltrim((string) $p, '/'); }));
    $env->addFunction(new \Twig\TwigFunction('get_champions',   function () { return []; }));
    $env->addFunction(new \Twig\TwigFunction('is_admin',        function ($c = null) { return false; }));
    $env->addFunction(new \Twig\TwigFunction('money',           function ($a, $n = false, $d = true) { return '$'.number_format((float) $a, 2); }));
    $env->addFunction(new \Twig\TwigFunction('seconds_to_time', function ($s) { return gmdate('i:s', (int) $s); }));
    $env->addFunction(new \Twig\TwigFunction('friendly_time',   function () { return 'soon'; }));
    return $env;
}

/* ---- Page Fields ----------------------------------------------------------
   Mirrors application/libraries/xsyte/Page_fields.php on the platform. Keep
   the grammar identical: one Twig comment per field,
     {# @field name type "Label" key="value" … #}
   Returns [schema, errors, defaults]. */
function xsyte_page_fields($content) {
    $types   = ['text','textarea','richtext','number','date','select','toggle','image','url'];
    $options = ['default','help','group','placeholder','required','options','rows','min','max','step','order'];
    $decl_re = '/\{#\s*@field\s+([A-Za-z_][A-Za-z0-9_]*)\s+([A-Za-z]+)\s+"((?:[^"\\\\]|\\\\.)*)"((?:\s+[a-z_]+="(?:[^"\\\\]|\\\\.)*")*)\s*#\}/s';
    $opt_re  = '/([a-z_]+)="((?:[^"\\\\]|\\\\.)*)"/s';
    $loose_re = '/\{#\s*@field\b.*?#\}/s';
    $unesc = function ($v) { return html_entity_decode(str_replace(['\\"', '\\\\'], ['"', '\\'], $v), ENT_QUOTES | ENT_HTML5, 'UTF-8'); };
    $line_at = function ($off) use ($content) { return substr_count(substr($content, 0, $off), "\n") + 1; };

    $schema = []; $errors = []; $seen = []; $matched = [];
    if (preg_match_all($decl_re, $content, $all, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
        foreach ($all as $m) {
            $line = $line_at($m[0][1]); $matched[] = $m[0][0];
            $name = $m[1][0]; $type = strtolower($m[2][0]); $label = $unesc($m[3][0]); $opts = [];
            if (preg_match_all($opt_re, $m[4][0], $om, PREG_SET_ORDER)) {
                foreach ($om as $o) {
                    if (!in_array($o[1], $options, true)) { $errors[] = [$line, "field \"$name\": unknown option \"{$o[1]}\" (ignored)"]; continue; }
                    $opts[$o[1]] = $unesc($o[2]);
                }
            }
            if (!preg_match('/^[a-z][a-z0-9_]*$/', $name)) { $errors[] = [$line, "field \"$name\": names are lower-case letters, digits and underscores, starting with a letter"]; continue; }
            if (!in_array($type, $types, true)) { $errors[] = [$line, "field \"$name\": unknown type \"$type\" — types are ".implode(', ', $types)]; continue; }
            if (isset($seen[$name])) { $errors[] = [$line, "field \"$name\" is declared twice (first on line {$seen[$name]})"]; continue; }
            if ($label === '') { $errors[] = [$line, "field \"$name\": label is empty"]; }
            $seen[$name] = $line;
            $f = ['name' => $name, 'type' => $type, 'label' => $label, 'default' => $opts['default'] ?? '', 'line' => $line, 'options' => []];
            if ($type === 'select') {
                $f['options'] = array_values(array_filter(array_map('trim', explode('|', $opts['options'] ?? '')), 'strlen'));
                if (!$f['options']) { $errors[] = [$line, "field \"$name\": a select needs options=\"A|B|C\""]; }
                elseif ($f['default'] !== '' && !in_array($f['default'], $f['options'], true)) { $errors[] = [$line, "field \"$name\": default \"{$f['default']}\" is not one of its options"]; }
            }
            if ($type === 'number' && $f['default'] !== '' && !is_numeric($f['default'])) { $errors[] = [$line, "field \"$name\": default \"{$f['default']}\" is not a number"]; $f['default'] = ''; }
            $schema[] = $f;
        }
    }
    if (preg_match_all($loose_re, $content, $loose, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
        foreach ($loose as $l) {
            if (!in_array($l[0][0], $matched, true)) {
                $errors[] = [$line_at($l[0][1]), 'could not read this field declaration: '.preg_replace('/\s+/', ' ', substr($l[0][0], 0, 90)).' — expected {# @field name type "Label" key="value" #}'];
            }
        }
    }
    $defaults = [];
    foreach ($schema as $f) {
        $d = $f['default'];
        if ($f['type'] === 'toggle')      $d = in_array(strtolower((string) $d), ['1','true','yes','on'], true);
        elseif ($f['type'] === 'number')  $d = is_numeric($d) ? $d + 0 : '';
        $defaults[$f['name']] = $d;
    }
    return [$schema, $errors, $defaults];
}

/* Library mode: when required by another script (render_page.php), stop here
   and expose xsyte_twig_env() / xsyte_page_fields() without running the CLI. */
if (defined('XSYTE_TWIG_LIB')) { return; }

if (!$paths) { fwrite(STDERR, "usage: php twig_lint.php [--vendor=PATH] file.html ...\n"); exit(2); }

$failed = 0;
foreach ($paths as $file) {
    $name = basename($file);
    if (!is_file($file)) { printf("FAIL %-40s file not found\n", $name); $failed++; continue; }
    $content = file_get_contents($file);

    /* Twig tags written as documentation inside an HTML comment. Neither the
       editor nor the Twig lexer treats an HTML comment as inert, so an example
       tag in a header block really opens a block — and the parser then reports
       "Unexpected end of template" at the LAST line of the file, which sends
       you looking in entirely the wrong place. Catch it first and point at the
       actual line. */
    if (preg_match_all('/<!--.*?-->/s', $content, $comments, PREG_OFFSET_CAPTURE)) {
        $bad = false;
        foreach ($comments[0] as $c) {
            if (preg_match_all('/\{\{.*?\}\}|\{%.*?%\}/s', $c[0], $tags, PREG_OFFSET_CAPTURE)) {
                foreach ($tags[0] as $t) {
                    $line = substr_count(substr($content, 0, $c[1] + $t[1]), "\n") + 1;
                    printf("FAIL %-40s line %-5s COMMENT  Twig tag inside an HTML comment: %s\n",
                           $name, $line, trim(preg_replace('/\s+/', ' ', $t[0])));
                    $bad = true;
                }
            }
        }
        if ($bad) {
            printf("     %-40s        Describe the delimiters in words, or move the example into a Twig comment (hash-brace), which the lexer does skip.\n", '');
            $failed++; continue;
        }
    }

    /* Page Fields: declarations, references, defaults for the mock render. */
    [$schema, $field_errors, $field_defaults] = xsyte_page_fields($content);
    $bad = false; $warned = 0;
    foreach ($field_errors as [$line, $msg]) {
        printf("FAIL %-40s line %-5s FIELD   %s\n", $name, $line, $msg); $bad = true;
    }
    $declared = array_column($schema, 'name');
    preg_match_all('/\bfields\.([A-Za-z_][A-Za-z0-9_]*)/', $content, $refs);
    $referenced = array_unique($refs[1]);
    foreach (array_diff($referenced, $declared) as $undeclared) {
        preg_match('/\bfields\.'.preg_quote($undeclared, '/').'\b/', $content, $mm, PREG_OFFSET_CAPTURE);
        $line = substr_count(substr($content, 0, $mm[0][1]), "\n") + 1;
        printf("FAIL %-40s line %-5s FIELD   template reads fields.%s but no field \"%s\" is declared\n", $name, $line, $undeclared, $undeclared);
        $bad = true;
    }
    foreach ($schema as $f) {
        if (!in_array($f['name'], $referenced, true)) {
            printf("warn %-40s line %-5s FIELD   \"%s\" is declared but the template never reads fields.%s\n", $name, $f['line'], $f['name'], $f['name']); $warned++;
        }
        if ($f['type'] === 'richtext' && preg_match('/\{\{\s*(?:fields|f)\.'.preg_quote($f['name'], '/').'\s*\}\}/', $content, $mm, PREG_OFFSET_CAPTURE)) {
            $line = substr_count(substr($content, 0, $mm[0][1]), "\n") + 1;
            printf("warn %-40s line %-5s FIELD   richtext \"%s\" is output without |raw — its formatting will show as literal tags\n", $name, $line, $f['name']); $warned++;
        }
    }
    if ($bad) { $failed++; continue; }

    $env = xsyte_twig_env();
    try {
        $env->parse($env->tokenize(new \Twig\Source($content, $name)));
    } catch (\Twig\Error\SyntaxError $e) {
        printf("FAIL %-40s line %-5s SYNTAX  %s\n", $name, $e->getTemplateLine(), $e->getRawMessage());
        $failed++; continue;
    }

    foreach ([false, true] as $success) {
        try {
            xsyte_twig_env()->createTemplate($content)->render([
                'success' => $success, 'ds' => [], 'fields' => $field_defaults, 'f' => $field_defaults,
            ]);
        } catch (\Throwable $e) {
            $line = ($e instanceof \Twig\Error\Error) ? $e->getTemplateLine() : '?';
            printf("FAIL %-40s line %-5s RENDER(success=%s)  %s\n",
                   $name, $line, var_export($success, true), $e->getMessage());
            $bad = true; break;
        }
    }
    if ($bad) { $failed++; continue; }
    $summary = $schema ? sprintf('%d field%s', count($schema), count($schema) == 1 ? '' : 's') : 'no fields';
    printf("ok   %-40s parses, renders both branches, %s%s\n", $name, $summary, $warned ? " ($warned warning".($warned == 1 ? '' : 's').')' : '');
}
exit($failed ? 1 : 0);
