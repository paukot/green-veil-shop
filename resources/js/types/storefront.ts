export type CategoryNavigationItem = {
    id: number;
    slug: string;
    name: string;
    children?: CategoryNavigationItem[];
};

export type CartSummary = {
    count: number;
    subtotal: number;
};

export type FlashMessages = {
    success?: string | null;
    error?: string | null;
};

export type CustomerUser = {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at?: string | null;
    created_at?: string;
};

export type SharedProps = {
    name: string;
    demo_mode?: boolean;
    demo_notice?: string;
    navigation: CategoryNavigationItem[];
    auth: {
        user: CustomerUser | null;
    };
    cart: CartSummary;
    favourites: number[];
    flash: FlashMessages;
    [key: string]: unknown;
};

export type HeroSlide = {
    id: number;
    image: string;
    url?: string | null;
    url_opens_in_new_tab: boolean;
    title?: string | null;
    subtitle?: string | null;
    alt_text?: string | null;
    button_label?: string | null;
};

export type PromotionBanner = {
    id: number;
    name: string;
    description?: string | null;
    code?: string | null;
    discount_type: 'percentage' | 'fixed_amount';
    discount_value: number;
    minimum_order_amount?: number | null;
};

export type ProductCardData = {
    id: number;
    sku?: string | null;
    slug: string;
    name: string;
    short_description?: string | null;
    price: number;
    compare_at_price?: number | null;
    discount_percentage?: number | null;
    main_image: string;
    category?: {
        id: number;
        slug: string;
        name: string;
    } | null;
    stock_quantity?: number;
    track_inventory?: boolean;
    is_in_stock: boolean;
    brand?: {
        id: number;
        name: string;
        slug: string;
    } | null;
    volume?: number | null;
    unit?: string;
    formatted_unit_price?: string | null;
    variants_count?: number;
};

export type CategoryCardData = {
    id: number;
    slug: string;
    name: string;
    description?: string | null;
    image?: string | null;
};

export type HomePageProps = {
    heroSlides: HeroSlide[];
    promotions: PromotionBanner[];
    featuredCategories: CategoryCardData[];
    featuredProducts: ProductCardData[];
};

export type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

export type PaginatedData<T> = {
    data: T[];
    links: {
        first: string | null;
        last: string | null;
        prev: string | null;
        next: string | null;
    };
    meta: {
        current_page: number;
        from: number | null;
        last_page: number;
        links: PaginationLink[];
        path: string;
        per_page: number;
        to: number | null;
        total: number;
    };
};

export type BreadcrumbItem = {
    name: string;
    url: string;
};

export type ProductVariantItem = {
    id: number;
    name: string;
    variant_type: string;
    color_hex?: string | null;
    sku?: string | null;
    barcode?: string | null;
    price: number;
    compare_at_price?: number | null;
    stock_quantity: number;
    track_inventory: boolean;
    is_in_stock: boolean;
    is_default: boolean;
    image?: string | null;
    sort_order: number;
};

export type CosmeticLookupItem = {
    id: number;
    name: string;
    slug: string;
};

export type FacetOption = {
    id?: number;
    value?: string;
    name?: string;
    slug?: string;
    products_count?: number;
    count?: number;
};

export type FilterFacets = {
    brands: FacetOption[];
    skin_types: FacetOption[];
    hair_types: FacetOption[];
    effects: FacetOption[];
    ingredients: FacetOption[];
    claims: FacetOption[];
    classifications: FacetOption[];
    formula_types: FacetOption[];
    genders: FacetOption[];
    spf: FacetOption[];
    origins: FacetOption[];
    price_bounds: { min: number; max: number };
};

export type CatalogFilters = {
    q?: string;
    category?: string;
    brands?: string;
    skin_types?: string;
    hair_types?: string;
    effects?: string;
    ingredients?: string;
    claims?: string;
    classifications?: string;
    formula_types?: string;
    genders?: string;
    spf?: string;
    manufactured_in?: string;
    min_price?: number | string | null;
    max_price?: number | string | null;
    in_stock?: boolean;
    sort?: string;
};

export type CategoryFilterItem = {
    id: number;
    slug: string;
    name: string;
    products_count: number;
};

export type CategoryPageProps = {
    category: {
        id: number;
        slug: string;
        name: string;
        description?: string | null;
        image?: string | null;
        parent?: {
            id: number;
            slug: string;
            name: string;
        } | null;
        children: Array<{
            id: number;
            slug: string;
            name: string;
            image?: string | null;
        }>;
    };
    products: PaginatedData<ProductCardData>;
    breadcrumbs: BreadcrumbItem[];
    filters: CatalogFilters;
    facets?: FilterFacets;
    priceRange?: {
        min: number;
        max: number;
    };
};

export type CatalogIndexProps = {
    products: PaginatedData<ProductCardData>;
    filters: CatalogFilters;
    categories: CategoryFilterItem[];
    facets?: FilterFacets;
    priceRange: {
        min: number;
        max: number;
    };
};

export type ProductImageDetail = {
    id: number;
    path: string;
    alt_text?: string | null;
    is_primary: boolean;
};

