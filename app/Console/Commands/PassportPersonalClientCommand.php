<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class PassportPersonalClientCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'passport:personalifnotexist';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create Personal Access Client if not already exist';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
      $client = \Laravel\Passport\Passport::personalAccessClient();
      if (! $client->exists()) {
        $this->call('passport:client', ['--personal' => true, '--name' => config('app.name').' Personal Access Client']);
      }
    }
}
