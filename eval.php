<?php
header("Access-Control-Allow-Origin: https://www.chess.com");
header("Content-Type: application/json");

$fen = $_GET['fen'] ?? null;

if (!$fen) {
    http_response_code(400);
    echo "Missing FEN parameter.";
    exit;
}

$cmd = './stockfish';
$descriptorspec = [
    0 => ["pipe", "r"], // stdin
    1 => ["pipe", "w"], // stdout
    2 => ["pipe", "w"]  // stderr
];
$process = proc_open($cmd, $descriptorspec, $pipes);

if (is_resource($process)) {
    fwrite($pipes[0], "position fen $fen\n");
    fwrite($pipes[0], "go depth 15\n");
    sleep(1); // wait for engine to respond
    fwrite($pipes[0], "quit\n");
    fclose($pipes[0]);

    $output = stream_get_contents($pipes[1]);
    fclose($pipes[1]);
    fclose($pipes[2]);

    proc_close($process);

    // Get best move from output
    if (preg_match("/bestmove\s+(\w+)/", $output, $matches)) {
        echo json_encode(["best_move" => $matches[1]]);
    } else {
        echo json_encode(["error" => "Could not parse move."]);
    }
} else {
    echo json_encode(["error" => "Failed to run Stockfish."]);
}
