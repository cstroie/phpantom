<?php
/**
 * CPU Information Utility
 *
 * This file is part of the System Information project.
 *
 * @package     SystemInfo
 * @author      Your Name <your.email@example.com>
 * @copyright   2025 Your Name
 * @license     http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 * @version     1.0.0
 * @link        https://github.com/yourusername/system-info
 */

/**
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Intel 8080 CPU Emulator
 */
class I8080Emulator {
    // 8-bit registers
    private $regA;  // Accumulator
    private $regB;
    private $regC;
    private $regD;
    private $regE;
    private $regH;
    private $regL;
    
    // 16-bit registers
    private $regSP;  // Stack pointer
    private $regPC;  // Program counter
    
    // Flags register (8-bit)
    private $flags;  // S, Z, 0, AC, 0, P, 1, CY
    
    // Memory (64KB)
    private $memory;
    
    // Flag bit positions
    const FLAG_S = 0x80;   // Sign flag
    const FLAG_Z = 0x40;   // Zero flag
    const FLAG_AC = 0x10;  // Auxiliary carry flag
    const FLAG_P = 0x04;   // Parity flag
    const FLAG_CY = 0x01;  // Carry flag
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->reset();
    }
    
    /**
     * Reset CPU to initial state
     */
    public function reset() {
        $this->regA = 0;
        $this->regB = 0;
        $this->regC = 0;
        $this->regD = 0;
        $this->regE = 0;
        $this->regH = 0;
        $this->regL = 0;
        $this->regSP = 0;
        $this->regPC = 0;
        $this->flags = 0x02;  // Bit 1 is always 1
        
        // Initialize memory to zeros
        $this->memory = array_fill(0, 65536, 0);
    }
    
    /**
     * Get register values
     */
    public function getRegisters() {
        return array(
            'A' => $this->regA,
            'B' => $this->regB,
            'C' => $this->regC,
            'D' => $this->regD,
            'E' => $this->regE,
            'H' => $this->regH,
            'L' => $this->regL,
            'SP' => $this->regSP,
            'PC' => $this->regPC,
            'FLAGS' => $this->flags
        );
    }
    
    /**
     * Get specific register value
     */
    public function getRegister($reg) {
        switch (strtoupper($reg)) {
            case 'A': return $this->regA;
            case 'B': return $this->regB;
            case 'C': return $this->regC;
            case 'D': return $this->regD;
            case 'E': return $this->regE;
            case 'H': return $this->regH;
            case 'L': return $this->regL;
            case 'SP': return $this->regSP;
            case 'PC': return $this->regPC;
            case 'FLAGS': return $this->flags;
            default: return null;
        }
    }
    
    /**
     * Set specific register value
     */
    public function setRegister($reg, $value) {
        $value = $value & 0xFF;  // Ensure 8-bit
        
        switch (strtoupper($reg)) {
            case 'A': $this->regA = $value; break;
            case 'B': $this->regB = $value; break;
            case 'C': $this->regC = $value; break;
            case 'D': $this->regD = $value; break;
            case 'E': $this->regE = $value; break;
            case 'H': $this->regH = $value; break;
            case 'L': $this->regL = $value; break;
            case 'SP': $this->regSP = $value & 0xFFFF; break;  // 16-bit
            case 'PC': $this->regPC = $value & 0xFFFF; break;  // 16-bit
        }
    }
    
    /**
     * Get memory value at address
     */
    public function getMemory($address) {
        if ($address >= 0 && $address < 65536) {
            return $this->memory[$address];
        }
        return 0;
    }
    
    /**
     * Set memory value at address
     */
    public function setMemory($address, $value) {
        if ($address >= 0 && $address < 65536) {
            $this->memory[$address] = $value & 0xFF;
        }
    }
    
    /**
     * Load a block of binary data into memory at a specific location
     * 
     * @param int $startAddress The memory address to start loading at
     * @param array $data Array of byte values to load
     */
    public function loadMemoryBlock($startAddress, $data) {
        for ($i = 0; $i < count($data); $i++) {
            $address = ($startAddress + $i) & 0xFFFF;
            $this->setMemory($address, $data[$i]);
        }
    }
    
    /**
     * Load a binary file into memory at a specific address
     * 
     * @param string $filename Path to the binary file
     * @param int $startAddress Memory address to load the file at
     * @return bool True on success, false on failure
     */
    public function loadBinaryFile($filename, $startAddress) {
        // Check if file exists
        if (!file_exists($filename)) {
            return false;
        }
        
        // Read the binary file
        $data = file_get_contents($filename);
        if ($data === false) {
            return false;
        }
        
        // Convert binary string to array of bytes
        $byteArray = array();
        for ($i = 0; $i < strlen($data); $i++) {
            $byteArray[] = ord($data[$i]);
        }
        
        // Load the data into memory
        $this->loadMemoryBlock($startAddress, $byteArray);
        
        return true;
    }
    
    /**
     * Get 16-bit register pair value
     */
    public function getRegisterPair($pair) {
        switch (strtoupper($pair)) {
            case 'BC': return ($this->regB << 8) | $this->regC;
            case 'DE': return ($this->regD << 8) | $this->regE;
            case 'HL': return ($this->regH << 8) | $this->regL;
            case 'SP': return $this->regSP;
            case 'PC': return $this->regPC;
            default: return 0;
        }
    }
    
    /**
     * Set 16-bit register pair value
     */
    public function setRegisterPair($pair, $value) {
        $value = $value & 0xFFFF;  // Ensure 16-bit
        
        switch (strtoupper($pair)) {
            case 'BC':
                $this->regB = ($value >> 8) & 0xFF;
                $this->regC = $value & 0xFF;
                break;
            case 'DE':
                $this->regD = ($value >> 8) & 0xFF;
                $this->regE = $value & 0xFF;
                break;
            case 'HL':
                $this->regH = ($value >> 8) & 0xFF;
                $this->regL = $value & 0xFF;
                break;
            case 'SP':
                $this->regSP = $value;
                break;
            case 'PC':
                $this->regPC = $value;
                break;
        }
    }
    
    /**
     * Get flag value
     */
    public function getFlag($flag) {
        return ($this->flags & $flag) != 0;
    }
    
    /**
     * Set flag value
     */
    public function setFlag($flag, $value) {
        if ($value) {
            $this->flags |= $flag;
        } else {
            $this->flags &= ~$flag;
        }
        // Ensure bit 1 is always 1
        $this->flags |= 0x02;
    }
    
    /**
     * Execute one instruction
     */
    public function step() {
        // Fetch the opcode
        $opcode = $this->memory[$this->regPC];
        $this->regPC = ($this->regPC + 1) & 0xFFFF;
        
        // Execute the opcode
        switch ($opcode) {
            // Data transfer instructions
            case 0x7F: // MOV A,A
                break;
            case 0x78: // MOV A,B
                $this->regA = $this->regB;
                break;
            case 0x79: // MOV A,C
                $this->regA = $this->regC;
                break;
            case 0x7A: // MOV A,D
                $this->regA = $this->regD;
                break;
            case 0x7B: // MOV A,E
                $this->regA = $this->regE;
                break;
            case 0x7C: // MOV A,H
                $this->regA = $this->regH;
                break;
            case 0x7D: // MOV A,L
                $this->regA = $this->regL;
                break;
            case 0x7E: // MOV A,M
                $addr = ($this->regH << 8) | $this->regL;
                $this->regA = $this->memory[$addr];
                break;
            case 0x40: // MOV B,B
                break;
            case 0x41: // MOV B,C
                $this->regB = $this->regC;
                break;
            case 0x42: // MOV B,D
                $this->regB = $this->regD;
                break;
            case 0x43: // MOV B,E
                $this->regB = $this->regE;
                break;
            case 0x44: // MOV B,H
                $this->regB = $this->regH;
                break;
            case 0x45: // MOV B,L
                $this->regB = $this->regL;
                break;
            case 0x46: // MOV B,M
                $addr = ($this->regH << 8) | $this->regL;
                $this->regB = $this->memory[$addr];
                break;
            case 0x47: // MOV B,A
                $this->regB = $this->regA;
                break;
            case 0x48: // MOV C,B
                $this->regC = $this->regB;
                break;
            case 0x49: // MOV C,C
                break;
            case 0x4A: // MOV C,D
                $this->regC = $this->regD;
                break;
            case 0x4B: // MOV C,E
                $this->regC = $this->regE;
                break;
            case 0x4C: // MOV C,H
                $this->regC = $this->regH;
                break;
            case 0x4D: // MOV C,L
                $this->regC = $this->regL;
                break;
            case 0x4E: // MOV C,M
                $addr = ($this->regH << 8) | $this->regL;
                $this->regC = $this->memory[$addr];
                break;
            case 0x4F: // MOV C,A
                $this->regC = $this->regA;
                break;
                
            // Arithmetic instructions
            case 0x80: // ADD B
                $this->add($this->regB);
                break;
            case 0x81: // ADD C
                $this->add($this->regC);
                break;
            case 0x82: // ADD D
                $this->add($this->regD);
                break;
            case 0x83: // ADD E
                $this->add($this->regE);
                break;
            case 0x84: // ADD H
                $this->add($this->regH);
                break;
            case 0x85: // ADD L
                $this->add($this->regL);
                break;
            case 0x86: // ADD M
                $addr = ($this->regH << 8) | $this->regL;
                $this->add($this->memory[$addr]);
                break;
            case 0x87: // ADD A
                $this->add($this->regA);
                break;
                
            // Immediate instructions
            case 0x3E: // MVI A,byte
                $this->regA = $this->memory[$this->regPC];
                $this->regPC = ($this->regPC + 1) & 0xFFFF;
                break;
            case 0x06: // MVI B,byte
                $this->regB = $this->memory[$this->regPC];
                $this->regPC = ($this->regPC + 1) & 0xFFFF;
                break;
            case 0x0E: // MVI C,byte
                $this->regC = $this->memory[$this->regPC];
                $this->regPC = ($this->regPC + 1) & 0xFFFF;
                break;
            case 0x16: // MVI D,byte
                $this->regD = $this->memory[$this->regPC];
                $this->regPC = ($this->regPC + 1) & 0xFFFF;
                break;
            case 0x1E: // MVI E,byte
                $this->regE = $this->memory[$this->regPC];
                $this->regPC = ($this->regPC + 1) & 0xFFFF;
                break;
            case 0x26: // MVI H,byte
                $this->regH = $this->memory[$this->regPC];
                $this->regPC = ($this->regPC + 1) & 0xFFFF;
                break;
            case 0x2E: // MVI L,byte
                $this->regL = $this->memory[$this->regPC];
                $this->regPC = ($this->regPC + 1) & 0xFFFF;
                break;
            case 0x36: // MVI M,byte
                $addr = ($this->regH << 8) | $this->regL;
                $this->memory[$addr] = $this->memory[$this->regPC];
                $this->regPC = ($this->regPC + 1) & 0xFFFF;
                break;
                
            // Register or memory to accumulator instructions
            case 0xC6: // ADI byte
                $value = $this->memory[$this->regPC];
                $this->regPC = ($this->regPC + 1) & 0xFFFF;
                $this->add($value);
                break;
                
            // Jump instructions
            case 0xC3: // JMP address
                $low = $this->memory[$this->regPC];
                $high = $this->memory[($this->regPC + 1) & 0xFFFF];
                $this->regPC = ($high << 8) | $low;
                break;
                
            // NOP
            case 0x00: // NOP
                break;
                
            // HLT
            case 0x76: // HLT
                // Halt - for now we'll just return
                return;
                
            default:
                // Unimplemented opcode - for now we'll just skip it
                break;
        }
    }
    
    /**
     * Helper function for ADD instruction
     */
    private function add($value) {
        $result = $this->regA + $value;
        
        // Set flags
        $this->setFlag(self::FLAG_S, ($result & 0x80) != 0);
        $this->setFlag(self::FLAG_Z, ($result & 0xFF) == 0);
        $this->setFlag(self::FLAG_CY, $result > 0xFF);
        
        // Auxiliary carry flag (carry from bit 3 to bit 4)
        $ac = (($this->regA & 0x0F) + ($value & 0x0F)) > 0x0F;
        $this->setFlag(self::FLAG_AC, $ac);
        
        // Parity flag (even parity)
        $parity = 0;
        $temp = $result & 0xFF;
        for ($i = 0; $i < 8; $i++) {
            $parity ^= ($temp & 1);
            $temp >>= 1;
        }
        $this->setFlag(self::FLAG_P, $parity == 0);
        
        $this->regA = $result & 0xFF;
    }
    
    /**
     * Helper function for ADC instruction
     */
    private function adc($value) {
        $cy = $this->getFlag(self::FLAG_CY) ? 1 : 0;
        $result = $this->regA + $value + $cy;
        
        // Set flags
        $this->setFlag(self::FLAG_S, ($result & 0x80) != 0);
        $this->setFlag(self::FLAG_Z, ($result & 0xFF) == 0);
        $this->setFlag(self::FLAG_CY, $result > 0xFF);
        
        // Auxiliary carry flag
        $ac = (($this->regA & 0x0F) + ($value & 0x0F) + $cy) > 0x0F;
        $this->setFlag(self::FLAG_AC, $ac);
        
        // Parity flag
        $parity = 0;
        $temp = $result & 0xFF;
        for ($i = 0; $i < 8; $i++) {
            $parity ^= ($temp & 1);
            $temp >>= 1;
        }
        $this->setFlag(self::FLAG_P, $parity == 0);
        
        $this->regA = $result & 0xFF;
    }
    
    /**
     * Helper function for SUB instruction
     */
    private function sub($value) {
        $result = $this->regA - $value;
        
        // Set flags
        $this->setFlag(self::FLAG_S, ($result & 0x80) != 0);
        $this->setFlag(self::FLAG_Z, ($result & 0xFF) == 0);
        $this->setFlag(self::FLAG_CY, $result < 0);
        
        // Auxiliary carry flag
        $ac = ($this->regA & 0x0F) < ($value & 0x0F);
        $this->setFlag(self::FLAG_AC, $ac);
        
        // Parity flag
        $parity = 0;
        $temp = $result & 0xFF;
        for ($i = 0; $i < 8; $i++) {
            $parity ^= ($temp & 1);
            $temp >>= 1;
        }
        $this->setFlag(self::FLAG_P, $parity == 0);
        
        $this->regA = $result & 0xFF;
    }
    
    /**
     * Helper function for SBB instruction
     */
    private function sbb($value) {
        $cy = $this->getFlag(self::FLAG_CY) ? 1 : 0;
        $result = $this->regA - $value - $cy;
        
        // Set flags
        $this->setFlag(self::FLAG_S, ($result & 0x80) != 0);
        $this->setFlag(self::FLAG_Z, ($result & 0xFF) == 0);
        $this->setFlag(self::FLAG_CY, $result < 0);
        
        // Auxiliary carry flag
        $ac = ($this->regA & 0x0F) < (($value & 0x0F) + $cy);
        $this->setFlag(self::FLAG_AC, $ac);
        
        // Parity flag
        $parity = 0;
        $temp = $result & 0xFF;
        for ($i = 0; $i < 8; $i++) {
            $parity ^= ($temp & 1);
            $temp >>= 1;
        }
        $this->setFlag(self::FLAG_P, $parity == 0);
        
        $this->regA = $result & 0xFF;
    }
    
    /**
     * Helper function for ANA instruction
     */
    private function ana($value) {
        $result = $this->regA & $value;
        
        // Set flags
        $this->setFlag(self::FLAG_S, ($result & 0x80) != 0);
        $this->setFlag(self::FLAG_Z, $result == 0);
        $this->setFlag(self::FLAG_AC, (($this->regA | $value) & 0x08) != 0);
        $this->setFlag(self::FLAG_CY, false);
        
        // Parity flag
        $parity = 0;
        $temp = $result;
        for ($i = 0; $i < 8; $i++) {
            $parity ^= ($temp & 1);
            $temp >>= 1;
        }
        $this->setFlag(self::FLAG_P, $parity == 0);
        
        $this->regA = $result;
    }
    
    /**
     * Helper function for XRA instruction
     */
    private function xra($value) {
        $result = $this->regA ^ $value;
        
        // Set flags
        $this->setFlag(self::FLAG_S, ($result & 0x80) != 0);
        $this->setFlag(self::FLAG_Z, $result == 0);
        $this->setFlag(self::FLAG_AC, false);
        $this->setFlag(self::FLAG_CY, false);
        
        // Parity flag
        $parity = 0;
        $temp = $result;
        for ($i = 0; $i < 8; $i++) {
            $parity ^= ($temp & 1);
            $temp >>= 1;
        }
        $this->setFlag(self::FLAG_P, $parity == 0);
        
        $this->regA = $result;
    }
    
    /**
     * Helper function for ORA instruction
     */
    private function ora($value) {
        $result = $this->regA | $value;
        
        // Set flags
        $this->setFlag(self::FLAG_S, ($result & 0x80) != 0);
        $this->setFlag(self::FLAG_Z, $result == 0);
        $this->setFlag(self::FLAG_AC, false);
        $this->setFlag(self::FLAG_CY, false);
        
        // Parity flag
        $parity = 0;
        $temp = $result;
        for ($i = 0; $i < 8; $i++) {
            $parity ^= ($temp & 1);
            $temp >>= 1;
        }
        $this->setFlag(self::FLAG_P, $parity == 0);
        
        $this->regA = $result;
    }
    
    /**
     * Helper function for CMP instruction
     */
    private function cmp($value) {
        $result = $this->regA - $value;
        
        // Set flags
        $this->setFlag(self::FLAG_S, ($result & 0x80) != 0);
        $this->setFlag(self::FLAG_Z, ($result & 0xFF) == 0);
        $this->setFlag(self::FLAG_CY, $result < 0);
        
        // Auxiliary carry flag
        $ac = ($this->regA & 0x0F) < ($value & 0x0F);
        $this->setFlag(self::FLAG_AC, $ac);
        
        // Parity flag
        $parity = 0;
        $temp = $result & 0xFF;
        for ($i = 0; $i < 8; $i++) {
            $parity ^= ($temp & 1);
            $temp >>= 1;
        }
        $this->setFlag(self::FLAG_P, $parity == 0);
    }
    
    /**
     * Helper function for INR instruction
     */
    private function inr($reg) {
        $value = $this->getRegister($reg);
        $result = ($value + 1) & 0xFF;
        $this->setRegister($reg, $result);
        
        // Set flags (except carry)
        $this->setFlag(self::FLAG_S, ($result & 0x80) != 0);
        $this->setFlag(self::FLAG_Z, $result == 0);
        $ac = (($value & 0x0F) + 1) > 0x0F;
        $this->setFlag(self::FLAG_AC, $ac);
        
        // Parity flag
        $parity = 0;
        $temp = $result;
        for ($i = 0; $i < 8; $i++) {
            $parity ^= ($temp & 1);
            $temp >>= 1;
        }
        $this->setFlag(self::FLAG_P, $parity == 0);
    }
    
    /**
     * Helper function for DCR instruction
     */
    private function dcr($reg) {
        $value = $this->getRegister($reg);
        $result = ($value - 1) & 0xFF;
        $this->setRegister($reg, $result);
        
        // Set flags (except carry)
        $this->setFlag(self::FLAG_S, ($result & 0x80) != 0);
        $this->setFlag(self::FLAG_Z, $result == 0);
        $ac = ($value & 0x0F) < 1;
        $this->setFlag(self::FLAG_AC, $ac);
        
        // Parity flag
        $parity = 0;
        $temp = $result;
        for ($i = 0; $i < 8; $i++) {
            $parity ^= ($temp & 1);
            $temp >>= 1;
        }
        $this->setFlag(self::FLAG_P, $parity == 0);
    }
    
    /**
     * Helper function for DAD instruction
     */
    private function dad($pair) {
        $hl = ($this->regH << 8) | $this->regL;
        $value = $this->getRegisterPair($pair);
        $result = $hl + $value;
        
        // Set flags
        $this->setFlag(self::FLAG_CY, $result > 0xFFFF);
        
        // Update HL register pair
        $this->regH = ($result >> 8) & 0xFF;
        $this->regL = $result & 0xFF;
    }
    
    /**
     * Helper function for DAA instruction
     */
    private function daa() {
        $result = $this->regA;
        $cy = $this->getFlag(self::FLAG_CY);
        $ac = $this->getFlag(self::FLAG_AC);
        
        // Adjust lower nibble
        if ($ac || (($result & 0x0F) > 9)) {
            $result += 0x06;
            $ac = true;
        }
        
        // Adjust upper nibble
        if ($cy || (($result & 0xF0) > 0x90) || (($result & 0xF0) == 0x90 && ($result & 0x0F) > 9)) {
            $result += 0x60;
            $cy = true;
        } else {
            $cy = false;
        }
        
        // Set flags
        $this->setFlag(self::FLAG_S, ($result & 0x80) != 0);
        $this->setFlag(self::FLAG_Z, ($result & 0xFF) == 0);
        $this->setFlag(self::FLAG_AC, $ac);
        $this->setFlag(self::FLAG_CY, $cy);
        
        // Parity flag
        $parity = 0;
        $temp = $result & 0xFF;
        for ($i = 0; $i < 8; $i++) {
            $parity ^= ($temp & 1);
            $temp >>= 1;
        }
        $this->setFlag(self::FLAG_P, $parity == 0);
        
        $this->regA = $result & 0xFF;
    }
}

