<?php
    
namespace App\Websocket;
use Ratchet\ConnectionInterface;
use App\Http\Controllers\JugadorController;
use App\Http\Controllers\UsuariController;
use App\Http\Controllers\PartidaController;
use App\Models\Jugador;
use App\Models\Pais;
use App\Models\Okupa;
use App\Models\Partida;
use React\EventLoop\Loop;

class GameManager
{
    public $handler;
    public $websocket_manager;
    public $maps = [];
    public $loading = [];
    public $timeManager;

    public function __construct(WebsocketManager $websocket_manager){
        $this->timeManager = Loop::get();
        $this->websocket_manager = $websocket_manager;
        $this->maps["world"] = [];
        $paisos = Pais::all();
        foreach ($paisos as $pais) {
            $this->maps["world"][$pais->nom] = [];
            $fronteres = $pais->fronteres;
            foreach ($fronteres as $frontera) {
                $this->maps["world"][$pais->nom][] = $frontera->nom;
            }
        }
        echo "Mapas: \n".json_encode($this->maps, JSON_PRETTY_PRINT);
    }   

    public function startPartida(ConnectionInterface $from, $data){
        $userId = UsuariController::$usuaris_ids[$from->resourceId];
        $gameId = JugadorController::$partida_jugador[$userId];
        $player = Jugador::where('skfUser_id',$userId)->where('skfPartida_id', $gameId )->first();
        $game = $player->partida;
        $jugadors = $game->jugadors;

        $adminId = $game->admin_id;
        if($adminId != $userId){
            WebsocketManager::error($from,"No pots començar la partida");
            return;
        }else if(count($jugadors) < 2){
            WebsocketManager::error($from,"Necesites minim dos jugadors per començar la partida.");
            return;
        }
        
        $nums = range(1, count($jugadors));
        $users = [];
        foreach ($jugadors as $jugador) {
            $user = $jugador->usuari()->first()->makeHidden(['password','login']);
            $index = rand(0, count($nums)-1);
            $num = $nums[$index];
            
            unset($nums[$index]);
            $nums = array_values($nums);

            $jugador->skfNumero = $num;
            $jugador->tropas = (50 - (count($jugadors) * 5));
            $jugador->save();

            $users[] = [
                "jugador" => $user,
                "posicio" => $num
            ];
        }

        $this->loading[$game->id] = [];
        $game->estat_torn = 1;
        $game->save();
        foreach ($game->jugadors as $jugador) {
            $usuari = $jugador->usuari;
            if($usuari->id != 0){
                $userConn = UsuariController::$usuaris[$usuari->id];
                $this->loading[$game->id][$userId] = 0;
                $userConn->send(json_encode([
                    "method" => "startPartida",
                    "data" => [
                        "jugadors" => $users,
                        "territoris" => $this->maps["world"],
                    ],
                ]));
            }
        }

        foreach (UsuariController::$usuaris as $conn) {
            PartidaController::getPartidas($conn, "");
        }
    }



    public function loaded(ConnectionInterface $from, $data){
        $userId = UsuariController::$usuaris_ids[$from->resourceId];
        $gameId = JugadorController::$partida_jugador[$userId];
        $player = Jugador::where('skfUser_id',$userId)->where('skfPartida_id', $gameId )->first();
        $game = $player->partida;
        $jugadors = $game->jugadors;

        foreach ($jugadors as $jugador) {
            $usuari = $jugador->usuari;
            if(isset(UsuariController::$usuaris[$usuari->id])){
                $userConn = UsuariController::$usuaris[$usuari->id];
                $userConn->send(json_encode([
                    "method" => "loaded",
                    "data" => [
                        "id" => $usuari->id,
                    ],
                ]));
                unset($this->loading[$game->id][$userId]);
            }
        }
        if(count($this->loading[$game->id]) == 0){
            foreach ($jugadors as $jugador) {
                $usuari = $jugador->usuari;
                if(isset(UsuariController::$usuaris[$usuari->id])){
                    $userConn = UsuariController::$usuaris[$usuari->id];
                    $userConn->send(json_encode([
                        "method" => "allLoaded",
                        "data" => [],
                    ]));
                }
            }
            unset($this->loading[$game->id]);
            $this->canviFase($from,$data);
        }
    }

