/**
 * Locale-aware date/time formatting and the Mexico timezone list.
 *
 * Times are stored as UTC ISO strings; render them in the user's locale and,
 * when a trip/site timezone is known, in that zone so operators read local
 * times rather than raw ISO.
 */
export const TIMEZONES = [
    'America/Mexico_City',
    'America/Monterrey',
    'America/Chihuahua',
    'America/Mazatlan',
    'America/Hermosillo',
    'America/Tijuana',
    'America/Cancun',
];

export function formatDateTime(
    value: string | null | undefined,
    timezone?: string | null,
): string {
    if (!value) {
        return '';
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return '';
    }

    return new Intl.DateTimeFormat(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
        ...(timezone ? { timeZone: timezone } : {}),
    }).format(date);
}
