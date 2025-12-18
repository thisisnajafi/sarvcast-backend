# SarvCast API Testing Guide

## Overview

This guide provides comprehensive instructions for testing the SarvCast API endpoints, including setup, authentication, and testing scenarios.

## Prerequisites

- Postman or similar API testing tool
- Access to SarvCast API (development or production)
- Valid phone number for testing
- Basic understanding of REST APIs

## Setup

### 1. Import Postman Collection

1. Download the `SarvCast_API.postman_collection.json` file
2. Open Postman
3. Click "Import" and select the collection file
4. The collection will be imported with all endpoints and examples

### 2. Configure Environment Variables

Create a new environment in Postman with the following variables:

```
base_url: https://sarvcast.com/api/v1
access_token: (leave empty, will be set after login)
user_id: (leave empty, will be set after login)
story_id: 1
episode_id: 1
```

### 3. Set Up Authentication

1. Use the "Authentication > Login" endpoint
2. Provide valid credentials
3. Copy the token from the response
4. Set the `access_token` environment variable
5. The token will be automatically used for authenticated requests

## Testing Scenarios

### Authentication Flow

#### 1. User Registration
```
POST /auth/register
```

**Test Data:**
```json
{
    "name": "Test User",
    "phone": "+989123456789",
    "password": "password123",
    "password_confirmation": "password123",
    "email": "test@example.com"
}
```

**Expected Response:**
- Status: 201 Created
- Contains user data and access token
- Token should be saved for subsequent requests

#### 2. User Login
```
POST /auth/login
```

**Test Data:**
```json
{
    "phone": "+989123456789",
    "password": "password123"
}
```

**Expected Response:**
- Status: 200 OK
- Contains user data and access token
- Token should be saved for subsequent requests

#### 3. User Logout
```
POST /auth/logout
```

**Expected Response:**
- Status: 200 OK
- Success message
- Token should be invalidated

### Content Management

#### 1. Get Stories List
```
GET /stories
```

**Test Cases:**
- Without parameters (default pagination)
- With pagination parameters (`page=1&per_page=10`)
- With category filter (`category_id=1`)
- With search term (`search=magic`)
- With sorting (`sort=title`)

**Expected Response:**
- Status: 200 OK
- Array of story objects
- Pagination metadata

#### 2. Get Story Details
```
GET /stories/{id}
```

**Test Cases:**
- Valid story ID
- Invalid story ID (should return 404)
- Premium story (should check access control)

**Expected Response:**
- Status: 200 OK for valid ID
- Status: 404 Not Found for invalid ID
- Complete story object with episodes and metadata

#### 3. Get Episodes List
```
GET /episodes
```

**Test Cases:**
- Without parameters
- With story filter (`story_id=1`)
- With pagination
- With search term

**Expected Response:**
- Status: 200 OK
- Array of episode objects
- Pagination metadata

#### 4. Play Episode
```
GET /episodes/{id}/play
```

**Test Cases:**
- Valid episode ID
- Invalid episode ID
- Premium episode (should check access control)
- Episode with no audio file

**Expected Response:**
- Status: 200 OK for valid ID
- Episode data with play URL
- Status: 404 Not Found for invalid ID

### User Management

#### 1. Get User Profile
```
GET /user
```

**Test Cases:**
- Authenticated user
- Unauthenticated user (should return 401)

**Expected Response:**
- Status: 200 OK for authenticated user
- Complete user profile data
- Status: 401 Unauthorized for unauthenticated user

#### 2. Update User Profile
```
PUT /user
```

**Test Data:**
```json
{
    "name": "Updated Name",
    "email": "updated@example.com"
}
```

**Test Cases:**
- Valid data
- Invalid email format
- Empty name
- Unauthenticated user

**Expected Response:**
- Status: 200 OK for valid data
- Updated user profile
- Status: 422 Unprocessable Entity for validation errors

### Favorites System

#### 1. Toggle Favorite
```
POST /favorites/toggle
```

**Test Data:**
```json
{
    "story_id": 1
}
```

**Test Cases:**
- Valid story ID
- Invalid story ID
- Already favorited story (should unfavorite)
- Unauthenticated user

**Expected Response:**
- Status: 200 OK
- Success message
- Favorite status change

#### 2. Get User Favorites
```
GET /favorites
```

**Test Cases:**
- Authenticated user with favorites
- Authenticated user with no favorites
- Unauthenticated user

**Expected Response:**
- Status: 200 OK
- Array of favorited stories
- Empty array if no favorites

### Search Functionality

#### 1. Global Search
```
GET /search?q=magic
```

**Test Cases:**
- Valid search term
- Empty search term
- Search with type filter (`type=stories`)
- Search with pagination
- Search with no results

**Expected Response:**
- Status: 200 OK
- Array of search results
- Pagination metadata

### File Upload

#### 1. Upload Image
```
POST /files/upload/image
Content-Type: multipart/form-data
```

**Test Cases:**
- Valid image file
- Invalid file type
- File too large
- No file provided
- Unauthenticated user

**Expected Response:**
- Status: 200 OK for valid file
- File information and URL
- Status: 422 Unprocessable Entity for validation errors

#### 2. Upload Audio
```
POST /files/upload/audio
Content-Type: multipart/form-data
```

**Test Cases:**
- Valid audio file
- Invalid file type
- File too large
- No file provided

**Expected Response:**
- Status: 200 OK for valid file
- File information and URL
- Status: 422 Unprocessable Entity for validation errors

### Error Handling

#### 1. Authentication Errors
- **401 Unauthorized**: Invalid or missing token
- **403 Forbidden**: Valid token but insufficient permissions

