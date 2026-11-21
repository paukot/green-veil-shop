import { nextTick, onBeforeUnmount, watch } from 'vue';
import type { Ref } from 'vue';

let openOverlayCount = 0;

function lockScroll(): void {
    openOverlayCount++;
    if (openOverlayCount === 1) {
        document.documentElement.style.overflow = 'hidden';
    }
}

function unlockScroll(): void {
    openOverlayCount = Math.max(0, openOverlayCount - 1);
    if (openOverlayCount === 0) {
        document.documentElement.style.overflow = '';
    }
}

const FOCUSABLE =
    'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';

/**
 * Shared behaviour for drawers and modals: scroll lock (counted, so stacked overlays
 * do not fight), Escape to close, focus moved into the panel, Tab kept inside it,
 * and focus returned to the trigger on close.
 */
export function useDialogBehaviour(
    isOpen: Ref<boolean>,
    panel: Ref<HTMLElement | null>,
    close: () => void,
): void {
    let previouslyFocused: HTMLElement | null = null;
    let isLocked = false;

    function focusableElements(): HTMLElement[] {
        return panel.value
            ? Array.from(panel.value.querySelectorAll<HTMLElement>(FOCUSABLE))
            : [];
    }

    function onKeydown(event: KeyboardEvent): void {
        if (event.key === 'Escape') {
            event.stopPropagation();
            close();

            return;
        }

        if (event.key !== 'Tab') {
            return;
        }

        const elements = focusableElements();
        if (elements.length === 0) {
            event.preventDefault();

            return;
        }

        const first = elements[0];
        const last = elements[elements.length - 1];

        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault();
            last.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first.focus();
        }
    }

    async function activate(): Promise<void> {
        previouslyFocused = document.activeElement as HTMLElement | null;
        lockScroll();
        isLocked = true;
        document.addEventListener('keydown', onKeydown);

        await nextTick();
        (focusableElements()[0] ?? panel.value)?.focus();
    }

    function deactivate(): void {
        if (!isLocked) {
            return;
        }

        isLocked = false;
        unlockScroll();
        document.removeEventListener('keydown', onKeydown);
        previouslyFocused?.focus();
        previouslyFocused = null;
    }

    watch(
        isOpen,
        (open) => {
            if (typeof document === 'undefined') {
                return;
            }

            if (open) {
                void activate();
            } else {
                deactivate();
            }
        },
        { immediate: true },
    );

    onBeforeUnmount(deactivate);
}
