<?php

namespace App\Console\Commands;

use App\Websocket\WebsocketManager;
use Illuminate\Console\Command;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use App\Websocket\WebsocketServer;
use React\EventLoop\Loop;

class ServeWebSocket extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'serve:websocket';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Serve Websocket';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $websocket = new WsServer(new WebsocketServer());

        $server = IoServer::factory(
            new HttpServer($websocket),
            8080,
            '0.0.0.0'
        );

        WebsocketManager::$gameManager->timeManager = $server->loop;

        $server->run();
    }
}
