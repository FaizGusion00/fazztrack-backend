<?php

namespace App\Providers;

use App\Models\Client;
use App\Models\Department;
// Models
use App\Models\FileAttachment;
use App\Models\Job;
use App\Models\Order;
use App\Models\OrderDesign;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Section;
use App\Models\User;
use App\Policies\ClientPolicy;
use App\Policies\DepartmentPolicy;
// Policies
use App\Policies\FileAttachmentPolicy;
use App\Policies\JobPolicy;
use App\Policies\OrderDesignPolicy;
use App\Policies\OrderItemPolicy;
use App\Policies\OrderPolicy;
use App\Policies\PaymentPolicy;
use App\Policies\ProductPolicy;
use App\Policies\SectionPolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Client::class => ClientPolicy::class,
        Department::class => DepartmentPolicy::class,
        FileAttachment::class => FileAttachmentPolicy::class,
        Job::class => JobPolicy::class,
        Order::class => OrderPolicy::class,
        OrderDesign::class => OrderDesignPolicy::class,
        OrderItem::class => OrderItemPolicy::class,
        Payment::class => PaymentPolicy::class,
        Product::class => ProductPolicy::class,
        Section::class => SectionPolicy::class,
        User::class => UserPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        // Define a super admin gate that can be used for any action
        Gate::before(function ($user, $ability) {
            if ($user->department && $user->department->name === 'SuperAdmin') {
                return true;
            }
        });
    }
}
