<?php
$dir = new RecursiveDirectoryIterator('.');
$iterator = new RecursiveIteratorIterator($dir);
$files = new RegexIterator($iterator, '/^.+\.php$/i', RecursiveRegexIterator::GET_MATCH);

$count = 0;
foreach($files as $file) {
    if (strpos($file[0], 'vendor/') !== false) continue;
    $content = file_get_contents($file[0]);
    
    // Pattern to look for the old logo block
    $pattern = '/<div class="bg-primary rounded-lg p-2 flex items-center justify-center">\s*<span class="material-symbols-outlined text-white">shield<\/span>\s*<\/div>\s*<div class="flex flex-col">\s*<h1 class="text-slate-900 dark:text-white text-base font-bold leading-tight">PGMN Inc\.<\/h1>/s';
    
    if (preg_match($pattern, $content)) {
        // Calculate relative path to the root from this file
        $depth = substr_count($file[0], '/') - 1; // '.' is 0
        $relPath = $depth > 0 ? str_repeat('../', $depth) : './';
        if ($file[0] == './index.php') $relPath = './';
        
        $imgTag = '<img src="' . $relPath . 'PGMN%20LOGOS%20white.png" alt="PGMN Inc." class="h-10 w-auto object-contain dark:invert-0 invert opacity-90" />';
        
        $replacement = $imgTag . "\n                <div class=\"flex flex-col\">";
        
        $newContent = preg_replace($pattern, $replacement, $content);
        file_put_contents($file[0], $newContent);
        echo "Updated logo in: " . $file[0] . "\n";
        $count++;
    }
}
echo "Total files updated: $count\n";
?>
