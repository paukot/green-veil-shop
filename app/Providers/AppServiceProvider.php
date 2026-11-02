<?php

namespace App\Providers;

use App\Models\AdminRole;
use App\Models\AdminUser;
use App\Models\Category;
use App\Models\DeliveryOption;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentOption;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\SliderItem;
use App\Models\StripeEvent;
use App\Models\User;
use App\Payments\Contracts\PaymentGateway;
use App\Payments\Stripe\StripeGateway;
use App\Policies\AdminRolePolicy;
use App\Policies\AdminUserPolicy;
use App\Policies\CategoryPolicy;
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
