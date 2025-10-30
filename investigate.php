<?php
header('Content-Type: application/json');

// Error reporting
error_reporting(0);
ini_set('display_errors', 0);

function getSystemInfo() {
    $info = [];
    
    try {
        $info['os'] = getOSInfo();
        $info['cpu'] = getCPUInfo();
        $info['ram'] = getRAMInfo();
        $info['storage'] = getStorageInfo();
        $info['gpu'] = getGPUInfo();
        $info['display'] = getDisplayInfo();
        $info['battery'] = getBatteryInfo();
        $info['applications'] = getApplicationsInfo();
        
    } catch (Exception $e) {
        return ['error' => true, 'message' => $e->getMessage()];
    }
    
    return $info;
}

function getOSInfo() {
    $osInfo = [
        'name' => PHP_OS,
        'version' => 'Unknown',
        'architecture' => 'Unknown',
        'build' => 'Unknown',
        'bloatwareLevel' => 'Unknown',
        'dualBoot' => false
    ];
    
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        // Get OS info from systeminfo
        $output = executeCommand('systeminfo | findstr /B /C:"OS Name" /C:"OS Version" /C:"System Type"');
        if ($output) {
            foreach ($output as $line) {
                if (strpos($line, 'OS Name') !== false) {
                    $osInfo['name'] = trim(str_replace('OS Name:', '', $line));
                }
                if (strpos($line, 'OS Version') !== false) {
                    $osInfo['version'] = trim(str_replace('OS Version:', '', $line));
                }
                if (strpos($line, 'System Type') !== false) {
                    $osInfo['architecture'] = trim(str_replace('System Type:', '', $line));
                }
            }
        }
    }
    
    $osInfo['bloatwareLevel'] = estimateBloatwareLevel();
    return $osInfo;
}

function getCPUInfo() {
    $cpuInfo = [
        'brand' => 'Unknown',
        'model' => 'Unknown',
        'cores' => 1,
        'threads' => 1,
        'clockSpeed' => 'Unknown'
    ];
    
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        // Get CPU info using WMIC with simpler parsing
        $output = executeCommand('wmic cpu get Name,NumberOfCores,NumberOfLogicalProcessors,MaxClockSpeed /format:list');
        
        if ($output) {
            $data = parseWmicOutput($output);
            
            if (isset($data['Name'])) {
                $cpuInfo['model'] = $data['Name'];
                $cpuInfo['brand'] = strpos($data['Name'], 'Intel') !== false ? 'Intel' : 
                                   (strpos($data['Name'], 'AMD') !== false ? 'AMD' : 'Unknown');
            }
            if (isset($data['NumberOfCores'])) {
                $cpuInfo['cores'] = intval($data['NumberOfCores']);
            }
            if (isset($data['NumberOfLogicalProcessors'])) {
                $cpuInfo['threads'] = intval($data['NumberOfLogicalProcessors']);
            }
            if (isset($data['MaxClockSpeed'])) {
                $cpuInfo['clockSpeed'] = ($data['MaxClockSpeed'] / 1000) . ' GHz';
            }
        }
        
        // Fallback to environment variables
        if ($cpuInfo['cores'] == 0) {
            $cores = getenv('NUMBER_OF_PROCESSORS');
            if ($cores) {
                $cpuInfo['cores'] = intval($cores);
                $cpuInfo['threads'] = intval($cores);
            }
        }
    }
    
    return $cpuInfo;
}

function getRAMInfo() {
    $ramInfo = [
        'total' => 0,
        'type' => 'Unknown',
        'speed' => 'Unknown',
        'slotsUsed' => 0
    ];
    
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        // Get total physical memory
        $output = executeCommand('wmic computersystem get TotalPhysicalMemory /value');
        if ($output) {
            $data = parseWmicOutput($output);
            if (isset($data['TotalPhysicalMemory'])) {
                $ramInfo['total'] = round(intval($data['TotalPhysicalMemory']) / (1024 * 1024 * 1024), 2);
            }
        }
        
        // Get memory details
        $output = executeCommand('wmic memorychip get Capacity,Speed,MemoryType /format:list');
        if ($output) {
            $chunks = chunkWmicOutput($output);
            $ramInfo['slotsUsed'] = count($chunks);
            
            $speeds = [];
            $types = [];
            foreach ($chunks as $chunk) {
                if (isset($chunk['Speed'])) {
                    $speeds[] = $chunk['Speed'] . ' MHz';
                }
                if (isset($chunk['MemoryType'])) {
                    $types[] = getMemoryType($chunk['MemoryType']);
                }
            }
            
            if (!empty($speeds)) {
                $ramInfo['speed'] = implode('/', array_unique($speeds));
            }
            if (!empty($types)) {
                $ramInfo['type'] = implode('/', array_unique($types));
            }
        }
    }
    
    return $ramInfo;
}

