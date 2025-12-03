<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Payment;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Carbon;
use App\Mail\TenantOverdueMail;
use App\Mail\TenantVoidedMail;
use App\Mail\TenantDepositReminderMail; // ✅ add this new mail

class UpdateTenantStatus extends Command
{
    protected $signature = 'update:tenant-status';
    protected $description = 'Update tenant payment statuses and send email notifications if overdue or voided.';

    public function handle()
    {
        $today = Carbon::now();
        $tenants = User::where('role', 'tenant')->get();

        foreach ($tenants as $tenant) {
            if (!$tenant->email) continue;

            $statusChanged = false;
            $oldStatus = $tenant->status;

            $hasPaidDeposit = Payment::where('user_id', $tenant->id)
                ->where('payment_for', 'Deposit')
                ->exists();

            $daysSinceCreation = $tenant->created_at->diffInDays($today);
            $daysSinceUpdate = $tenant->updated_at->diffInDays($today);

            // ⚠️ 1️⃣ Send deposit reminder after 3 days (if still unpaid)
            if (!$hasPaidDeposit && $daysSinceCreation == 3 && $tenant->status !== 'void') {
                try {
                    Mail::to($tenant->email)->send(new TenantDepositReminderMail($tenant));
                    $this->info("📧 Sent deposit reminder to {$tenant->email}");
                } catch (\Exception $e) {
                    $this->error("❌ Failed to send deposit reminder to {$tenant->email}: {$e->getMessage()}");
                }
            }

            // 🛑 2️⃣ Account voided after 7 days (still unpaid)
            if (!$hasPaidDeposit && $daysSinceCreation >= 7 && $tenant->status !== 'void') {
                $tenant->status = 'void';
                $tenant->rental_payment_status = 'pending';
                $tenant->utility_payment_status = 'pending';
                $statusChanged = true;

                try {
                    Mail::to($tenant->email)->send(new TenantVoidedMail($tenant));
                    $this->info("📩 Sent voided notice to {$tenant->email}");
                } catch (\Exception $e) {
                    $this->error("❌ Failed to send void email to {$tenant->email}: {$e->getMessage()}");
                }

                $this->info("{$tenant->name} → Account voided.");
            }

            // 🕒 3️⃣ Overdue payments
            if (($tenant->rent_balance > 0 || $tenant->utility_balance > 0)
                && $tenant->rental_payment_status !== 'overdue'
            ) {
                $tenant->rental_payment_status = 'overdue';
                $tenant->utility_payment_status = 'overdue';
                $statusChanged = true;

                try {
                    Mail::to($tenant->email)->send(new TenantOverdueMail($tenant));
                    $this->info("📩 Sent overdue notice to {$tenant->email}");
                } catch (\Exception $e) {
                    $this->error("❌ Failed to send overdue email to {$tenant->email}: {$e->getMessage()}");
                }
            }

            // 💰 4️⃣ Settled (no balance)
            if ($tenant->rent_balance == 0 && $tenant->utility_balance == 0) {
                $tenant->rental_payment_status = 'settled';
                $tenant->utility_payment_status = 'settled';
                $statusChanged = true;
            }

            // ⏳ 5️⃣ Pending (balance > 0, not overdue)
            if (($tenant->rent_balance > 0 || $tenant->utility_balance > 0)
                && $daysSinceUpdate <= 30
            ) {
                $tenant->rental_payment_status = 'pending';
                $tenant->utility_payment_status = 'pending';
                $statusChanged = true;
            }

            if ($statusChanged) {
                $tenant->save();
                if ($oldStatus !== $tenant->status) {
                    $this->info("✅ Updated {$tenant->name}'s status to {$tenant->status}");
                }
            }
        }

        $this->info("🎯 Tenant status check complete.");
    }
}
