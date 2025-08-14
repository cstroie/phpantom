<?php
/**
 * PHPantom - Ghost of Past Computing
 *
 * Intel 8080 CPU Emulator written in PHP
 *
 * @package     PHPantom
 * @author      Costin Stroie <costinstroie@eridu.eu.org>
 * @copyright   2025 Costin Stroie
 * @license     http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 * @version     1.0.0
 * @link        https://github.com/cstroie/phpantom
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
 *
 * PHPantom - Ghost of Past Computing
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
            case 0x02: // STAX B
                $addr = ($this->regB << 8) | $this->regC;
                $this->memory[$addr] = $this->regA;
                break;
            case 0x0A: // LDAX B
                $addr = ($this->regB << 8) | $this->regC;
                $this->regA = $this->memory[$addr];
                break;
            case 0x12: // STAX D
                $addr = ($this->regD << 8) | $this->regE;
                $this->memory[$addr] = $this->regA;
                break;
            case 0x1A: // LDAX D
                $addr = ($this->regD << 8) | $this->regE;
                $this->regA = $this->memory[$addr];
                break;
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
            case 0x50: // MOV D,B
                $this->regD = $this->regB;
                break;
            case 0x51: // MOV D,C
                $this->regD = $this->regC;
                break;
            case 0x52: // MOV D,D
                break;
            case 0x53: // MOV D,E
                $this->regD = $this->regE;
                break;
            case 0x54: // MOV D,H
                $this->regD = $this->regH;
                break;
            case 0x55: // MOV D,L
                $this->regD = $this->regL;
                break;
            case 0x56: // MOV D,M
                $addr = ($this->regH << 8) | $this->regL;
                $this->regD = $this->memory[$addr];
                break;
            case 0x57: // MOV D,A
                $this->regD = $this->regA;
                break;
            case 0x58: // MOV E,B
                $this->regE = $this->regB;
                break;
            case 0x59: // MOV E,C
                $this->regE = $this->regC;
                break;
            case 0x5A: // MOV E,D
                $this->regE = $this->regD;
                break;
            case 0x5B: // MOV E,E
                break;
            case 0x5C: // MOV E,H
                $this->regE = $this->regH;
                break;
            case 0x5D: // MOV E,L
                $this->regE = $this->regL;
                break;
            case 0x5E: // MOV E,M
                $addr = ($this->regH << 8) | $this->regL;
                $this->regE = $this->memory[$addr];
                break;
            case 0x5F: // MOV E,A
                $this->regE = $this->regA;
                break;
            case 0x60: // MOV H,B
                $this->regH = $this->regB;
                break;
            case 0x61: // MOV H,C
                $this->regH = $this->regC;
                break;
            case 0x62: // MOV H,D
                $this->regH = $this->regD;
                break;
            case 0x63: // MOV H,E
                $this->regH = $this->regE;
                break;
            case 0x64: // MOV H,H
                break;
            case 0x65: // MOV H,L
                $this->regH = $this->regL;
                break;
            case 0x66: // MOV H,M
                $addr = ($this->regH << 8) | $this->regL;
                $this->regH = $this->memory[$addr];
                break;
            case 0x67: // MOV H,A
                $this->regH = $this->regA;
                break;
            case 0x68: // MOV L,B
                $this->regL = $this->regB;
                break;
            case 0x69: // MOV L,C
                $this->regL = $this->regC;
                break;
            case 0x6A: // MOV L,D
                $this->regL = $this->regD;
                break;
            case 0x6B: // MOV L,E
                $this->regL = $this->regE;
                break;
            case 0x6C: // MOV L,H
                $this->regL = $this->regH;
                break;
            case 0x6D: // MOV L,L
                break;
            case 0x6E: // MOV L,M
                $addr = ($this->regH << 8) | $this->regL;
                $this->regL = $this->memory[$addr];
                break;
            case 0x6F: // MOV L,A
                $this->regL = $this->regA;
                break;
            case 0x70: // MOV M,B
                $addr = ($this->regH << 8) | $this->regL;
                $this->memory[$addr] = $this->regB;
                break;
            case 0x71: // MOV M,C
                $addr = ($this->regH << 8) | $this->regL;
                $this->memory[$addr] = $this->regC;
                break;
            case 0x72: // MOV M,D
                $addr = ($this->regH << 8) | $this->regL;
                $this->memory[$addr] = $this->regD;
                break;
            case 0x73: // MOV M,E
                $addr = ($this->regH << 8) | $this->regL;
                $this->memory[$addr] = $this->regE;
                break;
            case 0x74: // MOV M,H
                $addr = ($this->regH << 8) | $this->regL;
                $this->memory[$addr] = $this->regH;
                break;
            case 0x75: // MOV M,L
                $addr = ($this->regH << 8) | $this->regL;
                $this->memory[$addr] = $this->regL;
                break;
            case 0x77: // MOV M,A
                $addr = ($this->regH << 8) | $this->regL;
                $this->memory[$addr] = $this->regA;
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
            case 0x88: // ADC B
                $this->adc($this->regB);
                break;
            case 0x89: // ADC C
                $this->adc($this->regC);
                break;
            case 0x8A: // ADC D
                $this->adc($this->regD);
                break;
            case 0x8B: // ADC E
                $this->adc($this->regE);
                break;
            case 0x8C: // ADC H
                $this->adc($this->regH);
                break;
            case 0x8D: // ADC L
                $this->adc($this->regL);
                break;
            case 0x8E: // ADC M
                $addr = ($this->regH << 8) | $this->regL;
                $this->adc($this->memory[$addr]);
                break;
            case 0x8F: // ADC A
                $this->adc($this->regA);
                break;
            case 0x90: // SUB B
                $this->sub($this->regB);
                break;
            case 0x91: // SUB C
                $this->sub($this->regC);
                break;
            case 0x92: // SUB D
                $this->sub($this->regD);
                break;
            case 0x93: // SUB E
                $this->sub($this->regE);
                break;
            case 0x94: // SUB H
                $this->sub($this->regH);
                break;
            case 0x95: // SUB L
                $this->sub($this->regL);
                break;
            case 0x96: // SUB M
                $addr = ($this->regH << 8) | $this->regL;
                $this->sub($this->memory[$addr]);
                break;
            case 0x97: // SUB A
                $this->sub($this->regA);
                break;
            case 0x98: // SBB B
                $this->sbb($this->regB);
                break;
            case 0x99: // SBB C
                $this->sbb($this->regC);
                break;
            case 0x9A: // SBB D
                $this->sbb($this->regD);
                break;
            case 0x9B: // SBB E
                $this->sbb($this->regE);
                break;
            case 0x9C: // SBB H
                $this->sbb($this->regH);
                break;
            case 0x9D: // SBB L
                $this->sbb($this->regL);
                break;
            case 0x9E: // SBB M
                $addr = ($this->regH << 8) | $this->regL;
                $this->sbb($this->memory[$addr]);
                break;
            case 0x9F: // SBB A
                $this->sbb($this->regA);
                break;
            case 0xA0: // ANA B
                $this->ana($this->regB);
                break;
            case 0xA1: // ANA C
                $this->ana($this->regC);
                break;
            case 0xA2: // ANA D
                $this->ana($this->regD);
                break;
            case 0xA3: // ANA E
                $this->ana($this->regE);
                break;
            case 0xA4: // ANA H
                $this->ana($this->regH);
                break;
            case 0xA5: // ANA L
                $this->ana($this->regL);
                break;
            case 0xA6: // ANA M
                $addr = ($this->regH << 8) | $this->regL;
                $this->ana($this->memory[$addr]);
                break;
            case 0xA7: // ANA A
                $this->ana($this->regA);
                break;
            case 0xA8: // XRA B
                $this->xra($this->regB);
                break;
            case 0xA9: // XRA C
                $this->xra($this->regC);
                break;
            case 0xAA: // XRA D
                $this->xra($this->regD);
                break;
            case 0xAB: // XRA E
                $this->xra($this->regE);
                break;
            case 0xAC: // XRA H
                $this->xra($this->regH);
                break;
            case 0xAD: // XRA L
                $this->xra($this->regL);
                break;
            case 0xAE: // XRA M
                $addr = ($this->regH << 8) | $this->regL;
                $this->xra($this->memory[$addr]);
                break;
            case 0xAF: // XRA A
                $this->xra($this->regA);
                break;
            case 0xB0: // ORA B
                $this->ora($this->regB);
                break;
            case 0xB1: // ORA C
                $this->ora($this->regC);
                break;
            case 0xB2: // ORA D
                $this->ora($this->regD);
                break;
            case 0xB3: // ORA E
                $this->ora($this->regE);
                break;
            case 0xB4: // ORA H
                $this->ora($this->regH);
                break;
            case 0xB5: // ORA L
                $this->ora($this->regL);
                break;
            case 0xB6: // ORA M
                $addr = ($this->regH << 8) | $this->regL;
                $this->ora($this->memory[$addr]);
                break;
            case 0xB7: // ORA A
                $this->ora($this->regA);
                break;
            case 0xB8: // CMP B
                $this->cmp($this->regB);
                break;
            case 0xB9: // CMP C
                $this->cmp($this->regC);
                break;
            case 0xBA: // CMP D
                $this->cmp($this->regD);
                break;
            case 0xBB: // CMP E
                $this->cmp($this->regE);
                break;
            case 0xBC: // CMP H
                $this->cmp($this->regH);
                break;
            case 0xBD: // CMP L
                $this->cmp($this->regL);
                break;
            case 0xBE: // CMP M
                $addr = ($this->regH << 8) | $this->regL;
                $this->cmp($this->memory[$addr]);
                break;
            case 0xBF: // CMP A
                $this->cmp($this->regA);
                break;
                
            // Immediate instructions
            case 0x01: // LXI B, data16
                $low = $this->memory[$this->regPC];
                $high = $this->memory[($this->regPC + 1) & 0xFFFF];
                $this->regPC = ($this->regPC + 2) & 0xFFFF;
                $this->regB = $high;
                $this->regC = $low;
                break;
            case 0x11: // LXI D, data16
                $low = $this->memory[$this->regPC];
                $high = $this->memory[($this->regPC + 1) & 0xFFFF];
                $this->regPC = ($this->regPC + 2) & 0xFFFF;
                $this->regD = $high;
                $this->regE = $low;
                break;
            case 0x21: // LXI H, data16
                $low = $this->memory[$this->regPC];
                $high = $this->memory[($this->regPC + 1) & 0xFFFF];
                $this->regPC = ($this->regPC + 2) & 0xFFFF;
                $this->regH = $high;
                $this->regL = $low;
                break;
            case 0x31: // LXI SP, data16
                $low = $this->memory[$this->regPC];
                $high = $this->memory[($this->regPC + 1) & 0xFFFF];
                $this->regPC = ($this->regPC + 2) & 0xFFFF;
                $this->regSP = ($high << 8) | $low;
                break;
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
            case 0xC6: // ADI byte
                $value = $this->memory[$this->regPC];
                $this->regPC = ($this->regPC + 1) & 0xFFFF;
                $this->add($value);
                break;
            case 0xCE: // ACI byte
                $value = $this->memory[$this->regPC];
                $this->regPC = ($this->regPC + 1) & 0xFFFF;
                $this->adc($value);
                break;
            case 0xD6: // SUI byte
                $value = $this->memory[$this->regPC];
                $this->regPC = ($this->regPC + 1) & 0xFFFF;
                $this->sub($value);
                break;
            case 0xDE: // SBI byte
                $value = $this->memory[$this->regPC];
                $this->regPC = ($this->regPC + 1) & 0xFFFF;
                $this->sbb($value);
                break;
            case 0xE6: // ANI byte
                $value = $this->memory[$this->regPC];
                $this->regPC = ($this->regPC + 1) & 0xFFFF;
                $this->ana($value);
                break;
            case 0xEE: // XRI byte
                $value = $this->memory[$this->regPC];
                $this->regPC = ($this->regPC + 1) & 0xFFFF;
                $this->xra($value);
                break;
            case 0xF6: // ORI byte
                $value = $this->memory[$this->regPC];
                $this->regPC = ($this->regPC + 1) & 0xFFFF;
                $this->ora($value);
                break;
            case 0xFE: // CPI byte
                $value = $this->memory[$this->regPC];
                $this->regPC = ($this->regPC + 1) & 0xFFFF;
                $this->cmp($value);
                break;
                
            // Load/Store instructions
            case 0x22: // SHLD addr
                $low = $this->memory[$this->regPC];
                $high = $this->memory[($this->regPC + 1) & 0xFFFF];
                $addr = ($high << 8) | $low;
                $this->regPC = ($this->regPC + 2) & 0xFFFF;
                $this->memory[$addr] = $this->regL;
                $this->memory[($addr + 1) & 0xFFFF] = $this->regH;
                break;
            case 0x2A: // LHLD addr
                $low = $this->memory[$this->regPC];
                $high = $this->memory[($this->regPC + 1) & 0xFFFF];
                $addr = ($high << 8) | $low;
                $this->regPC = ($this->regPC + 2) & 0xFFFF;
                $this->regL = $this->memory[$addr];
                $this->regH = $this->memory[($addr + 1) & 0xFFFF];
                break;
            case 0x32: // STA addr
                $low = $this->memory[$this->regPC];
                $high = $this->memory[($this->regPC + 1) & 0xFFFF];
                $addr = ($high << 8) | $low;
                $this->regPC = ($this->regPC + 2) & 0xFFFF;
                $this->memory[$addr] = $this->regA;
                break;
            case 0x3A: // LDA addr
                $low = $this->memory[$this->regPC];
                $high = $this->memory[($this->regPC + 1) & 0xFFFF];
                $addr = ($high << 8) | $low;
                $this->regPC = ($this->regPC + 2) & 0xFFFF;
                $this->regA = $this->memory[$addr];
                break;
                
            // Jump instructions
            case 0xC3: // JMP address
                $low = $this->memory[$this->regPC];
                $high = $this->memory[($this->regPC + 1) & 0xFFFF];
                $this->regPC = ($high << 8) | $low;
                break;
            case 0xC2: // JNZ address
                $low = $this->memory[$this->regPC];
                $high = $this->memory[($this->regPC + 1) & 0xFFFF];
                $this->regPC = ($this->regPC + 2) & 0xFFFF;
                if (!$this->getFlag(self::FLAG_Z)) {
                    $this->regPC = ($high << 8) | $low;
                }
                break;
            case 0xCA: // JZ address
                $low = $this->memory[$this->regPC];
                $high = $this->memory[($this->regPC + 1) & 0xFFFF];
                $this->regPC = ($this->regPC + 2) & 0xFFFF;
                if ($this->getFlag(self::FLAG_Z)) {
                    $this->regPC = ($high << 8) | $low;
                }
                break;
            case 0xD2: // JNC address
                $low = $this->memory[$this->regPC];
                $high = $this->memory[($this->regPC + 1) & 0xFFFF];
                $this->regPC = ($this->regPC + 2) & 0xFFFF;
                if (!$this->getFlag(self::FLAG_CY)) {
                    $this->regPC = ($high << 8) | $low;
                }
                break;
            case 0xDA: // JC address
                $low = $this->memory[$this->regPC];
                $high = $this->memory[($this->regPC + 1) & 0xFFFF];
                $this->regPC = ($this->regPC + 2) & 0xFFFF;
                if ($this->getFlag(self::FLAG_CY)) {
                    $this->regPC = ($high << 8) | $low;
                }
                break;
            case 0xE2: // JPO address
                $low = $this->memory[$this->regPC];
                $high = $this->memory[($this->regPC + 1) & 0xFFFF];
                $this->regPC = ($this->regPC + 2) & 0xFFFF;
                if (!$this->getFlag(self::FLAG_P)) {
                    $this->regPC = ($high << 8) | $low;
                }
                break;
            case 0xEA: // JPE address
                $low = $this->memory[$this->regPC];
                $high = $this->memory[($this->regPC + 1) & 0xFFFF];
                $this->regPC = ($this->regPC + 2) & 0xFFFF;
                if ($this->getFlag(self::FLAG_P)) {
                    $this->regPC = ($high << 8) | $low;
                }
                break;
            case 0xF2: // JP address
                $low = $this->memory[$this->regPC];
                $high = $this->memory[($this->regPC + 1) & 0xFFFF];
                $this->regPC = ($this->regPC + 2) & 0xFFFF;
                if (!$this->getFlag(self::FLAG_S)) {
                    $this->regPC = ($high << 8) | $low;
                }
                break;
            case 0xFA: // JM address
                $low = $this->memory[$this->regPC];
                $high = $this->memory[($this->regPC + 1) & 0xFFFF];
                $this->regPC = ($this->regPC + 2) & 0xFFFF;
                if ($this->getFlag(self::FLAG_S)) {
                    $this->regPC = ($high << 8) | $low;
                }
                break;
                
            // Call instructions
            case 0xCD: // CALL address
                $low = $this->memory[$this->regPC];
                $high = $this->memory[($this->regPC + 1) & 0xFFFF];
                $this->regPC = ($this->regPC + 2) & 0xFFFF;
                $this->memory[($this->regSP - 1) & 0xFFFF] = ($this->regPC >> 8) & 0xFF;
                $this->memory[($this->regSP - 2) & 0xFFFF] = $this->regPC & 0xFF;
                $this->regSP = ($this->regSP - 2) & 0xFFFF;
                $this->regPC = ($high << 8) | $low;
                break;
            case 0xC4: // CNZ address
                $low = $this->memory[$this->regPC];
                $high = $this->memory[($this->regPC + 1) & 0xFFFF];
                $this->regPC = ($this->regPC + 2) & 0xFFFF;
                if (!$this->getFlag(self::FLAG_Z)) {
                    $this->memory[($this->regSP - 1) & 0xFFFF] = ($this->regPC >> 8) & 0xFF;
                    $this->memory[($this->regSP - 2) & 0xFFFF] = $this->regPC & 0xFF;
                    $this->regSP = ($this->regSP - 2) & 0xFFFF;
                    $this->regPC = ($high << 8) | $low;
                }
                break;
            case 0xCC: // CZ address
                $low = $this->memory[$this->regPC];
                $high = $this->memory[($this->regPC + 1) & 0xFFFF];
                $this->regPC = ($this->regPC + 2) & 0xFFFF;
                if ($this->getFlag(self::FLAG_Z)) {
                    $this->memory[($this->regSP - 1) & 0xFFFF] = ($this->regPC >> 8) & 0xFF;
                    $this->memory[($this->regSP - 2) & 0xFFFF] = $this->regPC & 0xFF;
                    $this->regSP = ($this->regSP - 2) & 0xFFFF;
                    $this->regPC = ($high << 8) | $low;
                }
                break;
            case 0xD4: // CNC address
                $low = $this->memory[$this->regPC];
                $high = $this->memory[($this->regPC + 1) & 0xFFFF];
                $this->regPC = ($this->regPC + 2) & 0xFFFF;
                if (!$this->getFlag(self::FLAG_CY)) {
                    $this->memory[($this->regSP - 1) & 0xFFFF] = ($this->regPC >> 8) & 0xFF;
                    $this->memory[($this->regSP - 2) & 0xFFFF] = $this->regPC & 0xFF;
                    $this->regSP = ($this->regSP - 2) & 0xFFFF;
                    $this->regPC = ($high << 8) | $low;
                }
                break;
            case 0xDC: // CC address
                $low = $this->memory[$this->regPC];
                $high = $this->memory[($this->regPC + 1) & 0xFFFF];
                $this->regPC = ($this->regPC + 2) & 0xFFFF;
                if ($this->getFlag(self::FLAG_CY)) {
                    $this->memory[($this->regSP - 1) & 0xFFFF] = ($this->regPC >> 8) & 0xFF;
                    $this->memory[($this->regSP - 2) & 0xFFFF] = $this->regPC & 0xFF;
                    $this->regSP = ($this->regSP - 2) & 0xFFFF;
                    $this->regPC = ($high << 8) | $low;
                }
                break;
            case 0xE4: // CPO address
                $low = $this->memory[$this->regPC];
                $high = $this->memory[($this->regPC + 1) & 0xFFFF];
                $this->regPC = ($this->regPC + 2) & 0xFFFF;
                if (!$this->getFlag(self::FLAG_P)) {
                    $this->memory[($this->regSP - 1) & 0xFFFF] = ($this->regPC >> 8) & 0xFF;
                    $this->memory[($this->regSP - 2) & 0xFFFF] = $this->regPC & 0xFF;
                    $this->regSP = ($this->regSP - 2) & 0xFFFF;
                    $this->regPC = ($high << 8) | $low;
                }
                break;
            case 0xEC: // CPE address
                $low = $this->memory[$this->regPC];
                $high = $this->memory[($this->regPC + 1) & 0xFFFF];
                $this->regPC = ($this->regPC + 2) & 0xFFFF;
                if ($this->getFlag(self::FLAG_P)) {
                    $this->memory[($this->regSP - 1) & 0xFFFF] = ($this->regPC >> 8) & 0xFF;
                    $this->memory[($this->regSP - 2) & 0xFFFF] = $this->regPC & 0xFF;
                    $this->regSP = ($this->regSP - 2) & 0xFFFF;
                    $this->regPC = ($high << 8) | $low;
                }
                break;
            case 0xF4: // CP address
                $low = $this->memory[$this->regPC];
                $high = $this->memory[($this->regPC + 1) & 0xFFFF];
                $this->regPC = ($this->regPC + 2) & 0xFFFF;
                if (!$this->getFlag(self::FLAG_S)) {
                    $this->memory[($this->regSP - 1) & 0xFFFF] = ($this->regPC >> 8) & 0xFF;
                    $this->memory[($this->regSP - 2) & 0xFFFF] = $this->regPC & 0xFF;
                    $this->regSP = ($this->regSP - 2) & 0xFFFF;
                    $this->regPC = ($high << 8) | $low;
                }
                break;
            case 0xFC: // CM address
                $low = $this->memory[$this->regPC];
                $high = $this->memory[($this->regPC + 1) & 0xFFFF];
                $this->regPC = ($this->regPC + 2) & 0xFFFF;
                if ($this->getFlag(self::FLAG_S)) {
                    $this->memory[($this->regSP - 1) & 0xFFFF] = ($this->regPC >> 8) & 0xFF;
                    $this->memory[($this->regSP - 2) & 0xFFFF] = $this->regPC & 0xFF;
                    $this->regSP = ($this->regSP - 2) & 0xFFFF;
                    $this->regPC = ($high << 8) | $low;
                }
                break;
                
            // Return instructions
            case 0xC9: // RET
                $low = $this->memory[$this->regSP];
                $high = $this->memory[($this->regSP + 1) & 0xFFFF];
                $this->regSP = ($this->regSP + 2) & 0xFFFF;
                $this->regPC = ($high << 8) | $low;
                break;
            case 0xC0: // RNZ
                if (!$this->getFlag(self::FLAG_Z)) {
                    $low = $this->memory[$this->regSP];
                    $high = $this->memory[($this->regSP + 1) & 0xFFFF];
                    $this->regSP = ($this->regSP + 2) & 0xFFFF;
                    $this->regPC = ($high << 8) | $low;
                } else {
                    $this->regSP = ($this->regSP + 2) & 0xFFFF;
                }
                break;
            case 0xC8: // RZ
                if ($this->getFlag(self::FLAG_Z)) {
                    $low = $this->memory[$this->regSP];
                    $high = $this->memory[($this->regSP + 1) & 0xFFFF];
                    $this->regSP = ($this->regSP + 2) & 0xFFFF;
                    $this->regPC = ($high << 8) | $low;
                } else {
                    $this->regSP = ($this->regSP + 2) & 0xFFFF;
                }
                break;
            case 0xD0: // RNC
                if (!$this->getFlag(self::FLAG_CY)) {
                    $low = $this->memory[$this->regSP];
                    $high = $this->memory[($this->regSP + 1) & 0xFFFF];
                    $this->regSP = ($this->regSP + 2) & 0xFFFF;
                    $this->regPC = ($high << 8) | $low;
                } else {
                    $this->regSP = ($this->regSP + 2) & 0xFFFF;
                }
                break;
            case 0xD8: // RC
                if ($this->getFlag(self::FLAG_CY)) {
                    $low = $this->memory[$this->regSP];
                    $high = $this->memory[($this->regSP + 1) & 0xFFFF];
                    $this->regSP = ($this->regSP + 2) & 0xFFFF;
                    $this->regPC = ($high << 8) | $low;
                } else {
                    $this->regSP = ($this->regSP + 2) & 0xFFFF;
                }
                break;
            case 0xE0: // RPO
                if (!$this->getFlag(self::FLAG_P)) {
                    $low = $this->memory[$this->regSP];
                    $high = $this->memory[($this->regSP + 1) & 0xFFFF];
                    $this->regSP = ($this->regSP + 2) & 0xFFFF;
                    $this->regPC = ($high << 8) | $low;
                } else {
                    $this->regSP = ($this->regSP + 2) & 0xFFFF;
                }
                break;
            case 0xE8: // RPE
                if ($this->getFlag(self::FLAG_P)) {
                    $low = $this->memory[$this->regSP];
                    $high = $this->memory[($this->regSP + 1) & 0xFFFF];
                    $this->regSP = ($this->regSP + 2) & 0xFFFF;
                    $this->regPC = ($high << 8) | $low;
                } else {
                    $this->regSP = ($this->regSP + 2) & 0xFFFF;
                }
                break;
            case 0xF0: // RP
                if (!$this->getFlag(self::FLAG_S)) {
                    $low = $this->memory[$this->regSP];
                    $high = $this->memory[($this->regSP + 1) & 0xFFFF];
                    $this->regSP = ($this->regSP + 2) & 0xFFFF;
                    $this->regPC = ($high << 8) | $low;
                } else {
                    $this->regSP = ($this->regSP + 2) & 0xFFFF;
                }
                break;
            case 0xF8: // RM
                if ($this->getFlag(self::FLAG_S)) {
                    $low = $this->memory[$this->regSP];
                    $high = $this->memory[($this->regSP + 1) & 0xFFFF];
                    $this->regSP = ($this->regSP + 2) & 0xFFFF;
                    $this->regPC = ($high << 8) | $low;
                } else {
                    $this->regSP = ($this->regSP + 2) & 0xFFFF;
                }
                break;
                
            // Restart instructions
            case 0xC0: // RST 0
            case 0xC8: // RST 1
            case 0xD0: // RST 2
            case 0xD8: // RST 3
            case 0xE0: // RST 4
            case 0xE8: // RST 5
            case 0xF0: // RST 6
            case 0xF8: // RST 7
                $this->memory[($this->regSP - 1) & 0xFFFF] = ($this->regPC >> 8) & 0xFF;
                $this->memory[($this->regSP - 2) & 0xFFFF] = $this->regPC & 0xFF;
                $this->regSP = ($this->regSP - 2) & 0xFFFF;
                $this->regPC = (($opcode >> 3) & 0x07) * 8;
                break;
            case 0xC7: // RST 0
                $this->memory[($this->regSP - 1) & 0xFFFF] = ($this->regPC >> 8) & 0xFF;
                $this->memory[($this->regSP - 2) & 0xFFFF] = $this->regPC & 0xFF;
                $this->regSP = ($this->regSP - 2) & 0xFFFF;
                $this->regPC = 0x0000;
                break;
            case 0xCF: // RST 1
                $this->memory[($this->regSP - 1) & 0xFFFF] = ($this->regPC >> 8) & 0xFF;
                $this->memory[($this->regSP - 2) & 0xFFFF] = $this->regPC & 0xFF;
                $this->regSP = ($this->regSP - 2) & 0xFFFF;
                $this->regPC = 0x0008;
                break;
            case 0xD7: // RST 2
                $this->memory[($this->regSP - 1) & 0xFFFF] = ($this->regPC >> 8) & 0xFF;
                $this->memory[($this->regSP - 2) & 0xFFFF] = $this->regPC & 0xFF;
                $this->regSP = ($this->regSP - 2) & 0xFFFF;
                $this->regPC = 0x0010;
                break;
            case 0xDF: // RST 3
                $this->memory[($this->regSP - 1) & 0xFFFF] = ($this->regPC >> 8) & 0xFF;
                $this->memory[($this->regSP - 2) & 0xFFFF] = $this->regPC & 0xFF;
                $this->regSP = ($this->regSP - 2) & 0xFFFF;
                $this->regPC = 0x0018;
                break;
            case 0xE7: // RST 4
                $this->memory[($this->regSP - 1) & 0xFFFF] = ($this->regPC >> 8) & 0xFF;
                $this->memory[($this->regSP - 2) & 0xFFFF] = $this->regPC & 0xFF;
                $this->regSP = ($this->regSP - 2) & 0xFFFF;
                $this->regPC = 0x0020;
                break;
            case 0xEF: // RST 5
                $this->memory[($this->regSP - 1) & 0xFFFF] = ($this->regPC >> 8) & 0xFF;
                $this->memory[($this->regSP - 2) & 0xFFFF] = $this->regPC & 0xFF;
                $this->regSP = ($this->regSP - 2) & 0xFFFF;
                $this->regPC = 0x0028;
                break;
            case 0xF7: // RST 6
                $this->memory[($this->regSP - 1) & 0xFFFF] = ($this->regPC >> 8) & 0xFF;
                $this->memory[($this->regSP - 2) & 0xFFFF] = $this->regPC & 0xFF;
                $this->regSP = ($this->regSP - 2) & 0xFFFF;
                $this->regPC = 0x0030;
                break;
            case 0xFF: // RST 7
                $this->memory[($this->regSP - 1) & 0xFFFF] = ($this->regPC >> 8) & 0xFF;
                $this->memory[($this->regSP - 2) & 0xFFFF] = $this->regPC & 0xFF;
                $this->regSP = ($this->regSP - 2) & 0xFFFF;
                $this->regPC = 0x0038;
                break;
                
            // Push instructions
            case 0xC5: // PUSH B
                $this->memory[($this->regSP - 1) & 0xFFFF] = $this->regB;
                $this->memory[($this->regSP - 2) & 0xFFFF] = $this->regC;
                $this->regSP = ($this->regSP - 2) & 0xFFFF;
                break;
            case 0xD5: // PUSH D
                $this->memory[($this->regSP - 1) & 0xFFFF] = $this->regD;
                $this->memory[($this->regSP - 2) & 0xFFFF] = $this->regE;
                $this->regSP = ($this->regSP - 2) & 0xFFFF;
                break;
            case 0xE5: // PUSH H
                $this->memory[($this->regSP - 1) & 0xFFFF] = $this->regH;
                $this->memory[($this->regSP - 2) & 0xFFFF] = $this->regL;
                $this->regSP = ($this->regSP - 2) & 0xFFFF;
                break;
            case 0xF5: // PUSH PSW
                $this->memory[($this->regSP - 1) & 0xFFFF] = $this->regA;
                $this->memory[($this->regSP - 2) & 0xFFFF] = $this->flags;
                $this->regSP = ($this->regSP - 2) & 0xFFFF;
                break;
                
            // Pop instructions
            case 0xC1: // POP B
                $this->regC = $this->memory[$this->regSP];
                $this->regB = $this->memory[($this->regSP + 1) & 0xFFFF];
                $this->regSP = ($this->regSP + 2) & 0xFFFF;
                break;
            case 0xD1: // POP D
                $this->regE = $this->memory[$this->regSP];
                $this->regD = $this->memory[($this->regSP + 1) & 0xFFFF];
                $this->regSP = ($this->regSP + 2) & 0xFFFF;
                break;
            case 0xE1: // POP H
                $this->regL = $this->memory[$this->regSP];
                $this->regH = $this->memory[($this->regSP + 1) & 0xFFFF];
                $this->regSP = ($this->regSP + 2) & 0xFFFF;
                break;
            case 0xF1: // POP PSW
                $this->flags = $this->memory[$this->regSP];
                $this->regA = $this->memory[($this->regSP + 1) & 0xFFFF];
                $this->regSP = ($this->regSP + 2) & 0xFFFF;
                // Ensure bit 1 is always 1
                $this->flags |= 0x02;
                break;
                
            // Exchange instructions
            case 0xEB: // XCHG
                $tempD = $this->regD;
                $tempE = $this->regE;
                $this->regD = $this->regH;
                $this->regE = $this->regL;
                $this->regH = $tempD;
                $this->regL = $tempE;
                break;
            case 0xE3: // XTHL
                $tempL = $this->regL;
                $tempH = $this->regH;
                $this->regL = $this->memory[$this->regSP];
                $this->regH = $this->memory[($this->regSP + 1) & 0xFFFF];
                $this->memory[$this->regSP] = $tempL;
                $this->memory[($this->regSP + 1) & 0xFFFF] = $tempH;
                break;
            case 0xF9: // SPHL
                $this->regSP = ($this->regH << 8) | $this->regL;
                break;
                
            // Undocumented NOP instructions
            case 0x08: // NOP
            case 0x10: // NOP
            case 0x18: // NOP
            case 0x20: // NOP
            case 0x28: // NOP
            case 0x30: // NOP
            case 0x38: // NOP
                break;
                
            // Increment register pair instructions
            case 0x03: // INX B
                $value = ($this->regB << 8) | $this->regC;
                $value = ($value + 1) & 0xFFFF;
                $this->regB = ($value >> 8) & 0xFF;
                $this->regC = $value & 0xFF;
                break;
            case 0x13: // INX D
                $value = ($this->regD << 8) | $this->regE;
                $value = ($value + 1) & 0xFFFF;
                $this->regD = ($value >> 8) & 0xFF;
                $this->regE = $value & 0xFF;
                break;
            case 0x23: // INX H
                $value = ($this->regH << 8) | $this->regL;
                $value = ($value + 1) & 0xFFFF;
                $this->regH = ($value >> 8) & 0xFF;
                $this->regL = $value & 0xFF;
                break;
            case 0x33: // INX SP
                $this->regSP = ($this->regSP + 1) & 0xFFFF;
                break;
                
            // Decrement register pair instructions
            case 0x0B: // DCX B
                $value = ($this->regB << 8) | $this->regC;
                $value = ($value - 1) & 0xFFFF;
                $this->regB = ($value >> 8) & 0xFF;
                $this->regC = $value & 0xFF;
                break;
            case 0x1B: // DCX D
                $value = ($this->regD << 8) | $this->regE;
                $value = ($value - 1) & 0xFFFF;
                $this->regD = ($value >> 8) & 0xFF;
                $this->regE = $value & 0xFF;
                break;
            case 0x2B: // DCX H
                $value = ($this->regH << 8) | $this->regL;
                $value = ($value - 1) & 0xFFFF;
                $this->regH = ($value >> 8) & 0xFF;
                $this->regL = $value & 0xFF;
                break;
            case 0x3B: // DCX SP
                $this->regSP = ($this->regSP - 1) & 0xFFFF;
                break;
                
            // Add register pair to HL instructions
            case 0x09: // DAD B
                $this->dad('BC');
                break;
            case 0x19: // DAD D
                $this->dad('DE');
                break;
            case 0x29: // DAD H
                $this->dad('HL');
                break;
            case 0x39: // DAD SP
                $hl = ($this->regH << 8) | $this->regL;
                $result = $hl + $this->regSP;
                $this->setFlag(self::FLAG_CY, $result > 0xFFFF);
                $this->regH = ($result >> 8) & 0xFF;
                $this->regL = $result & 0xFF;
                break;
                
            // Increment register instructions
            case 0x04: // INR B
                $this->inr('B');
                break;
            case 0x0C: // INR C
                $this->inr('C');
                break;
            case 0x14: // INR D
                $this->inr('D');
                break;
            case 0x1C: // INR E
                $this->inr('E');
                break;
            case 0x24: // INR H
                $this->inr('H');
                break;
            case 0x2C: // INR L
                $this->inr('L');
                break;
            case 0x34: // INR M
                $addr = ($this->regH << 8) | $this->regL;
                $value = $this->memory[$addr];
                $result = ($value + 1) & 0xFF;
                $this->memory[$addr] = $result;
                $this->setFlag(self::FLAG_S, ($result & 0x80) != 0);
                $this->setFlag(self::FLAG_Z, $result == 0);
                $ac = (($value & 0x0F) + 1) > 0x0F;
                $this->setFlag(self::FLAG_AC, $ac);
                $parity = 0;
                $temp = $result;
                for ($i = 0; $i < 8; $i++) {
                    $parity ^= ($temp & 1);
                    $temp >>= 1;
                }
                $this->setFlag(self::FLAG_P, $parity == 0);
                break;
            case 0x3C: // INR A
                $this->inr('A');
                break;
                
            // Decrement register instructions
            case 0x05: // DCR B
                $this->dcr('B');
                break;
            case 0x0D: // DCR C
                $this->dcr('C');
                break;
            case 0x15: // DCR D
                $this->dcr('D');
                break;
            case 0x1D: // DCR E
                $this->dcr('E');
                break;
            case 0x25: // DCR H
                $this->dcr('H');
                break;
            case 0x2D: // DCR L
                $this->dcr('L');
                break;
            case 0x35: // DCR M
                $addr = ($this->regH << 8) | $this->regL;
                $value = $this->memory[$addr];
                $result = ($value - 1) & 0xFF;
                $this->memory[$addr] = $result;
                $this->setFlag(self::FLAG_S, ($result & 0x80) != 0);
                $this->setFlag(self::FLAG_Z, $result == 0);
                $ac = ($value & 0x0F) < 1;
                $this->setFlag(self::FLAG_AC, $ac);
                $parity = 0;
                $temp = $result;
                for ($i = 0; $i < 8; $i++) {
                    $parity ^= ($temp & 1);
                    $temp >>= 1;
                }
                $this->setFlag(self::FLAG_P, $parity == 0);
                break;
            case 0x3D: // DCR A
                $this->dcr('A');
                break;
                
            // Rotate instructions
            case 0x07: // RLC
                $cy = ($this->regA & 0x80) != 0;
                $this->regA = (($this->regA << 1) | ($cy ? 1 : 0)) & 0xFF;
                $this->setFlag(self::FLAG_CY, $cy);
                break;
            case 0x0F: // RRC
                $cy = ($this->regA & 0x01) != 0;
                $this->regA = (($this->regA >> 1) | ($cy ? 0x80 : 0)) & 0xFF;
                $this->setFlag(self::FLAG_CY, $cy);
                break;
            case 0x17: // RAL
                $cy = $this->getFlag(self::FLAG_CY);
                $newCy = ($this->regA & 0x80) != 0;
                $this->regA = (($this->regA << 1) | ($cy ? 1 : 0)) & 0xFF;
                $this->setFlag(self::FLAG_CY, $newCy);
                break;
            case 0x1F: // RAR
                $cy = $this->getFlag(self::FLAG_CY);
                $newCy = ($this->regA & 0x01) != 0;
                $this->regA = (($this->regA >> 1) | ($cy ? 0x80 : 0)) & 0xFF;
                $this->setFlag(self::FLAG_CY, $newCy);
                break;
                
            // Special instructions
            case 0x27: // DAA
                $this->daa();
                break;
            case 0x2F: // CMA
                $this->regA = (~$this->regA) & 0xFF;
                break;
            case 0x37: // STC
                $this->setFlag(self::FLAG_CY, true);
                break;
            case 0x3F: // CMC
                $this->setFlag(self::FLAG_CY, !$this->getFlag(self::FLAG_CY));
                break;
                
            // Input/Output instructions
            case 0xDB: // IN port
                // For emulation purposes, we'll just increment PC and do nothing
                $this->regPC = ($this->regPC + 1) & 0xFFFF;
                break;
            case 0xD3: // OUT port
                // For emulation purposes, we'll just increment PC and do nothing
                $this->regPC = ($this->regPC + 1) & 0xFFFF;
                break;
                
            // HLT
            case 0x76: // HLT
                // Halt - for now we'll just return
                return;
                
            // NOP
            case 0x00: // NOP
                break;
                
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
