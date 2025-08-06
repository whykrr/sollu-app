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
 * @property-read Collection|MerchantType $type
 * @property-read Collection|Outlet[] $outlets
 * @property-read Collection|User[] $users
 * @property-read Collection|MerchantOutletSubscription $subscriptions
 * @property-read Collection|ProductVariation[] $product_variations
 * @property-read Collection|Product[] $products
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
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read int|null $outlets_count
 * @property-read int|null $product_variations_count
 * @property-read int|null $products_count
 * @property-read int|null $subscriptions_count
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
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperMerchant {}
}

namespace App\Models{
/**
 * 
 *
 * @property-read Collection|SubscriptionPlan $subscription_plan
 * @property-read Collection|Merchant $merchant
 * @property-read Collection|Outlet $outlet
 * @property string $id
 * @property string $merchant_id
 * @property string $outlet_id
 * @property int $subscription_plans_id
 * @property string $start_date
 * @property string|null $end_date
 * @property string $status options: payment, active, cancelled
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
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
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperMerchantOutletSubscription {}
}

namespace App\Models{
/**
 * 
 *
 * @property Collection|Merchant[] $merchants
 * @property Collection|ProductCategory[] $productCategories
 * @property int $id
 * @property string $name
 * @property string $code
 * @property mixed|null $default_settings
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read int|null $merchants_count
 * @property-read int|null $product_categories_count
 * @method static \Database\Factories\MerchantTypeFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|MerchantType newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|MerchantType newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|MerchantType query()
 * @method static \Illuminate\Database\Eloquent\Builder|MerchantType whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MerchantType whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MerchantType whereDefaultSettings($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MerchantType whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MerchantType whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MerchantType whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperMerchantType {}
}

namespace App\Models{
/**
 * 
 *
 * @property-read Collection|Merchant $merchant
 * @property-read Collection|MerchantOutletSubscription[] $merchant_subscriptions
 * @property-read Collection|User[] $users
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
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\MerchantOutletSubscription> $subscription_plans
 * @property-read int|null $subscription_plans_count
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
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperOutlet {}
}

namespace App\Models\Product{
/**
 * 
 *
 * @package App\Models\Product
 * @property-read Collection|Merchant $merchant
 * @property-read Collection|ProductType $type
 * @property-read Collection|ProductUnit $unit
 * @property-read Collection|ProductCategory[] $categories
 * @property-read Collection|ProductCombination[] $combinations
 * @property-read Collection|ProductVariation[] $variations
 * @property-read Collection|ProductVariationOptionValue[] $variation_values
 * @property string $id
 * @property string $merchant_id
 * @property int $product_type_id
 * @property string $name
 * @property string $description
 * @property string $base_price
 * @property int $product_unit_id
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property-read int|null $categories_count
 * @property-read int|null $combinations_count
 * @property-read int|null $variation_values_count
 * @property-read int|null $variations_count
 * @method static \Illuminate\Database\Eloquent\Builder|Product newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Product newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Product query()
 * @method static \Illuminate\Database\Eloquent\Builder|Product whereBasePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Product whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Product whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Product whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Product whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Product whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Product whereMerchantId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Product whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Product whereProductTypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Product whereProductUnitId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Product whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperProduct {}
}

namespace App\Models\Product{
/**
 * 
 *
 * @property-read ProductCategory|null $parent
 * @property-read Collection|ProductCategory[] $children
 * @property-read Collection|Product[] $products
 * @property-read Collection|MerchantType[] $merchantTypes
 * @property int $id
 * @property string|null $merchant_id
 * @property int|null $parent_id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property int $level
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read int|null $children_count
 * @property-read string $full_path
 * @property-read int|null $merchant_types_count
 * @property-read int|null $products_count
 * @method static \Illuminate\Database\Eloquent\Builder|ProductCategory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProductCategory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProductCategory onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|ProductCategory query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProductCategory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductCategory whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductCategory whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductCategory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductCategory whereLevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductCategory whereMerchantId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductCategory whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductCategory whereParentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductCategory whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductCategory whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductCategory withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|ProductCategory withoutTrashed()
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperProductCategory {}
}

namespace App\Models\Product{
/**
 * 
 *
 * @property-read Collection|Product $product
 * @property string $id
 * @property string $product_id
 * @property string $combination
 * @property string $SKU
 * @property string $barcode
 * @property string $unique_string
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|ProductCombination newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProductCombination newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProductCombination query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProductCombination whereBarcode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductCombination whereCombination($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductCombination whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductCombination whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductCombination whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductCombination whereSKU($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductCombination whereUniqueString($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductCombination whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperProductCombination {}
}

namespace App\Models\Product{
/**
 * 
 *
 * @property-read Collection|Product[] $products
 * @property int $id
 * @property string $name
 * @property string $description
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read int|null $products_count
 * @method static \Illuminate\Database\Eloquent\Builder|ProductType newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProductType newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProductType query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProductType whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductType whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductType whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductType whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductType whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperProductType {}
}

namespace App\Models\Product{
/**
 * 
 *
 * @property-read Collection|Product[] $products
 * @property int $id
 * @property string $name
 * @property string $symbol
 * @property string|null $description
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read int|null $products_count
 * @method static \Illuminate\Database\Eloquent\Builder|ProductUnit newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProductUnit newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProductUnit query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProductUnit whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductUnit whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductUnit whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductUnit whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductUnit whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductUnit whereSymbol($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductUnit whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperProductUnit {}
}

namespace App\Models\Product{
/**
 * 
 *
 * @property-read Collection|Merchant $merchant
 * @property int $id
 * @property string|null $merchant_id
 * @property string $name
 * @property string $slug
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|ProductVariation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProductVariation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProductVariation query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProductVariation whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductVariation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductVariation whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductVariation whereMerchantId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductVariation whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductVariation whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductVariation whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperProductVariation {}
}

namespace App\Models\Product{
/**
 * 
 *
 * @property-read Collection|Product $product
 * @property-read Collection|ProductVariation $master
 * @property string $id
 * @property string $product_id
 * @property int $product_variation_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|ProductVariationOption newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProductVariationOption newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProductVariationOption query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProductVariationOption whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductVariationOption whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductVariationOption whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductVariationOption whereProductVariationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductVariationOption whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperProductVariationOption {}
}

namespace App\Models\Product{
/**
 * 
 *
 * @property-read Collection|Product $product
 * @property-read Collection|ProductVariationOption $variant_option
 * @property-read Collection|ProductVariationValue $master
 * @property string $id
 * @property string $product_id
 * @property string $product_variation_option_id
 * @property int $product_variation_value_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|ProductVariationOptionValue newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProductVariationOptionValue newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProductVariationOptionValue query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProductVariationOptionValue whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductVariationOptionValue whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductVariationOptionValue whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductVariationOptionValue whereProductVariationOptionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductVariationOptionValue whereProductVariationValueId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductVariationOptionValue whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperProductVariationOptionValue {}
}

namespace App\Models\Product{
/**
 * 
 *
 * @property-read Collection|ProductVariation $variation
 * @property-read Collection|ProductVariationOptionValue[] $product_variation_option_values
 * @property int $id
 * @property int $product_variation_id
 * @property string $name
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read int|null $product_variation_option_values_count
 * @method static \Illuminate\Database\Eloquent\Builder|ProductVariationValue newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProductVariationValue newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProductVariationValue query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProductVariationValue whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductVariationValue whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductVariationValue whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductVariationValue whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductVariationValue whereProductVariationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductVariationValue whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperProductVariationValue {}
}

namespace App\Models{
/**
 * 
 *
 * @property-read Collection|MerchantOutletSubscription[] $transactions
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
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperSubscriptionPlan {}
}

namespace App\Models{
/**
 * 
 *
 * @property-read Collection|Merchant $merchant
 * @property-read Collection|Outlet[] $outlets
 * @mixin HasRoles
 * @property string $id
 * @property string $merchant_id
 * @property string $name
 * @property string $email
 * @property string|null $phone
 * @property string|null $email_verified_at
 * @property mixed $password
 * @property mixed|null $pin
 * @property string|null $photo
 * @property bool $is_root_user
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read int|null $outlets_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Permission\Models\Permission> $permissions
 * @property-read int|null $permissions_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Permission\Models\Role> $roles
 * @property-read int|null $roles_count
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|User onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|User permission($permissions, $without = false)
 * @method static \Illuminate\Database\Eloquent\Builder|User query()
 * @method static \Illuminate\Database\Eloquent\Builder|User role($roles, $guard = null, $without = false)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereIsRootUser($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereMerchantId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User wherePhoto($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User wherePin($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|User withoutPermission($permissions)
 * @method static \Illuminate\Database\Eloquent\Builder|User withoutRole($roles, $guard = null)
 * @method static \Illuminate\Database\Eloquent\Builder|User withoutTrashed()
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperUser {}
}

