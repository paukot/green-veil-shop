/**
 * Centralized API client for JSON fetch requests with automatic CSRF token injection
 * and standardized error handling.
 */

export function getXsrfToken(): string {
    if (typeof document === 'undefined') return '';
    const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
    return match ? decodeURIComponent(match[1]) : '';
}

export class ApiError extends Error {
    constructor(
        message: string,
        public status: number,
        public data?: Record<string, unknown> | null,
    ) {
        super(message);
        this.name = 'ApiError';
    }
}

function getDefaultHeaders(): Record<string, string> {
    const token = getXsrfToken();
    const headers: Record<string, string> = {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    };
    if (token) {
        headers['X-XSRF-TOKEN'] = token;
    }
    return headers;
}

export async function postJson<T>(url: string, body?: unknown): Promise<T> {
    const response = await fetch(url, {
        method: 'POST',
        headers: {
            ...getDefaultHeaders(),
            'Content-Type': 'application/json',
        },
        body: body !== undefined ? JSON.stringify(body) : undefined,
    });

    return handleResponse<T>(response);
}

export async function deleteJson<T>(url: string): Promise<T> {
    const response = await fetch(url, {
        method: 'DELETE',
        headers: getDefaultHeaders(),
    });

    return handleResponse<T>(response);
}

async function handleResponse<T>(response: Response): Promise<T> {
    const data = await response.json().catch(() => null);

    if (!response.ok) {
        const firstErrorKey = data?.errors ? Object.keys(data.errors)[0] : null;
        const firstError = firstErrorKey
            ? data.errors[firstErrorKey]?.[0]
            : null;
        const errorMsg =
            firstError ||
            data?.message ||
            `Request failed with status ${response.status}`;

        throw new ApiError(errorMsg, response.status, data);
    }

    return data as T;
}