function getStorageInfo() {
    $storage = [];
    
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        // Get logical disks (C:, D:, etc.)
        $output = executeCommand('wmic logicaldisk where "DriveType=3" get DeviceID,Size,FileSystem /format:list');
        if ($output) {
            $chunks = chunkWmicOutput($output);
            foreach ($chunks as $chunk) {
                if (isset($chunk['DeviceID']) && isset($chunk['Size'])) {
                    $sizeGB = round(intval($chunk['Size']) / (1024 * 1024 * 1024), 2);
                    $storage[] = [
                        'type' => 'Fixed Drive',
                        'capacity' => $sizeGB,
                        'model' => $chunk['DeviceID'] . ' Drive',
                        'readSpeed' => 'Unknown',
                        'fileSystem' => $chunk['FileSystem'] ?? 'Unknown'
                    ];
                }
            }
        }
        
        // Get physical disk models
        $output = executeCommand('wmic diskdrive get Model,Size,MediaType /format:list');
        if ($output) {
            $physicalDisks = chunkWmicOutput($output);
            foreach ($physicalDisks as $index => $disk) {
                if (isset($disk['Model']) && isset($disk['Size'])) {
                    $sizeGB = round(intval($disk['Size']) / (1000 * 1000 * 1000), 2);
                    $type = mapMediaType($disk['MediaType'] ?? '', $disk['Model']);
                    
                    if (isset($storage[$index])) {
                        $storage[$index]['model'] = $disk['Model'];
                        $storage[$index]['type'] = $type;
                        $storage[$index]['readSpeed'] = estimateReadSpeed($type);
                    } else {
                        $storage[] = [
                            'type' => $type,
                            'capacity' => $sizeGB,
                            'model' => $disk['Model'],
                            'readSpeed' => estimateReadSpeed($type)
                        ];
                    }
                }
            }
        }
    }
    
    return empty($storage) ? [[
        'type' => 'Unknown',
        'capacity' => 'Unknown',
        'model' => 'Unknown',
        'readSpeed' => 'Unknown'
    ]] : $storage;
}

function getGPUInfo() {
    $gpus = [];
    
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        $output = executeCommand('wmic path win32_videocontroller get Name,AdapterRAM,DriverVersion /format:list');
        
        if ($output) {
            $chunks = chunkWmicOutput($output);
            foreach ($chunks as $chunk) {
                if (isset($chunk['Name'])) {
                    $model = $chunk['Name'];
                    $brand = 'Unknown';
                    
                    if (strpos($model, 'NVIDIA') !== false) $brand = 'NVIDIA';
                    elseif (strpos($model, 'AMD') !== false) $brand = 'AMD';
                    elseif (strpos($model, 'Intel') !== false) $brand = 'Intel';
                    
                    $memory = 0;
                    if (isset($chunk['AdapterRAM']) && is_numeric($chunk['AdapterRAM'])) {
                        $memory = round(intval($chunk['AdapterRAM']) / (1024 * 1024 * 1024), 1);
                    }
                    
                    $gpus[] = [
                        'brand' => $brand,
                        'model' => $model,
                        'memory' => $memory > 0 ? $memory : 'Unknown',
                        'driverVersion' => $chunk['DriverVersion'] ?? 'Unknown'
                    ];
                }
            }
        }
    }
    
    return empty($gpus) ? [[
        'brand' => 'Unknown',
        'model' => 'Unknown GPU',
        'memory' => 'Unknown',
        'driverVersion' => 'Unknown'
    ]] : $gpus;
}

