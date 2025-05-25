<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use App\Websocket\WebsocketServer;
use React\EventLoop\Loop;
use React\Socket\SocketServer;


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
        $loop = Loop::get();
        $loop->addPeriodicTimer(1, function() {});


        $server = IoServer::factory(
            new HttpServer(
                new WsServer(
                    new WebsocketServer($loop)
                )
            ), 8080, '0.0.0.0');
        
        $loop->addTimer(1, function () use ($server) {
            $server->run();
        });
        
        $loop->run();
    }
}
