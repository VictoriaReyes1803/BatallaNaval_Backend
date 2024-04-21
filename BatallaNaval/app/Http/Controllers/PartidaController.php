<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Events\StartGame;
use App\Events\TestEvent;
use App\Models\Game;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Util\Test;

class PartidaController extends Controller
{
    function sendNotify(Request $request){
        $player_id = Auth::user()->id;

        event(new AlertEvent("Se destruyo un barco rival!", $player_id));
        return response()->json([
            'msg' => 'Notification sent successfully',
        ]);
    }

    public function getGames(Request $request)
    {
        $games = Game::where('player1_id', $request->user()->id)
            ->orWhere('player2_id', $request->user()->id)
            ->get();

        return response()->json(['games' => $games], 200);
    }
    public function createGame(Request $request)
    {
        
        $player1_ships = $this->generateRandomShips($request->input('board_size'));
        $player2_ships = $this->generateRandomShips($request->input('board_size'));


        $game = Game::create([
            'status' => 'queue',
            'player1_id' => $request->user()->id, 
            'player1_ships' => json_encode($player1_ships),
            'player2_ships' => json_encode($player2_ships),
        ]);
        $game->save();
        event(new StartGame($game));

        return response()->json(['game' => $game], 201);
    }


    public function findMatch(Request $request)
{

    $game = Game::where('status', 'queue')
                ->where('player1_id', '!=', $request->user()->id)
                ->first();

    if ($game) {
        $game->update([
            'status' => 'playing',
            'player2_id' => $request->user()->id,
        ]);
        return response()->json(['game' => $game], 200);
        event(new StartGame($game));
    }

    return response()->json(['message' => 'No se encontró ninguna partida disponible'], 404);
}
private function generateRandomShips($boardSize)
{
    $ships = [];

    // Generar 15 barcos
    for ($i = 0; $i < 15; $i++) {
        $ship = [];

        $x = rand(0, $boardSize - 1);
        $y = rand(0, $boardSize - 1);
        while (in_array([$x, $y], $ships)) {
            $x = rand(0, $boardSize - 1);
            $y = rand(0, $boardSize - 1);
        }

        // Agregar las coordenadas al array de barcos
        $ship['x'] = $x;
        $ship['y'] = $y;

        // Agregar las coordenadas al array de barcos generados
        $ships[] = [$x, $y];
    }

    return $ships;
}
}
