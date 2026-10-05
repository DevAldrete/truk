<?php

namespace App\Actions\Loads;

use App\Models\Load;
use App\Models\Team;
use Illuminate\Support\Facades\DB;

/**
 * Creates and updates loads. The load number is assigned by the server.
 */
class SaveLoad
{
    /**
     * Create a load.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(Team $team, array $data): Load
    {
        return DB::transaction(fn (): Load => $team->loads()->create([
            'number' => $this->nextNumber($team),
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

    /**
     * Build the next load number for the team.
     */
    protected function nextNumber(Team $team): string
    {
        $count = Load::withTrashed()->where('team_id', $team->id)->count();

        return 'LOAD-'.str_pad((string) ($count + 1), 5, '0', STR_PAD_LEFT);
    }
}
