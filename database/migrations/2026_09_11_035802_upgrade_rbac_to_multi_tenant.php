<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $tableNames = config('permission.table_names');
        $columnNames = config('permission.column_names');
        $pivotRole = $columnNames['role_pivot_key'] ?? 'role_id';
        $pivotPermission = $columnNames['permission_pivot_key'] ?? 'permission_id';

        if (empty($tableNames)) {
            throw new Exception('Error: config/permission.php not found. Run [php artisan config:clear] and try again.');
        }

        // Roles table modifications
        Schema::table($tableNames['roles'], function (Blueprint $table) use ($tableNames, $columnNames) {
            if (! Schema::hasColumn($tableNames['roles'], $columnNames['team_foreign_key'])) {
                $table->uuid($columnNames['team_foreign_key'])->nullable()->after('id');
            }
            if (! Schema::hasColumn($tableNames['roles'], 'label')) {
                $table->string('label')->nullable()->after('name');
            }
            if (! Schema::hasColumn($tableNames['roles'], 'is_default')) {
                $table->boolean('is_default')->default(false)->after('guard_name');
            }
        });

        $needsPivotUpgrade = ! Schema::hasColumn($tableNames['model_has_roles'], $columnNames['team_foreign_key']);

        if ($needsPivotUpgrade) {
            try {
                Schema::table($tableNames['roles'], function (Blueprint $table) {
                    $table->dropUnique(['name', 'guard_name']);
                });
            } catch (Throwable $e) {
                // Ignore if unique does not exist
            }

            Schema::table($tableNames['roles'], function (Blueprint $table) use ($columnNames) {
                $table->index($columnNames['team_foreign_key'], 'roles_team_foreign_key_index');
                $table->unique([$columnNames['team_foreign_key'], 'name', 'guard_name']);
            });

            // Add column to pivots
            Schema::table($tableNames['model_has_permissions'], function (Blueprint $table) use ($columnNames) {
                $table->uuid($columnNames['team_foreign_key'])->nullable()->after('model_type');
            });

            Schema::table($tableNames['model_has_roles'], function (Blueprint $table) use ($columnNames) {
                $table->uuid($columnNames['team_foreign_key'])->nullable()->after('model_type');
            });

            // Backfill data
            if (DB::getDriverName() === 'pgsql') {
                DB::statement("UPDATE {$tableNames['model_has_roles']} SET {$columnNames['team_foreign_key']} = users.business_id FROM users WHERE {$tableNames['model_has_roles']}.model_id::uuid = users.id AND {$tableNames['model_has_roles']}.model_type = 'App\\\\Models\\\\User'");

                DB::statement("UPDATE {$tableNames['model_has_permissions']} SET {$columnNames['team_foreign_key']} = users.business_id FROM users WHERE {$tableNames['model_has_permissions']}.model_id::uuid = users.id AND {$tableNames['model_has_permissions']}.model_type = 'App\\\\Models\\\\User'");
            }

            // Delete orphans to prevent null constraint violations on primary key
            DB::table($tableNames['model_has_roles'])->whereNull($columnNames['team_foreign_key'])->delete();
            DB::table($tableNames['model_has_permissions'])->whereNull($columnNames['team_foreign_key'])->delete();

            // Model Has Permissions modifications
            Schema::table($tableNames['model_has_permissions'], function (Blueprint $table) use ($columnNames, $pivotPermission) {
                $table->dropPrimary(['model_has_permissions_permission_model_type_primary']);
                $table->index($columnNames['team_foreign_key'], 'model_has_permissions_team_foreign_key_index');
                $table->primary(
                    [$columnNames['team_foreign_key'], $pivotPermission, $columnNames['model_morph_key'], 'model_type'],
                    'model_has_permissions_permission_model_type_primary'
                );
            });

            // Model Has Roles modifications
            Schema::table($tableNames['model_has_roles'], function (Blueprint $table) use ($columnNames, $pivotRole) {
                $table->dropPrimary(['model_has_roles_role_model_type_primary']);
                $table->index($columnNames['team_foreign_key'], 'model_has_roles_team_foreign_key_index');
                $table->primary(
                    [$columnNames['team_foreign_key'], $pivotRole, $columnNames['model_morph_key'], 'model_type'],
                    'model_has_roles_role_model_type_primary'
                );
            });
        }

        app('cache')
            ->store(config('permission.cache.store') != 'default' ? config('permission.cache.store') : null)
            ->forget(config('permission.cache.key'));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tableNames = config('permission.table_names');
        $columnNames = config('permission.column_names');
        $pivotRole = $columnNames['role_pivot_key'] ?? 'role_id';
        $pivotPermission = $columnNames['permission_pivot_key'] ?? 'permission_id';

        Schema::table($tableNames['model_has_roles'], function (Blueprint $table) use ($columnNames, $pivotRole) {
            $table->dropPrimary(['model_has_roles_role_model_type_primary']);
            $table->dropIndex('model_has_roles_team_foreign_key_index');
            $table->dropColumn($columnNames['team_foreign_key']);
            $table->primary(
                [$pivotRole, $columnNames['model_morph_key'], 'model_type'],
                'model_has_roles_role_model_type_primary'
            );
        });

        Schema::table($tableNames['model_has_permissions'], function (Blueprint $table) use ($columnNames, $pivotPermission) {
            $table->dropPrimary(['model_has_permissions_permission_model_type_primary']);
            $table->dropIndex('model_has_permissions_team_foreign_key_index');
            $table->dropColumn($columnNames['team_foreign_key']);
            $table->primary(
                [$pivotPermission, $columnNames['model_morph_key'], 'model_type'],
                'model_has_permissions_permission_model_type_primary'
            );
        });

        Schema::table($tableNames['roles'], function (Blueprint $table) use ($columnNames) {
            $table->dropUnique([$columnNames['team_foreign_key'], 'name', 'guard_name']);
            $table->dropIndex('roles_team_foreign_key_index');

            $table->dropColumn('is_default');
            $table->dropColumn('label');
            $table->dropColumn($columnNames['team_foreign_key']);

            $table->unique(['name', 'guard_name']);
        });

        app('cache')
            ->store(config('permission.cache.store') != 'default' ? config('permission.cache.store') : null)
            ->forget(config('permission.cache.key'));
    }
};
