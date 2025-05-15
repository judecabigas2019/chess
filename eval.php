<?php
header("Access-Control-Allow-Origin: https://www.chess.com");
header("Content-Type: application/json");

// === Get FEN from query ===
$fen = $_GET['fen'] ?? null;
$skill = $_GET['skill'] ?? 10; // Skill level: 0 (worst) - 20 (best)
$movetime = $_GET['time'] ?? 1500; // in milliseconds (default: 1.5 sec)

// === Validate input ===
if (!$fen) {
    echo json_encode(['error' => 'Missing FEN']);
    exit;
}

$skill = 20; // Clamp between 0-20
$movetime = max(200, min(10000, intval($movetime))); // Clamp between 200ms-10s

// === Setup process to run Stockfish ===
$cmd = './stockfish';
$descriptorspec = [
    0 => ["pipe", "r"],  // stdin
    1 => ["pipe", "w"],  // stdout
    2 => ["pipe", "w"]   // stderr
];

$process = proc_open($cmd, $descriptorspec, $pipes);

if (!is_resource($process)) {
    echo json_encode(['error' => 'Could not start Stockfish']);
    exit;
}

// === Feed commands to Stockfish ===
fwrite($pipes[0], "uci\n");
fwrite($pipes[0], "setoption name Skill Level value $skill\n");
fwrite($pipes[0], "isready\n");
fwrite($pipes[0], "position fen $fen\n");
fwrite($pipes[0], "go movetime $movetime\n");

// Wait briefly for Stockfish to calculate
usleep($movetime * 1000); // Convert to microseconds

fwrite($pipes[0], "quit\n");

// === Capture output ===
$output = stream_get_contents($pipes[1]);

fclose($pipes[0]);
fclose($pipes[1]);
fclose($pipes[2]);
proc_close($process);

// === Parse best move from Stockfish output ===
if (preg_match('/bestmove\s+(\w+)/', $output, $matches)) {
    echo json_encode([
        'best_move' => $matches[1],
        'skill_level' => $skill,
        'movetime_ms' => $movetime,
        'raw' => $output // optional, remove if you want clean JSON
    ]);
} else {
    echo json_encode([
        'error' => 'No best move found',
        'raw' => $output
    ]);
}
