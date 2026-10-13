<?php

namespace App\Actions\Execution;

use App\Models\ProofOfDelivery;
use App\Models\Stop;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Records a proof of delivery: recipient, signature, photos, and scanned
 * documents stored privately.
 *
 * The record is append-only and idempotent on the client key. A correction is a
 * new record; the original evidence and its files are never replaced.
 */
class RecordProofOfDelivery
{
    /**
     * Store the evidence and create the POD row.
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(Team $team, Stop $stop, array $data, ?User $user = null): ProofOfDelivery
    {
        $existing = $team->proofsOfDelivery()
            ->where('idempotency_key', $data['idempotency_key'])
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        $base = 'teams/'.$team->id.'/pods/'.Str::uuid();

        $signature = null;
        $photos = [];
        $documents = [];

        try {
            $signature = isset($data['signature']) && $data['signature'] !== ''
                ? $this->storeSignature($base, (string) $data['signature'])
                : null;

            $photos = $this->storeUploads($base.'/photos', $data['photos'] ?? []);
            $documents = $this->storeUploads($base.'/documents', $data['documents'] ?? []);
        } catch (\Throwable $exception) {
            // Never leave orphaned evidence on the private disk if storing fails.
            $this->deleteStored($signature, $photos, $documents);

            throw $exception;
        }

        try {
            return DB::transaction(fn (): ProofOfDelivery => $team->proofsOfDelivery()->create([
                'delivery_attempt_id' => $data['delivery_attempt_id'] ?? null,
                'stop_id' => $stop->id,
                'shipment_id' => $data['shipment_id'] ?? null,
                'recipient_name' => $data['recipient_name'] ?? null,
                'signature_path' => $signature,
                'photos' => $photos === [] ? null : $photos,
                'document_paths' => $documents === [] ? null : $documents,
                'consent' => (bool) ($data['consent'] ?? false),
                'notes' => $data['notes'] ?? null,
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
                'captured_at' => $data['captured_at'],
                'idempotency_key' => $data['idempotency_key'],
                'recorded_by' => $user?->id,
            ]));
        } catch (UniqueConstraintViolationException) {
            // A concurrent retry already inserted the same key; drop the orphan
            // files and replay the existing record.
            $this->deleteStored($signature, $photos, $documents);

            return $team->proofsOfDelivery()
                ->where('idempotency_key', $data['idempotency_key'])
                ->firstOrFail();
        }
    }

    /**
     * Remove stored evidence after a failed or raced handle.
     *
     * @param  array<int, string>  $photos
     * @param  array<int, string>  $documents
     */
    protected function deleteStored(?string $signature, array $photos, array $documents): void
    {
        $paths = array_values(array_filter([
            $signature,
            ...$photos,
            ...$documents,
        ]));

        if ($paths !== []) {
            Storage::disk('evidence')->delete($paths);
        }
    }

    /**
     * Decode and store a base64 image data URL signature.
     */
    protected function storeSignature(string $base, string $dataUrl): string
    {
        if (! preg_match('/^data:image\/(png|jpe?g|webp);base64,(.*)$/s', $dataUrl, $matches)) {
            throw ValidationException::withMessages([
                'signature' => __('The signature is not a valid image.'),
            ]);
        }

        $binary = base64_decode($matches[2], true);

        if ($binary === false) {
            throw ValidationException::withMessages([
                'signature' => __('The signature is not a valid image.'),
            ]);
        }

        $maxBytes = (int) config('uploads.signature_max_kilobytes') * 1024;

        if (strlen($binary) > $maxBytes) {
            throw ValidationException::withMessages([
                'signature' => __('The signature is too large. Please sign again.'),
            ]);
        }

        $extension = $matches[1] === 'jpeg' ? 'jpg' : $matches[1];
        $path = $base.'/signature.'.$extension;

        $this->put($path, $binary);

        return $path;
    }

    /**
     * Store uploaded evidence files and return their paths.
     *
     * @param  array<int, UploadedFile>  $files
     * @return array<int, string>
     */
    protected function storeUploads(string $directory, array $files): array
    {
        return collect($files)
            ->map(function (UploadedFile $file) use ($directory): string {
                $path = $file->store($directory, 'evidence');

                if ($path === false) {
                    throw ValidationException::withMessages([
                        'photos' => __('The file could not be stored. Please try again.'),
                    ]);
                }

                return (string) $path;
            })
            ->values()
            ->all();
    }

    /**
     * Write a file to the private evidence disk, failing loudly instead of
     * silently storing an empty path.
     */
    protected function put(string $path, string $contents): void
    {
        $stored = Storage::disk('evidence')->put($path, $contents);

        if ($stored === false) {
            throw ValidationException::withMessages([
                'signature' => __('The file could not be stored. Please try again.'),
            ]);
        }
    }
}
