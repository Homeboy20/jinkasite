<?php
$content = file_get_contents('admin/alibaba_import_api.php');
$lines = explode("\n", $content);

$braceLevel = 0;
$maxBraceLevel = 0;
$problemLines = [];

foreach ($lines as $lineNum => $line) {
    $openBraces = substr_count($line, '{');
    $closeBraces = substr_count($line, '}');
    
    $braceLevel += $openBraces - $closeBraces;
    
    if ($braceLevel > $maxBraceLevel) {
        $maxBraceLevel = $braceLevel;
    }
    
    if ($braceLevel < 0) {
        $problemLines[] = [
            'line' => $lineNum + 1,
            'content' => trim($line),
            'level' => $braceLevel
        ];
    }
}

echo "Max brace depth: $maxBraceLevel\n";
echo "Final brace level: $braceLevel\n\n";

if (!empty($problemLines)) {
    echo "Problem lines (negative brace level):\n";
    foreach ($problemLines as $problem) {
        echo "Line {$problem['line']}: {$problem['content']} (level: {$problem['level']})\n";
    }
} else {
    echo "No negative brace levels found.\n";
}

if ($braceLevel < 0) {
    echo "\nThere are " . abs($braceLevel) . " extra closing braces.\n";
} elseif ($braceLevel > 0) {
    echo "\nThere are $braceLevel unclosed opening braces.\n";
} else {
    echo "\nBraces are balanced!\n";
}
?>