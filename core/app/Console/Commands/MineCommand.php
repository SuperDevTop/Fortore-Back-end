<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Log;
use App\Models\User;
use App\Models\Transaction;
use App\Models\Invest;
use Carbon\Carbon;

class MineCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'demo:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
     * @return int
     */
    public function handle() : int
    {
          $invests = Invest::all();

          foreach($invests as $invest)
          {
              if(strtotime($invest->next_time) < strtotime(date("Y-m-d h:i:sa")))
              {
                 Log::insert([
                        'user_id' => 2
                    ]);
                       
                    $id = $invest->user_id;
                  
                    $invest->next_time = Carbon::parse($invest->next_time)->addDays(1)->toDateTimeString();
                   
                    $invest->return_rec_time = $invest->return_rec_time + 1;
                    $invest->save();
                    
                    // Get post_balance of the user
                    $post_balance = User::where('id', $id)
                                    ->get()[0]
                                    ->interest_wallet;

                    // Plus post_balance
                    $post_balance = $post_balance + $invest->interest;

                    // Save interest from invest //
                    $transaction = new Transaction();
                    $transaction->user_id = $id;
                    $transaction->amount = $invest->interest;
                    $transaction->charge = getAmount(0.0);
                    $transaction->post_balance = $post_balance;
                    $transaction->trx_type = '+';
                    $transaction->trx = getTrx();
                    $transaction->wallet_type = 'interest_wallet';
                    $transaction->details = $invest->interest . " SAR Interest from FORTOREMALL";
                    $transaction->save();
                    // End Save //

                    // Increase user's interest wallet 
                    $user = User::where('id', $id)->get()[0];
                    $user->interest_wallet = $user->interest_wallet + $invest->interest;
                    $user->save();

                  
              }
          }

        return 0;
    }
}
