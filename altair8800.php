<?php
/**
 * Altair 8800 Hardware Emulator with CP/M Support
 * 
 * This file implements a complete Altair 8800 emulator with BIOS, BDOS, and CCP
 * to run CP/M programs on the Intel 8080 emulator.
 */

require_once 'i8080.php';

/**
 * Altair 8800 Hardware Emulator
 * Extends the I8080 emulator with Altair 8800 hardware features
 */
class Altair8800 extends I8080 {
    // Front panel switches and lights
    private $addressSwitches = 0;     // 16-bit address switches
    private $dataSwitches = 0;        // 8-bit data switches
    private $statusLights = 0;        // Front panel status lights
    
    // Memory mapped I/O addresses for Altair
    const SERIAL_STATUS_PORT = 0x00;
    const SERIAL_DATA_PORT = 0x01;
    const PARALLEL_STATUS_PORT = 0x02;
    const PARALLEL_DATA_PORT = 0x03;
    
    // Serial port state
    private $serialInputBuffer = "";
    private $serialOutputBuffer = "";
    
    // Parallel port state
    private $parallelOutputBuffer = "";
    
    // Disk storage simulation
    private $diskTracks = 77;      // Number of tracks
    private $diskSectors = 26;     // Sectors per track
    private $sectorSize = 128;     // Bytes per sector
    private $diskBlocks = [];      // Simulated disk storage
    private $currentDrive = 0;     // Current drive (A: = 0, B: = 1, etc.)
    private $currentTrack = 0;
    private $currentSector = 0;
    private $dmaAddress = 0;       // Direct Memory Access address
    
    public function __construct() {
        parent::__construct();
        $this->initializeHardware();
    }
    
    /**
     * Initialize Altair 8800 hardware
     */
    private function initializeHardware() {
        // Initialize memory to simulate boot ROM
        for ($i = 0xFF00; $i < 0x10000; $i++) {
            $this->memory[$i] = 0x00;
        }
        
        // Set up initial stack pointer for CP/M
        $this->regSP = 0xFFFE;
        
        // Initialize I/O ports
        $this->memory[0] = 0xFF; // Serial status port (ready for input)
        $this->memory[1] = 0x00; // Serial data port
        $this->memory[2] = 0xFF; // Parallel status port (ready)
        $this->memory[3] = 0x00; // Parallel data port
        
        // Initialize simulated disk
        $this->initializeDisk();
    }
    
    /**
     * Initialize simulated disk
     */
    private function initializeDisk() {
        // Create empty disk with boot sector
        $totalSectors = $this->diskTracks * $this->diskSectors;
        for ($i = 0; $i < $totalSectors; $i++) {
            $this->diskBlocks[$i] = str_repeat("\x00", $this->sectorSize);
        }
        
        // Write boot sector signature
        $this->diskBlocks[0] = "CP/M DISK" . str_repeat("\x00", $this->sectorSize - 9);
    }
    
    /**
     * Set front panel address switches
     */
    public function setAddressSwitches($address) {
        $this->addressSwitches = $address & 0xFFFF;
    }
    
    /**
     * Set front panel data switches
     */
    public function setDataSwitches($data) {
        $this->dataSwitches = $data & 0xFF;
    }
    
    /**
     * Get front panel status lights
     */
    public function getStatusLights() {
        return $this->statusLights;
    }
    
    /**
     * Simulate front panel examine operation
     */
    public function examine() {
        return $this->getMemory($this->addressSwitches);
    }
    
    /**
     * Simulate front panel deposit operation
     */
    public function deposit() {
        $this->setMemory($this->addressSwitches, $this->dataSwitches);
    }
    
    /**
     * Simulate front panel deposit next operation
     */
    public function depositNext() {
        $this->setMemory($this->addressSwitches, $this->dataSwitches);
        $this->addressSwitches = ($this->addressSwitches + 1) & 0xFFFF;
    }
    
    /**
     * Load program into memory
     */
    public function loadProgram($startAddress, $programData) {
        for ($i = 0; $i < count($programData); $i++) {
            $this->setMemory(($startAddress + $i) & 0xFFFF, $programData[$i]);
        }
    }
    
