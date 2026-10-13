import { router } from '@inertiajs/vue3';
import { reactive } from 'vue';

type Filters = Record<string, string | null>;

/**
 * Keeps a list filter state in the URL and reloads only the affected props.
 *
 * Text input is debounced so typing does not fire a request per keystroke;
 * selecting a filter applies immediately.
 */
export function useFilteredList(options: {
    url: string | (() => string);
    only: string[];
    reset?: string[];
    filters: Filters;
    delay?: number;
}) {
    const state = reactive<Filters>({ ...options.filters });

    let timer: ReturnType<typeof setTimeout> | undefined;

    const apply = () => {
        const query = Object.fromEntries(
            Object.entries(state).filter(
                ([, value]) => value !== null && value !== '',
            ),
        );

        const url =
            typeof options.url === 'function' ? options.url() : options.url;

        router.get(url, query, {
            only: options.only,
            // Reset the paginated prop so the infinite list starts from page 1
            // instead of appending the new results to the previous ones.
            reset: options.reset ?? options.only,
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    const search = (value: string) => {
        clearTimeout(timer);

        timer = setTimeout(() => {
            state.search = value === '' ? null : value;
            apply();
        }, options.delay ?? 300);
    };

    const filter = (key: string, value: string | null) => {
        state[key] = value;
        apply();
    };

    return { state, search, filter };
}
