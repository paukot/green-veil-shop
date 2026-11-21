#!/usr/bin/env node
/**
 * Design token check for the storefront.
 *
 * Flags Tailwind classes that break the design system documented in resources/css/app.css:
 * off-scale spacing and sizes, arbitrary pixel values, undefined colours, and radii, shadows
 * or font sizes outside the agreed set.
 *
 * Usage: node scripts/check-design-tokens.mjs [--strict]
 * Without --strict it reports and exits 0; with it, any violation exits 1.
 */
import { readFileSync, readdirSync, statSync } from 'node:fs';
import { join, relative } from 'node:path';

const root = new URL('..', import.meta.url).pathname;
const strict = process.argv.includes('--strict');

const SOURCE_DIRS = [
    'resources/js/pages',
    'resources/js/components',
    'resources/js/layouts',
    'resources/js/lib',
];
const EXTENSIONS = ['.vue', '.ts'];

/** Spacing steps (×0.25rem): 0.25 0.5 0.75 1 1.5 2 3 4 6 8 rem. */
const SPACING_STEPS = new Set([
    '0',
    'px',
    '1',
    '2',
    '3',
    '4',
    '6',
    '8',
    '12',
    '16',
    '24',
    '32',
]);
/** Width and height may also use the larger clean steps. */
const SIZE_STEPS = new Set([...SPACING_STEPS, '40', '48', '64', '80', '96']);
const SPACING_PREFIXES = new Set([
    'p',
    'px',
    'py',
    'pt',
    'pr',
    'pb',
    'pl',
    'ps',
    'pe',
    'm',
    'mx',
    'my',
    'mt',
    'mr',
    'mb',
    'ml',
    'ms',
    'me',
    'gap',
    'gap-x',
    'gap-y',
    'space-x',
    'space-y',
    'inset',
    'inset-x',
    'inset-y',
    'top',
    'right',
    'bottom',
    'left',
    'start',
    'end',
    'translate-x',
    'translate-y',
    'scroll-mt',
    'scroll-pt',
]);
const SIZE_PREFIXES = new Set([
    'w',
    'h',
    'size',
    'min-w',
    'min-h',
    'max-h',
    'basis',
]);

const RADII = new Set([
    'rounded-none',
    'rounded-full',
    'rounded-lg',
    'rounded-2xl',
]);
const SHADOWS = new Set(['shadow-none', 'shadow-sm', 'shadow-lg', 'shadow-xl']);
const COLOUR_UTILITIES = [
    'text',
    'bg',
    'border',
    'border-t',
    'border-b',
    'border-l',
    'border-r',
    'border-x',
    'border-y',
    'ring',
    'ring-offset',
    'from',
    'via',
    'to',
    'fill',
    'stroke',
    'divide',
    'outline',
    'placeholder',
    'decoration',
    'accent',
    'caret',
];
const COLOUR_UTILITIES_LONGEST_FIRST = [...COLOUR_UTILITIES].sort(
    (a, b) => b.length - a.length,
);
const BUILTIN_COLOURS = new Set([
    'white',
    'black',
    'transparent',
    'current',
    'inherit',
]);