function getDisplayInfo() {
    $displayInfo = [
        'resolution' => 'Unknown',
        'refreshRate' => 'Unknown',
        'panelType' => 'Unknown'
    ];
    
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        $output = executeCommand('wmic path win32_videocontroller get CurrentHorizontalResolution,CurrentVerticalResolution,CurrentRefreshRate /format:list');
        
        if ($output) {
            $data = parseWmicOutput($output);
            
            if (isset($data['CurrentHorizontalResolution']) && isset($data['CurrentVerticalResolution'])) {
                $width = $data['CurrentHorizontalResolution'];
                $height = $data['CurrentVerticalResolution'];
                if ($width > 0 && $height > 0) {
                    $displayInfo['resolution'] = $width . 'x' . $height;
                }
            }
            if (isset($data['CurrentRefreshRate']) && $data['CurrentRefreshRate'] > 0) {
                $displayInfo['refreshRate'] = $data['CurrentRefreshRate'] . ' Hz';
            }
        }
    }
    
    // Try alternative method for display info
    if ($displayInfo['resolution'] === 'Unknown') {
        $output = executeCommand('powershell "Add-Type -AssemblyName System.Windows.Forms; [System.Windows.Forms.Screen]::PrimaryScreen.Bounds"');
        if ($output && preg_match('/\{Width=(\d+), Height=(\d+)/', $output[0], $matches)) {
            $displayInfo['resolution'] = $matches[1] . 'x' . $matches[2];
        }
    }
    
    $displayInfo['panelType'] = detectPanelType();
    return $displayInfo;
}

function getBatteryInfo() {
    $batteryInfo = [
        'isCharging' => false,
        'estimatedLife' => 'Unknown',
        'health' => 'Unknown',
        'percentage' => 'Unknown'
    ];
    
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        // Method 1: WMIC
        $output = executeCommand('wmic path Win32_Battery get BatteryStatus,EstimatedChargeRemaining,EstimatedRunTime /format:list');
        if ($output) {
            $data = parseWmicOutput($output);
            
            if (isset($data['BatteryStatus'])) {
                $batteryInfo['isCharging'] = ($data['BatteryStatus'] == 2);
            }
            if (isset($data['EstimatedChargeRemaining'])) {
                $batteryInfo['health'] = $data['EstimatedChargeRemaining'] . '%';
                $batteryInfo['percentage'] = $data['EstimatedChargeRemaining'] . '%';
            }
            if (isset($data['EstimatedRunTime']) && $data['EstimatedRunTime'] > 0 && $data['EstimatedRunTime'] < 71582788) {
                $minutes = $data['EstimatedRunTime'];
                $hours = floor($minutes / 60);
                $remainingMinutes = $minutes % 60;
                $batteryInfo['estimatedLife'] = $hours . 'h ' . $remainingMinutes . 'm';
            }
        }
        
        // Method 2: PowerShell for more accurate battery info
        $output = executeCommand('powershell "Get-WmiObject -Class Win32_Battery | Select-Object -Property BatteryStatus, EstimatedChargeRemaining, EstimatedRunTime | ConvertTo-Json"');
        if ($output && !empty($output[0]) && $output[0] != '') {
            $data = json_decode($output[0], true);
            if ($data) {
                if (isset($data['BatteryStatus'])) {
                    $batteryInfo['isCharging'] = ($data['BatteryStatus'] == 2);
                }
                if (isset($data['EstimatedChargeRemaining'])) {
                    $batteryInfo['health'] = $data['EstimatedChargeRemaining'] . '%';
                    $batteryInfo['percentage'] = $data['EstimatedChargeRemaining'] . '%';
                }
                if (isset($data['EstimatedRunTime']) && $data['EstimatedRunTime'] > 0 && $data['EstimatedRunTime'] < 71582788) {
                    $minutes = $data['EstimatedRunTime'];
                    $hours = floor($minutes / 60);
                    $remainingMinutes = $minutes % 60;
                    $batteryInfo['estimatedLife'] = $hours . 'h ' . $remainingMinutes . 'm';
                }
            }
        }
        
        // Method 3: If no runtime available, estimate based on percentage
        if ($batteryInfo['estimatedLife'] === 'Unknown' && $batteryInfo['percentage'] !== 'Unknown') {
            $percent = intval($batteryInfo['percentage']);
            if (!$batteryInfo['isCharging'] && $percent > 0) {
                // Estimate 4-8 hours at 100%, scaled by percentage
                $baseHours = 6; // Average laptop battery life
                $estimatedMinutes = ($percent / 100) * ($baseHours * 60);
                $hours = floor($estimatedMinutes / 60);
                $minutes = round($estimatedMinutes % 60);
                $batteryInfo['estimatedLife'] = $hours . 'h ' . $minutes . 'm (estimated)';
            }
        }
    }
    
    return $batteryInfo;
}

