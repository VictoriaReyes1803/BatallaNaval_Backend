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
        event(new StartGame($game));
        return response()->json(['game' => $game], 200);
       
    }

    return response()->json(['message' => 'No se encontró ninguna partida disponible'], 404);
        }

        
        public function finishGame(Request $request, $id)
        {
            $game = Game::find($id);

            if (!$game) {
                return response()->json(['message' => 'Partida no encontrada'], 404);
            }
        
          
            if ($game->player1_id !== $request->user()->id && $game->player2_id !== $request->user()->id) {
                return response()->json(['message' => 'No tienes permiso para finalizar esta partida'], 403);
            }
        
            
            $game->update(['status' => 'finished']);
            event(new GameFinished($game->id));
        
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
        function sendNotify(Request $request){
            $player_id = Auth::user()->id;
    
            event(new AlertEvent("Se destruyo un barco rival!", $player_id));
            return response()->json([
                'msg' => 'Notification sent successfully',
            ]);
        }
    
        public function queueGame(){
            $player_id = Auth::user()->id;
    
            $existingGameAsP1 = game::where('player1_id', $player_id)
                ->whereIn('status', ['playing', 'queue'])
                ->first();
    
            $existingGameAsP2 = game::where('player2_id', $player_id)
                ->whereIn('status', ['playing', 'queue'])
                ->first();
    
            if ($existingGameAsP1 || $existingGameAsP2) {
                return response()->json([
                    'msg' => 'You already have a game in progress or in queue. Please finish it before starting a new one.',
                ], 400);
            }
    
            $game = new Game();
            $game->player1_id = $player_id;
            $game->save();
    
            return response()->json([
                'msg' => 'Game queued successfully',
                'gameId' => $game->id,
            ]);
        }
    
        public function cancelRandomQueue(Request $request){
            $player_id = Auth::user()->id;
    
            Cache::put($player_id, 'cancelled', 1);
    
            return response()->json([
                'msg' => 'Game search cancelled',
            ], 200);
        }
    
        public function joinRandomGame(Request $request){
            $player2_id = Auth::user()->id;
    
            $existingGameAsPlayerOne = game::where('player1_id', $player2_id)
                ->whereIn('status', ['playing', 'queue'])
                ->first();
    
            $existingGameAsPlayerTwo = game::where('player2_id', $player2_id)
                ->whereIn('status', ['playing', 'queue'])
                ->first();
    
            if ($existingGameAsPlayerTwo || $existingGameAsPlayerOne) {
                return response()->json([
                    'msg' => 'You already have a game in progress or in queue. Please finish it before starting a new one.',
                ], 400);
            }
    
            $random_game = game::where('status', 'queue')->first();
            if (!$random_game) {
                return response()->json([
                    'game_found' => false,
                    'msg' => 'No games in queue',
                ], 400);
            }
    
            $random_game->player2_id = $player2_id;
            $random_game->status = 'playing';
            $random_game->save();
    
            try {
                event(new TestEvent(['gameId' => $random_game->id, 'players' => [$random_game->player1_id, $random_game->player2_id]]));
                Log::info('El evento TestEvent se ha enviado correctamente.');
            } catch (\Exception $e) {
                Log::error('Error al emitir el evento TestEvent: ' . $e->getMessage());
                return response()->json(['error' => $e->getMessage()]);
            }
    
            return response()->json([
                'game_found' => true,
                'msg' => 'Game started successfully',
                'players' => [$random_game->player1_id, $random_game->player2_id],
                'turn' => $random_game->player2_id,
                'gameId' => $random_game->id,
            ]);
        }
    
        public function endGame(Request $request){
            $validator = Validator::make($request->all(), [
                'loser_id' => 'required|integer|exists:users,id',
                'gameId' => 'required|integer|exists:games,id',
            ]);
    
            if ($validator->fails()) {
                return response()->json(["errors    " => $validator->errors()], 400);
            }
    
            $game_id = $request->gameId;
            $loser_id = $request->loser_id;
    
            $game = game::find($game_id);
            $game->status = 'finished';
    
            if ($game->player1_id == $loser_id) {
                $game->winner_id = $game->player2_id;
            }
            else if ($game->player2_id == $loser_id) {
                $game->winner_id = $game->player1_id;
            }
    
            $game->save();
            event(new AlertWinner($game->winner_id));
    
            return response()->json([
                'msg' => 'Game ended successfully',
                'game_id' => $game->id,
                'winner_id' => $game->winner_id,
            ]);
        }
    
        public function myGameHistory(Request $request){
            $player_id = Auth::user()->id;
            $perPage = 5;
    
            $games = Game::where('player1_id', $player_id)
                ->orWhere('player2_id', $player_id)
                ->join('users as player1', 'games.player1_id', '=', 'player1.id')
                ->join('users as player2', 'games.player2_id', '=', 'player2.id')
                ->join('users as winner', 'games.winner_id', '=', 'winner.id')
                ->select('games.id', 'games.status', 'games.created_at', 'player1.id as player1_id', 'player2.id as player2_id', 'winner.id as winner_id', 'player1.name as player1_name',  'player2.name as player2_name', 'winner.name as winner_name')
                ->where('status', 'finished')
                ->paginate($perPage);
    
            foreach ($games as $game) {
                $game->player_id = $player_id;
            }
    
            return response()->json([
                'page' =>$games->currentPage(),
                'games' => $games->items(),
            ]);
        }
    
    
    
        public function dequeueGame(Request $request){
            $validator = Validator::make($request->all(), [
                'gameId' => 'required|integer|exists:games,id',
            ]);
    
            if ($validator->fails()) {
                return response()->json(["errors" => $validator->errors()], 400);
            }
    
            $gameId = $request->gameId;
            //$player_id = Auth::user()->id;
    
            $game = game::find($gameId);
            if ($game->status != 'queue'){
                return response()->json([
                    'msg' => 'Game is not in queue',
                ], 400);
            }
            $game->delete();
    
            return response()->json([
                'msg' => 'Game unqueued successfully',
                'game_id' => $game->id,
            ]);
        }
    
        public function sendBoard(Request $request){
            $validator = Validator::make($request->all(), [
                'gameId' => 'required|integer|exists:games,id',
                'board' => 'required|array',
            ]);
    
            if ($validator->fails()) {
                return response()->json(["errors" => $validator->errors()], 400);
            }
    
            $game = game::find($request->gameId);
            if($game->status != 'playing'){
                return response()->json([
                    'msg' => 'Game is not in progress',
                ], 400);
            }
    
            $turn = $request->turn;
            $newTurn = ($turn == 1) ? 2 : 1;
    
            event(new NotifyEvent($newTurn, $request->board));
        }
    
        public function attack(Request  $request){
            $validator = Validator::make($request->all(), [
                'gameId' => 'required|integer|exists:games,id',
                'playerAttacked' => 'required|integer|exists:users,id',
                //'playerWhoAttacked' => 'required|integer|exists:users,id',
                'cell' => 'required|array',
            ]);
    
            if ($validator->fails()) {
                return response()->json(["errors" => $validator->errors()], 400);
            }
    
            $playerWhoAttacked = Auth::user()->id;
                                 
            event(new AttackEvent([$request->gameId, $request->cell, $request->playerAttacked, $playerWhoAttacked]));
    
            return response()->json([
                'msg' => 'Attack sent successfully',
                'data' => [
                    'gameId' => $request->gameId,
                    'cell' => $request->cell,
                    'playerAttacked' => $request->playerAttacked,
                    'playerWhoAttacked' => $playerWhoAttacked,
                    ]
            ]);
        }
    
        public function attackSuccess(Request $request){
            $validator = Validator::make($request->all(), [
                'hited' => 'required|boolean',
                'turn' => 'required|integer|exists:users,id',
                'cell' => 'required|array',
                'playerWhoAttacked' => 'required|integer|exists:users,id',
            ]);
    
            if ($validator->fails()) {
                return response()->json(["errors" => $validator->errors()], 400);
            }      
            event(new AttackSuccessEvent([$request->hited, $request->turn, $request->cell, $request->playerWhoAttacked]));
    
            return response()->json([
                'msg' => 'Attack success sent successfully',
                'data' => [
                    'hited' => $request->hited,
                    'turn' => $request->turn,
                    'cell' => $request->cell,
                    'playerWhoAttacked' => $request->playerWhoAttacked,
                ]
            ]);
    
        }
    
        public function attackFailed(Request $request){
            $validator = Validator::make($request->all(), [
                'hited' => 'required|boolean',
                'turn' => 'required|integer|exists:users,id',
                'cell' => 'required|array',
                'playerWhoAttacked' => 'required|integer|exists:users,id',
            ]);
    
            if ($validator->fails()) {
                return response()->json(["errors" => $validator->errors()], 400);
            }
            event(new AttackFailedEvent([$request->hited, $request->turn, $request->cell, $request->playerWhoAttacked]));
    
            return response()->json([
                'msg' => 'Attack failed sent successfully',
                'data' => [
                    'hited' => $request->hited,
                    'turn' => $request->turn,
                    'cell' => $request->cell,
                    'playerWhoAttacked' => $request->playerWhoAttacked,
                ]
            ]);
    
        }
}
