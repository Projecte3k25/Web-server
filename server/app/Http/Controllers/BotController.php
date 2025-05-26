<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Jugador;
use App\Models\Pais;
use App\Models\Okupa;
use App\Websocket\WebsocketManager;


class BotController extends Controller
{


    public static function accio(Jugador $player) {
        $game = $player->partida;
        $jugadors = $game->jugadors;
        $player = $game->jugadors()->where("skfNumero", $game->torn_player)->first();

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
                $noUtilizats = array_diff_key(WebsocketManager::$gameManager->maps["world"], $territoris);
                if (!empty($noUtilizats)) {
                    $territoriAleatori = array_rand($noUtilizats);
                    $game->torn_player = ($game->torn_player % count($jugadors)) + 1;
                    $territori = Pais::firstWhere("nom",$territoriAleatori);
                    Okupa::create(["pais_id" => $territori->id, "player_id" => $player->id, "tropes" => 1]);
                    $player->tropas = $player->tropas - 1;
                    $player->save();
                    $game->save();
                    $game->refresh();

                    foreach ($jugadors as $jugador2) {
                        $usuari = $jugador2->usuari;
                        if(isset(UsuariController::$usuaris[$usuari->id])){
                            $userConn = UsuariController::$usuaris[$usuari->id];
                            $userConn->send(json_encode([
                                "method" => "accio",
                                "data" => [
                                    "territori" => $territoriAleatori,
                                    "posicio" => $player->skfNumero,
                                ]
                            ]));
                            $userConn->send(json_encode([
                                "method" => "finalFase",
                                "data" => []
                            ]));
                            $territoris[$territoriAleatori] = [
                                "posicio" => $player->skfNumero,
                                "tropas" => 1,
                            ];
                        }
                    }
                    WebsocketManager::$gameManager->enviarCanviFase($game, 1);
                }else{
                    WebsocketManager::$gameManager->canviFaseJugador($player);
                }
                break;
            case 3;
                $okupes = $player->okupes->keyBy(fn ($okupe) => $okupe->pais->nom)->all();
                $okupeAleatori = array_rand($okupes);
                $okupa = $okupes[$okupeAleatori];
                $game->torn_player = ($game->torn_player % count($jugadors)) + 1;
                $okupa->tropes = $okupa->tropes + 1;
                $player->tropas = $player->tropas - 1;

                $player->save();
                $okupa->save();
                $game->save();
                $game->refresh();

                foreach ($jugadors as $jugador2) {
                    $usuari = $jugador2->usuari;
                    if(isset(UsuariController::$usuaris[$usuari->id])){
                        $userConn = UsuariController::$usuaris[$usuari->id];
                        $userConn->send(json_encode([
                            "method" => "accio",
                            "data" => [
                                "territori" => $okupeAleatori,
                                "posicio" => $player->skfNumero,
                            ]
                        ]));
                        $userConn->send(json_encode([
                            "method" => "finalFase",
                            "data" => []
                        ]));
                    }
                }
                $game->refresh();
                if($game->jugadors->sum('tropas') == 0){
                    WebsocketManager::$gameManager->canviFaseJugador($player);
                }else{
                    WebsocketManager::$gameManager->enviarCanviFase($game, 1);
                }
                break;
            case 4;
                WebsocketManager::$gameManager->skipFaseJugador($player);
                break;
            case 5:
                WebsocketManager::$gameManager->skipFaseJugador($player);
                break;
            case 6:
                WebsocketManager::$gameManager->skipFaseJugador($player);
                break;
        }
    }
}
