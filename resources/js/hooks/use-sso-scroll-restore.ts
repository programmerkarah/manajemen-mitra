import {
    applyFormFields,
    popSavedScrollState,
} from '@/hooks/use-sso-session-sync';
import { useEffect } from 'react';

const SSO_SCROLL_MAX_AGE_MS = 5 * 60 * 1000; // 5 minutes

/**
 * Delay for the second form-restoration pass.
 * The first pass restores top-level inputs (e.g. checkboxes that control
 * visibility). React then re-renders, revealing conditionally rendered
 * inputs. The second pass restores those newly visible inputs.
 */
const FORM_SECOND_PASS_DELAY_MS = 300;

export function useSsoScrollRestore(): void {
    useEffect(() => {
        const state = popSavedScrollState();

        if (!state) {
            return;
        }

        const currentUrl =
            window.location.pathname +
            window.location.search +
            window.location.hash;

        if (state.url !== currentUrl) {
            return;
        }

        if (Date.now() - state.timestamp > SSO_SCROLL_MAX_AGE_MS) {
            return;
        }

        requestAnimationFrame(() => {
            // First pass: restore form fields (including checkboxes that
            // control conditional rendering of other inputs).
            if (state.formFields?.length) {
                applyFormFields(state.formFields);
            }

            // Restore scroll position (only if the user was scrolled).
            if (state.scrollY > 0) {
                window.scrollTo({ top: state.scrollY, behavior: 'instant' });
            }

            // Second pass: restore inputs that were conditionally revealed
            // after React processed the checkbox changes above.
            if (state.formFields?.length) {
                setTimeout(() => {
                    applyFormFields(state.formFields!);
                }, FORM_SECOND_PASS_DELAY_MS);
            }
        });
    }, []);
}
