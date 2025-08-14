<?php
/**
 * Altair 8800 Emulator with CP/M Demo
 * 
 * This file demonstrates the Altair 8800 emulator with CP/M support
 * and redirects serial output to the browser.
 */

require_once 'altair8800.php';

// Start output buffering to capture all output
ob_start();

// Create the Altair CP/M system
$system = new AltairCPMSystem();

// Boot the system
$system->boot();

// Process any POST input
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['command'])) {
    $command = $_POST['command'];
    // Add command to system input with carriage return
    $system->sendInput($command . "\r\n");
}

// Run the system for a number of instructions
$system->run(50000);

// Get the output from the system
$output = $system->getOutput();

// Get current register state
$registers = $system->getRegisters();

// End output buffering
ob_end_clean();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Altair 8800 Emulator with CP/M</title>
    <style>
        body {
            font-family: 'Courier New', Courier, monospace;
            background-color: #000;
            color: #0f0;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
        }
        .terminal {
            background-color: #000;
            border: 2px solid #0f0;
            padding: 15px;
            margin-bottom: 20px;
            height: 300px;
            overflow-y: auto;
            white-space: pre-wrap;
        }
        .input-area {
            display: flex;
            margin-bottom: 20px;
        }
        .input-area input {
            flex: 1;
            background-color: #000;
            color: #0f0;
            border: 1px solid #0f0;
            padding: 10px;
            font-family: 'Courier New', Courier, monospace;
        }
        .input-area button {
            background-color: #0f0;
            color: #000;
            border: none;
            padding: 10px 20px;
            cursor: pointer;
            font-family: 'Courier New', Courier, monospace;
            font-weight: bold;
        }
        .registers {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            background-color: #111;
            padding: 15px;
            border: 1px solid #0f0;
        }
        .register {
            text-align: center;
        }
        .register-name {
            font-weight: bold;
            margin-bottom: 5px;
        }
        .register-value {
            font-family: 'Courier New', Courier, monospace;
        }
        h1 {
            text-align: center;
            color: #0f0;
        }
        .description {
            margin-bottom: 20px;
            line-height: 1.5;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Altair 8800 Emulator with CP/M</h1>
        
        <div class="description">
            <p>This is a demonstration of the Altair 8800 emulator running CP/M. 
            The emulator includes a complete Intel 8080 CPU implementation with 
            Altair 8800 hardware features and CP/M BIOS/BDOS/CCP.</p>
        </div>
        
        <div class="terminal" id="terminal"><?php echo htmlspecialchars($output); ?></div>
        
        <form method="POST" class="input-area">
            <input type="text" name="command" id="command" placeholder="Enter CP/M command (e.g. DIR, TYPE FILE.TXT)" autocomplete="off">
            <button type="submit">Execute</button>
        </form>
        
        <h2>CPU Registers</h2>
        <div class="registers">
            <?php foreach ($registers as $reg => $value): ?>
            <div class="register">
                <div class="register-name"><?php echo htmlspecialchars($reg); ?></div>
                <div class="register-value">0x<?php echo is_numeric($value) ? sprintf("%04X", $value) : '0000'; ?></div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="description">
            <h3>Available CP/M Commands:</h3>
            <ul>
                <li><strong>DIR</strong> - List files</li>
                <li><strong>TYPE filename.EXT</strong> - Display file contents</li>
            </ul>
            <p>Note: This is a simplified demonstration. The emulator is running a minimal CP/M implementation.</p>
        </div>
    </div>
    
    <script>
        // Auto-scroll terminal to bottom
        const terminal = document.getElementById('terminal');
        terminal.scrollTop = terminal.scrollHeight;
        
        // Focus on input field
        document.getElementById('command').focus();
    </script>
</body>
</html>
