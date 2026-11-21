import type { CategoryNavigationItem } from '@/types/storefront';

export type NavigationLayout = {
    columnCount: number;
    gridColsClass: string;
    columns: CategoryNavigationItem[][];
};

/** Column count by number of subcategories, so long departments stay short. */
const LAYOUT_TIERS = [
    { minCount: 25, columnCount: 4, gridColsClass: 'grid-cols-4' },
    { minCount: 15, columnCount: 3, gridColsClass: 'grid-cols-3' },
    { minCount: 7, columnCount: 2, gridColsClass: 'grid-cols-2' },
    { minCount: 0, columnCount: 1, gridColsClass: 'grid-cols-1' },
] as const;

/**
 * Split a department's subcategories into evenly filled columns for the mega menu.
 */
export function getNavigationLayout(
    cat: CategoryNavigationItem,
): NavigationLayout {
    const children = cat.children ?? [];
    const tier =
        LAYOUT_TIERS.find((t) => children.length >= t.minCount) ??
        LAYOUT_TIERS[LAYOUT_TIERS.length - 1];
    const itemsPerCol = Math.max(
        1,
        Math.ceil(children.length / tier.columnCount),
    );

    const columns: CategoryNavigationItem[][] = [];
    for (let start = 0; start < children.length; start += itemsPerCol) {
        columns.push(children.slice(start, start + itemsPerCol));
    }

    return {
        columnCount: tier.columnCount,
        gridColsClass: tier.gridColsClass,
        columns,
    };
}
