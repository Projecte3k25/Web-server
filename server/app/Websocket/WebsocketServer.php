<?php

namespace App\Websocket;


use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use App\Http\Controllers\UsuariController;

class WebsocketServer implements MessageComponentInterface
{
    public \SplObjectStorage $clients;

    protected $websocketmanager;


    public function __construct()
    {
        $this->clients = new \SplObjectStorage;
        $this->websocketmanager = new WebsocketManager($this);
    }

    public function onOpen(ConnectionInterface $conn)
    {
        echo "\nClient (".$conn->resourceId.") connectat";
    }

    public function onMessage(ConnectionInterface $from, $msg){
        
        try {
            echo "\nClient (".$from->resourceId."):\n".json_encode(json_decode($msg), JSON_PRETTY_PRINT);
            $data = json_decode($msg);
            if(!$this->clients->contains($from) && (!isset($data->method) || $data->method != "login")){
                $from->close();
                echo "\nError login";
            }else{
                if(isset($data->method) && isset($data->data)){
                    $this->websocketmanager->handler[$data->method]($from, $data->data);
                }   
            }
        } catch (\Throwable $th) {
            echo $th->getMessage();
        }
    }

    public function onClose(ConnectionInterface $conn)
    {
        echo "\nClient (".$conn->resourceId.") desconnectat";
        if(isset(UsuariController::$usuaris_ids[$conn->resourceId])){
            $userId = UsuariController::$usuaris_ids[$conn->resourceId];
            unset(UsuariController::$usuaris[UsuariController::$usuaris_ids[$conn->resourceId]]);
            unset(UsuariController::$usuaris_ids[$conn->resourceId]);
            WebsocketManager::removeJugadorFromPartidas($userId);
        }
        $this->clients->detach($conn);
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        echo "\nError amb client(".$conn->resourceId."):\n".$e->getMessage();
        if(isset(UsuariController::$usuaris_ids[$conn->resourceId])){
            $userId = UsuariController::$usuaris_ids[$conn->resourceId];
            unset(UsuariController::$usuaris[UsuariController::$usuaris_ids[$conn->resourceId]]);
            unset(UsuariController::$usuaris_ids[$conn->resourceId]);
            WebsocketManager::removeJugadorFromPartidas($userId);
        }
        $conn->close();
        $this->clients->detach($conn);
    }
}

?>