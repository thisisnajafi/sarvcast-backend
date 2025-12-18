# Role and Permission System Documentation

## 📋 Overview

The Role and Permission System provides comprehensive access control for the SarvCast admin panel. It allows fine-grained control over what different admin users can access and modify within the system.

## 🎯 System Architecture

### Core Components

1. **Roles**: Define user groups with specific access levels
2. **Permissions**: Define specific actions or resources that can be accessed
3. **Role-Permission Mapping**: Many-to-many relationship between roles and permissions
4. **User-Role Assignment**: Many-to-many relationship between users and roles
5. **Middleware**: Check permissions before allowing access to routes

## 🏗️ Database Schema

### Tables

#### `roles`
- `id`: Primary key
- `name`: Unique role identifier (e.g., 'super_admin', 'admin')
- `display_name`: Human-readable role name (e.g., 'مدیر کل')
- `description`: Role description
- `is_active`: Whether the role is active
- `created_at`, `updated_at`: Timestamps

#### `permissions`
- `id`: Primary key
- `name`: Unique permission identifier (e.g., 'coins.view', 'coupons.create')
- `display_name`: Human-readable permission name
- `description`: Permission description
- `group`: Permission group for organization (e.g., 'coin_management')
- `is_active`: Whether the permission is active
- `created_at`, `updated_at`: Timestamps

#### `role_permission`
- `id`: Primary key
- `role_id`: Foreign key to roles table
- `permission_id`: Foreign key to permissions table
- `created_at`, `updated_at`: Timestamps

#### `user_role`
- `id`: Primary key
- `user_id`: Foreign key to users table
- `role_id`: Foreign key to roles table
- `created_at`, `updated_at`: Timestamps

## 👥 Predefined Roles

### Super Admin (`super_admin`)
- **Display Name**: مدیر کل
- **Description**: دسترسی کامل به تمام بخش‌های سیستم
- **Permissions**: All permissions
- **Special Features**: Cannot be deleted, has access to role management

### Admin (`admin`)
- **Display Name**: مدیر
- **Description**: دسترسی به مدیریت محتوا و کاربران
- **Permissions**: Dashboard, coin management, coupon management, payment management, partner management, analytics, user management, content management

### Payment Admin (`payment_admin`)
- **Display Name**: مدیر پرداخت
- **Description**: دسترسی به مدیریت پرداخت‌ها و کمیسیون
- **Permissions**: Dashboard, payment management, coupon management, analytics

### Partner Admin (`partner_admin`)
- **Display Name**: مدیر شرکا
- **Description**: دسترسی به مدیریت شرکا و کوپن‌ها
- **Permissions**: Dashboard, partner management, coupon management, analytics

### Coin Admin (`coin_admin`)
- **Display Name**: مدیر سکه
- **Description**: دسترسی به مدیریت سیستم سکه
- **Permissions**: Dashboard, coin management, analytics

### Analytics Admin (`analytics_admin`)
- **Display Name**: مدیر آمار
- **Description**: دسترسی به مشاهده آمار و گزارش‌ها
- **Permissions**: Dashboard, analytics

### Content Admin (`content_admin`)
- **Display Name**: مدیر محتوا
- **Description**: دسترسی به مدیریت محتوا
- **Permissions**: Dashboard, content management, user management

## 🔐 Permission Groups

### Dashboard (`dashboard`)
- `dashboard.view`: مشاهده داشبورد

### Coin Management (`coin_management`)
- `coins.view`: مشاهده مدیریت سکه
- `coins.award`: اعطای سکه
- `coins.redemption_manage`: مدیریت گزینه‌های تبدیل
- `coins.transactions_view`: مشاهده تراکنش‌ها
- `coins.users_view`: مشاهده کاربران سکه

### Coupon Management (`coupon_management`)
- `coupons.view`: مشاهده کدهای کوپن
- `coupons.create`: ایجاد کد کوپن
- `coupons.edit`: ویرایش کد کوپن
- `coupons.delete`: حذف کد کوپن
- `coupons.toggle_status`: تغییر وضعیت کوپن

### Payment Management (`payment_management`)
- `payments.view`: مشاهده پرداخت‌ها
- `payments.process`: پردازش پرداخت
- `payments.create_manual`: ایجاد پرداخت دستی
- `payments.bulk_process`: پردازش دسته‌ای
- `payments.mark_paid`: علامت‌گذاری پرداخت شده
- `payments.mark_failed`: علامت‌گذاری ناموفق
- `payments.reset`: بازنشانی پرداخت

### Partner Management (`partner_management`)
- `partners.view`: مشاهده شرکا
- `partners.create`: ایجاد شریک
- `partners.edit`: ویرایش شریک
- `partners.delete`: حذف شریک
- `partners.approve`: تایید شریک

### Analytics (`analytics`)
- `analytics.view`: مشاهده آمار
- `analytics.coin`: آمار سکه
- `analytics.referral`: آمار ارجاع
- `analytics.export`: صادرات آمار

### User Management (`user_management`)
- `users.view`: مشاهده کاربران
- `users.create`: ایجاد کاربر
- `users.edit`: ویرایش کاربر
- `users.delete`: حذف کاربر
- `users.status_change`: تغییر وضعیت کاربر

### Content Management (`content_management`)
- `content.view`: مشاهده محتوا
- `content.create`: ایجاد محتوا
- `content.edit`: ویرایش محتوا
- `content.delete`: حذف محتوا
- `content.publish`: انتشار محتوا