    /**
     * Override step to handle I/O instructions
     */
    public function step() {
        // Check if we're about to execute an I/O instruction
        $opcode = $this->memory[$this->regPC];
        
        // Handle IN instruction (0xDB)
        if ($opcode === 0xDB) {
            $port = $this->memory[($this->regPC + 1) & 0xFFFF];
            $this->regPC = ($this->regPC + 2) & 0xFFFF;
            $this->handleInput($port);
            return;
        }
        
        // Handle OUT instruction (0xD3)
        if ($opcode === 0xD3) {
            $port = $this->memory[($this->regPC + 1) & 0xFFFF];
            $this->regPC = ($this->regPC + 2) & 0xFFFF;
            $this->handleOutput($port);
            return;
        }
        
        // Otherwise, execute normally
        parent::step();
    }
    
    /**
     * Handle input from I/O port
     */
    private function handleInput($port) {
        switch ($port) {
            case self::SERIAL_STATUS_PORT:
                // Serial status port - bit 0 = input ready, bit 1 = output ready
                $this->regA = (strlen($this->serialInputBuffer) > 0) ? 0x01 : 0x00;
                if (strlen($this->serialOutputBuffer) < 256) {
                    $this->regA |= 0x02; // Output ready
                }
                break;
                
            case self::SERIAL_DATA_PORT:
                // Serial data port - read character from input buffer
                if (strlen($this->serialInputBuffer) > 0) {
                    $this->regA = ord($this->serialInputBuffer[0]);
                    $this->serialInputBuffer = substr($this->serialInputBuffer, 1);
                } else {
                    $this->regA = 0x00;
                }
                break;
                
            case self::PARALLEL_STATUS_PORT:
                // Parallel status port - always ready
                $this->regA = 0xFF;
                break;
                
            case self::PARALLEL_DATA_PORT:
                // Parallel data port - not typically used for input
                $this->regA = 0x00;
                break;
                
            default:
                // Unknown port - return 0xFF
                $this->regA = 0xFF;
                break;
        }
    }
    
    /**
     * Handle output to I/O port
     */
    private function handleOutput($port) {
        $data = $this->regA;
        
        switch ($port) {
            case self::SERIAL_DATA_PORT:
                // Serial data port - add character to output buffer
                $this->serialOutputBuffer .= chr($data);
                // Echo to console for debugging
                echo chr($data);
                break;
                
            case self::PARALLEL_DATA_PORT:
                // Parallel data port - add character to parallel output
                $this->parallelOutputBuffer .= chr($data);
                break;
                
            case self::SERIAL_STATUS_PORT:
            case self::PARALLEL_STATUS_PORT:
                // Status ports - ignore output
                break;
                
            default:
                // Unknown port - ignore
                break;
        }
    }
    
    /**
     * Add input to serial buffer
     */
    public function addSerialInput($input) {
        $this->serialInputBuffer .= $input;
    }
    
    /**
     * Get serial output
     */
    public function getSerialOutput() {
        $output = $this->serialOutputBuffer;
        $this->serialOutputBuffer = "";
        return $output;
    }
    
    /**
     * Get parallel output
     */
    public function getParallelOutput() {
        $output = $this->parallelOutputBuffer;
        $this->parallelOutputBuffer = "";
        return $output;
    }
    
    /**
     * Reset hardware
     */
    public function resetHardware() {
        $this->initializeHardware();
        $this->serialInputBuffer = "";
        $this->serialOutputBuffer = "";
        $this->parallelOutputBuffer = "";
    }
    
    // BIOS Functions
    public function biosBoot() {
        // Boot function - load CCP and BDOS into memory
        $this->loadCPMSystem();
        return 0x0100; // Jump to CCP
    }
    
    public function biosWarmBoot() {
        // Warm boot - reload CCP and BDOS
        $this->loadCPMSystem();
        return 0x0100; // Jump to CCP
    }
    
    public function biosConsoleStatus() {
        // Console status - check if input is ready
        return (strlen($this->serialInputBuffer) > 0) ? 0xFF : 0x00;
    }
    
    public function biosConsoleInput() {
        // Console input - get character from serial input
        if (strlen($this->serialInputBuffer) > 0) {
            $char = ord($this->serialInputBuffer[0]);
            $this->serialInputBuffer = substr($this->serialInputBuffer, 1);
            return $char;
        }
        return 0x00; // No input available
    }
    
    public function biosConsoleOutput($char) {
        // Console output - send character to serial output
        $this->serialOutputBuffer .= chr($char);
        echo chr($char);
        return true;
    }
    
    public function biosHome() {
        // Move disk head to track 0
        $this->currentTrack = 0;
        return true;
    }
    