/** Arbitrary values that are allowed: ratios, safe areas, viewport caps, grid templates. */
const ARBITRARY_ALLOWED = [
    /^aspect-\[/,
    /^grid-(cols|rows)-\[/,
    /env\(safe-area/,
    /^max-[hw]-\[\d+v[hw]\]$/,
    /^animate-\[/,
    /^transition-\[/,
];

const definedColours = new Set(
    [
        ...readFileSync(join(root, 'resources/css/app.css'), 'utf8').matchAll(
            /--color-([a-z]+(?:-[a-z]+)*?-\d{2,3}|[a-z]+(?:-[a-z]+)*)\s*:/g,
        ),
    ].map((m) => m[1]),
);

function filesIn(dir) {
    const full = join(root, dir);
    return readdirSync(full).flatMap((entry) => {
        const path = join(full, entry);
        if (statSync(path).isDirectory()) {
            return filesIn(relative(root, path));
        }
        return EXTENSIONS.some((ext) => entry.endsWith(ext)) ? [path] : [];
    });
}

/** Return a reason when the class breaks a rule, or null when it is fine. */
function violation(rawClass) {
    const utility = rawClass
        .split(':')
        .pop()
        .replace(/^!/, '')
        .replace(/^-/, '');

    if (utility.includes('[')) {
        if (ARBITRARY_ALLOWED.some((pattern) => pattern.test(utility))) {
            return null;
        }
        return utility.startsWith('text-[')
            ? 'arbitrary font size (min text-xs)'
            : 'arbitrary value';
    }

    const numeric = utility.match(/^([a-z]+(?:-[a-z]+)*)-(\d+(?:\.\d+)?|px)$/);
    if (numeric) {
        const [, prefix, value] = numeric;
        if (SPACING_PREFIXES.has(prefix) && !SPACING_STEPS.has(value)) {
            return `off-scale spacing (${value})`;
        }
        if (SIZE_PREFIXES.has(prefix) && !SIZE_STEPS.has(value)) {
            return `off-scale size (${value})`;
        }
        if (SPACING_PREFIXES.has(prefix) || SIZE_PREFIXES.has(prefix)) {
            return null;
        }
    }

    if (/^rounded(-[trblse]{1,2})?(-[a-z0-9]+)?$/.test(utility)) {
        const normalised = utility.replace(
            /^rounded-[trblse]{1,2}(?=-|$)/,
            'rounded',
        );
        return RADII.has(normalised)
            ? null
            : 'radius outside the set (none, lg, 2xl, full)';
    }

    if (/^shadow(-(2xs|xs|sm|md|lg|xl|2xl|inner|none))?$/.test(utility)) {
        return SHADOWS.has(utility)
            ? null
            : 'shadow outside the set (sm, lg, xl)';
    }

    for (const prefix of COLOUR_UTILITIES_LONGEST_FIRST) {
        const match = utility.match(
            new RegExp(
                `^${prefix}-([a-z]+(?:-[a-z]+)*?)(?:-(\\d{2,3}))?(?:/\\d+)?$`,
            ),
        );
        if (!match) {
            continue;
        }
        const [, name, shade] = match;
        if (BUILTIN_COLOURS.has(name) || !shade) {
            return null;
        }
        return definedColours.has(`${name}-${shade}`)
            ? null
            : `colour not in the theme (${name}-${shade})`;
    }

    return null;
}

const CLASS_TOKEN =
    /(?<![\w$.-])!?-?(?:[a-z0-9-]+:)*-?[a-z][a-z0-9]*(?:-[a-z0-9.]+)*(?:-\[[^\]\s"'`]+\])?(?:\/\d+)?(?![\w-])/g;

const findings = [];
for (const dir of SOURCE_DIRS) {
    for (const file of filesIn(dir)) {
        const lines = readFileSync(file, 'utf8').split('\n');
        // Class lists span several lines in templates, so every line is tokenised.
        lines.forEach((line, index) => {
            for (const match of line.matchAll(CLASS_TOKEN)) {
                const token = match[0];
                // Skip prop and object keys such as `rounded?:` or `:rounded="…"`.
                const nextChar = line[match.index + token.length] ?? '';
                if ('=?:'.includes(nextChar) && nextChar !== '') {
                    continue;
                }
                // Skip identifiers compared in expressions, e.g. `rounded === 'full'`.
                if (/^\s*[!=]==/.test(line.slice(match.index + token.length))) {
                    continue;
                }
                // Skip a bare identifier bound as a prop value, e.g. `:rounded="rounded"`.
                if (
                    nextChar === '"' &&
                    /:[\w-]+="$/.test(line.slice(0, match.index))
                ) {
                    continue;
                }
                const reason = violation(token);
                if (reason) {
                    findings.push(
                        `${relative(root, file)}:${index + 1}  ${token}  — ${reason}`,
                    );
                }
            }
        });
    }
}

const unique = [...new Set(findings)];
if (unique.length === 0) {
    console.log('Design tokens: no violations.');
    process.exit(0);
}

console.log(unique.join('\n'));
console.log(
    `\nDesign tokens: ${unique.length} violation(s).${strict ? '' : ' (report only; pass --strict to fail)'}`,
);
process.exit(strict ? 1 : 0);