### Role Management (`role_management`)
- `roles.view`: مشاهده نقش‌ها
- `roles.create`: ایجاد نقش
- `roles.edit`: ویرایش نقش
- `roles.delete`: حذف نقش
- `roles.assign`: اختصاص نقش

### Permission Management (`permission_management`)
- `permissions.view`: مشاهده مجوزها
- `permissions.create`: ایجاد مجوز
- `permissions.edit`: ویرایش مجوز
- `permissions.delete`: حذف مجوز
- `permissions.assign`: اختصاص مجوز

## 🛠️ Implementation

### Models

#### User Model
```php
// Check if user has a specific role
$user->hasRole('super_admin');

// Check if user has a specific permission
$user->hasPermission('coins.view');

// Check if user has any of multiple roles
$user->hasAnyRole(['admin', 'super_admin']);

// Check if user has any of multiple permissions
$user->hasAnyPermission(['coins.view', 'coins.award']);

// Assign role to user
$user->assignRole($role);

// Remove role from user
$user->removeRole($role);

// Check if user is super admin
$user->isSuperAdmin();

// Check if user is admin (super_admin or admin)
$user->isAdmin();
```

#### Role Model
```php
// Check if role has permission
$role->hasPermission('coins.view');

// Give permission to role
$role->givePermission($permission);

// Revoke permission from role
$role->revokePermission($permission);

// Sync permissions (replace all permissions)
$role->syncPermissions($permissionIds);
```

### Middleware

#### CheckPermission Middleware
```php
// Apply to routes
Route::middleware('permission:coins.view')->group(function () {
    // Routes that require coins.view permission
});

// Multiple permissions
Route::middleware('permission:coins.view,coins.award')->group(function () {
    // Routes that require either permission
});
```

### Blade Directives

#### @can Directive
```blade
@can('coins.view')
    <a href="{{ route('admin.coins.index') }}">مدیریت سکه</a>
@endcan

@can('coins.award')
    <button onclick="awardCoins()">اعطای سکه</button>
@endcan
```

#### @role Directive
```blade
@role('super_admin')
    <a href="{{ route('admin.roles.index') }}">مدیریت نقش‌ها</a>
@endrole
```

## 🚀 Usage Examples

### Protecting Routes
```php
// Single permission
Route::get('/coins', [CoinController::class, 'index'])
    ->middleware('permission:coins.view');

// Multiple permissions (user needs at least one)
Route::get('/coins/award', [CoinController::class, 'award'])
    ->middleware('permission:coins.award,coins.view');

// Role-based protection
Route::middleware('role:super_admin')->group(function () {
    Route::get('/roles', [RoleController::class, 'index']);
});
```

### Controller Authorization
```php
class CoinController extends Controller
{
    public function index()
    {
        $this->authorize('coins.view');
        // Controller logic
    }

    public function award(Request $request)
    {
        $this->authorize('coins.award');
        // Controller logic
    }
}
```

### Blade Templates
```blade
@can('coins.view')
    <div class="coin-management-section">
        <h2>مدیریت سکه</h2>
        @can('coins.award')
            <button class="award-coins-btn">اعطای سکه</button>
        @endcan
    </div>
@endcan
```

## 🔧 Management Commands

### Create Super Admin
```bash
php artisan admin:create-super-admin {phone_number} {password}
```

Example:
```bash
php artisan admin:create-super-admin 09136708883 admin123
```

### Seed Roles and Permissions
```bash
php artisan db:seed --class=RolePermissionSeeder
```

## 📊 Current Super Admin

### Abolfazl (09136708883)
- **Phone Number**: 09136708883
- **Role**: Super Admin
- **Permissions**: All permissions
- **Special Access**: Can manage roles and permissions for other admins

## 🔒 Security Features

### Protection Mechanisms
1. **Super Admin Protection**: Super admin role cannot be deleted
2. **Self-Protection**: Super admin cannot remove their own super admin role
3. **Permission Validation**: All routes are protected by permission middleware
4. **Role Hierarchy**: Super admin has access to everything
5. **Audit Trail**: All role and permission changes are logged

### Best Practices
1. **Principle of Least Privilege**: Give users only the permissions they need
2. **Regular Audits**: Review user roles and permissions regularly
3. **Strong Passwords**: Use strong passwords for admin accounts
4. **Two-Factor Authentication**: Consider implementing 2FA for admin accounts
5. **Session Management**: Implement proper session timeout for admin accounts

## 🚨 Troubleshooting

### Common Issues

#### User Cannot Access Admin Panel
1. Check if user has any roles assigned
2. Verify user has required permissions
3. Check if user account is active
4. Verify middleware is properly applied

#### Permission Not Working
1. Check if permission exists in database
2. Verify role has the permission assigned
3. Check if user has the role assigned
4. Verify middleware is correctly configured

#### Role Assignment Issues
1. Check if role exists and is active
2. Verify user exists and is active
3. Check for duplicate assignments
4. Verify database constraints

### Debug Commands
```php
// Check user roles
$user = User::find(1);
dd($user->roles->pluck('name'));

// Check user permissions
dd($user->permissions->pluck('name'));

// Check role permissions
$role = Role::find(1);
dd($role->permissions->pluck('name'));
```

## 📞 Support

For technical support or questions about the Role and Permission System:
- **Email**: admin-support@sarvcast.com
- **Phone**: +98-21-1234-5678
- **Documentation**: https://docs.sarvcast.com/role-permission-system
- **Admin Panel**: https://admin.sarvcast.com/roles

---

*This documentation is regularly updated to reflect system changes and improvements. Last updated: September 2025*
