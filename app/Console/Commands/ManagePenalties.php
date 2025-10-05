<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Bill;
use Carbon\Carbon;
use App\Services\PaymentBreakdownService;

class ManagePenalties extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:manage-penalties';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Apply penalties to overdue bills';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting penalty management...');

        Bill::with('reading')
            ->where('isPaid', false)
            ->chunk(100, function ($bills) {
                $paymentBreakdownService = new PaymentBreakdownService;
                $penalties = $paymentBreakdownService::getPenalty();

                $currentTimestamp = Carbon::now()->startOfDay(); // Use current day

                foreach ($bills as $bill) {

                    // Skip bills that already have a penalty
                    if ($bill->hasPenalty) {
                        continue;
                    }

                    $dueTimestamp = Carbon::parse($bill->due_date)->startOfDay();

                    // Skip bills not yet due
                    if ($currentTimestamp->lte($dueTimestamp)) {
                        continue;
                    }

                    // Calculate overdue days
                    $dueCount = $dueTimestamp->diffInDays($currentTimestamp);

                    $penalty = $this->findPenaltyForDueCount($penalties, $dueCount);

                    if ($penalty === null) {
                        continue;
                    }

                    $this->applyPenaltyToBill($bill, $penalty);
                    $this->info("Applied penalty to Bill ID: {$bill->id}");
                }
            });

        $this->info('Penalty management completed.');
    }

    /**
     * Find the penalty range that fits the due count.
     */
    protected function findPenaltyForDueCount($penalties, int $dueCount)
    {
        foreach ($penalties as $penalty) {
            $from = (int) $penalty['due_from'];
            $to = $penalty['due_to'] === '*' ? PHP_INT_MAX : (int) $penalty['due_to'];

            if ($dueCount >= $from && $dueCount <= $to) {
                return $penalty;
            }
        }
        return null;
    }

    /**
     * Apply penalty to a single bill.
     */
    protected function applyPenaltyToBill(Bill $bill, $penalty)
{
    $amountPayable = $bill->amount; // original billing
    $penaltyAmount = 0;

    if (strtolower($penalty['amount_type']) === 'percentage') {
        $penaltyAmount = $amountPayable * ($penalty['amount'] / 100);
    } else {
        $penaltyAmount = $penalty['amount'];
    }

    // Add penalty directly to the total amount
    $totalAmount = $amountPayable + $penaltyAmount;

    $bill->update([
        'amount' => $totalAmount,      // total amount includes penalty
        'penalty' => $penaltyAmount,   // optional, can still keep for display
        'hasPenalty' => true,
    ]);
}

}
