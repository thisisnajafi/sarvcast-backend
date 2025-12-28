# Assign Super Admin Role - Instructions

## Users to Update
- **09136708883** → super_admin
- **09138333293** → super_admin

## Method 1: Using SQL (Recommended if database is accessible)

Run the SQL script:
```bash
mysql -u your_username -p your_database_name < assign_super_admin_roles.sql
```

Or run directly in MySQL:
```sql
UPDATE users 
SET role = 'super_admin', 
    updated_at = NOW()
WHERE phone_number IN ('09136708883', '09138333293');
```

## Method 2: Using Artisan Command

When your database is available, you can use the artisan command:

```bash
php artisan users:assign-super-admin 09136708883
php artisan users:assign-super-admin 09138333293
```

## Method 3: Using PHP Script

When your database is available, run:
```bash
php update_super_admins.php
```

## Verification

After running any of the above methods, verify the changes:

```sql
SELECT id, phone_number, first_name, last_name, role, status
FROM users
WHERE phone_number IN ('09136708883', '09138333293');
```

Or using Artisan Tinker:
```bash
php artisan tinker
```

Then in tinker:
```php
User::whereIn('phone_number', ['09136708883', '09138333293'])->get(['id', 'phone_number', 'first_name', 'last_name', 'role']);
```

