import type { CategoryNavigationItem } from '@/types/storefront';

export type NavigationLayout = {
    columnCount: number;
    isMegaMenu: boolean;
    containerWidthClass: string;
    gridColsClass: string;
    columns: CategoryNavigationItem[][];
};

/**
 * Dynamically determine navigation layout and column distribution strictly
 * based on the number of child categories, adhering to clean architecture
 * standards without any hardcoded category slugs.
 *
 * Automatic sizing tiers:
 * - 1 to 6 items   => 1 column  (compact w-56)
 * - 7 to 14 items  => 2 columns (medium w-[420px])
 * - 15 to 24 items => 3 columns (wide w-[640px])
 * - 25+ items      => 4 columns (mega-menu w-[840px])
 */
export function getNavigationLayout(
    cat: CategoryNavigationItem,
): NavigationLayout {
    const children = cat.children ?? [];
    const count = children.length;

    if (count === 0) {
        return {
            columnCount: 1,
            isMegaMenu: false,
            containerWidthClass: 'w-56 left-0',
            gridColsClass: 'grid-cols-1',
            columns: [],
        };
    }

    let columnCount = 1;
    let isMegaMenu = false;
    let containerWidthClass = 'w-56 left-0';
    let gridColsClass = 'grid-cols-1';

    if (count > 24) {
        // Large department (25+ items, e.g. 44 items in Makeup) => 4-Column Mega-Menu
        columnCount = 4;
        isMegaMenu = true;
        containerWidthClass = 'w-[820px] xl:w-[880px] -left-20 xl:-left-36';
        gridColsClass = 'grid-cols-4';
    } else if (count > 14) {
        // Medium-large department (15-24 items) => 3-Column Dropdown
        columnCount = 3;
        isMegaMenu = true;
        containerWidthClass = 'w-[640px] -left-12 xl:-left-20';
        gridColsClass = 'grid-cols-3';
    } else if (count > 6) {
        // Medium department (7-14 items, e.g. Hair, Skincare) => 2-Column Dropdown
        columnCount = 2;
        isMegaMenu = false;
        containerWidthClass = 'w-[420px] -left-6';
        gridColsClass = 'grid-cols-2';
    } else {
        // Small department (1-6 items, e.g. Men) => 1-Column Dropdown
        columnCount = 1;
        isMegaMenu = false;
        containerWidthClass = 'w-56 left-0';
        gridColsClass = 'grid-cols-1';
    }

    // Distribute children evenly across columns
    const columns: CategoryNavigationItem[][] = [];
    const itemsPerCol = Math.ceil(count / columnCount);

    for (let col = 0; col < columnCount; col++) {
        const start = col * itemsPerCol;
        const end = Math.min(start + itemsPerCol, count);
        if (start < count) {
            columns.push(children.slice(start, end));
        }
    }

    return {
        columnCount,
        isMegaMenu,
        containerWidthClass,
        gridColsClass,
        columns,
    };
}
