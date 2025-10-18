<?php

public function sendDueEmail($branchId)
    {
        $branch = Branch::findOrFail($branchId);
        $latestSubscribe = $branch->latestSubscribe;

        if (!$latestSubscribe || !$latestSubscribe->end_date) {
            return redirect()->back()->with('error', 'No active subscription found for this branch.');
        }

        $mailData = [
            'branch_name' => $branch->branch_name,
            'recipient_name' => $branch->manager ? $branch->manager->name : 'Branch Manager',
            'package_name' => "hello",
            'start_date' => "9-10-2025",
            'end_date' => "9-10-2025",
            'status' => 'Subscription Due',
        ];

        try {
            // Send to branch manager if exists
            if ($branch->manager && $branch->manager->email) {
                Mail::to($branch->manager->email)->send(new BranchSubscriptionUpdate($mailData));
                Log::info("Due email sent to branch manager: {$branch->manager->email}");
            }

            // Send to business owner
            if ($branch->business && $branch->business->owner && $branch->business->owner->email) {
                $mailData['recipient_name'] = $branch->business->owner->name;
                Mail::to($branch->business->owner->email)->send(new BranchSubscriptionUpdate($mailData));
                Log::info("Due email sent to business owner: {$branch->business->owner->email}");
            }

            // Also send to the business email if different from owner's email
            if ($branch->business && $branch->business->email && 
                (!$branch->business->owner || $branch->business->email !== $branch->business->owner->email)) {
                $mailData['recipient_name'] = $branch->business->name;
                Mail::to($branch->business->email)->send(new BranchSubscriptionUpdate($mailData));
                Log::info("Due email sent to business email: {$branch->business->email}");
            }

            return redirect()->back()->with('success', 'Due notification emails sent successfully.');
        } catch (\Exception $e) {
            Log::error("Error sending due emails for branch {$branchId}: " . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to send notification emails. Please try again.');
        }
    }

    namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BranchSubscriptionUpdate extends Mailable
{
    use Queueable, SerializesModels;

    public $mailData;

    /**
     * Create a new message instance.
     */
    public function __construct($mailData)
    {
        $this->mailData = $mailData;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Branch Subscription Update',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'mail.branch-subscription-update',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}

