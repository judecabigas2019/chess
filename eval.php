<?php
header("Access-Control-Allow-Origin: https://www.chess.com");
header("Content-Type: application/json");

$fen = $_GET['fen'] ?? null;
$depth = $_GET['depth'] ?? 20;

if (!$fen) {
    echo json_encode(['error' => 'Missing FEN']);
    exit;
}

$depth = max(8, min(30, intval($depth))); // Clamp depth to reasonable range

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

// Send commands to Stockfish
fwrite($pipes[0], "uci\n");
fwrite($pipes[0], "isready\n");
fwrite($pipes[0], "ucinewgame\n");
fwrite($pipes[0], "position fen $fen\n");
fwrite($pipes[0], "go depth $depth\n");

// Read until 'bestmove' appears
$bestMove = null;
$output = '';

while ($line = fgets($pipes[1])) {
    $output .= $line;
    if (strpos($line, 'bestmove') === 0) {
        if (preg_match('/bestmove\s+(\w+)/', $line, $matches)) {
            $bestMove = $matches[1];
        }
        break;
    }
}

// Clean up
fclose($pipes[0]);
fclose($pipes[1]);
fclose($pipes[2]);
proc_close($process);

// Return JSON
if ($bestMove) {
    echo json_encode([
        'best_move' => $bestMove,
        'depth' => $depth,
        'raw' => $output // optional: comment out if not needed
    ]);
} else {
    echo json_encode([
        'error' => 'No best move found',
        'raw' => $output
    ]);
}