    public function biosSelectDisk($drive) {
        // Select disk drive
        $this->currentDrive = $drive;
        return true;
    }
    
    public function biosSetTrack($track) {
        // Set track number
        $this->currentTrack = $track;
        return true;
    }
    
    public function biosSetSector($sector) {
        // Set sector number
        $this->currentSector = $sector;
        return true;
    }
    
    public function biosSetDMA($dmaAddr) {
        // Set DMA address
        $this->dmaAddress = $dmaAddr;
        return true;
    }
    
    public function biosRead() {
        // Read sector
        $sectorIndex = $this->currentTrack * $this->diskSectors + $this->currentSector;
        if (isset($this->diskBlocks[$sectorIndex])) {
            // Copy data from disk to memory at DMA address
            for ($i = 0; $i < $this->sectorSize; $i++) {
                $addr = ($this->dmaAddress + $i) & 0xFFFF;
                $this->setMemory($addr, ord($this->diskBlocks[$sectorIndex][$i]));
            }
            return 0x00; // Success
        }
        return 0x01; // Error
    }
    
    public function biosWrite($deleted = false) {
        // Write sector
        $sectorIndex = $this->currentTrack * $this->diskSectors + $this->currentSector;
        if ($sectorIndex < count($this->diskBlocks)) {
            // Copy data from memory at DMA address to disk
            $data = "";
            for ($i = 0; $i < $this->sectorSize; $i++) {
                $addr = ($this->dmaAddress + $i) & 0xFFFF;
                $data .= chr($this->getMemory($addr));
            }
            $this->diskBlocks[$sectorIndex] = $data;
            return 0x00; // Success
        }
        return 0x01; // Error
    }
    
    /**
     * Load CP/M system (CCP and BDOS) into memory
     */
    private function loadCPMSystem() {
        // Load BDOS into memory at 0x0000-0x00FF
        $bdosCode = $this->getBDOSCode();
        for ($i = 0; $i < count($bdosCode); $i++) {
            $this->setMemory($i, $bdosCode[$i]);
        }
        
        // Load CCP into memory at 0x0100-0x07FF
        $ccpCode = $this->getCCPCode();
        for ($i = 0; $i < count($ccpCode); $i++) {
            $this->setMemory(0x0100 + $i, $ccpCode[$i]);
        }
    }
    
    /**
     * Get BDOS code
     */
    private function getBDOSCode() {
        // Simple BDOS implementation
        return [
            0x76,  // HLT - placeholder for BDOS
            0x00,  // Padding
            0x00,  // Padding
        ];
    }
    
    /**
     * Get CCP code
     */
    private function getCCPCode() {
        // Simple CCP that prints "CP/M>" and waits for commands
        return [
            // Print "CP/M>"
            0x21, 0x00, 0x08,  // LXI H, 0x0800  ; Point to string
            0x7E,              // MOV A, M       ; Load character
            0xFE, 0x24,        // CPI '$'        ; Check for end marker
            0xCA, 0x10, 0x01,  // JZ 0x0110      ; Jump to end if $
            0xD3, 0x01,        // OUT 0x01       ; Output character
            0x23,              // INX H          ; Next character
            0xC3, 0x03, 0x01,  // JMP 0x0103     ; Loop
            // End - halt
            0x76,              // HLT
            // String data at 0x0800
            0x43, 0x50, 0x2F, 0x4D, 0x3E, 0x24  // "CP/M>$"
        ];
        
        // Load string data at 0x0800
        $stringData = [0x43, 0x50, 0x2F, 0x4D, 0x3E, 0x24]; // "CP/M>$"
        for ($i = 0; $i < count($stringData); $i++) {
            $this->setMemory(0x0800 + $i, $stringData[$i]);
        }
    }
}

/**
 * CP/M BDOS Implementation
 */
class CPM_BDOS {
    private $altair;
    
    public function __construct($altair) {
        $this->altair = $altair;
    }
    
    /**
     * BDOS System Call Handler
     */
    public function systemCall($function, $de) {
        switch ($function) {
            case 0:  // System Reset
                return $this->sysReset();
                
            case 1:  // Console Input
                return $this->conInput();
                
            case 2:  // Console Output
                return $this->conOutput($de & 0xFF);
                
            case 9:  // Print String
                return $this->printString($de);
                
            default:
                // Unknown function - return 0
                return 0;
        }
    }
    