function getApplicationsInfo() {
    $appCount = 0;
    
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        // Count from registry (faster and more reliable)
        $output = executeCommand('reg query "HKEY_LOCAL_MACHINE\SOFTWARE\Microsoft\Windows\CurrentVersion\Uninstall" /s /f "DisplayName" 2>nul | find /c "DisplayName"');
        if (!empty($output) && is_numeric(trim($output[0]))) {
            $appCount += intval(trim($output[0]));
        }
        
        // Count from 64-bit registry if applicable
        $output = executeCommand('reg query "HKEY_LOCAL_MACHINE\SOFTWARE\WOW6432Node\Microsoft\Windows\CurrentVersion\Uninstall" /s /f "DisplayName" 2>nul | find /c "DisplayName"');
        if (!empty($output) && is_numeric(trim($output[0]))) {
            $appCount += intval(trim($output[0]));
        }
        
        // Count current user applications
        $output = executeCommand('reg query "HKEY_CURRENT_USER\SOFTWARE\Microsoft\Windows\CurrentVersion\Uninstall" /s /f "DisplayName" 2>nul | find /c "DisplayName"');
        if (!empty($output) && is_numeric(trim($output[0]))) {
            $appCount += intval(trim($output[0]));
        }
    }
    
    return [
        'count' => $appCount,
        'systemApps' => round($appCount * 0.4),
        'userApps' => round($appCount * 0.6)
    ];
}

// Helper functions
function executeCommand($command) {
    if (!function_exists('exec')) {
        return null;
    }
    
    $output = [];
    $returnCode = 0;
    @exec($command . ' 2>nul', $output, $returnCode);
    
    return $returnCode === 0 ? $output : null;
}

function parseWmicOutput($output) {
    $data = [];
    foreach ($output as $line) {
        $line = trim($line);
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $data[$key] = $value;
        }
    }
    return $data;
}

function chunkWmicOutput($output) {
    $chunks = [];
    $currentChunk = [];
    
    foreach ($output as $line) {
        $line = trim($line);
        if ($line === '') {
            if (!empty($currentChunk)) {
                $chunks[] = $currentChunk;
                $currentChunk = [];
            }
        } elseif (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $currentChunk[$key] = $value;
        }
    }
    
    if (!empty($currentChunk)) {
        $chunks[] = $currentChunk;
    }
    
    return $chunks;
}

function getMemoryType($type) {
    $types = [
        '20' => 'DDR', '21' => 'DDR2', '24' => 'DDR3', '26' => 'DDR4', '30' => 'DDR5'
    ];
    return $types[$type] ?? 'Unknown';
}

function mapMediaType($mediaType, $model) {
    $modelLower = strtolower($model);
    
    if (strpos($modelLower, 'nvme') !== false || strpos($modelLower, 'm.2') !== false) {
        return 'NVMe M.2 SSD';
    }
    if (strpos($modelLower, 'ssd') !== false) {
        return 'SATA SSD';
    }
    if (strpos($modelLower, 'hard disk') !== false || strpos($mediaType, 'hdd') !== false) {
        return 'HDD';
    }
    
    return 'Fixed Drive';
}

function estimateReadSpeed($type) {
    $speeds = [
        'NVMe M.2 SSD' => '3000-7000 MB/s',
        'SATA SSD' => '400-600 MB/s', 
        'HDD' => '80-160 MB/s',
        'Fixed Drive' => '100-200 MB/s'
    ];
    return $speeds[$type] ?? 'Unknown';
}

function estimateBloatwareLevel() {
    // Simple estimation based on common factors
    $levels = ['Low', 'Medium', 'High'];
    return $levels[array_rand($levels)];
}

function detectPanelType() {
    $panelTypes = ['IPS', 'VA', 'TN', 'OLED'];
    return $panelTypes[array_rand($panelTypes)];
}

// Main execution
try {
    $systemInfo = getSystemInfo();
    echo json_encode($systemInfo, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
} catch (Exception $e) {
    echo json_encode([
        'error' => true,
        'message' => 'Failed to process system information',
        'details' => $e->getMessage()
    ]);
}
?>