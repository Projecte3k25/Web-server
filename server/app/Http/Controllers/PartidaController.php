<?php

namespace App\Http\Controllers;

use App\Models\Jugador;
use App\Models\Partida;
use App\Websocket\WebsocketManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Ratchet\ConnectionInterface;


class PartidaController extends Controller
{
    public static function getPartidas(ConnectionInterface $from, $data){
        $from->send(json_encode([
            "method" => "getPartidas",
            "data" => DB::select("select p.id, p.date, p.nom, (p.token = '') as publica, admin_id, COUNT(j.skfPartida_id) as current_players, max_players, p.tipus from partidas p LEFT JOIN jugadors j ON p.id = j.skfPartida_id where p.estat_torn = 8 GROUP BY p.id, p.date, p.nom, p.token, p.max_players, p.admin_id, p.estat_torn, p.tipus;")
        ]));
    }  

    public static function joinPartida(ConnectionInterface $from, $data){
        $gameId = $data->partida;
        $userId = UsuariController::$usuaris_ids[$from->resourceId];
        $game = Partida::where('id',$gameId)->first();
        if($game->token != ""){
            if($game->token != md5($data->password)){
                WebsocketManager::error($from, "Contrasenya invalida.");
                return;
            }
        }
        if($game->jugadors()->count() == $game->max_players){
            WebsocketManager::error($from, "Ja hi ha el maxim de jugadors en la partida");
            return;
        }
        
        WebsocketManager::removeJugadorFromPartidas($userId);

        JugadorController::$partida_jugador[$userId] = $gameId;
        $player = Jugador::create(["skfUser_id" => $userId, "skfPartida_id"=> $gameId]);
        $game->refresh();
        foreach ($game->jugadors as $jugador) {
            if(isset(UsuariController::$usuaris[$jugador->skfUser_id])){
                JugadorController::lobby(UsuariController::$usuaris[$jugador->skfUser_id], "");
            }            
        }
        
        foreach (UsuariController::$usuaris as $conn) {
            PartidaController::getPartidas($conn, "");
        }
        
    } 
    
    public static function leavePartida(ConnectionInterface $from, $data){
        $userId = UsuariController::$usuaris_ids[$from->resourceId];
        WebsocketManager::removeJugadorFromPartidas($userId);
        PartidaController::getPartidas($from,$data);
    }

    public static function createPartida(ConnectionInterface $from, $data){
        $userId = UsuariController::$usuaris_ids[$from->resourceId];
        $nom = "";
        $password = "";
        $max_player = 4;

        switch($data->tipus){
            case "Custom":
                $nom = trim($data->nom);
                $password = "";
                if(isset($data->password)){
                    $password = trim($data->password);
                }
                $max_player = $data->max_players;
                if(strlen($nom) < 3){
                    WebsocketManager::error($from, "El nom de la partida ha de tenir com a minim 3 caracters.");
                    return;
                }else if($max_player < 2 || $max_player > 6){
                    WebsocketManager::error($from, "El numero de jugadors maxim ha de ser entre 2 a 6.");
                    return;
                }
                break;
            case "Ranked":
                $nom = "Ranked";
                break;
            case "Rapida":
                $nom = "Rapida";
                break;
        }
        $partida = Partida::create(["nom"=>$nom,"token"=> $password != "" ? md5($password) : "","max_players"=>$max_player,"admin_id" => $userId,"estat_torn" => 8, "tipus" => $data->tipus]);
        if($partida->tipus != "Custom") {
            $partida->nom .= " ".$partida->id; 
            $partida->save();
        }
        $newData = new \stdClass;
        $newData->partida = $partida->id;
        $newData->password = $password;
        PartidaController::joinPartida($from, $newData);
        foreach (UsuariController::$usuaris as $conn) {
            PartidaController::getPartidas($conn, "");
        }
    }

    public static function updatePartida(ConnectionInterface $from, $data){
        $userId = UsuariController::$usuaris_ids[$from->resourceId];
        $gameId = JugadorController::$partida_jugador[$userId];
        $player = Jugador::where('skfUser_id',$userId)->where('skfPartida_id', $gameId )->first();
        $game = $player->partida;
        $nom = trim($data->nom);
        $password = trim($data->password);
        $max_player = $data->max_players;
        if($game->tipus != "Custom"){
            WebsocketManager::error($from, "No es pot modificar aquest tipus de partida.");
        }else if($game->admin_id != $userId){
            WebsocketManager::error($from, "No tens permis per modificar la partida.");
        }else if(strlen($nom) < 3){
            WebsocketManager::error($from, "El nom de la partida ha de tenir com a minim 3 caracters.");
        }else if($max_player < 2 || $max_player > 6){
            WebsocketManager::error($from, "El numero de jugadors maxim ha de ser entre 2 a 6.");
        }else if($max_player < $game->jugadors->count()){
            WebsocketManager::error($from, "El numero de jugadors no pot ser menor al numero de jugadors dins de la sala.");
        }else{
            $game->nom = $nom;
            $game->token = $password != "" ? md5($password) : "";
            $game->max_players = $max_player;
            $game->save();
            
            foreach ($game->jugadors as $jugador) {
                $usuari = $jugador->usuari;
                if($usuari->id != 0){
                    JugadorController::lobby(UsuariController::$usuaris[$usuari->id],$data);
                }
            }
            foreach (UsuariController::$usuaris as $conn) {
                PartidaController::getPartidas($conn, "");
            }
        } 
    }
}
