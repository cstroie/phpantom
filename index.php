<?php
/**
 * PHPantom - Ghost of Past Computing
 * 
 * Intel 8080 CPU Emulator Demo
 * 
 * This file demonstrates the I8080 emulator in a web interface.
 */

require_once 'i8080.php';

// Initialize emulator
$cpu = new I8080();

// Example program: Calculate 5 + 3
$program = [
    0x3E, 0x05,  // MVI A, 0x05    ; Load 5 into register A
    0x06, 0x03,  // MVI B, 0x03    ; Load 3 into register B
    0x80,        // ADD B          ; Add B to A
    0x76         // HLT            ; Halt
];

// Load program into memory at address 0x0000
$cpu->loadMemoryBlock(0x0000, $program);

// Execute program step by step
$steps = [];
$steps[] = [
    'pc' => $cpu->getRegister('PC'),
    'registers' => $cpu->getRegisters(),
    'flags' => [
        'S' => $cpu->getFlag(I8080::FLAG_S),
        'Z' => $cpu->getFlag(I8080::FLAG_Z),
        'AC' => $cpu->getFlag(I8080::FLAG_AC),
        'P' => $cpu->getFlag(I8080::FLAG_P),
        'CY' => $cpu->getFlag(I8080::FLAG_CY)
    ],
    'instruction' => 'Initial state'
];

// Execute each instruction
while ($cpu->getRegister('PC') < count($program)) {
    $pcBefore = $cpu->getRegister('PC');
    $opcode = $cpu->getMemory($pcBefore);
    
    // Get instruction name for display
    $instructionName = getInstructionName($opcode);
    
    // Execute one step
    $cpu->step();
    
    $steps[] = [
        'pc' => $cpu->getRegister('PC'),
        'registers' => $cpu->getRegisters(),
        'flags' => [
            'S' => $cpu->getFlag(I8080::FLAG_S),
            'Z' => $cpu->getFlag(I8080::FLAG_Z),
            'AC' => $cpu->getFlag(I8080::FLAG_AC),
            'P' => $cpu->getFlag(I8080::FLAG_P),
            'CY' => $cpu->getFlag(I8080::FLAG_CY)
        ],
        'instruction' => $instructionName . ' (0x' . sprintf('%02X', $opcode) . ')'
    ];
    
    // Stop if halted
    if ($opcode === 0x76) {
        break;
    }
}

// Helper function to get instruction names
function getInstructionName($opcode) {
    $instructions = [
        0x3E => 'MVI A,byte',
        0x06 => 'MVI B,byte',
        0x80 => 'ADD B',
        0x76 => 'HLT'
    ];
    
    return isset($instructions[$opcode]) ? $instructions[$opcode] : 'Unknown';
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PHPantom - Intel 8080 Emulator Demo</title>
    <style>
        body {
            font-family: 'Courier New', Courier, monospace;
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        h1, h2 {
            color: #333;
            text-align: center;
        }
        .container {
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .program {
            background-color: #2d2d2d;
            color: #f8f8f2;
            padding: 15px;
            border-radius: 5px;
            font-family: 'Courier New', Courier, monospace;
            margin: 15px 0;
        }
        .step {
            border-left: 4px solid #4CAF50;
            padding: 10px 15px;
            margin: 15px 0;
            background-color: #f9f9f9;
        }
        .registers {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            gap: 10px;
            margin: 10px 0;
        }
        .register {
            background-color: #e8f5e9;
            padding: 8px;
            border-radius: 4px;
            text-align: center;
        }
        .flags {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(60px, 1fr));
            gap: 5px;
            margin: 10px 0;
        }
        .flag {
            background-color: #e3f2fd;
            padding: 5px;
            border-radius: 4px;
            text-align: center;
            font-size: 0.9em;
        }
        .flag.set {
            background-color: #ffebee;
            font-weight: bold;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            color: #666;
            font-size: 0.9em;
        }
    </style>
</head>
<body>
    <h1>PHPantom - Intel 8080 Emulator</h1>
    <h2>Demo: 5 + 3 Calculation</h2>
    
    <div class="container">
        <h3>Program</h3>
        <div class="program">
            0x3E, 0x05  ; MVI A, 0x05    ; Load 5 into register A<br>
            0x06, 0x03  ; MVI B, 0x03    ; Load 3 into register B<br>
            0x80        ; ADD B          ; Add B to A<br>
            0x76        ; HLT            ; Halt
        </div>
        <p>This simple program loads the values 5 and 3 into registers A and B respectively, then adds them together.</p>
    </div>
    
    <div class="container">
        <h3>Execution Steps</h3>
        <?php foreach ($steps as $index => $step): ?>
        <div class="step">
            <h4>Step <?= $index ?>: <?= htmlspecialchars($step['instruction']) ?></h4>
            <p>Program Counter: 0x<?= sprintf('%04X', $step['pc']) ?></p>
            
            <h5>Registers</h5>
            <div class="registers">
                <?php foreach (['A', 'B', 'C', 'D', 'E', 'H', 'L', 'SP', 'PC'] as $reg): ?>
                <div class="register">
                    <strong><?= $reg ?></strong><br>
                    0x<?= sprintf('%02X', $step['registers'][$reg]) ?>
                </div>
                <?php endforeach; ?>
            </div>
            
            <h5>Flags</h5>
            <div class="flags">
                <?php foreach ($step['flags'] as $flag => $value): ?>
                <div class="flag <?= $value ? 'set' : '' ?>">
                    <strong><?= $flag ?></strong><br>
                    <?= $value ? '1' : '0' ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <div class="container">
        <h3>Final Result</h3>
        <p>The calculation 5 + 3 = <?= $cpu->getRegister('A') ?> was successfully executed.</p>
        <p>Register A contains the result: 0x<?= sprintf('%02X', $cpu->getRegister('A')) ?> (<?= $cpu->getRegister('A') ?> in decimal)</p>
    </div>
    
    <div class="container">
        <h3>About PHPantom</h3>
        <p>PHPantom is a complete Intel 8080 CPU emulator implemented in PHP. It accurately simulates the behavior of the Intel 8080 microprocessor, including all 256 opcodes, registers, flags, and memory operations.</p>
        <p>This emulator can run original Intel 8080 machine code and is suitable for educational purposes, retro computing, and historical software preservation.</p>
    </div>
    
    <div class="footer">
        <p>PHPantom - Ghost of Past Computing | Intel 8080 Emulator in PHP</p>
    </div>
</body>
</html>
