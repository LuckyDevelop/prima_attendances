<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class generateFaceEmbeddingCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generateFaceEmbedding';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $data = [
                0.0123,
                -0.0456,
                0.0789,
                0.0345,
                -0.0122
            ];

        $this->info(json_encode($data));
    }
}
