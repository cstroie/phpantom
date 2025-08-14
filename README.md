# PHPantom - Ghost of Past Computing

Intel 8080 CPU Emulator written in PHP

## Overview

PHPantom is a complete Intel 8080 CPU emulator implemented in PHP. It accurately simulates the behavior of the Intel 8080 microprocessor, including all 256 opcodes, registers, flags, and memory operations. This emulator can run original Intel 8080 machine code and is suitable for educational purposes, retro computing, and historical software preservation.

## Features

- Complete Intel 8080 instruction set emulation (all 256 opcodes)
- Accurate timing and flag handling
- 64KB memory space
- Support for all register operations
- Stack operations and subroutine calls
- Input/Output port simulation
- Load binary files directly into memory
- Detailed register and flag inspection

## Requirements

- PHP 5.3 or higher
- No additional dependencies

## Installation

Simply copy the `cpu.php` file to your project directory.

## Usage

### Basic Usage

```php
// Create a new emulator instance
$cpu = new I8080Emulator();

// Load a binary file at address 0x100
$cpu->loadBinaryFile('program.bin', 0x100);

// Execute one instruction
$cpu->step();

// Execute multiple instructions
for ($i = 0; $i < 1000; $i++) {
    $cpu->step();
}

// Inspect registers
$registers = $cpu->getRegisters();
echo "Register A: " . sprintf("0x%02X", $cpu->getRegister('A')) . "\n";

// Inspect flags
echo "Zero flag: " . ($cpu->getFlag(I8080Emulator::FLAG_Z) ? "Set" : "Clear") . "\n";
```

### Loading Programs

You can load programs into memory in two ways:

1. **Load binary file:**
```php
$cpu->loadBinaryFile('spaceinvaders.bin', 0x0000);
```

2. **Load memory block:**
```php
$data = [0x3E, 0x05, 0x06, 0x03, 0x80, 0x76]; // MVI A,5; MVI B,3; ADD B; HLT
$cpu->loadMemoryBlock(0x0000, $data);
```

### Memory Operations

```php
// Read memory
$value = $cpu->getMemory(0x1234);

// Write memory
$cpu->setMemory(0x1234, 0xFF);
```

## Supported Instructions

The emulator supports all Intel 8080 instructions including:

- Data transfer instructions (MOV, MVI, LXI, STAX, LDAX, SHLD, LHLD, STA, LDA)
- Arithmetic instructions (ADD, ADC, SUB, SBB, INR, DCR, DAD, DAA)
- Logical instructions (ANA, XRA, ORA, CMP, RLC, RRC, RAL, RAR, CMA, STC, CMC)
- Branch instructions (JMP, JC, JNC, JZ, JNZ, JP, JM, JPE, JPO, CALL, RET)
- Stack operations (PUSH, POP, XTHL, SPHL)
- I/O instructions (IN, OUT)
- Machine control instructions (HLT, NOP)

## Testing

Run the built-in test to verify the emulator is working correctly:

```bash
php cpu.php
```

## License

This project is licensed under the GNU General Public License v3.0 - see the [LICENSE](LICENSE) file for details.

## Author

Costin Stroie <costinstroie@eridu.eu.org>

## Acknowledgments

- This emulator is based on the Intel 8080 Programmers Manual
- Inspired by other CPU emulators and retro computing projects