export type ProductDetail = {
    id: number;
    sku?: string | null;
    barcode?: string | null;
    slug: string;
    name: string;
    short_description?: string | null;
    description?: string | null;
    price: number;
    compare_at_price?: number | null;
    discount_percentage?: number | null;
    stock_quantity: number;
    track_inventory: boolean;
    is_in_stock: boolean;
    main_image?: string | null;
    images: ProductImageDetail[];
    category?: {
        id: number;
        slug: string;
        name: string;
    } | null;
    promotions: PromotionBanner[];

    // Brand & Series
    brand?: {
        id: number;
        name: string;
        slug: string;
        logo_path?: string | null;
    } | null;
    series?: string | null;

    // Volume & EU Unit Pricing
    volume?: number | null;
    unit?: string;
    unit_price_base_amount?: number;
    formatted_unit_price?: string | null;

    // Cosmetic Classification & Demographics
    classification?: string | null;
    gender?: string;
    age_range?: string | null;
    formula_type?: string | null;
    spf?: string | null;

    // Origin & Regulatory
    manufactured_in?: string | null;
    pao_months?: number | null;
    responsible_person?: string | null;

    // Editorial & Regulatory Content
    inci_list?: string | null;
    how_to_use?: string | null;
    warnings?: string | null;
    custom_characteristics?: Record<string, string> | null;

    // Variants & Lookups
    variants?: ProductVariantItem[];
    skin_types?: CosmeticLookupItem[];
    hair_types?: CosmeticLookupItem[];
    effects?: CosmeticLookupItem[];
    ingredients?: CosmeticLookupItem[];
    claims?: CosmeticLookupItem[];
};

export type ProductDetailPageProps = {
    product: ProductDetail;
    relatedProducts: ProductCardData[];
    breadcrumbs: BreadcrumbItem[];
};

export type CartItemData = {
    id: number | string;
    product_id: number;
    quantity: number;
    subtotal: number;
    product: {
        id: number;
        name: string;
        slug: string;
        sku?: string | null;
        price: number;
        compare_at_price?: number | null;
        discount_percentage?: number | null;
        main_image?: string | null;
        stock_quantity: number;
        track_inventory: boolean;
        is_in_stock: boolean;
        category?: {
            id: number;
            name: string;
            slug: string;
        } | null;
    };
};

export type DeliveryOptionItem = {
    id: number;
    code: string;
    name: string;
    price: number;
    free_threshold?: number | null;
    is_free: boolean;
    delivery_label?: string | null;
    description?: string | null;
};

export type CartSummaryData = {
    count: number;
    subtotal: number;
    free_shipping_threshold: number;
    amount_until_free_shipping: number;
    qualifies_for_free_shipping: boolean;
};

export type CartPageProps = {
    items: CartItemData[];
    summary: CartSummaryData;
    deliveryOptions: DeliveryOptionItem[];
};

export type PaymentOptionItem = {
    id: number;
    code: string;
    driver: string;
    name: string;
    description?: string | null;
    instructions?: string | null;
};

export type CheckoutCustomer = {
    name: string;
    email: string;
} | null;

export type CheckoutPageProps = {
    items: Array<{
        id: number | string;
        product_id: number;
        quantity: number;
        subtotal: number;
        product: {
            id: number;
            name: string;
            slug: string;
            sku?: string | null;
            price: number;
            main_image?: string | null;
        };
    }>;
    summary: CartSummary;
    deliveryOptions: Array<{
        id: number;
        code: string;
        name: string;
        price: number;
        free_threshold?: number | null;
        is_free: boolean;
        estimated_delivery_label?: string | null;
        description?: string | null;
    }>;
    paymentOptions: PaymentOptionItem[];
    customer: CheckoutCustomer;
};

export type OrderDetailData = {
    public_id: string;
    number: string;
    status: string;
    payment_status: string;
    subtotal: number;
    delivery_amount: number;
    discount_amount: number;
    tax_amount: number;
    total: number;
    currency: string;
    customer_name: string;
    customer_email: string;
    customer_phone?: string | null;
    shipping_address: {
        line1: string;
        line2?: string | null;
        city: string;
        postal_code: string;
        country: string;
    };
    billing_address?: {
        line1: string;
        line2?: string | null;
        city: string;
        postal_code: string;
        country: string;
    } | null;
    placed_at?: string | null;
    paid_at?: string | null;
    notes?: string | null;
    items?: OrderItemData[];
    deliveryOption?: {
        name: string;
        code: string;
        estimated_delivery_label?: string | null;
    } | null;
    paymentOption?: {
        name: string;
        code: string;
        driver: string;
        description?: string | null;
        instructions?: string | null;
    } | null;
};

export type OrderItemData = {
    id: number;
    product_name: string;
    product_sku?: string | null;
    product_image?: string | null;
    unit_price: number;
    quantity: number;
    subtotal: number;
};

export type CheckoutCancelPageProps = {
    order: {
        public_id: string;
        number: string;
        total: number;
        currency: string;
    };
};

export type AccountOrderListItem = {
    public_id: string;
    number: string;
    status: string;
    payment_status: string;
    total: number;
    currency: string;
    placed_at: string;
    items_count: number;
    first_item_image?: string | null;
    delivery_name?: string | null;
    payment_driver?: string | null;
};

export type AccountOrdersPageProps = {
    orders: PaginatedData<AccountOrderListItem>;
};

export type AccountOrderDetailProps = {
    order: OrderDetailData;
    items: OrderItemData[];
    deliveryOption?: OrderDetailData['deliveryOption'];
    paymentOption?: OrderDetailData['paymentOption'];
};
