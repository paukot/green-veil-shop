import { clsx } from 'clsx';
import type { ClassValue } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs: ClassValue[]): string {
    return twMerge(clsx(inputs));
}

const currencyFormatters = new Map<string, Intl.NumberFormat>();

/**
 * Format a number into currency (defaults to EUR with Irish/European formatting).
 * Caches Intl.NumberFormat instances to avoid expensive instantiation in reactive loops.
 */
export function formatCurrency(
    amount: number,
    locale = 'en-IE',
    currency = 'EUR',
): string {
    const key = `${locale}:${currency}`;
    let formatter = currencyFormatters.get(key);
    if (!formatter) {
        formatter = new Intl.NumberFormat(locale, {
            style: 'currency',
            currency,
        });
        currencyFormatters.set(key, formatter);
    }
    return formatter.format(amount);
}

/**
 * Formats an ISO date or timestamp string into localized readable date.
 */
export function formatDate(dateString: string | null | undefined): string {
    if (!dateString) return '';
    const date = new Date(dateString);
    if (isNaN(date.getTime())) return dateString;
    return new Intl.DateTimeFormat('en-IE', {
        dateStyle: 'medium',
    }).format(date);
}

/**
 * Normalizes an image or asset URL, prepending `/storage/` if not an absolute or full URL.
 */
export function resolveStorageUrl(path?: string | null): string | undefined {
    if (!path) return undefined;
    if (
        path.startsWith('http://') ||
        path.startsWith('https://') ||
        path.startsWith('/')
    ) {
        return path;
    }
    return `/storage/${path}`;
}

export const PLACEHOLDER_IMAGE_URL = '/images/placeholder.png';

/**
 * `@error` handler for `<img>`: swaps a broken image for the shared placeholder, once,
 * so a missing placeholder cannot trigger an endless error loop.
 */
export function showPlaceholderOnError(event: Event): void {
    const image = event.target;

    if (!(image instanceof HTMLImageElement) || image.dataset.fallback) {
        return;
    }

    image.dataset.fallback = 'true';
    // srcset takes precedence over src, so it must go for the placeholder to show.
    image.removeAttribute('srcset');
    image.src = PLACEHOLDER_IMAGE_URL;
}
