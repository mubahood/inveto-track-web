<?php

namespace App\Jobs;

use App\Models\BudgetProgram;
use App\Models\User;
use App\Models\Utils;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendBudgetItemUpdateEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $budgetItem;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($budgetItem)
    {
        $this->budgetItem = $budgetItem;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $data = $this->budgetItem;
        
        // Generate email content
        $budget_download_link = url('budget-program-print?id=' . $data->budget_program_id);
        $unit_price = number_format($data->unit_price);
        $quantity = number_format($data->quantity);
        $invested_amount = number_format($data->invested_amount);
        $balance = number_format($data->balance);
        $percentage_done = round($data->percentage_done, 2);
        
        $mail_body = <<<EOD
                    <p>Dear Admin,</p><br>
                    <p>Budget item <b>{$data->name} - {$data->category->name}</b> has been updated.</p>
                    <p><b>Quantity:</b> $quantity</p>
                    <p><b>Unit price:</b> {$unit_price}</p>
                    <p><b>Invested Amount:</b> {$invested_amount}</p>
                    <p><b>Percentage Done:</b> {$percentage_done}%</p>
                    <p><b>Balance:</b> {$balance}</p>
                    <p><b>Details:</b> {$data->category->details}</p>
                    <p>Click <a href="{$budget_download_link}">here to DOWNLOAD UPDATED Budget</a> pdf.</p>
                    <br><p>Thank you.</p>
                EOD;
        
        // Get all company users' emails
        $users = User::where('company_id', $data->company_id)->get();
        $emails = [];
        
        foreach ($users as $user) {
            if (filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
                $emails[] = $user->email;
            }
            if (filter_var($user->username, FILTER_VALIDATE_EMAIL)) {
                if (!in_array($user->username, $emails)) {
                    $emails[] = $user->username;
                }
            }
        }
        
        // Add notification email from config (instead of hardcoded)
        $notificationEmail = config('mail.notification_email', 'mubahood360@gmail.com');
        if (!in_array($notificationEmail, $emails)) {
            $emails[] = $notificationEmail;
        }
        
        // Get program name for subject
        $program = BudgetProgram::find($data->budget_program_id);
        $title = $program->name . " - Budget Updates.";
        
        // Prepare email data
        $emailData = [
            'email' => $emails,
            'subject' => $title,
            'body' => $mail_body,
            'data' => $mail_body,
            'name' => 'Admin'
        ];
        
        // Send email
        try {
            Utils::mail_sender($emailData);
        } catch (\Throwable $th) {
            // Log error but don't fail the job
            Log::error('Failed to send budget item update email: ' . $th->getMessage());
        }
    }
}