#### 2. Validation Errors
- **422 Unprocessable Entity**: Invalid request data
- Check error messages for specific field validation

#### 3. Not Found Errors
- **404 Not Found**: Resource doesn't exist
- Check error message for specific resource

#### 4. Rate Limiting
- **429 Too Many Requests**: Rate limit exceeded
- Check retry-after header for wait time

## Performance Testing

### 1. Load Testing

Use tools like Apache Bench or JMeter to test:

- **Concurrent Users**: Test with 10, 50, 100+ concurrent users
- **Response Times**: Measure average response times
- **Throughput**: Requests per second
- **Error Rates**: Percentage of failed requests

### 2. Stress Testing

Test system limits:

- **Maximum Concurrent Users**: Find breaking point
- **Large Payloads**: Test with large file uploads
- **Long-running Requests**: Test timeout handling
- **Memory Usage**: Monitor memory consumption

### 3. Endurance Testing

Test system stability:

- **Long-running Tests**: Run tests for hours/days
- **Memory Leaks**: Check for memory leaks
- **Resource Cleanup**: Verify proper resource cleanup

## Security Testing

### 1. Authentication Testing

- **Token Validation**: Test with invalid tokens
- **Token Expiration**: Test expired tokens
- **Token Hijacking**: Test token security
- **Brute Force**: Test login rate limiting

### 2. Authorization Testing

- **Access Control**: Test user permissions
- **Resource Access**: Test cross-user data access
- **Admin Endpoints**: Test admin-only endpoints
- **Premium Content**: Test premium content access

### 3. Input Validation Testing

- **SQL Injection**: Test for SQL injection vulnerabilities
- **XSS**: Test for cross-site scripting
- **File Upload**: Test malicious file uploads
- **Data Validation**: Test input validation

## Automated Testing

### 1. Unit Tests

Test individual components:

- **Model Tests**: Test data models
- **Controller Tests**: Test API controllers
- **Service Tests**: Test business logic
- **Validation Tests**: Test input validation

### 2. Integration Tests

Test component interactions:

- **API Integration**: Test API endpoints
- **Database Integration**: Test database operations
- **External Service Integration**: Test third-party services
- **Authentication Integration**: Test auth flow

### 3. End-to-End Tests

Test complete user journeys:

- **User Registration**: Complete registration flow
- **Story Discovery**: Complete story discovery flow
- **Episode Playback**: Complete playback flow
- **Subscription**: Complete subscription flow

## Test Data Management

### 1. Test Data Setup

Create consistent test data:

- **Users**: Test users with different roles
- **Stories**: Test stories with different categories
- **Episodes**: Test episodes with different properties
- **Subscriptions**: Test subscriptions with different plans

### 2. Data Cleanup

Clean up test data:

- **After Tests**: Clean up created data
- **Before Tests**: Reset test environment
- **Database Reset**: Reset database state
- **File Cleanup**: Clean up uploaded files

### 3. Test Isolation

Ensure test isolation:

- **Independent Tests**: Tests don't depend on each other
- **Clean State**: Each test starts with clean state
- **No Side Effects**: Tests don't affect each other
- **Parallel Execution**: Tests can run in parallel

## Monitoring and Logging

### 1. API Monitoring

Monitor API performance:

- **Response Times**: Track response times
- **Error Rates**: Monitor error rates
- **Throughput**: Monitor request throughput
- **Availability**: Monitor API availability

### 2. Logging

Implement comprehensive logging:

- **Request Logging**: Log all API requests
- **Error Logging**: Log all errors
- **Performance Logging**: Log performance metrics
- **Security Logging**: Log security events

### 3. Alerting

Set up alerts for:

- **High Error Rates**: Alert on high error rates
- **Slow Response Times**: Alert on slow responses
- **System Down**: Alert on system downtime
- **Security Events**: Alert on security events

## Best Practices

### 1. Test Organization

- **Group Related Tests**: Group related test cases
- **Use Descriptive Names**: Use clear test names
- **Document Test Cases**: Document test scenarios
- **Maintain Test Data**: Keep test data up to date

### 2. Test Execution

- **Run Tests Regularly**: Run tests frequently
- **Automate Testing**: Automate test execution
- **Parallel Execution**: Run tests in parallel
- **Test Reporting**: Generate test reports

### 3. Test Maintenance

- **Update Tests**: Update tests with API changes
- **Remove Obsolete Tests**: Remove outdated tests
- **Optimize Tests**: Optimize test performance
- **Review Tests**: Regularly review test coverage

## Troubleshooting

### Common Issues

1. **Authentication Failures**
   - Check token validity
   - Verify token format
   - Check token expiration

2. **Validation Errors**
   - Check request format
   - Verify required fields
   - Check data types

3. **Rate Limiting**
   - Check rate limit headers
   - Implement retry logic
   - Use appropriate delays

4. **File Upload Issues**
   - Check file size limits
   - Verify file types
   - Check upload permissions

### Debugging Tips

1. **Check Response Headers**
   - Look for error information
   - Check rate limit headers
   - Verify content type

2. **Review Request Data**
   - Verify request format
   - Check required fields
   - Validate data types

3. **Monitor Logs**
   - Check server logs
   - Review error logs
   - Monitor performance logs

4. **Use Debug Tools**
   - Use Postman console
   - Check browser dev tools
   - Use API monitoring tools

## Conclusion

This testing guide provides comprehensive coverage of the SarvCast API testing scenarios. Follow these guidelines to ensure thorough testing of all API endpoints and functionality. Regular testing helps maintain API quality and reliability.