    public function canviFase(ConnectionInterface $from, $data) {
        $userId = UsuariController::$usuaris_ids[$from->resourceId];
        $gameId = JugadorController::$partida_jugador[$userId];
        $player = Jugador::where('skfUser_id',$userId)->where('skfPartida_id', $gameId )->first();
        $game = $player->partida;
        
        switch($game->estat_torn){
            case 1;
                $game->estat_torn = 2;
                $game->torn_player = 1;
                $game->save();
                $game->refresh();
                $this->enviarCanviFase($game, 10);
                break;
            case 2;
                $game->estat_torn = 3;
                $game->torn_player = 1;
                $game->save();
                $game->refresh();
                $this->enviarCanviFase($game, 10);
                break;
            case 3;
                $game->estat_torn = 4;
                $game->torn_player = 1;
                $game->save();
                $game->refresh();
                $this->enviarCanviFase($game, 10);
                break;
        }
        
    }

    public function enviarCanviFase(Partida $game, $time) {
        $jugadors = $game->jugadors;
        $jugador = $game->jugadors()->where("skfNumero", $game->torn_player)->first();
        $cartes = $jugador->cartes;
        $newCartes = [];
        foreach ($cartes as $carta) {
            $newCartes[] = [
                "nom" =>  $carta->pais->nom,
                "tipus" => $carta->tipusCarta->nom,
            ];
        }
        $territoris = [];
        foreach ($jugadors as $jugador2) {
            $okupes = $jugador2->okupes;
            foreach($okupes as $okupe){
                $territoris[$okupe->pais->nom] = [
                    "posicio" => $jugador2->skfNumero,
                    "tropas" => $okupe->tropes,
                ];
            }
        }

        foreach ($jugadors as $jugador2) {
            $usuari = $jugador2->usuari;
            if(isset(UsuariController::$usuaris[$usuari->id])){
                $userConn = UsuariController::$usuaris[$usuari->id];
                $userConn->send(json_encode([
                    "method" => "canviFase",
                    "data" => [
                        "fase" => $game->estat->nom,
                        "jugadorActual" => $jugador->usuari()->first()->makeHidden(['password','login']),
                        "posicio" => $game->torn_player,
                        "tropas" => $jugador->tropas,
                        "cartas" => $newCartes,
                        "temps" => $time,
                        "territoris" => $territoris,
                    ]
                ]));
            }
        }
    }

    public function accio(ConnectionInterface $from, $data) {
        $userId = UsuariController::$usuaris_ids[$from->resourceId];
        $gameId = JugadorController::$partida_jugador[$userId];
        $player = Jugador::where('skfUser_id',$userId)->where('skfPartida_id', $gameId )->first();
        $game = $player->partida;
        $jugadors = $game->jugadors;
        
        $territoris = [];
        foreach ($jugadors as $jugador2) {
            $okupes = $jugador2->okupes;
            foreach($okupes as $okupe){
                $territoris[$okupe->pais->nom] = [
                    "posicio" => $jugador2->skfNumero,
                    "tropas" => $okupe->tropes,
                ];
            }
        }

        switch($game->estat_torn){
            case 2;
                if(!isset($territoris[$data->territori])){
                    $game->torn_player = ($game->torn_player % count($jugadors)) + 1;
                    $territori = Pais::firstWhere("nom",$data->territori);
                    Okupa::create(["pais_id" => $territori->id, "player_id" => $player->id, "tropes" => 1]);
                    $player->tropas = $player->tropas - 1;
                    $player->save();
                    $game->save();
                    $game->refresh();
                    if(count($territoris) == count($this->maps["world"])){
                        $this->canviFase($from,$data);
                    }else{
                        $this->enviarCanviFase($game, 10);
                    }
                    
                }else{
                    WebsocketManager::error($from,"No pot colocar una tropa en aquest territori.");
                }
                break;
            case 3;
                if(isset($territoris[$data->territori]) && $territoris[$data->territori]["posicio"] == $player->skfNumero){
                    $game->torn_player = ($game->torn_player % count($jugadors)) + 1;
                    $territori = Pais::firstWhere("nom",$data->territori);
                    $okupa = Okupa::where("pais_id", $territori->id)->where("player_id", $player->id)->first();
                    $okupa->tropes = $okupa->tropes + 1;
                    $player->tropas = $player->tropas - 1;

                    $player->save();
                    $okupa->save();
                    $game->save();

                    $game->refresh();
                    if($jugadors->sum('tropes') == 0){
                        $this->canviFase($from, $data);
                    }else{
                        $this->enviarCanviFase($game, 10);
                    }
                    
                }else{
                    WebsocketManager::error($from,"No pot colocar una tropa en aquest territori.");
                }
                break;
        }
    }

    

}



?>