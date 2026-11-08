<?php

namespace App\Providers;

use App\Models\AdminRole;
use App\Models\AdminUser;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Claim;
use App\Models\Country;
use App\Models\DeliveryOption;
use App\Models\Effect;
use App\Models\HairType;
use App\Models\Ingredient;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentOption;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\SkinType;
use App\Models\SliderItem;
use App\Models\StripeEvent;
use App\Models\User;
use App\Observers\CategoryObserver;
use App\Observers\DeliveryOptionObserver;
use App\Observers\PaymentOptionObserver;
use App\Observers\ProductObserver;
use App\Observers\PromotionObserver;
use App\Observers\SliderItemObserver;
use App\Observers\TaxonomyObserver;
use App\Payments\Contracts\PaymentGateway;
use App\Payments\Stripe\StripeGateway;
use App\Policies\AdminRolePolicy;
use App\Policies\AdminUserPolicy;
use App\Policies\CategoryPolicy;
use App\Policies\CountryPolicy;
use App\Policies\DeliveryOptionPolicy;
use App\Policies\OrderPolicy;
use App\Policies\PaymentOptionPolicy;
use App\Policies\PaymentPolicy;
use App\Policies\ProductPolicy;
use App\Policies\PromotionPolicy;
use App\Policies\SliderItemPolicy;
use App\Policies\StripeEventPolicy;
use App\Policies\UserPolicy;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Stripe\StripeClient;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(StripeClient::class, function () {
            $secretKey = config('services.stripe.secret') ?: 'sk_test_placeholder';

            return new StripeClient($secretKey);
        });

        $this->app->bind(PaymentGateway::class, StripeGateway::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureAuthorization();
        $this->configureModelObservers();
    }

    /**
     * Register model observers for reactive cache invalidation.
     */
    protected function configureModelObservers(): void
    {
        Product::observe(ProductObserver::class);
        Category::observe(CategoryObserver::class);
        DeliveryOption::observe(DeliveryOptionObserver::class);
        PaymentOption::observe(PaymentOptionObserver::class);
        Promotion::observe(PromotionObserver::class);
        SliderItem::observe(SliderItemObserver::class);

        Brand::observe(TaxonomyObserver::class);
        SkinType::observe(TaxonomyObserver::class);
        HairType::observe(TaxonomyObserver::class);
        Effect::observe(TaxonomyObserver::class);
        Ingredient::observe(TaxonomyObserver::class);
        Claim::observe(TaxonomyObserver::class);
    }

    /**
     * Configure Gate authorization hooks and resource policies.
     */
    protected function configureAuthorization(): void
    {
        Gate::before(function (Authenticatable $user, string $ability, array $arguments = []) {
            if ($user instanceof AdminUser && empty($arguments)) {
                return $user->hasPermission($ability);
            }

            return null;
        });

        Gate::policy(Order::class, OrderPolicy::class);
        Gate::policy(Product::class, ProductPolicy::class);
        Gate::policy(Category::class, CategoryPolicy::class);
        Gate::policy(Promotion::class, PromotionPolicy::class);
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Payment::class, PaymentPolicy::class);
        Gate::policy(SliderItem::class, SliderItemPolicy::class);
        Gate::policy(DeliveryOption::class, DeliveryOptionPolicy::class);
        Gate::policy(PaymentOption::class, PaymentOptionPolicy::class);
        Gate::policy(Country::class, CountryPolicy::class);
        Gate::policy(AdminUser::class, AdminUserPolicy::class);
        Gate::policy(AdminRole::class, AdminRolePolicy::class);
        Gate::policy(StripeEvent::class, StripeEventPolicy::class);
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        JsonResource::withoutWrapping();

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
