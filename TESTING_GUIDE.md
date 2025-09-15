# SarvCast Testing Guide

This guide provides comprehensive testing instructions for the SarvCast Laravel application.

## 🧪 Test Structure

The application includes comprehensive tests covering all major functionality:

### Test Suites

1. **Authentication Tests** (`tests/Feature/AuthTest.php`)
   - User registration and validation
   - User login and logout
   - Profile management
   - Password changes
   - Admin authorization
   - JWT token handling

2. **Story Tests** (`tests/Feature/StoryTest.php`)
   - Story CRUD operations
   - Episode management
   - Favorites functionality
   - Rating system
   - Search and filtering
   - Category-based filtering

3. **Payment Tests** (`tests/Feature/PaymentTest.php`)
   - Payment initiation
   - Payment verification
   - Payment history
   - Subscription management
   - Payment callbacks
   - Gateway integration

4. **Notification Tests** (`tests/Feature/NotificationTest.php`)
   - In-app notifications
   - Bulk notifications
   - Email notifications
   - Push notifications
   - Notification management
   - Read status tracking

## 🚀 Running Tests

### Prerequisites

```bash
# Install dependencies
composer install

# Set up test environment
cp .env.example .env.testing
```

### Test Commands

```bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test --testsuite=Feature
php artisan test --testsuite=Unit

# Run specific test file
php artisan test tests/Feature/AuthTest.php

# Run tests with coverage
php artisan test --coverage

# Run tests in parallel
php artisan test --parallel

# Run tests with verbose output
php artisan test --verbose
```

### Test Configuration

The `phpunit.xml` file is configured with:
- SQLite in-memory database for testing
- Array cache driver
- Sync queue driver
- Array mail driver
- Testing-specific environment variables

## 📊 Test Coverage

### Authentication System
- ✅ User registration with validation
- ✅ User login/logout functionality
- ✅ Profile management
- ✅ Password change functionality
- ✅ Admin role authorization
- ✅ JWT token handling
- ✅ Protected route access

### Story Management
- ✅ Story listing and pagination
- ✅ Story detail retrieval
- ✅ Episode management
- ✅ Favorites functionality
- ✅ Rating system
- ✅ Search and filtering
- ✅ Category-based filtering
- ✅ User play history

### Payment System
- ✅ Payment initiation
- ✅ Payment verification
- ✅ Payment history
- ✅ Subscription management
- ✅ Payment callbacks (ZarinPal, Pay.ir)
- ✅ Gateway integration
- ✅ Error handling

### Notification System
- ✅ In-app notifications
- ✅ Bulk notifications
- ✅ Email notifications
- ✅ Push notifications
- ✅ Notification management
- ✅ Read status tracking
- ✅ Notification filtering
- ✅ Notification search

## 🔧 Test Environment Setup

### Database Configuration

```env
DB_CONNECTION=sqlite
DB_DATABASE=:memory:
```

### Cache Configuration

```env
CACHE_DRIVER=array
QUEUE_CONNECTION=sync
SESSION_DRIVER=array
```

### Mail Configuration

```env
MAIL_MAILER=array
```

### Notification Configuration

```env
PUSH_NOTIFICATIONS_ENABLED=false
IN_APP_NOTIFICATIONS_ENABLED=true
LOG_NOTIFICATIONS=false
```

## 📝 Writing Tests

### Test Structure

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

class ExampleTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    public function test_example()
    {
        // Arrange
        $data = ['key' => 'value'];
        
        // Act
        $response = $this->postJson('/api/endpoint', $data);
        
        // Assert
        $response->assertStatus(200)
                ->assertJson(['success' => true]);
    }
}
```

### Database Testing

```php
// Use RefreshDatabase trait
use RefreshDatabase;

// Test database operations
$this->assertDatabaseHas('table', ['column' => 'value']);
$this->assertDatabaseMissing('table', ['column' => 'value']);
```

### API Testing

```php
// Test API endpoints
$response = $this->getJson('/api/endpoint');
$response->assertStatus(200)
         ->assertJsonStructure(['data']);

// Test with authentication
$response = $this->withHeaders([
    'Authorization' => 'Bearer ' . $token
])->postJson('/api/endpoint', $data);
```

### Mail Testing

```php
// Test email sending
Mail::fake();
// ... perform action that sends email
Mail::assertSent(MailableClass::class);
```

## 🐛 Debugging Tests

### Common Issues

1. **Database Connection Issues**
   ```bash
   # Check database configuration
   php artisan config:show database
   ```

2. **Permission Issues**
   ```bash
   # Set proper permissions
   chmod -R 755 storage
   chmod -R 755 bootstrap/cache
   ```

3. **Cache Issues**
   ```bash
   # Clear caches
   php artisan cache:clear
   php artisan config:clear
   ```

### Debug Commands

```bash
# Run tests with debug output
php artisan test --verbose

# Run specific test with debug
php artisan test --filter=test_name --verbose

# Check test environment
php artisan about
```

## 📈 Performance Testing

### Load Testing

```bash
# Install Apache Bench
sudo apt install apache2-utils

# Run load test
ab -n 1000 -c 10 http://localhost/api/v1/stories
```

### Memory Testing

```bash
# Run tests with memory monitoring
php artisan test --coverage --coverage-text
```

## 🔒 Security Testing

### Authentication Testing

- Test invalid credentials
- Test expired tokens
- Test unauthorized access
- Test admin authorization

### Input Validation Testing

- Test SQL injection attempts
- Test XSS attempts
- Test CSRF protection
- Test file upload security

## 📊 Test Reports

### Coverage Reports

```bash
# Generate HTML coverage report
php artisan test --coverage --coverage-html=coverage

# Generate text coverage report
php artisan test --coverage --coverage-text
```

### Test Results

```bash
# Run tests and save results
php artisan test --log-junit=test-results.xml
```

## 🚀 Continuous Integration

### GitHub Actions

```yaml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    
    steps:
    - uses: actions/checkout@v2
    
    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '8.2'
        
    - name: Install dependencies
      run: composer install
      
    - name: Run tests
      run: php artisan test
```

### Pre-commit Hooks

```bash
# Install pre-commit
pip install pre-commit

# Create .pre-commit-config.yaml
repos:
  - repo: local
    hooks:
      - id: php-tests
        name: PHP Tests
        entry: php artisan test
        language: system
        files: \.php$
```

## 📋 Test Checklist

### Before Deployment

- [ ] All tests pass
- [ ] Code coverage > 80%
- [ ] No critical security issues
- [ ] Performance tests pass
- [ ] Integration tests pass
- [ ] End-to-end tests pass

### Test Maintenance

- [ ] Update tests when features change
- [ ] Add tests for new features
- [ ] Remove obsolete tests
- [ ] Review test coverage regularly
- [ ] Update test documentation

## 🎯 Best Practices

1. **Test Isolation**: Each test should be independent
2. **Clear Naming**: Use descriptive test method names
3. **Arrange-Act-Assert**: Follow the AAA pattern
4. **Mock External Services**: Don't rely on external APIs
5. **Test Edge Cases**: Include boundary conditions
6. **Keep Tests Fast**: Avoid slow operations
7. **Maintain Test Data**: Use factories for test data
8. **Document Tests**: Add comments for complex tests

## 📞 Support

For testing issues or questions:

1. Check the test logs
2. Review this documentation
3. Contact the development team
4. Create an issue in the repository

---

**Note**: This testing guide assumes PHP 8.2+ and Laravel 11+. Adjust commands accordingly for different versions.
