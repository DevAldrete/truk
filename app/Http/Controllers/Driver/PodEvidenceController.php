<?php

namespace App\Http\Controllers\Driver;

use App\Enums\TeamPermission;
use App\Http\Controllers\Controller;
use App\Models\ProofOfDelivery;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Streams private POD evidence through an authorized, tenant-scoped route.
 *
 * Files are never served directly from the disk; the membership check has
 * already run, and the POD is bound inside the tenant, so another tenant's
 * evidence is a 404.
 */
class PodEvidenceController extends Controller
{
    /**
     * Stream the signature image.
     */
    public function signature(Request $request, Team $current_team, ProofOfDelivery $pod): StreamedResponse
    {
        return $this->respond($request, $current_team, $pod, $pod->signature_path);
    }

    /**
     * Stream one of the POD photos.
     */
    public function photo(Request $request, Team $current_team, ProofOfDelivery $pod, int $index): StreamedResponse
    {
        return $this->respond($request, $current_team, $pod, ($pod->photos ?? [])[$index] ?? null);
    }

    /**
     * Stream one of the scanned documents.
     */
    public function document(Request $request, Team $current_team, ProofOfDelivery $pod, int $index): StreamedResponse
    {
        return $this->respond($request, $current_team, $pod, ($pod->document_paths ?? [])[$index] ?? null);
    }

    /**
     * Authorize and stream a stored evidence path.
     */
    protected function respond(Request $request, Team $team, ProofOfDelivery $pod, ?string $path): StreamedResponse
    {
        $user = $request->user();

        abort_if($user === null, 403);

        if (! $user->hasTeamPermission($team, TeamPermission::ManageOperations)) {
            abort_unless($user->hasTeamPermission($team, TeamPermission::ExecuteOperations), 403);

            $driver = $user->driverProfileFor($team);
            $tripDriverId = $pod->stop?->trip?->driver_id;

            // A driver may only read evidence captured on their own trip.
            abort_unless(
                $driver !== null && $tripDriverId !== null && $driver->id === $tripDriverId,
                403,
            );
        }

        abort_if($path === null || ! Storage::disk('evidence')->exists($path), 404);

        return Storage::disk('evidence')->response($path);
    }
}
