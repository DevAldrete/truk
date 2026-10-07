import { t } from '@/lib/i18n';

/**
 * Client-side guard rails for private evidence uploads (POD photos, scanned
 * documents, and expense receipts).
 *
 * The server is still the source of truth, but checking type and size here
 * means a too-large or incompatible file is explained immediately instead of
 * failing inside PHP with a confusing 413.
 */

export const MAX_EVIDENCE_FILES = 10;

export type EvidenceKind = 'image' | 'document';

export type EvidenceValidation = {
    accepted: File[];
    errors: string[];
};

const IMAGE_TYPES = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
const DOCUMENT_TYPES = [
    'image/jpeg',
    'image/png',
    'image/webp',
    'application/pdf',
];

/**
 * Whether a file has an accepted MIME type for its evidence kind.
 */
export function isAcceptedEvidence(file: File, kind: EvidenceKind): boolean {
    const allowed = kind === 'image' ? IMAGE_TYPES : DOCUMENT_TYPES;

    return allowed.includes(file.type);
}

/**
 * Size in kilobytes, rounded up so a file exactly at the limit passes.
 */
export function sizeInKilobytes(bytes: number): number {
    return Math.ceil(bytes / 1024);
}

/**
 * A human label for a kilobyte ceiling.
 */
export function formatMaxSize(maxKilobytes: number): string {
    if (maxKilobytes >= 1024) {
        return `${Math.round(maxKilobytes / 1024)} MB`;
    }

    return `${maxKilobytes} KB`;
}

/**
 * Split a selection into accepted files and translated error messages.
 *
 * `remaining` is how many more files may still be added to the collection.
 */
export function validateEvidenceFiles(
    files: File[],
    kind: EvidenceKind,
    maxKilobytes: number,
    remaining: number,
): EvidenceValidation {
    const accepted: File[] = [];
    const errors: string[] = [];

    for (const file of files) {
        if (accepted.length >= remaining) {
            errors.push(
                t('You can add up to :max files.', {
                    max: String(MAX_EVIDENCE_FILES),
                }),
            );
            break;
        }

        if (!isAcceptedEvidence(file, kind)) {
            errors.push(
                t('":name" is not a supported file type.', {
                    name: file.name,
                }),
            );
            continue;
        }

        if (sizeInKilobytes(file.size) > maxKilobytes) {
            errors.push(
                t('":name" is too large (max :max).', {
                    name: file.name,
                    max: formatMaxSize(maxKilobytes),
                }),
            );
            continue;
        }

        accepted.push(file);
    }

    return { accepted, errors };
}

/**
 * Downscale and re-encode an image so phone photos upload quickly and fit under
 * the configured ceiling. Non-images and already-small files are returned
 * unchanged.
 */
export async function compressImage(
    file: File,
    maxDimension = 1600,
    quality = 0.8,
): Promise<File> {
    if (!file.type.startsWith('image/')) {
        return file;
    }

    try {
        const bitmap = await createImageBitmap(file);
        const scale = Math.min(
            1,
            maxDimension / Math.max(bitmap.width, bitmap.height),
        );
        const width = Math.max(1, Math.round(bitmap.width * scale));
        const height = Math.max(1, Math.round(bitmap.height * scale));

        const canvas = document.createElement('canvas');
        canvas.width = width;
        canvas.height = height;

        const context = canvas.getContext('2d');

        if (!context) {
            return file;
        }

        context.drawImage(bitmap, 0, 0, width, height);

        const blob = await new Promise<Blob | null>((resolve) =>
            canvas.toBlob(resolve, 'image/jpeg', quality),
        );

        if (!blob) {
            return file;
        }

        const name = `${file.name.replace(/\.[^.]+$/, '')}.jpg`;

        return new File([blob], name, { type: 'image/jpeg' });
    } catch {
        // Compression is best-effort; the server still validates the original.
        return file;
    }
}
