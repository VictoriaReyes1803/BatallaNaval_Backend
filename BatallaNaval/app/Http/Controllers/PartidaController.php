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
use App\Models\User;

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
            foreach ($games as $game) {
                if ($game->player1_id == $request->user()->id) {
                    $opponent = User::find($game->player2_id);
                } else {
                    $opponent = User::find($game->player1_id);
                }
              
                if ($opponent) {
                    $game->opponent_name = $opponent->nombre;
                } else {
                    $game->opponent_name = 'Jugador desconocido';
                }
              
            }
        return response()->json(['games' => $games], 200);
    }
    public function createGame(Request $request)
    {
        $existingGame = Game::where('status', 'playing')
        ->where(function ($query) use ($request) {
            $query->where('player1_id', $request->user()->id)
                  ->orWhere('player2_id', $request->user()->id);
        })
        ->first();

        if ($existingGame) {
        return response()->json(['Partida sin terminar','game' => $existingGame], 200);
        }

        $existingGameque = Game::where('status', 'queue')
        ->where(function ($query) use ($request) {
            $query->where('player1_id', $request->user()->id)
                  ->orWhere('player2_id', $request->user()->id);
        })
        ->first();

        if ($existingGameque) {
        return response()->json(['Buscando Rival','game' => $existingGameque], 200);
        }


        $player1_ships = $this->generateRandomShips();
        $player2_ships = $this->generateRandomShips();


        $game = Game::create([
            'status' => 'queue',
            'player1_id' => $request->user()->id, 
            'player1_ships' => json_encode($player1_ships),
            'player2_ships' => json_encode($player2_ships),
        ]);
        $game->save();
        event(new StartGame($game));

        return response()->json(['Partida creada','game' => $game], 201);
    }


    public function findMatch(Request $request)

{   
    $existingGame = Game::where('status', 'playing')
    ->where(function ($query) use ($request) {
        $query->where('player1_id', $request->user()->id)
              ->orWhere('player2_id', $request->user()->id);
    })
    ->first();

    if ($existingGame) {
    return response()->json(['Partida sin terminar','game' => $existingGame], 200);
    }

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

        
        public function finishGame(Request $request, $id)
        {
            $game = Game::find($id);

            if (!$game) {
                return response()->json(['message' => 'Partida no encontrada'], 404);
            }
        
            // Verifica si el usuario actual es uno de los jugadores
            if ($game->player1_id !== $request->user()->id && $game->player2_id !== $request->user()->id) {
                return response()->json(['message' => 'No tienes permiso para finalizar esta partida'], 403);
            }
        
            // Cambia el estado de la partida a "finished"
            $game->update(['status' => 'finished']);
        
            return response()->json(['message' => 'Partida finalizada con éxito'], 200);
        }

        private function generateRandomShips()
        {
            $ships = [];
            $boardWidth = 8;
            $boardHeight = 5;

            // Generar 15 barcos
            for ($i = 0; $i < 15; $i++) {
                $ship = [];

                $x = rand(0, $boardWidth - 1);
                $y = rand(0, $boardHeight - 1);
                
                while (in_array([$x, $y], $ships)) {
                    $x = rand(0, $boardWidth - 1);
                    $y = rand(0, $boardHeight - 1);
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
