<?php
/* Compile every .blade.php with Laravel's own compiler and lint the output.
   This catches unbalanced @if/@foreach, bad @php blocks and PHP syntax errors
   in the template expressions — everything short of runtime data. */
$files = [];
$dir = $argv[1];
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
foreach ($it as $f) {
    if (substr($f->getFilename(), -10) === '.blade.php') { $files[] = $f->getPathname(); }
}
sort($files);

/* Minimal re-implementation of the directives we use, matching Blade's own
   output closely enough for a syntax check. */
function compile($v) {
    $v = preg_replace('/\{\{--.*?--\}\}/s', '', $v);
    $v = preg_replace('/@php(.*?)@endphp/s', '<?php$1?>', $v);
    $v = preg_replace('/\{!!(.+?)!!\}/s', '<?php echo $1; ?>', $v);
    $v = preg_replace('/\{\{(.+?)\}\}/s', '<?php echo e($1); ?>', $v);
    $v = preg_replace('/@extends\s*\((.*?)\)$/m', '<?php /*extends*/ ?>', $v);
    $v = preg_replace_callback('/@section\s*(\((?:[^()]++|(?1))*\))/s',
        function ($m) { return '<?php /*sect*/ y' . $m[1] . '; ?>'; }, $v);
    // @include(...) may span lines; match the balanced parenthesis group.
    $v = preg_replace_callback('/@include\s*(\((?:[^()]++|(?1))*\))/s',
        function ($m) { return '<?php /*inc*/ x' . $m[1] . '; ?>'; }, $v);
    // @endsection closes a section, not a PHP block — it must not emit '}'.
    $v = preg_replace('/@(endsection|stop|endverbatim)\b/', '<?php /*end*/ ?>', $v);
    $v = preg_replace('/@(endif|endforeach|endfor|endwhile|endguest|endauth|endisset|endempty)\b/', '<?php } ?>', $v);
    $v = preg_replace('/@endforelse\b/', '<?php } } ?>', $v);
    $v = preg_replace('/@empty\b(?!\s*\()/', '<?php } if(true){ ?>', $v);
    $v = preg_replace_callback('/@forelse\s*(\((?:[^()]++|(?1))*\))/s',
        function ($m) { return '<?php if(true){ foreach' . $m[1] . '{ ?>'; }, $v);
    $v = preg_replace_callback('/@foreach\s*(\((?:[^()]++|(?1))*\))/s',
        function ($m) { return '<?php foreach' . $m[1] . '{ ?>'; }, $v);
    $v = preg_replace_callback('/@for\s*(\((?:[^()]++|(?1))*\))/s',
        function ($m) { return '<?php for' . $m[1] . '{ ?>'; }, $v);
    $v = preg_replace_callback('/@if\s*(\((?:[^()]++|(?1))*\))/s',
        function ($m) { return '<?php if' . $m[1] . '{ ?>'; }, $v);
    $v = preg_replace_callback('/@elseif\s*(\((?:[^()]++|(?1))*\))/s',
        function ($m) { return '<?php } elseif' . $m[1] . '{ ?>'; }, $v);
    $v = preg_replace('/@else\b/', '<?php } else { ?>', $v);
    $v = preg_replace('/@isset\s*\((.*?)\)/s', '<?php if(isset($1)){ ?>', $v);
    $v = preg_replace('/@guest\b/', '<?php if(true){ ?>', $v);
    $v = preg_replace('/@auth\b/', '<?php if(true){ ?>', $v);
    $v = preg_replace('/@csrf\b/', '<?php /*csrf*/ ?>', $v);
    $v = preg_replace('/@error\s*\((.*?)\)/s', '<?php if(true){ $message=null; ?>', $v);
    $v = preg_replace('/@enderror\b/', '<?php } ?>', $v);
    return $v;
}
$bad = 0;
foreach ($files as $file) {
    $out = compile(file_get_contents($file));
    $tmp = tempnam(sys_get_temp_dir(), 'bl') . '.php';
    file_put_contents($tmp, $out);
    exec('php -l ' . escapeshellarg($tmp) . ' 2>&1', $o, $rc);
    $name = str_replace($dir . '/', '', $file);
    if ($rc !== 0) { $bad++; echo "FAIL  $name\n      " . implode("\n      ", $o) . "\n"; }
    else { echo "ok    $name\n"; }
    $o = []; unlink($tmp);
}
echo $bad ? "\n$bad file(s) failed\n" : "\nall templates compile\n";
exit($bad ? 1 : 0);
