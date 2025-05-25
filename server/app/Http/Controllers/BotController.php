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
                $okupes = $player->okupes->keyBy(fn ($okupe) => $okupe->pais->nom)->all();
                while($player->tropas != 0){
                    $okupeAleatori = array_rand($okupes);
                    $okupa = $okupes[$okupeAleatori];
                    $tropas = rand(1, $player->tropas);
                    
                    $okupa->tropes = $okupa->tropes + $tropas;
                    $player->tropas = $player->tropas - $tropas;

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
                                    "tropas" => $tropas
                                ]
                            ]));

                        }
                    }
                    $game->refresh();
                }
                WebsocketManager::$gameManager->skipFaseJugador($player);
                break;
            case 5:
                $okupes = $player->okupes->keyBy(fn ($okupe) => $okupe->pais->nom)->all();

                foreach ($okupes as $key => $okupa) {
                    if($okupa->tropes > 1){
                        $fronteres = WebsocketManager::$gameManager->maps["world"][$key];
                        foreach ($fronteres as $frontera) {
                            
                            $territori = $territoris[$frontera];
                            if($territori["posicio"] != $player->skfNumero){
                                if(($territori["tropas"] - ($okupa->tropes - 1)) < -2){
                                    $defensor = $game->jugadors()->firstWhere("skfNumero", $territori["posicio"]);
                                    $territori2 = Pais::firstWhere("nom",$frontera);
                                    $okupa2 = Okupa::where("pais_id", $territori2->id)->where("player_id", $defensor->id)->first();
                                    if($okupa2 == null){
                                        continue;
                                    }
                                    echo $okupa->tropes." ".$okupa2->tropes;
                                    $tropas = rand(1, $okupa->tropes-1);
                                    $dAtack = min($tropas, 3);
                                    $dDef = min($okupa2->tropes, 2);
                                    $numAtack = [];
                                    $numDef = [];
                                    for ($i = 0; $i < $dAtack; $i++) {
                                        $numAtack[] = random_int(1, 6); 
                                    }
                                    for ($i = 0; $i < $dDef; $i++) {
                                        $numDef[] = random_int(1, 6); 
                                    }

                                    rsort($numAtack);
                                    rsort($numDef);

                                    $pAtack = 0;
                                    $pDef = 0;
                                    foreach ($numDef as $key => $value) {
                                        if($numAtack[$key] <= $value){
                                            $pAtack++;
                                        }else{
                                            $pDef++;
                                        }
                                    }
                            
                                    $conquista = (($okupa2->tropes - $pDef) == 0);
                                    if($conquista){
                                        $okupa->tropes = ($okupa->tropes - $tropas);
                                        $okupa2->tropes = ($tropas - $pAtack);
                                        $okupa2->player_id = $player->id; 
                                        WebsocketManager::$gameManager->cardsToRedem[$player->id] = true;
                                    }else{
                                        $okupa->tropes = ($okupa->tropes - $pAtack);
                                        $okupa2->tropes = ($okupa2->tropes - $pDef);
                                    }
                                    $okupa->save();
                                    $okupa2->save();

                                    foreach ($jugadors as $jugador2) {
                                        $usuari = $jugador2->usuari;
                                        if(isset(UsuariController::$usuaris[$usuari->id])){
                                            $userConn = UsuariController::$usuaris[$usuari->id];
                                            $userConn->send(json_encode([
                                                "method" => "accio",
                                                "data" => [
                                                    "dausAtac" => $numAtack,
                                                    "dausDefensa" => $numDef,
                                                    "from" => $okupa->pais->nom,
                                                    "to" => $okupa2->pais->nom,
                                                    "atacTropas" => $pAtack,
                                                    "defTropas" => $pDef,
                                                    "conquista" => $conquista,
                                                    "tropasTotalsAtac" => $okupa->tropes,
                                                    "torpasTotalsDefensa" => $okupa2->tropes,
                                                ]
                                            ]));
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                WebsocketManager::$gameManager->skipFaseJugador($player);
                break;
            case 6:
                WebsocketManager::$gameManager->skipFaseJugador($player);
                break;
        }
    }
}
