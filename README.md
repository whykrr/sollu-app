# Sollu App

Sollu POS adalah aplikasi Point of Sale berbasis SaaS yang dirancang khusus untuk bisnis dengan model multi-outlet.
Setiap merchant dapat mengelola satu atau lebih outlet dengan sistem langganan fleksibel.

## Instalasi

Pastikan memiliki PHP 8.3 dan NPM V22 atau yang lebih baru terpasang di operating sistem kamu.

**Run Command:**

```bash
php artisan migrate
php artisan db:seed --class=RolePermissionSeeder
php artisan db:seed --class=MerchantTypeSeeder
php artisan db:seed --class=SubscriptionPlanSeeder
php artisan db:seed --class=UnitSeeder
php artisan db:seed --class=DummySeeder
```