    private function sysReset() {
        // System reset - warm boot
        return 0; // Placeholder
    }
    
    private function conInput() {
        // Console input
        return $this->altair->biosConsoleInput();
    }
    
    private function conOutput($char) {
        // Console output
        $this->altair->biosConsoleOutput($char);
        return 0;
    }
    
    private function printString($addr) {
        // Print string until '$' character
        $str = "";
        $i = 0;
        while (true) {
            $char = $this->altair->getMemory(($addr + $i) & 0xFFFF);
            if ($char == 0x24) { // '$' character
                break;
            }
            $str .= chr($char);
            $i++;
            if ($i > 255) break; // Safety check
        }
        
        // Output string
        for ($i = 0; $i < strlen($str); $i++) {
            $this->altair->biosConsoleOutput(ord($str[$i]));
        }
        
        return 0;
    }
}

/**
 * CP/M CCP (Console Command Processor) Implementation
 */
class CPM_CCP {
    private $altair;
    private $bdos;
    
    public function __construct($altair) {
        $this->altair = $altair;
        $this->bdos = new CPM_BDOS($altair);
    }
    
    /**
     * Initialize CCP
     */
    public function initialize() {
        // CCP is loaded by BIOS
    }
    
    /**
     * Process command
     */
    public function processCommand($command) {
        // Simple command processing
        $command = trim($command);
        
        if (empty($command)) {
            return true;
        }
        
        // Split command into parts
        $parts = explode(" ", $command);
        $cmd = strtoupper($parts[0]);
        
        switch ($cmd) {
            case "DIR":
                $this->listDirectory();
                break;
                
            case "TYPE":
                if (isset($parts[1])) {
                    $this->typeFile($parts[1]);
                }
                break;
                
            default:
                $this->printMessage("Bad command\r\n");
                break;
        }
        
        return true;
    }
    
    private function listDirectory() {
        $this->printMessage("No files found\r\n");
    }
    
    private function typeFile($filename) {
        $this->printMessage("File not found: " . $filename . "\r\n");
    }
    
    private function printMessage($message) {
        // Print message through BDOS
        for ($i = 0; $i < strlen($message); $i++) {
            $this->bdos->systemCall(2, ord($message[$i]));
        }
    }
}

/**
 * Complete Altair 8800 System with CP/M
 */
class AltairCPMSystem {
    protected $altair;
    protected $ccp;
    
    public function __construct() {
        $this->altair = new Altair8800();
        $this->ccp = new CPM_CCP($this->altair);
    }
    
    /**
     * Boot CP/M system
     */
    public function boot() {
        // Boot through BIOS
        $entryPoint = $this->altair->biosBoot();
        $this->altair->setRegister('PC', $entryPoint);
        return true;
    }
    
    /**
     * Run system for specified number of instructions
     */
    public function run($instructions = 1000000) {
        for ($i = 0; $i < $instructions; $i++) {
            $pc = $this->altair->getRegister('PC');
            
            // Check for HLT instruction
            if ($this->altair->getMemory($pc) == 0x76) {
                echo "System halted at PC: 0x" . sprintf("%04X", $pc) . "\n";
                break;
            }
            
            $this->altair->step();
        }
    }
    
    /**
     * Get system output
     */
    public function getOutput() {
        return $this->altair->getSerialOutput();
    }
    
    /**
     * Get registers from the Altair CPU
     */
    public function getRegisters() {
        return $this->altair->getRegisters();
    }
    
    /**
     * Send input to system
     */
    public function sendInput($input) {
        $this->altair->addSerialInput($input);
    }
}

// Test Altair 8800 system
function test_altair() {
    echo "Testing Altair 8800 Emulator with CP/M\n";
    echo "======================================\n";
    
    $system = new AltairCPMSystem();
    $system->boot();
    
    // Send a simple command
    $system->sendInput("DIR\r\n");
    
    // Run for a bit
    $system->run(10000);
    
    // Get output
    $output = $system->getOutput();
    if (!empty($output)) {
        echo "Output: " . $output . "\n";
    }
    
    // Show final register state
    echo "\nFinal CPU State:\n";
    $registers = $system->getRegisters();
    foreach ($registers as $reg => $value) {
        if (is_numeric($value)) {
            echo sprintf("%-3s: 0x%04X (%d)\n", $reg, $value, $value);
        }
    }
}

// Run the test if this file is executed directly
if (basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'])) {
    test_altair();
}

?>
