<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Ratchet\ConnectionInterface;
use App\Models\Partida;
use App\Models\Usuari;
use App\Models\Jugador;
use App\Websocket\WebsocketManager;
use Illuminate\Support\Facades\DB;

class JugadorController extends Controller
{
    public static $partida_jugador = [];

    public static function getJugadorByUser($conn){
        $userId = UsuariController::$usuaris_ids[$conn->resourceId];
        $gameId = JugadorController::$partida_jugador[$userId];
        $player = Jugador::where('skfUser_id',$userId)->where('skfPartida_id', $gameId )->first();
        return $player;
    }

    public static function jugadorEnPartida(Jugador $player, Partida $game){
        if($player->id == 0 || !isset(JugadorController::$partida_jugador[$player->usuari->id])){
            return false;
        }
        $gameId = JugadorController::$partida_jugador[$player->usuari->id];
        if(!isset(UsuariController::$usuaris[$player->usuari->id])){
            return false;
        }
        return $gameId == $game->id;
    }

    public static function lobby(ConnectionInterface $from, $data){
        $player = JugadorController::getJugadorByUser($from);
        $game = $player->partida;
       
        $users = [];
        $jugadors = $game->jugadors;
        foreach ($jugadors as $jugador) {
            $users[] = $jugador->usuari()->first()->makeHidden(['password','login']);
        }

        $from->send(json_encode(
            [
                "method" => "lobby",
                "data" => [
                    "jugadors" => $users,
                    "partida" => DB::select("select p.id, p.date, p.nom, (p.token = '') as publica, admin_id, COUNT(j.skfPartida_id) as current_players, max_players, p.tipus from partidas p LEFT JOIN jugadors j ON p.id = j.skfPartida_id where p.id = ".$game->id." GROUP BY p.id, p.date, p.nom, p.token, p.max_players, p.admin_id, p.estat_torn, p.tipus;")[0]
                ],
            ]
        ));
    }

    public static function chat(ConnectionInterface $from, $data){
        $player = JugadorController::getJugadorByUser($from);
        $game = $player->partida;
        
        $users = Usuari::whereHas('jugadors', function ($query) use ($game) {
            $query->where('skfPartida_id', $game->id);
        })->get()->makeHidden(['password','login']);

        foreach ($users as $user) {
            if(isset(UsuariController::$usuaris[$user->id])){
                UsuariController::$usuaris[$user->id]->send(json_encode(
                    [
                        "method" => "chat",
                        "data" => [
                            "user" => $player->usuari->id,
                            "message" => $data->message
                        ]
                    ]
                ));
            }
        }
    }

    
    public static function kickJugador(ConnectionInterface $from, $data){
        $requester = JugadorController::getJugadorByUser($from);
        $game = $requester->partida;
        $userId = $data->user;

        $player = Jugador::where('skfUser_id',$userId)->where('skfPartida_id', $game->id )->first();
        
        $adminId = $game->admin_id;
        
        if($adminId == $requester->usuari->id && $adminId != $userId){
            if($userId != 0){
                UsuariController::$usuaris[$userId]->send(json_encode([
                    "method" => "kickJugador",
                    "data" => "",
                ]));
            }
            $player->delete();
            
            foreach ($game->jugadors as $jugador) {
                if(JugadorController::jugadorEnPartida($jugador,$game)){
                    JugadorController::lobby(UsuariController::$usuaris[$jugador->skfUser_id], "");
                }            
            }
            foreach (UsuariController::$usuaris as $conn) {
                PartidaController::getPartidas($conn, "");
            }

        }else{
            WebsocketManager::error($from,"No pots expulsar aquest jugador");
        }
    }


    public static function addBot(ConnectionInterface $from, $data){
        $player = JugadorController::getJugadorByUser($from);
        $game = $player->partida;

        $adminId = $game->admin_id;
        if($adminId == $player->usuari->id){
            if($game->jugadors()->count() == $game->max_players){
                WebsocketManager::error($from, "Ja hi ha el maxim de jugadors en la partida");
                return;
            }
            $player = Jugador::create(["skfUser_id" => 0, "skfPartida_id"=> $game->id]);
            $game->refresh();
            foreach ($game->jugadors as $jugador) {
                if(isset(UsuariController::$usuaris[$jugador->skfUser_id])){
                    JugadorController::lobby(UsuariController::$usuaris[$jugador->skfUser_id], "");
                }            
            }
        }else{
            WebsocketManager::error($from,"No tens permis per agregar un bot");
        }
    }

    public static function getRanking(ConnectionInterface $from, $data){
        $from->send(json_encode([
            "method" => "getRanking",
            "data" => Usuari::orderBy('elo', 'desc')->where('id', '!=', 0)->take(10)->get()->makeHidden(['password','login']),
        ]));
    }
    
}
