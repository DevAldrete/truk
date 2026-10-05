<?php

namespace App\Http\Controllers;

use App\Models\ComplianceDocument;
use App\Models\Driver;
use App\Models\TeamInvitation;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $email = strtolower($request->user()->email);

        $pendingInvitations = TeamInvitation::query()
            ->with(['inviter', 'team'])
            ->whereRaw('LOWER(email) = ?', [$email])
            ->whereNull('accepted_at')
            ->where(fn ($query) => $query
                ->whereNull('expires_at')
                ->orWhere('expires_at', '>=', now()))
            ->latest()
            ->get()
            ->map(fn (TeamInvitation $invitation) => [
                'code' => $invitation->code,
                'inviterName' => $invitation->inviter->name,
                'team' => [
                    'name' => $invitation->team->name,
                    'slug' => $invitation->team->slug,
                ],
            ]);

        return Inertia::render('Dashboard', [
            'pendingInvitations' => $pendingInvitations,
            'fleetWarnings' => [
                'expired_licenses' => Driver::query()
                    ->whereNotNull('license_expires_at')
                    ->whereDate('license_expires_at', '<', now())
                    ->count(),
                'expired_documents' => ComplianceDocument::query()
                    ->whereNotNull('expires_at')
                    ->whereDate('expires_at', '<', now())
                    ->count(),
                'expiring_documents' => ComplianceDocument::query()
                    ->whereNotNull('expires_at')
                    ->whereDate('expires_at', '>=', now())
                    ->whereDate('expires_at', '<=', now()->addDays(30))
                    ->count(),
            ],
        ]);
    }
}
