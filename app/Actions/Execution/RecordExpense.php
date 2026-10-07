<?php

namespace App\Actions\Execution;

use App\Models\Expense;
use App\Models\Team;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Records an operational expense on a trip.
 *
 * Idempotent on the client key. Fuel carries volume, unit price, odometer, and
 * tank so efficiency can be computed later; the receipt is stored privately.
 */
class RecordExpense
{
    /**
     * Create the expense.
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(Team $team, Trip $trip, array $data, ?User $user = null): Expense
    {
        return DB::transaction(function () use ($team, $trip, $data, $user): Expense {
            $existing = $team->expenses()
                ->where('idempotency_key', $data['idempotency_key'])
                ->first();

            if ($existing !== null) {
                return $existing;
            }

            return $team->expenses()->create([
                'trip_id' => $trip->id,
                'stop_id' => $data['stop_id'] ?? null,
                'driver_id' => $trip->driver_id,
                'type' => $data['type'],
                'amount_minor' => $data['amount_minor'],
                'currency' => $data['currency'] ?? 'MXN',
                'incurred_at' => $data['incurred_at'],
                'vendor' => $data['vendor'] ?? null,
                'notes' => $data['notes'] ?? null,
                'liters_ml' => $data['liters_ml'] ?? null,
                'price_per_liter_minor' => $data['price_per_liter_minor'] ?? null,
                'odometer_meters' => $data['odometer_meters'] ?? null,
                'tank' => $data['tank'] ?? null,
                'receipt_path' => $this->storeReceipt($team, $data['receipt'] ?? null),
                'idempotency_key' => $data['idempotency_key'],
                'recorded_by' => $user?->id,
            ]);
        });
    }

    /**
     * Store the optional receipt on the private evidence disk.
     */
    protected function storeReceipt(Team $team, ?UploadedFile $receipt): ?string
    {
        if ($receipt === null) {
            return null;
        }

        $path = $receipt->store('teams/'.$team->id.'/expenses', 'evidence');

        if ($path === false) {
            throw ValidationException::withMessages([
                'receipt' => __('The file could not be stored. Please try again.'),
            ]);
        }

        return (string) $path;
    }
}
