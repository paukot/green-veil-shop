/**
 * Shared look for text-like form controls (inputs, selects, textareas), so every field
 * has the same surface, border, focus ring and error state.
 */
export const fieldSurfaceClasses =
    'block w-full rounded-lg border bg-surface-raised text-ink placeholder:text-ink-subtle transition-colors focus:outline-hidden disabled:cursor-not-allowed disabled:bg-surface-sunken disabled:opacity-60';

/** sm is a 2rem compact control; md and lg are 3rem. Mobile uses 1rem text to avoid iOS zoom. */
export const fieldSizeClasses = {
    sm: 'h-8 px-3 text-base sm:text-sm',
    md: 'h-12 px-4 text-base sm:text-sm',
    lg: 'h-12 px-4 text-base',
} as const;

export function fieldStateClasses(hasError: boolean | undefined): string {
    return hasError
        ? 'border-danger-500 focus:border-danger-600 focus:ring-2 focus:ring-danger-200'
        : 'border-border-strong hover:border-cream-400 focus:border-sage-600 focus:ring-2 focus:ring-sage-200';
}
