import { usePage } from '@inertiajs/vue3';

type Replacements = Record<string, string | number>;

/**
 * Translate an interface string using the dictionary the server shares for the
 * active locale.
 *
 * The English text is the key, so a string that has no translation yet still
 * renders in English instead of showing a key.
 */
export function t(key: string, replacements: Replacements = {}): string {
    // `props` can be absent when this runs outside a rendered page, e.g. during
    // SSR while a page module is being evaluated (Inertia types it as always
    // present). Fall back to the key, which is the English source string.
    const translations = (usePage().props?.translations ?? {}) as Record<
        string,
        string
    >;

    return Object.entries(replacements).reduce(
        (value, [name, replacement]) =>
            value.replaceAll(`:${name}`, String(replacement)),
        translations[key] ?? key,
    );
}
