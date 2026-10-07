<?php

namespace App\Actions\Loads;

use App\Actions\GenerateDocumentNumber;
use App\Models\Load;
use App\Models\Team;
use Illuminate\Support\Facades\DB;

/**
 * Creates and updates loads. The load number is assigned by the server.
 */
class SaveLoad
{
    public function __construct(private GenerateDocumentNumber $numbers) {}

    /**
     * Create a load.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(Team $team, array $data): Load
    {
        return DB::transaction(fn (): Load => $team->loads()->create([
            'number' => $this->numbers->handle($team, Load::withTrashed(), 'LOAD'),
            'status' => $data['status'],
            'notes' => $data['notes'] ?? null,
        ]));
    }

    /**
     * Update a load.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Load $load, array $data): Load
    {
        $load->update([
            'status' => $data['status'],
            'notes' => $data['notes'] ?? null,
        ]);

        return $load;
    }
}
