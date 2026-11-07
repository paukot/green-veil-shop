import type { CategoryNavigationItem } from '@/types/storefront';

export type NavigationLayout = {
    columnCount: number;
    isMegaMenu: boolean;
    containerWidthClass: string;
    innerCardClass: string;
    gridColsClass: string;
    columns: CategoryNavigationItem[][];
};

const LAYOUT_TIERS = [
    {
        minCount: 25,
        columnCount: 4,
        isMegaMenu: true,
        containerWidthClass: 'left-0 right-0 w-full',
        innerCardClass: 'mx-auto max-w-5xl',
        gridColsClass: 'grid-cols-4',
    },
    {
        minCount: 15,
        columnCount: 3,
        isMegaMenu: true,
        containerWidthClass: 'left-0 right-0 w-full',
        innerCardClass: 'mx-auto max-w-3xl',
        gridColsClass: 'grid-cols-3',
    },
    {
        minCount: 7,
        columnCount: 2,
        isMegaMenu: false,
        containerWidthClass: 'w-96 left-1/2 -translate-x-1/2',
        innerCardClass: 'w-full',
        gridColsClass: 'grid-cols-2',
    },
] as const;

const DEFAULT_LAYOUT = {
    columnCount: 1,
    isMegaMenu: false,
    containerWidthClass: 'w-56 left-1/2 -translate-x-1/2',
    innerCardClass: 'w-full',
    gridColsClass: 'grid-cols-1',
};

/**
 * Dynamically determine navigation layout and column distribution strictly
 * based on the number of child categories.
 */
export function getNavigationLayout(
    cat: CategoryNavigationItem,
): NavigationLayout {
    const children = cat.children ?? [];
    const count = children.length;

    if (count === 0) {
        return {
            ...DEFAULT_LAYOUT,
            columns: [],
        };
    }

    const tier =
        LAYOUT_TIERS.find((t) => count >= t.minCount) ?? DEFAULT_LAYOUT;
    const itemsPerCol = Math.ceil(count / tier.columnCount);

    const columns: CategoryNavigationItem[][] = [];
    for (let col = 0; col < tier.columnCount; col++) {
        const start = col * itemsPerCol;
        if (start >= count) break;
        const end = Math.min(start + itemsPerCol, count);
        columns.push(children.slice(start, end));
    }

    return {
        ...tier,
        columns,
    };
}