/**
 * Get CPU information
 *
 * @return array CPU information
 */
function get_cpu_info() {
    $cpu_info = array();
    
    // Implementation would go here
    // This is just a placeholder function
    
    return $cpu_info;
}

// Test the emulator
function test_emulator() {
    echo "Testing I8080 Emulator\n";
    echo "=====================\n";
    
    // Create emulator instance
    $cpu = new I8080Emulator();
    
    // Load a simple program:
    // MVI A, 0x05    ; Load 5 into register A
    // MVI B, 0x03    ; Load 3 into register B
    // ADD B          ; Add B to A
    // HLT            ; Halt
    
    $cpu->setMemory(0x0000, 0x3E);  // MVI A, 0x05
    $cpu->setMemory(0x0001, 0x05);
    $cpu->setMemory(0x0002, 0x06);  // MVI B, 0x03
    $cpu->setMemory(0x0003, 0x03);
    $cpu->setMemory(0x0004, 0x80);  // ADD B
    $cpu->setMemory(0x0005, 0x76);  // HLT
    
    echo "Initial state:\n";
    print_r($cpu->getRegisters());
    
    echo "\nExecuting program...\n";
    
    // Execute 6 instructions
    for ($i = 0; $i < 6; $i++) {
        echo "Step " . ($i+1) . ": PC=" . sprintf("0x%04X", $cpu->getRegister('PC')) . 
             ", Opcode=" . sprintf("0x%02X", $cpu->getMemory($cpu->getRegister('PC'))) . "\n";
        $cpu->step();
    }
    
    echo "\nFinal state:\n";
    print_r($cpu->getRegisters());
    
    echo "Result: A = " . $cpu->getRegister('A') . " (0x" . sprintf("%02X", $cpu->getRegister('A')) . ")\n";
    
    // Check flags
    echo "Flags - S:" . ($cpu->getFlag(I8080Emulator::FLAG_S) ? "1" : "0") . 
         " Z:" . ($cpu->getFlag(I8080Emulator::FLAG_Z) ? "1" : "0") . 
         " AC:" . ($cpu->getFlag(I8080Emulator::FLAG_AC) ? "1" : "0") . 
         " P:" . ($cpu->getFlag(I8080Emulator::FLAG_P) ? "1" : "0") . 
         " CY:" . ($cpu->getFlag(I8080Emulator::FLAG_CY) ? "1" : "0") . "\n";
}

// Run the test if this file is executed directly
if (basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'])) {
    test_emulator();
}

?>
