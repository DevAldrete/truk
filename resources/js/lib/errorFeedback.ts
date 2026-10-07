import { router } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import { t } from '@/lib/i18n';

/**
 * Central feedback for failures that are not field-level validation errors:
 * a lost connection, a body PHP rejected before validation, or an unexpected
 * server error.
 *
 * Field errors keep their inline rendering; these become toasts so the user is
 * never left wondering whether an action worked. The Inertia error dialog is
 * suppressed so the app explains the failure in its own voice.
 */
export function initializeErrorFeedback(): void {
    router.on('networkError', (event) => {
        event.preventDefault();
        toast.error(t('Connection lost. Check your network and try again.'));
    });

    router.on('httpException', (event) => {
        const status = event.detail.response.status;

        if (status === 419) {
            toast.error(
                t('Your session expired. Refresh the page and try again.'),
            );
        } else if (status === 413) {
            toast.error(
                t(
                    'The file is too large to upload. Please choose a smaller file.',
                ),
            );
        } else if (status >= 500) {
            toast.error(
                t('Something went wrong on our side. Please try again.'),
            );
        } else {
            toast.error(t('The action could not be completed.'));
        }

        event.preventDefault();
    });
}
