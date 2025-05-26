<?php

namespace App\Websocket;

use App\Http\Controllers\BotController;
use Ratchet\ConnectionInterface;
use App\Http\Controllers\JugadorController;
use App\Http\Controllers\UsuariController;
use App\Http\Controllers\PartidaController;
use App\Models\Continent;
use App\Models\Jugador;
use App\Models\Pais;
use App\Models\Okupa;
use App\Models\Partida;
use App\Models\Carta;
use App\Models\Usuari;
use React\EventLoop\Loop;

class GameManager
{
    public $handler;
    public $websocket_manager;
    public $maps = [];
    public $loading = [];
    public $timeManager;
    public $cartes = [];
    public $timers = [];
    public $cardsToRedem = [];
    public $deathPlayers = [];

    public $eloTable = [
        10, 5, -5, -10
    ];
    public $tradeTable = [
        4, 6, 8, 10, 12, 15
    ];
    public $lastTrade = [];

    public function __construct(WebsocketManager $websocket_manager)
    {
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
        echo "Mapas: \n" . json_encode($this->maps, JSON_PRETTY_PRINT);
    }

    public function startPartida(ConnectionInterface $from, $data)
    {
        $player = JugadorController::getJugadorByUser($from);

        $game = $player->partida;
        $jugadors = $game->jugadors;

        $adminId = $game->admin_id;
        if ($adminId != $player->usuari->id) {
            WebsocketManager::error($from, "No pots començar la partida");
            return;
        } else if (count($jugadors) < 2) {
            WebsocketManager::error($from, "Necesites minim dos jugadors per començar la partida.");
            return;
        }

        $nums = range(1, count(Carta::all()));
        $nums[] = 1;
        shuffle($nums);
        $this->cartes[$game->id] = $nums;
        $this->deathPlayers[$game->id] = [];

        $nums = range(1, count($jugadors));
        $users = [];
        foreach ($jugadors as $jugador) {
            $user = $jugador->usuari()->first()->makeHidden(['password', 'login']);
            $index = rand(0, count($nums) - 1);
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
            if (JugadorController::jugadorEnPartida($jugador, $game)) {
                $userConn = UsuariController::$usuaris[$usuari->id];
                $this->loading[$game->id][$usuari->id] = 1;
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



    public function loaded(ConnectionInterface $from, $data)
    {
        $player = JugadorController::getJugadorByUser($from);
        $game = $player->partida;
        $jugadors = $game->jugadors;

        foreach ($jugadors as $jugador) {
            $usuari = $jugador->usuari;
            if (JugadorController::jugadorEnPartida($jugador, $game)) {
                $userConn = UsuariController::$usuaris[$usuari->id];
                $userConn->send(json_encode([
                    "method" => "loaded",
                    "data" => [
                        "id" => $player->usuari->id,
                    ],
                ]));
                unset($this->loading[$game->id][$player->usuari->id]);
            }
        }
        if (count($this->loading[$game->id]) == 0) {
            foreach ($jugadors as $jugador) {
                $usuari = $jugador->usuari;
                if (JugadorController::jugadorEnPartida($jugador, $game)) {
                    $userConn = UsuariController::$usuaris[$usuari->id];
                    $userConn->send(json_encode([
                        "method" => "allLoaded",
                        "data" => [],
                    ]));
                }
            }
            if (isset($this->loading[$game->id])) {
                unset($this->loading[$game->id]);
            }
            $this->canviFase($from, $data);
        }
    }

    public function skipFase(ConnectionInterface $from, $data)
    {
        $userId = UsuariController::$usuaris_ids[$from->resourceId];
        $gameId = JugadorController::$partida_jugador[$userId];
        $player = Jugador::where('skfUser_id', $userId)->where('skfPartida_id', $gameId)->first();
        $game = $player->partida;

        foreach ($game->jugadors as $jugador) {
            $usuari = $jugador->usuari;
            if (JugadorController::jugadorEnPartida($jugador, $game)) {
                $userConn = UsuariController::$usuaris[$usuari->id];
                $userConn->send(json_encode([
                    "method" => "finalFase",
                    "data" => []
                ]));
            }
        }

        if ($game->estat_torn >= 4 && $game->estat_torn < 7) {
            $this->canviFase($from, $data);
        }
    }

    public function skipFaseJugador(Jugador $player)
    {
        $game = $player->partida;

        if ($game->estat_torn < 4) {
            BotController::accio($player);
        } else {
            foreach ($game->jugadors as $jugador) {
                $usuari = $jugador->usuari;
                if (JugadorController::jugadorEnPartida($jugador, $game)) {
                    $userConn = UsuariController::$usuaris[$usuari->id];
                    $userConn->send(json_encode([
                        "method" => "finalFase",
                        "data" => []
                    ]));
                }
            }

            if ($game->estat_torn >= 4 && $game->estat_torn < 7) {
                $this->canviFaseJugador($player);
            }
        }
    }

    public function nextTorn(Partida $game)
    {
        $game->refresh();
        $deathsPlayers = $this->deathPlayers[$game->id];
        $playerIds = [];
        foreach ($deathsPlayers as $player) {
            $playerIds[] = $player->id;
        }

        $i = 0;
        while (true) {
            echo "\nBlocat " . $game->torn_player;

            $game->torn_player = ($game->torn_player % count($game->jugadors)) + 1;
            $jugador = $game->jugadors()->where("skfNumero", $game->torn_player)->first();
            if (!in_array($jugador->id, $playerIds)) {
                break;
            }
            $i++;
            if($i == $game->jugadors){
               
                $game->estat_torn = 7;
                if(isset($this->timers[$game->id])){
                    $this->timeManager->cancelTimer($this->timers[$game->id]);
                }
                break;
            }
        }
        $game->save();
    }

    public function canviFaseJugador(Jugador $player)
    {
        $game = $player->partida;
        switch ($game->estat_torn) {
            case 1;
                $game->estat_torn = 2;
                $game->torn_player = 1;
                $game->save();
                $game->refresh();
                $this->enviarCanviFase($game, 1);

                break;
            case 2;
                $game->estat_torn = 3;
                $game->torn_player = 1;
                $game->save();
                $game->refresh();
                $this->enviarCanviFase($game, 1);
                break;
            case 3;
                $game->estat_torn = 4;
                $game->torn_player = 1;
                $game->save();
                $game->refresh();
                $this->eventsFase($game);
                $this->enviarCanviFase($game, 60);
                break;
            case 4;
                $game->estat_torn = 5;
                $game->save();
                $game->refresh();
                $this->eventsFase($game);
                $this->enviarCanviFase($game, 60);
                break;
            case 5;
                $game->estat_torn = 6;
                $game->save();
                $game->refresh();
                $this->eventsFase($game);
                $this->enviarCanviFase($game, 60);
                break;
            case 6;
                $game->estat_torn = 4;
                $game->save();
                $this->nextTorn($game);
                $game->refresh();
                $this->eventsFase($game);
                $this->enviarCanviFase($game, 60);
                break;
        }
    }


    public function canviFase(ConnectionInterface $from, $data)
    {
        $userId = UsuariController::$usuaris_ids[$from->resourceId];
        $gameId = JugadorController::$partida_jugador[$userId];
        $player = Jugador::where('skfUser_id', $userId)->where('skfPartida_id', $gameId)->first();
        $this->canviFaseJugador($player);
    }

    public function enviarCanviFase(Partida $game, $time)
    {
        $jugadors = $game->jugadors;
        $jugador = $game->jugadors()->where("skfNumero", $game->torn_player)->first();
        $cartes = $jugador->cartes;
        $newCartes = [];
        foreach ($cartes as $carta) {
            if ($carta->tipus == 1) {
                $newCartes[] = [
                    "nom" =>  "comodin",
                    "tipus" => "comodin",
                ];
            } else {
                $newCartes[] = [
                    "nom" =>  $carta->pais->nom,
                    "tipus" => $carta->tipusCarta->nom,
                ];
            }
        }
        $territoris = [];
        foreach ($jugadors as $jugador2) {
            $okupes = $jugador2->okupes;
            foreach ($okupes as $okupe) {
                $territoris[$okupe->pais->nom] = [
                    "posicio" => $jugador2->skfNumero,
                    "tropas" => $okupe->tropes,
                ];
            }
        }

        foreach ($jugadors as $jugador2) {
            $usuari = $jugador2->usuari;
            if (JugadorController::jugadorEnPartida($jugador2, $game)) {
                $userConn = UsuariController::$usuaris[$usuari->id];
                $userConn->send(json_encode([
                    "method" => "canviFase",
                    "data" => [
                        "fase" => $game->estat->nom,
                        "jugadorActual" => $jugador->usuari()->first()->makeHidden(['password', 'login']),
                        "posicio" => $game->torn_player,
                        "tropas" => $jugador->tropas,
                        "cartas" => $newCartes,
                        "temps" => $time,
                        "territoris" => $territoris,
                    ]
                ]));
            }
        }
        if (isset($this->timers[$game->id])) {
            $this->timeManager->cancelTimer($this->timers[$game->id]);
        }
        if (!JugadorController::jugadorEnPartida($jugador, $game)) {
            BotController::accio($jugador);
        } else {
            $timer = $this->timeManager->addTimer($time, function () use ($jugador) {
                $this->skipFaseJugador($jugador);
            });
            $this->timers[$game->id] = $timer;
        }
    }

    public function bonusContinent(Jugador $jugador)
    {
        $jugador->refresh();
        $continents = Continent::all();
        $territorisJugador = $jugador->okupes;
        $territorisAgrupats = [];
        $bonus = 0;
        foreach ($territorisJugador as $okupe) {
            if (!isset($territorisAgrupats[$okupe->pais->continent->id])) {
                $territorisAgrupats[$okupe->pais->continent->id] = [];
            }
            $territorisAgrupats[$okupe->pais->continent->id][] = $okupe;
        }
        foreach ($continents as $continent) {
            if (isset($territorisAgrupats[$continent->id])) {
                if (count($continent->paisos) == count($territorisAgrupats[$continent->id])) {
                    $bonus = $bonus + $continent->reforc_tropes;
                }
            }
        }
        return $bonus;
    }

    public function eventsFase(Partida $game)
    {
        $player = $game->jugadors()->where("skfNumero", $game->torn_player)->first();

        switch ($game->estat_torn) {
            case 4;
                $tropas = $this->bonusContinent($player);
                $tropas += floor(count($player->okupes) / 3);
                $player->tropas += $tropas;
                $player->save();
                break;
            case 6;
                if (isset($this->cardsToRedem[$player->id])) {
                    unset($this->cardsToRedem[$player->id]);
                    if (isset($this->cartes[$game->id][0])) {
                        $carta = $this->cartes[$game->id][0];
                        unset($this->cartes[$game->id][0]);
                        $this->cartes[$game->id] = array_values($this->cartes[$game->id]);
                        $newCarta = Carta::find($carta);
                        $player->cartes()->attach($carta);
                        if (JugadorController::jugadorEnPartida($player, $game)) {
                            UsuariController::$usuaris[$player->usuari->id]->send(json_encode([
                                "method" => "robaCarta",
                                "data" => [
                                    "nom" =>  $newCarta->pais->nom,
                                    "tipus" => $newCarta->tipusCarta->nom,
                                ]
                            ]));
                        }
                    }
                }
                break;
        }
        $game->save();
        $game->refresh();
    }

    private function validateTrade($cards): bool
    {
        if (count($cards) !== 3) {
            return false;
        }
        $LlistatTipus = array_map(function ($card) {
            return Pais::where('nom', $card->nom)
                ->with('carta')
                ->first()?->carta->tipus;
        }, $cards);

        $comodins = count(array_filter($LlistatTipus, fn ($tipus) => $tipus === 1));
        $tipusSenseComodi = array_filter($LlistatTipus, fn ($tipus) => $tipus != 1);
        if ($comodins === 3) return false;

        if ($comodins == 1) return true;

        $tipusUnics = array_unique($tipusSenseComodi);

        if ($comodins === 0) {
            if (count($tipusUnics) === 1) return true;

            sort($tipusUnics);
            $setValid = [2, 3, 4];
            if ($tipusUnics === $setValid) return true;
        }

        return false;
    }

    public function tradeCards(ConnectionInterface $from, $data)
    {
        if ($this->validateTrade($data->cards)) {
            $player = JugadorController::getJugadorByUser($from);
            if (!isset($this->lastTrade[$player->partida->id])) {
                $this->lastTrade[$player->partida->id] = 0;
            }
            $lastTrade = $this->lastTrade[$player->partida->id];
            $tropas = 0;
            if ($lastTrade >= 6) {
                $tropas = 15 + (($lastTrade - 6) * 5);
            } else {

                $tropas = $this->tradeTable[$lastTrade];
            }
            $this->lastTrade[$player->partida->id] = $lastTrade + 1;

            $okupes = $player->okupes;
            $territoris = [];
            foreach ($okupes as $okupe) {
                $territoris[$okupe->pais->nom] = $okupe;
            }

            $bonus = [];
            foreach ($data->cards as $cards) {
                $carta = Pais::where('nom', $cards->nom)
                    ->with('carta')
                    ->first()?->carta;
                $player->cartes()->detach($carta->id);

                if (isset($territoris[$cards->nom])) {
                    $bonus[] = $cards->nom;
                    $territoris[$cards->nom]->tropes += 2;
                    $territoris[$cards->nom]->save();
                }
            }

            $player->tropas += $lastTrade;
            $player->save();

            UsuariController::$usuaris[$player->usuari->id]->send(json_encode([
                "method" => "tradeCards",
                "data" => [
                    "tropas" => $tropas,
                    "territorios" => $bonus,
                ],
            ]));
        } else {
            WebsocketManager::error($from, "Combinació de cartes invalides.");
        }
    }

    public function accio(ConnectionInterface $from, $data)
    {
        $userId = UsuariController::$usuaris_ids[$from->resourceId];
        $gameId = JugadorController::$partida_jugador[$userId];
        $player = Jugador::where('skfUser_id', $userId)->where('skfPartida_id', $gameId)->first();
        $game = $player->partida;
        $jugadors = $game->jugadors;
        $jugador = $game->jugadors()->where("skfNumero", $game->torn_player)->first();

        if ($jugador->id != $player->id) {
            WebsocketManager::error($from, "No es el teu torn.");
            return;
        }

        $territoris = [];
        foreach ($jugadors as $jugador2) {
            $okupes = $jugador2->okupes;
            foreach ($okupes as $okupe) {
                $territoris[$okupe->pais->nom] = [
                    "posicio" => $jugador2->skfNumero,
                    "tropas" => $okupe->tropes,
                ];
            }
        }

        switch ($game->estat_torn) {
            case 2;
                if (!isset($territoris[$data->territori])) {
                    $game->torn_player = ($game->torn_player % count($jugadors)) + 1;
                    $territori = Pais::firstWhere("nom", $data->territori);
                    Okupa::create(["pais_id" => $territori->id, "player_id" => $player->id, "tropes" => 1]);
                    $player->tropas = $player->tropas - 1;
                    $player->save();
                    $game->save();
                    $game->refresh();

                    foreach ($jugadors as $jugador2) {
                        $usuari = $jugador2->usuari;
                        if (JugadorController::jugadorEnPartida($jugador2, $game)) {
                            $userConn = UsuariController::$usuaris[$usuari->id];
                            $userConn->send(json_encode([
                                "method" => "accio",
                                "data" => [
                                    "territori" => $data->territori,
                                    "posicio" => $player->skfNumero,
                                ]
                            ]));
                            $userConn->send(json_encode([
                                "method" => "finalFase",
                                "data" => []
                            ]));
                            $territoris[$data->territori] = [
                                "posicio" => $player->skfNumero,
                                "tropas" => 1,
                            ];
                        }
                    }

                    if (count($territoris) == count($this->maps["world"])) {
                        $this->canviFase($from, $data);
                    } else {
                        $this->enviarCanviFase($game, 1);
                    }
                } else {
                    WebsocketManager::error($from, "No pot colocar una tropa en aquest territori.");
                }
                break;
            case 3;
                if (isset($territoris[$data->territori]) && $territoris[$data->territori]["posicio"] == $player->skfNumero) {
                    $game->torn_player = ($game->torn_player % count($jugadors)) + 1;
                    $territori = Pais::firstWhere("nom", $data->territori);
                    $okupa = Okupa::where("pais_id", $territori->id)->where("player_id", $player->id)->first();
                    $okupa->tropes = $okupa->tropes + 1;
                    $player->tropas = $player->tropas - 1;

                    $player->save();
                    $okupa->save();
                    $game->save();
                    $game->refresh();

                    foreach ($jugadors as $jugador2) {
                        $usuari = $jugador2->usuari;
                        if (JugadorController::jugadorEnPartida($jugador2, $game)) {
                            $userConn = UsuariController::$usuaris[$usuari->id];
                            $userConn->send(json_encode([
                                "method" => "accio",
                                "data" => [
                                    "territori" => $data->territori,
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
                    if ($game->jugadors->sum('tropas') == 0) {
                        $this->canviFase($from, $data);
                    } else {
                        $this->enviarCanviFase($game, 1);
                    }
                } else {
                    WebsocketManager::error($from, "No pot colocar una tropa en aquest territori.");
                }
                break;

            case 4;
                if (isset($territoris[$data->territori]) && $territoris[$data->territori]["posicio"] == $player->skfNumero) {
                    if ($data->tropas < 0) {
                        WebsocketManager::error($from, "El numero de tropes ha de ser positiu.");
                        return;
                    } else if ($data->tropas > $player->tropas) {
                        WebsocketManager::error($from, "No tens tropes suficients.");
                        return;
                    }

                    $territori = Pais::firstWhere("nom", $data->territori);
                    $okupa = Okupa::where("pais_id", $territori->id)->where("player_id", $player->id)->first();

                    $okupa->tropes = $okupa->tropes + $data->tropas;
                    $player->tropas = $player->tropas - $data->tropas;

                    $player->save();
                    $okupa->save();
                    $game->save();
                    $game->refresh();

                    foreach ($jugadors as $jugador2) {
                        $usuari = $jugador2->usuari;
                        if (JugadorController::jugadorEnPartida($jugador2, $game)) {
                            $userConn = UsuariController::$usuaris[$usuari->id];
                            $userConn->send(json_encode([
                                "method" => "accio",
                                "data" => [
                                    "territori" => $data->territori,
                                    "posicio" => $player->skfNumero,
                                    "tropas" => $data->tropas
                                ]
                            ]));
                        }
                    }
                    $game->refresh();
                } else {
                    WebsocketManager::error($from, "No pot colocar tropes en aquest territori.");
                }
                break;
            case 5:
                if (isset($territoris[$data->from]) && $territoris[$data->from]["posicio"] == $player->skfNumero) {
                    if (isset($territoris[$data->to]) && $territoris[$data->to]["posicio"] != $player->skfNumero) {
                        if ($data->tropas <= 0) {
                            WebsocketManager::error($from, "Has de atacar utlitzant una o més tropes.");
                            return;
                        }
                        $defensor = $game->jugadors()->firstWhere("skfNumero", $territoris[$data->to]["posicio"]);
                        $territori = Pais::firstWhere("nom", $data->from);
                        $okupa = Okupa::where("pais_id", $territori->id)->where("player_id", $player->id)->first();
                        $territori = Pais::firstWhere("nom", $data->to);
                        $okupa2 = Okupa::where("pais_id", $territori->id)->where("player_id", $defensor->id)->first();

                        if ($okupa->tropes > $data->tropas) {
                            $dAtack = min($data->tropas, 3);
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
                                if ($numAtack[$key] <= $value) {
                                    $pAtack++;
                                } else {
                                    $pDef++;
                                }
                            }

                            $conquista = (($okupa2->tropes - $pDef) == 0);
                            if ($conquista) {
                                $okupa->tropes = ($okupa->tropes - $data->tropas);
                                $okupa2->tropes = ($data->tropas - $pAtack);
                                $okupa2->player_id = $player->id;
                                $this->cardsToRedem[$player->id] = true;
                            } else {
                                $okupa->tropes = ($okupa->tropes - $pAtack);
                                $okupa2->tropes = ($okupa2->tropes - $pDef);
                            }
                            $okupa->save();
                            $okupa2->save();

                            foreach ($jugadors as $jugador2) {
                                $usuari = $jugador2->usuari;
                                if (JugadorController::jugadorEnPartida($jugador2, $game)) {
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

                            $defensor->refresh();
                            if (count($defensor->okupes) == 0) {
                                $this->deathPlayers[$defensor->partida->id][] = $defensor;
                                $userConn = UsuariController::$usuaris[$defensor->usuari->id];
                                $userConn->send(json_encode([
                                    "method" => "jugadorEliminado",
                                    "data" => ""
                                ]));
                                if (count($this->deathPlayers[$defensor->partida->id]) == count($defensor->partida->jugadors) - 1) {
                                    if (isset($this->timers[$defensor->partida->id])) {
                                        $this->timeManager->cancelTimer($this->timers[$defensor->partida->id]);
                                    }
                                    $defensor->partida->estat_torn = 7;
                                    $defensor->partida->save();
                                    $this->deathPlayers[$defensor->partida->id][] = $player;
                                    $reversed = array_values(array_reverse($this->deathPlayers[$defensor->partida->id]));
                                    $result = [];
                                    $ranked = $defensor->partida->tipus == "Ranked";
                                    foreach ($reversed as $index => $player) {
                                        $user = Usuari::find($player->usuari->id);
                                        $user->games++;
                                        if ($index == 0) {
                                            $user->wins++;
                                        }
                                        $eloChange = 0;
                                        if ($ranked) {
                                            $eloChange = $this->eloTable[$key];
                                            $user->elo += $eloChange;
                                        }
                                        $result[] = [
                                            "jugador" => [
                                                "id" => $user->id,
                                                "nom" => $user->nom,
                                                "avatar" => $user->avatar,
                                                "wins" => $user->wins,
                                                "games" => $user->games,
                                                "elo" => $user->elo,
                                            ],
                                            "eloChange" => $eloChange,
                                        ];
                                        $user->save();
                                    }


                                    foreach ($jugadors as $jugador2) {
                                        $usuari = $jugador2->usuari;
                                        if (JugadorController::jugadorEnPartida($jugador2, $game)) {
                                            $userConn = UsuariController::$usuaris[$usuari->id];
                                            $userConn->send(json_encode([
                                                "method" => "partidaTerminada",
                                                "data" => $result,
                                            ]));
                                        }
                                    }
                                }
                            }
                        } else {
                            WebsocketManager::error($from, "No tens tropes suficients per atacar.");
                        }
                    } else {
                        WebsocketManager::error($from, "No pots atacar a aquest territori.");
                    }
                } else {
                    WebsocketManager::error($from, "No pots atacar utilitzant aquest territori.");
                }
                break;
            case 6:
                if (isset($territoris[$data->from]) && $territoris[$data->from]["posicio"] == $player->skfNumero) {
                    if (isset($territoris[$data->to]) && $territoris[$data->to]["posicio"] == $player->skfNumero) {
                        if ($data->tropas <= 0) {
                            WebsocketManager::error($from, "Has de seleccionar utlitzant una o més tropes per recolocar.");
                            return;
                        }
                        $territori = Pais::firstWhere("nom", $data->from);
                        $okupa = Okupa::where("pais_id", $territori->id)->where("player_id", $player->id)->first();
                        $territori = Pais::firstWhere("nom", $data->to);
                        $okupa2 = Okupa::where("pais_id", $territori->id)->where("player_id", $player->id)->first();

                        if ($okupa->tropes > $data->tropas) {

                            $okupa->tropes = ($okupa->tropes - $data->tropas);
                            $okupa2->tropes = ($okupa2->tropes + $data->tropas);

                            $okupa->save();
                            $okupa2->save();

                            foreach ($jugadors as $jugador2) {
                                $usuari = $jugador2->usuari;
                                if (JugadorController::jugadorEnPartida($jugador2, $game)) {
                                    $userConn = UsuariController::$usuaris[$usuari->id];
                                    $userConn->send(json_encode([
                                        "method" => "accio",
                                        "data" => [
                                            "from" => $okupa->pais->nom,
                                            "to" => $okupa2->pais->nom,
                                            "tropas" => $data->tropas,
                                        ]
                                    ]));
                                }
                            }
                        } else {
                            WebsocketManager::error($from, "No tens tropes suficients per recolocar.");
                        }
                    } else {
                        WebsocketManager::error($from, "No pots recolocar tropes a aquest territori.");
                    }
                } else {
                    WebsocketManager::error($from, "No pots recolocar tropes utilitzant aquest territori.");
                }
                break;
        }
    }
}
