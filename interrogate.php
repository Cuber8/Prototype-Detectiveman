<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['exeFile'])) {
    // Analyze uploaded EXE file
    $result = analyzeExeFile($_FILES['exeFile']);
    echo json_encode($result);
} elseif (isset($_GET['scan']) && $_GET['scan'] === 'malware') {
    // Perform malware scan
    $result = performMalwareScan();
    echo json_encode($result);
} else {
    echo json_encode(['error' => 'Invalid request']);
}

function analyzeExeFile($file) {
    $fileName = $file['name'];
    $fileSize = $file['size'];
    
    // Simulate file analysis
    $analysis = [
        'fileName' => $fileName,
        'fileSize' => $fileSize,
        'status' => 'Analyzed',
        'fileType' => 'Executable',
        'architecture' => 'x64',
        'compilationDate' => date('Y-m-d H:i:s', rand(1609459200, 1672531199)),
        'digitalSignature' => rand(0, 1) ? 'Valid' : 'Invalid',
        'errors' => [],
        'warnings' => []
    ];
    
    // Simulate random errors and warnings
    $possibleErrors = [
        'Missing DLL dependencies',
        'Corrupted file header',
        'Invalid entry point',
        'Memory access violation',
        'Stack overflow detected'
    ];
    
    $possibleWarnings = [
        'Unexpected API calls',
        'Suspicious string patterns',
        'Unusual file size',
        'Potential memory leaks',
        'Deprecated functions used'
    ];
    
    if (rand(0, 1)) {
        $analysis['errors'] = array_rand($possibleErrors, rand(1, 2));
        if (!is_array($analysis['errors'])) {
            $analysis['errors'] = [$analysis['errors']];
        }
        $analysis['errors'] = array_map(function($key) use ($possibleErrors) {
            return $possibleErrors[$key];
        }, $analysis['errors']);
    }
    
    if (rand(0, 1)) {
        $analysis['warnings'] = array_rand($possibleWarnings, rand(1, 3));
        if (!is_array($analysis['warnings'])) {
            $analysis['warnings'] = [$analysis['warnings']];
        }
        $analysis['warnings'] = array_map(function($key) use ($possibleWarnings) {
            return $possibleWarnings[$key];
        }, $analysis['warnings']);
    }
    
    return $analysis;
}

function performMalwareScan() {
    $malwarePatterns = [
        'trojan' => ['Win32/Trojan', 'Backdoor:Win32', 'Trojan:Win32'],
        'virus' => ['Virus:Win32', 'Worm:Win32'],
        'ransomware' => ['Ransom:Win32', 'CryptoLocker'],
        'spyware' => ['Spyware:Win32', 'Keylogger']
    ];
    
    $malwareFound = rand(0, 10) > 7; // 30% chance of finding malware
    
    if ($malwareFound) {
        $malwareType = array_rand($malwarePatterns);
        $malwareName = $malwarePatterns[$malwareType][array_rand($malwarePatterns[$malwareType])];
        
        return [
            'malwareFound' => true,
            'malwareName' => $malwareName,
            'threatLevel' => ['Low', 'Medium', 'High', 'Critical'][rand(0, 3)],
            'location' => 'C:\\Windows\\Temp\\' . bin2hex(random_bytes(8)) . '.exe',
            'suggestedAction' => 'Quarantine and remove'
        ];
    } else {
        return [
            'malwareFound' => false,
            'scanTime' => date('Y-m-d H:i:s'),
            'filesScanned' => rand(1000, 5000),
            'threatsFound' => 0
        ];
    }
}
?>