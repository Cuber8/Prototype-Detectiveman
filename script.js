class MrDetective {
    constructor() {
        this.debugDisplay = document.getElementById('debugDisplay');
        this.statusBar = document.getElementById('statusBar');
        this.commandInput = document.getElementById('commandInput');
        this.exeFileInput = document.getElementById('exeFile');
        
        this.initializeEventListeners();
        this.testConnection();
    }

    initializeEventListeners() {
        document.getElementById('investigateBtn').addEventListener('click', () => this.investigate());
        document.getElementById('interrogateBtn').addEventListener('click', () => this.interrogate());
        document.getElementById('refreshBtn').addEventListener('click', () => this.refresh());
        document.getElementById('selectExeBtn').addEventListener('click', () => this.exeFileInput.click());
        
        this.exeFileInput.addEventListener('change', (e) => this.handleFileSelect(e));
        
        this.commandInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                this.executeCommand(this.commandInput.value);
                this.commandInput.value = '';
            }
        });
    }

    async testConnection() {
        try {
            const response = await fetch('test.php');
            const data = await response.json();
            if (data.status === 'success') {
                this.log('PHP backend connected successfully', 'success');
                this.updateSystemInfo();
            }
        } catch (error) {
            this.log('⚠️ PHP backend not available. Running in simulation mode.', 'warning');
            this.log('To enable full features, run this application on a web server with PHP support.', 'info');
        }
    }

    log(message, type = 'info') {
        const line = document.createElement('div');
        line.className = `debug-line ${type}`;
        line.textContent = `> ${message}`;
        this.debugDisplay.appendChild(line);
        this.debugDisplay.scrollTop = this.debugDisplay.scrollHeight;
    }

    setStatus(message) {
        this.statusBar.textContent = message;
    }

    async investigate() {
        this.setStatus('INVESTIGATING SYSTEM...');
        this.log('Starting comprehensive system investigation...', 'loading');
        
        try {
            const response = await fetch('investigate.php');
            const text = await response.text();
            
            let data;
            try {
                data = JSON.parse(text);
            } catch (parseError) {
                this.log('Failed to parse server response', 'error');
                this.log('Server returned:', 'error');
                this.log(text.substring(0, 200) + '...', 'error');
                this.fallbackToSimulation();
                return;
            }
            
            if (data.error) {
                this.log(`Server error: ${data.message}`, 'error');
                this.fallbackToSimulation();
            } else {
                this.displayInvestigationResults(data);
            }
            
        } catch (error) {
            this.log(`Investigation failed: ${error.message}`, 'error');
            this.fallbackToSimulation();
        }
        
        this.setStatus('READY');
    }

    fallbackToSimulation() {
        this.log('Falling back to simulation mode...', 'warning');
        this.log('Displaying simulated system information', 'info');
        
        const simulatedData = this.getSimulatedSystemInfo();
        this.displayInvestigationResults(simulatedData);
    }

    getSimulatedSystemInfo() {
        return {
            os: {
                name: 'Windows 10 Pro',
                version: '10.0.19045',
                architecture: '64-bit',
                build: '19045',
                bloatwareLevel: 'Medium',
                dualBoot: false
            },
            cpu: {
                brand: 'Intel',
                model: 'Core i7-10700K',
                cores: 8,
                threads: 16,
                clockSpeed: '3.8 GHz'
            },
            ram: {
                total: 16,
                type: 'DDR4',
                speed: '3200 MHz',
                slotsUsed: 2
            },
            storage: [
                {
                    type: 'NVMe M.2 SSD',
                    capacity: 512,
                    model: 'Samsung SSD 970 EVO',
                    readSpeed: '3500 MB/s'
                },
                {
                    type: 'SATA SSD',
                    capacity: 1000,
                    model: 'Crucial MX500',
                    readSpeed: '560 MB/s'
                }
            ],
            gpu: [
                {
                    brand: 'NVIDIA',
                    model: 'GeForce RTX 3070',
                    memory: 8,
                    driverVersion: '456.71'
                }
            ],
            display: {
                resolution: '1920x1080',
                refreshRate: '144 Hz',
                panelType: 'IPS'
            },
            battery: {
                isCharging: false,
                estimatedLife: '5 hours',
                health: '95%'
            },
            applications: {
                count: 147,
                systemApps: 29,
                userApps: 118
            }
        };
    }

    displayInvestigationResults(data) {
        this.log('=== SYSTEM INVESTIGATION RESULTS ===', 'success');
        this.log('', 'info');
        
        // OS Information
        this.log('--- OPERATING SYSTEM ---', 'success');
        this.log(`Name: ${data.os.name}`, 'info');
        this.log(`Version: ${data.os.version}`, 'info');
        this.log(`Architecture: ${data.os.architecture}`, 'info');
        this.log(`Bloatware Level: ${data.os.bloatwareLevel}`, 'info');
        this.log(`Dual Boot: ${data.os.dualBoot ? 'Yes' : 'No'}`, 'info');
        this.log('', 'info');
        
        // CPU Information
        this.log('--- PROCESSOR (CPU) ---', 'success');
        this.log(`Brand: ${data.cpu.brand}`, 'info');
        this.log(`Model: ${data.cpu.model}`, 'info');
        this.log(`Cores: ${data.cpu.cores}`, 'info');
        this.log(`Threads: ${data.cpu.threads}`, 'info');
        this.log(`Clock Speed: ${data.cpu.clockSpeed}`, 'info');
        this.log('', 'info');
        
        // RAM Information
        this.log('--- MEMORY (RAM) ---', 'success');
        this.log(`Total: ${data.ram.total} GB`, 'info');
        this.log(`Type: ${data.ram.type}`, 'info');
        this.log(`Speed: ${data.ram.speed}`, 'info');
        this.log(`Memory Slots Used: ${data.ram.slotsUsed}`, 'info');
        this.log('', 'info');
        
        // Storage Information
        this.log('--- STORAGE DEVICES ---', 'success');
        data.storage.forEach((drive, index) => {
            this.log(`Drive ${index + 1}:`, 'info');
            this.log(`  Type: ${drive.type}`, 'info');
            this.log(`  Capacity: ${drive.capacity} GB`, 'info');
            this.log(`  Model: ${drive.model}`, 'info');
            this.log(`  Read Speed: ${drive.readSpeed}`, 'info');
        });
        this.log('', 'info');
        
        // GPU Information
        this.log('--- GRAPHICS CARDS (GPU) ---', 'success');
        data.gpu.forEach((gpu, index) => {
            this.log(`GPU ${index + 1}:`, 'info');
            this.log(`  Brand: ${gpu.brand}`, 'info');
            this.log(`  Model: ${gpu.model}`, 'info');
            this.log(`  Memory: ${gpu.memory} GB`, 'info');
            this.log(`  Driver: ${gpu.driverVersion}`, 'info');
        });
        this.log('', 'info');
        
        // Display Information
        this.log('--- DISPLAY ---', 'success');
        this.log(`Resolution: ${data.display.resolution}`, 'info');
        this.log(`Refresh Rate: ${data.display.refreshRate}`, 'info');
        this.log(`Panel Type: ${data.display.panelType}`, 'info');
        this.log('', 'info');
        
        // Battery Information
        this.log('--- POWER ---', 'success');
        if (data.battery.isCharging) {
            this.log('Battery: Plugged in (charging)', 'info');
        } else {
            this.log(`Battery: ${data.battery.estimatedLife} estimated life`, 'info');
        }
        this.log(`Battery Health: ${data.battery.health}`, 'info');
        this.log('', 'info');
        
        // Applications
        this.log('--- APPLICATIONS ---', 'success');
        this.log(`Total Installed Applications: ${data.applications.count}`, 'info');
        this.log(`System Applications: ${data.applications.systemApps}`, 'info');
        this.log(`User Applications: ${data.applications.userApps}`, 'info');
        
        this.log('', 'info');
        this.log('Investigation complete. All system components scanned.', 'success');
    }

    // ... rest of the methods remain the same as previous version
    async interrogate() {
        this.setStatus('INTERROGATING FILE...');
        
        if (!this.selectedFile) {
            this.log('No file selected. Scanning for malware...', 'loading');
            this.scanForMalware();
            return;
        }
        
        this.log(`Analyzing file: ${this.selectedFile.name}`, 'loading');
        
        try {
            const formData = new FormData();
            formData.append('exeFile', this.selectedFile);
            
            const response = await fetch('interrogate.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            this.displayInterrogationResults(result);
        } catch (error) {
            this.log(`Interrogation failed: ${error.message}`, 'error');
            this.log('Showing simulated file analysis...', 'info');
            this.displaySimulatedInterrogation();
        }
        
        this.setStatus('READY');
    }

    displaySimulatedInterrogation() {
        const simulatedResult = {
            fileName: this.selectedFile?.name || 'unknown.exe',
            fileSize: this.selectedFile?.size || 0,
            status: 'Analyzed (Simulated)',
            fileType: 'Executable',
            architecture: 'x64',
            digitalSignature: 'Valid',
            compilationDate: new Date().toISOString(),
            errors: [],
            warnings: ['Simulated analysis - PHP backend not available']
        };
        
        this.displayInterrogationResults(simulatedResult);
    }

    async scanForMalware() {
        try {
            const response = await fetch('interrogate.php?scan=malware');
            const result = await response.json();
            
            this.log('=== MALWARE SCAN RESULTS ===', 'success');
            if (result.malwareFound) {
                this.log(`🚨 MALWARE DETECTED: ${result.malwareName}`, 'error');
                this.log(`Threat Level: ${result.threatLevel}`, 'error');
                this.log(`Location: ${result.location}`, 'error');
                this.log(`Suggested Action: ${result.suggestedAction}`, 'error');
            } else {
                this.log('✅ No malware detected.', 'success');
                this.log(`Files scanned: ${result.filesScanned}`, 'info');
                this.log(`Scan time: ${result.scanTime}`, 'info');
            }
        } catch (error) {
            this.log(`Malware scan failed: ${error.message}`, 'error');
            this.log('Showing simulated malware scan...', 'info');
            
            // Simulated malware scan result
            this.log('=== MALWARE SCAN RESULTS (SIMULATED) ===', 'success');
            this.log('✅ No malware detected.', 'success');
            this.log('Files scanned: 0 (Simulation mode)', 'info');
            this.log('Scan time: ' + new Date().toISOString(), 'info');
        }
    }

    async refresh() {
        this.setStatus('REFRESHING...');
        this.debugDisplay.innerHTML = '';
        this.log('Display cleared.', 'success');
        this.log('Mr.Detective ready for new commands.', 'info');
        this.setStatus('READY');
    }

    handleFileSelect(event) {
        this.selectedFile = event.target.files[0];
        if (this.selectedFile) {
            this.log(`File selected: ${this.selectedFile.name}`, 'success');
            this.log('Click "INTERROGATE" to analyze this file.', 'info');
        }
    }

    executeCommand(command) {
        this.log(`Executing: ${command}`, 'info');
        
        switch(command.toLowerCase()) {
            case 'help':
                this.log('Available commands: help, system, clear, scan, test', 'info');
                break;
            case 'system':
                this.investigate();
                break;
            case 'clear':
                this.refresh();
                break;
            case 'scan':
                this.scanForMalware();
                break;
            case 'test':
                this.testConnection();
                break;
            default:
                this.log(`Unknown command: ${command}`, 'error');
                this.log('Type "help" for available commands', 'info');
        }
    }

    updateSystemInfo() {
        // Update footer with basic system info
        document.getElementById('cpuInfo').textContent = 'CPU: Ready';
        document.getElementById('ramInfo').textContent = 'RAM: Ready';
        document.getElementById('osInfo').textContent = 'OS: Ready';
    }
}

// Initialize the application
document.addEventListener('DOMContentLoaded', () => {
    new MrDetective();
});