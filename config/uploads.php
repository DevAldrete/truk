<?php

/**
 * Upload limits for private evidence: POD photos, scanned documents, and
 * expense receipts.
 *
 * PHP's `upload_max_filesize` and `post_max_size` are the real ceiling. If the
 * application accepts more than PHP does, an oversized file never reaches
 * validation: the request body is discarded and the user gets a confusing 413.
 * We therefore cap the application rule at whichever is smaller — the
 * configured value or PHP's own limit — so the user always sees a normal
 * validation message instead of a crash.
 */
$configured = (int) env('UPLOAD_MAX_KILOBYTES', 10240);

/**
 * Convert a PHP shorthand size (`2M`, `8M`, `1G`, `512K`) to kilobytes.
 */
$phpKilobytes = static function (string $setting): int {
    $value = trim((string) ini_get($setting));

    if ($value === '') {
        return 0;
    }

    $unit = strtolower(substr($value, -1));
    $number = (int) $value;

    return match ($unit) {
        'g' => $number * 1024 * 1024,
        'm' => $number * 1024,
        'k' => $number,
        default => (int) round($number / 1024),
    };
};

$effective = $configured;

foreach ([$phpKilobytes('upload_max_filesize'), $phpKilobytes('post_max_size')] as $limit) {
    if ($limit > 0) {
        $effective = min($effective, $limit);
    }
}

// A signature is a base64 data URL, so roughly 4/3 of the decoded bytes. The
// character ceiling is deliberately generous; the action enforces the decoded
// byte limit exactly.
$signatureKilobytes = (int) env('UPLOAD_SIGNATURE_MAX_KILOBYTES', 1024);

return [
    // Effective per-file ceiling used by the validation rules.
    'max_kilobytes' => $effective,

    // What the application was configured to accept, before PHP's ceiling.
    'configured_max_kilobytes' => $configured,

    // Maximum decoded size of a base64 signature data URL.
    'signature_max_kilobytes' => $signatureKilobytes,

    // Character ceiling for the base64 signature string (base64 + prefix).
    'signature_max_characters' => $signatureKilobytes * 1024 * 2,
];
