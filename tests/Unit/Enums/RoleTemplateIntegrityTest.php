<?php

namespace Tests\Unit\Enums;

use App\Enums\PermissionEnum;
use App\Enums\RoleTemplateEnum;
use Tests\TestCase;

class RoleTemplateIntegrityTest extends TestCase
{
    /**
     * Pastikan setiap permission yang didefinisikan dalam template peran ada di PermissionEnum.
     */
    public function test_all_template_permissions_exist_in_permission_enum(): void
    {
        $validPermissions = PermissionEnum::values();

        foreach (RoleTemplateEnum::cases() as $template) {
            foreach ($template->permissions() as $perm) {
                $this->assertContains(
                    $perm,
                    $validPermissions,
                    "Template '{$template->value}' berisi permission '{$perm}' yang tidak terdaftar di PermissionEnum."
                );
            }
        }
    }

    /**
     * Pastikan seluruh template peran memiliki label, deskripsi, kategori, dan target jenis bisnis.
     */
    public function test_all_templates_have_complete_metadata(): void
    {
        foreach (RoleTemplateEnum::cases() as $template) {
            $this->assertNotEmpty($template->label(), "Label untuk template '{$template->value}' tidak boleh kosong.");
            $this->assertNotEmpty($template->description(), "Deskripsi untuk template '{$template->value}' tidak boleh kosong.");
            $this->assertNotEmpty($template->category(), "Kategori untuk template '{$template->value}' tidak boleh kosong.");
            $this->assertNotEmpty($template->categoryLabel(), "Category label untuk template '{$template->value}' tidak boleh kosong.");
            $this->assertNotEmpty($template->businessTypes(), "Business types untuk template '{$template->value}' tidak boleh kosong.");
            $this->assertNotEmpty($template->permissions(), "Permissions untuk template '{$template->value}' tidak boleh kosong.");
            $this->assertNotEmpty($template->summaryGroups(), "Summary groups untuk template '{$template->value}' tidak boleh kosong.");
        }
    }

    /**
     * Pastikan format output formattedList valid untuk konsumsi frontend.
     */
    public function test_formatted_list_returns_valid_structure(): void
    {
        $list = RoleTemplateEnum::formattedList();

        $this->assertIsArray($list);
        $this->assertCount(count(RoleTemplateEnum::cases()), $list);

        foreach ($list as $item) {
            $this->assertArrayHasKey('key', $item);
            $this->assertArrayHasKey('label', $item);
            $this->assertArrayHasKey('description', $item);
            $this->assertArrayHasKey('category', $item);
            $this->assertArrayHasKey('category_label', $item);
            $this->assertArrayHasKey('business_types', $item);
            $this->assertArrayHasKey('permissions', $item);
            $this->assertArrayHasKey('permissions_count', $item);
            $this->assertArrayHasKey('summary_groups', $item);
            $this->assertEquals(count($item['permissions']), $item['permissions_count']);
        }
    }
}
