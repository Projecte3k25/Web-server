<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class ServeWithWebSocket extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'serve:full';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Serve Laravel and WebSocket server';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting Laravel server...');
        $laravel = new Process(['php', 'artisan', 'serve','--host=0.0.0.0','--port=8000']);
        $laravel->start();

        $this->info('Starting WebSocket server...');
        $websocket = new Process(['php', 'artisan', 'serve:websocket']);
        $websocket->start();

        while ($laravel->isRunning() || $websocket->isRunning()) {
            echo $websocket->getIncrementalOutput();
            $out = $laravel->getIncrementalOutput();
            if($out != ""){
                echo "\n".$out;
            }
           
            sleep(1);
        }
        
    }
}
