<?php
    
namespace App\Websocket;

use App\Http\Controllers\JugadorController;
use App\Http\Controllers\UsuariController;
use App\Http\Controllers\PartidaController;
use App\Models\Jugador;
use App\Models\Partida;
use Ratchet\ConnectionInterface;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class WebsocketManager
{
    public $handler;

    public WebsocketServer $websocket;
    public GameManager $gameManager;
    public $playerlist;

    public function __construct(WebsocketServer $server){
        $this->websocket = $server;
        $this->gameManager = new GameManager($this);

        $this->handler = [
            "getPartidas" =>  function (ConnectionInterface $from, $data) {PartidaController::getPartidas($from, $data);},
            "login" => function (ConnectionInterface $from, $data){
                try {
                    $decoded = JWT::decode($data->token, new Key(UsuariController::$jwt_key, 'HS256'));
                    PartidaController::getPartidas($from, $data);
                    $this->websocket->clients->attach($from);
                    UsuariController::$usuaris[$decoded->id] = $from;
                    UsuariController::$usuaris_ids[$from->resourceId] = $decoded->id;
                    WebsocketManager::removeJugadorFromPartidas($decoded->id);
                    UsuariController::profile($from, $data);
                    JugadorController::getRanking($from, $data);
                } catch (\Throwable $th) {
                    echo "\nError amb client(".$from->resourceId."):\n".$th->getMessage();
                    $from->close();
                }
            },
            "joinPartida" =>  function (ConnectionInterface $from, $data) {PartidaController::joinPartida($from, $data);},
            "createPartida" => function (ConnectionInterface $from, $data) {PartidaController::createPartida($from, $data);},
            "updatePartida" => function (ConnectionInterface $from, $data) {PartidaController::updatePartida($from, $data);},
            "leavePartida" => function (ConnectionInterface $from, $data) {PartidaController::leavePartida($from, $data);},

            "addBot" => function (ConnectionInterface $from, $data) {JugadorController::addBot($from, $data);},
            "kickJugador" => function (ConnectionInterface $from, $data) {JugadorController::kickJugador($from, $data);},
            "lobby" =>  function (ConnectionInterface $from, $data) {JugadorController::lobby($from, $data);},
            "chat" =>  function (ConnectionInterface $from, $data) {JugadorController::chat($from, $data);},
            "getRanking" => function (ConnectionInterface $from, $data) {JugadorController::getRanking($from, $data);},
            
            "profile" => function (ConnectionInterface $from, $data) {UsuariController::profile($from, $data);},

            "startPartida" =>  function (ConnectionInterface $from, $data) {$this->gameManager->startPartida($from, $data);},
            "loaded" =>  function (ConnectionInterface $from, $data) {$this->gameManager->loaded($from, $data);},
            "accio" =>  function (ConnectionInterface $from, $data) {$this->gameManager->accio($from, $data);},
        ];  
    }

    public static function error(ConnectionInterface $conn, $msg){
        $conn->send(json_encode([
            "method" => "error",
            "data" => [
                "message" => $msg
            ]
        ]));
    }

    public static function removeJugadorFromPartidas($userId){
        $jugadors = Jugador::where('skfUser_id', $userId)
        ->whereHas('partida', function ($query) {
            $query->where('estat_torn', 8);
        })->get();
        foreach($jugadors as $jugador){
            $partida = $jugador->partida;
            $jugador->delete();
            $partida->refresh();
            if($partida->admin_id == $userId){
                WebsocketManager::transferAdminNextJugador($partida->id);
            }
            foreach ($partida->jugadors as $jugador2) {
                if(isset(UsuariController::$usuaris[$jugador2->skfUser_id])){
                    JugadorController::lobby(UsuariController::$usuaris[$jugador2->skfUser_id], "");
                }else{
                    unset(UsuariController::$usuaris[$jugador2->skfUser_id]);
                }
            }
        }
        foreach (UsuariController::$usuaris as $conn) {
            PartidaController::getPartidas($conn, "");
        }
    }
    public static function transferAdminNextJugador($partidaId){
        $partida = Partida::find($partidaId);
        $jugadors = $partida->jugadors;
        $trobat = false;
        foreach ($jugadors as $jugador) {
            if($jugador->usuari->id != 0){
                $partida->admin_id = $jugador->usuari->id;
                $partida->save();
                $trobat = true;
                break;
            }
        }
        if(!$trobat){
            $partida->delete();
        }
        
    }
}

?>