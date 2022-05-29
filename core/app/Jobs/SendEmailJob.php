<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $config;
    private $email;
    private $username;
    private $email_from;
    private $sitetitle;
    private $subj;
    private $message;
    private $general;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($config, $email, $username, $email_from, $sitetitle, $subj, $message, $general)
    {
        //
        $this->config = $config;
        $this->email = $email;
        $this->username = $username;
        $this->email_from = $email_from;
        $this->sitetitle = $sitetitle;
        $this->subj = $subj;
        $this->message = $message;
        $this->general = $general;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if ($this->config->name == 'php') {
            send_php_mail($this->email, $this->username, $this->email_from, $this->subj, $this->message, $this->general);
        } else if ($this->config->name == 'smtp') {
            send_smtp_mail($this->config, $this->email, $this->username, $this->email_from, $this->sitetitle, $this->subj, $this->message);
        } else if ($this->config->name == 'sendgrid') {
            send_sendGrid_mail($this->config, $this->email, $this->username, $this->email_from, $this->sitetitle, $this->subj, $this->message);
        } else if ($this->config->name == 'mailjet') {
            send_mailjet_mail($this->config, $this->email, $this->username, $this->email_from, $this->sitetitle, $this->subj, $this->message);
        }
    }
}
