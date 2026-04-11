<?php
$dir = new RecursiveDirectoryIterator('.');
$iterator = new RecursiveIteratorIterator($dir);
$files = new RegexIterator($iterator, '/^.+\.php$/i', RecursiveRegexIterator::GET_MATCH);

$count = 0;
foreach($files as $file) {
    if (strpos($file[0], 'vendor/') !== false) continue;
    $content = file_get_contents($file[0]);
    
    $pattern = '/<img src="\.\.\/PGMN%20LOGOS%20white\.png" alt="PGMN Inc\. Logo" class="h-10 w-auto object-contain dark:invert-0 invert" \/>\s*<div class="flex flex-col">/s';
    
    if (preg_match($pattern, $content)) {
        
        $replacement = '<img src="../PGMN%20LOGOS%20white.png" alt="PGMN Inc. Logo" class="h-12 w-auto object-contain dark:invert-0 invert" />' . "\n" .
                       '                <div class="flex flex-col justify-center">' . "\n" .
                       '                    <h1 class="text-slate-900 dark:text-white text-base font-bold leading-tight">PGMN Inc.</h1>';
        
        $newContent = preg_replace($pattern, $replacement, $content);
        file_put_contents($file[0], $newContent);
        echo "Updated text in: " . $file[0] . "\n";
        $count++;
    }
}
echo "Total files updated: $count\n";
?>
