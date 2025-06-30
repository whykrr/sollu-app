<?php

// @formatter:off
// phpcs:ignoreFile
/**
 * A helper file for your Eloquent Models
 * Copy the phpDocs from this file to the correct Model,
 * And remove them from this file, to prevent double declarations.
 *
 * @author Barry vd. Heuvel <barryvdh@gmail.com>
 */


namespace App\Models{
/**
 * 
 *
 * @property string $id
 * @property string $slug
 * @property string $name
 * @property string $owner_name
 * @property string $email
 * @property string $phone
 * @property string|null $address
 * @property string|null $logo_url
 * @property bool $already_free_trial
 * @property int $merchant_type_id
 * @property array|null $settings
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Outlet> $outlets
 * @property-read int|null $outlets_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\MerchantOutletSubscription> $subscriptions
 * @property-read int|null $subscriptions_count
 * @property-read \App\Models\MerchantType|null $type
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $users
 * @property-read int|null $users_count
 * @method static \Database\Factories\MerchantFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|Merchant newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Merchant newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Merchant query()
 * @method static \Illuminate\Database\Eloquent\Builder|Merchant whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Merchant whereAlreadyFreeTrial($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Merchant whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Merchant whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Merchant whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Merchant whereLogoUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Merchant whereMerchantTypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Merchant whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Merchant whereOwnerName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Merchant wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Merchant whereSettings($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Merchant whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Merchant whereUpdatedAt($value)
 */
	class Merchant extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property string $id
 * @property string $merchant_id
 * @property string $outlet_id
 * @property int $subscription_plans_id
 * @property string $start_date
 * @property string|null $end_date
 * @property string $status options: payment, active, cancelled
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Merchant $merchant
 * @property-read \App\Models\Outlet $outlet
 * @property-read \App\Models\SubscriptionPlan|null $subscription_plan
 * @method static \Database\Factories\MerchantOutletSubscriptionFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|MerchantOutletSubscription newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|MerchantOutletSubscription newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|MerchantOutletSubscription query()
 * @method static \Illuminate\Database\Eloquent\Builder|MerchantOutletSubscription whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MerchantOutletSubscription whereEndDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MerchantOutletSubscription whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MerchantOutletSubscription whereMerchantId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MerchantOutletSubscription whereOutletId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MerchantOutletSubscription whereStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MerchantOutletSubscription whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MerchantOutletSubscription whereSubscriptionPlansId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MerchantOutletSubscription whereUpdatedAt($value)
 */
	class MerchantOutletSubscription extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property string $name
 * @property string $code
 * @property object|null $settings
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Merchant> $merchants
 * @property-read int|null $merchants_count
 * @method static \Database\Factories\MerchantTypeFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|MerchantType newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|MerchantType newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|MerchantType query()
 * @method static \Illuminate\Database\Eloquent\Builder|MerchantType whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MerchantType whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MerchantType whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MerchantType whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MerchantType whereSettings($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MerchantType whereUpdatedAt($value)
 */
	class MerchantType extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property string $id
 * @property string $merchant_id
 * @property string $slug
 * @property string $name
 * @property string|null $address
 * @property string|null $latitude
 * @property string|null $longitude
 * @property string $status options: active, grace, expired, inactive
 * @property string|null $expired_at
 * @property bool $is_main_outlet
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Merchant $merchant
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\MerchantOutletSubscription> $subscription_plans
 * @property-read int|null $subscription_plans_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $users
 * @property-read int|null $users_count
 * @method static \Database\Factories\OutletFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|Outlet newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Outlet newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Outlet query()
 * @method static \Illuminate\Database\Eloquent\Builder|Outlet whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Outlet whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Outlet whereExpiredAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Outlet whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Outlet whereIsMainOutlet($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Outlet whereLatitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Outlet whereLongitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Outlet whereMerchantId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Outlet whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Outlet whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Outlet whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Outlet whereUpdatedAt($value)
 */
	class Outlet extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property string $price
 * @property string $billing_cycle
 * @property string $status
 * @property int $duration duration in days
 * @property bool $is_trial indicates if the plan is free trial period
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\MerchantOutletSubscription> $transactions
 * @property-read int|null $transactions_count
 * @method static \Illuminate\Database\Eloquent\Builder|SubscriptionPlan newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|SubscriptionPlan newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|SubscriptionPlan query()
 * @method static \Illuminate\Database\Eloquent\Builder|SubscriptionPlan whereBillingCycle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SubscriptionPlan whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SubscriptionPlan whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SubscriptionPlan whereDuration($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SubscriptionPlan whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SubscriptionPlan whereIsTrial($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SubscriptionPlan whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SubscriptionPlan wherePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SubscriptionPlan whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SubscriptionPlan whereUpdatedAt($value)
 */
	class SubscriptionPlan extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property string $id
 * @property string $merchant_id
 * @property string $name
 * @property string $email
 * @property string|null $email_verified_at
 * @property mixed $password
 * @property string|null $pin
 * @property string|null $photo
 * @property bool $is_root_user
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\Merchant $merchant
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Outlet> $outlets
 * @property-read int|null $outlets_count
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|User onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|User query()
 * @method static \Illuminate\Database\Eloquent\Builder|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereIsRootUser($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereMerchantId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User wherePhoto($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User wherePin($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|User withoutTrashed()
 */
	class User extends \Eloquent {}
}

