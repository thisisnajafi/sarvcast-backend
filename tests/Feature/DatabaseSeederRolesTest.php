<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use Database\Seeders\AdminSeeder;
use Database\Seeders\CategorySeeder;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SmsPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseSeederRolesTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seeder_includes_role_and_sms_permission_seeders(): void
    {
        $seeder = new DatabaseSeeder();
        $reflection = new \ReflectionClass($seeder);
        $method = $reflection->getMethod('run');
        $source = file_get_contents($method->getFileName());

        $this->assertStringContainsString('AdminSeeder::class', $source);
        $this->assertStringContainsString('RolePermissionSeeder::class', $source);
        $this->assertStringContainsString('SmsPermissionSeeder::class', $source);
        $this->assertStringContainsString('CategorySeeder::class', $source);

        $adminPos = strpos($source, 'AdminSeeder::class');
        $rolePos = strpos($source, 'RolePermissionSeeder::class');
        $smsPos = strpos($source, 'SmsPermissionSeeder::class');
        $categoryPos = strpos($source, 'CategorySeeder::class');

        $this->assertNotFalse($adminPos);
        $this->assertNotFalse($rolePos);
        $this->assertNotFalse($smsPos);
        $this->assertNotFalse($categoryPos);
        $this->assertTrue($adminPos < $rolePos);
        $this->assertTrue($rolePos < $smsPos);
        $this->assertTrue($smsPos < $categoryPos);
    }

    public function test_database_seeder_populates_permissions_and_roles(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertGreaterThan(0, Permission::count());
        $this->assertTrue(Role::where('name', 'admin')->exists());
        $this->assertTrue(Role::where('name', 'super_admin')->exists());
        $this->assertTrue(Permission::where('name', 'users.update')->exists());
    }
}
