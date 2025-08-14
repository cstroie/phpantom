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
    <!-- PicoCSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@picocss/pico@1/css/pico.min.css">
    <style>
        :root {
            --primary: #4CAF50;
            --primary-hover: #45a049;
            --primary-focus: rgba(76, 175, 80, 0.125);
            --primary-inverse: #FFF;
        }
        
        body {
            background-color: #1e1e1e;
            color: #e0e0e0;
            font-family: 'Courier New', Courier, monospace;
        }
        
        header, main, footer {
            padding: 2rem 1rem;
        }
        
        .card {
            background-color: #2d2d2d;
            border: 1px solid #444;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .program {
            background-color: #1e1e1e;
            color: #4CAF50;
            padding: 1rem;
            border-radius: 5px;
            font-family: 'Courier New', Courier, monospace;
            margin: 1rem 0;
            overflow-x: auto;
        }
        
        .step {
            border-left: 4px solid var(--primary);
            padding: 1rem 1.5rem;
            margin: 1rem 0;
            background-color: #2a2a2a;
        }
        
        .registers, .flags {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            gap: 0.75rem;
            margin: 1rem 0;
        }
        
        .register, .flag {
            background-color: #333;
            padding: 0.75rem;
            border-radius: 4px;
            text-align: center;
        }
        
        .flag.set {
            background-color: var(--primary);
            color: var(--primary-inverse);
            font-weight: bold;
        }
        
        h1, h2, h3, h4, h5 {
            color: #4CAF50;
        }
        
        a {
            color: #4CAF50;
        }
        
        a:hover {
            color: #45a049;
        }
        
        code {
            background-color: #1e1e1e;
            color: #4CAF50;
        }
    </style>
</head>
<body>
    <main class="container">
        <header>
            <h1>PHPantom - Intel 8080 Emulator</h1>
            <h2>Demo: 5 + 3 Calculation</h2>
        </header>
        
        <div class="card">
            <h3>Program</h3>
            <div class="program">
                0x3E, 0x05  ; MVI A, 0x05    ; Load 5 into register A<br>
                0x06, 0x03  ; MVI B, 0x03    ; Load 3 into register B<br>
                0x80        ; ADD B          ; Add B to A<br>
                0x76        ; HLT            ; Halt
            </div>
            <p>This simple program loads the values 5 and 3 into registers A and B respectively, then adds them together.</p>
        </div>
        
        <div class="card">
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
        
        <div class="card">
            <h3>Final Result</h3>
            <p>The calculation 5 + 3 = <?= $cpu->getRegister('A') ?> was successfully executed.</p>
            <p>Register A contains the result: 0x<?= sprintf('%02X', $cpu->getRegister('A')) ?> (<?= $cpu->getRegister('A') ?> in decimal)</p>
        </div>
        
        <div class="card">
            <h3>About PHPantom</h3>
            <p>PHPantom is a complete Intel 8080 CPU emulator implemented in PHP. It accurately simulates the behavior of the Intel 8080 microprocessor, including all 256 opcodes, registers, flags, and memory operations.</p>
            <p>This emulator can run original Intel 8080 machine code and is suitable for educational purposes, retro computing, and historical software preservation.</p>
        </div>
        
        <footer>
            <p>PHPantom - Ghost of Past Computing | Intel 8080 Emulator in PHP</p>
        </footer>
    </main>
</body>
</html>
