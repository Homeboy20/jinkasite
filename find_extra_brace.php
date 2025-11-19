<?php
$content = file_get_contents('admin/alibaba_import_api.php');
$lines = explode("\n", $content);

$braceLevel = 0;
$braceHistory = [];

foreach ($lines as $lineNum => $line) {
    $openBraces = substr_count($line, '{');
    $closeBraces = substr_count($line, '}');
    
    $braceLevel += $openBraces - $closeBraces;
    
    $braceHistory[] = [
        'line' => $lineNum + 1,
        'content' => trim($line),
        'open' => $openBraces,
        'close' => $closeBraces,
        'level' => $braceLevel
    ];
    
    // If we hit negative, we found the first extra closing brace
    if ($braceLevel < 0) {
        echo "FIRST EXTRA CLOSING BRACE FOUND:\n";
        echo "Line {$braceHistory[$lineNum]['line']}: {$braceHistory[$lineNum]['content']}\n";
        echo "Brace level: {$braceHistory[$lineNum]['level']}\n\n";
        
        echo "Context (previous 5 lines):\n";
        for ($i = max(0, $lineNum - 5); $i <= $lineNum; $i++) {
            $marker = ($i == $lineNum) ? '>>> ' : '    ';
            echo $marker . "Line {$braceHistory[$i]['line']}: {$braceHistory[$i]['content']} (level: {$braceHistory[$i]['level']})\n";
        }
        break;
    }
}
?>