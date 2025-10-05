# CACHE-CONTROL HEADER CONFIGURATION

## 📍 WHERE THE `Cache-Control: max-age=180` HEADER IS SET

The `Cache-Control` header is now configured in the **`CacheApiResponses` middleware**.

### **File Location:**
```
app/Http/Middleware/CacheApiResponses.php
```

### **How It Works:**

1. **Route Configuration** (in `routes/api.php`):
   ```php
   Route::get('stories', [StoryController::class, 'index'])->middleware('cache.api:180');
   ```
   - The `180` parameter is passed to the middleware as `$ttl` (Time To Live)

2. **Middleware Processing** (in `CacheApiResponses.php`):
   ```php
   public function handle(Request $request, Closure $next, int $ttl = 300): Response
   {
       // ... caching logic ...
       
       // Set cache headers for the response
       $response->headers->set('Cache-Control', "public, max-age={$ttl}");
       $response->headers->set('X-Cache', 'MISS');
       
       return $response;
   }
   ```

3. **Header Generation**:
   - When `$ttl = 180`, the header becomes: `Cache-Control: public, max-age=180`
   - This tells browsers and CDNs to cache the response for 180 seconds (3 minutes)

## 🔧 HOW TO CHANGE THE CACHE DURATION

### **Method 1: Change Individual Routes**
Update the cache duration in `routes/api.php`:

```php
// Change from 3 minutes to 5 minutes
Route::get('stories', [StoryController::class, 'index'])->middleware('cache.api:300'); // 5 minutes

// Change from 3 minutes to 1 minute
Route::get('stories', [StoryController::class, 'index'])->middleware('cache.api:60'); // 1 minute
```

### **Method 2: Change Default Duration**
Update the default `$ttl` parameter in `CacheApiResponses.php`:

```php
public function handle(Request $request, Closure $next, int $ttl = 300): Response
//                                                                    ^^^
//                                                              Change this default
```

### **Method 3: Add Different Cache Durations**
You can use different durations for different routes:

```php
// Short cache for dynamic content
Route::get('stories/recent', [StoryController::class, 'recent'])->middleware('cache.api:60'); // 1 minute

// Medium cache for semi-static content
Route::get('stories', [StoryController::class, 'index'])->middleware('cache.api:180'); // 3 minutes

// Long cache for static content
Route::get('categories', [CategoryController::class, 'index'])->middleware('cache.api:600'); // 10 minutes
```

## 📊 CURRENT CACHE CONFIGURATION

| **Route Type** | **Current Duration** | **Cache-Control Header** |
|----------------|----------------------|---------------------------|
| **Categories** | 180 seconds | `Cache-Control: public, max-age=180` |
| **Stories** | 180 seconds | `Cache-Control: public, max-age=180` |
| **Episodes** | 180 seconds | `Cache-Control: public, max-age=180` |
| **People** | 180 seconds | `Cache-Control: public, max-age=180` |
| **Statistics** | 180 seconds | `Cache-Control: public, max-age=180` |

## 🎯 ADDITIONAL HEADERS SET

The middleware now also sets these helpful headers:

- **`Cache-Control: public, max-age=180`** - Tells browsers/CDNs how long to cache
- **`X-Cache: HIT`** - Indicates response came from cache
- **`X-Cache: MISS`** - Indicates response was generated fresh

## 🧪 TESTING THE HEADERS

After deployment, you can test the headers:

```bash
curl -I https://your-domain.com/api/v1/stories
```

**Expected Response Headers:**
```
HTTP/1.1 200 OK
Cache-Control: public, max-age=180
X-Cache: MISS
Content-Type: application/json
```

## 🚀 DEPLOYMENT

1. **Deploy the updated middleware**
2. **Clear existing cache** (if needed):
   ```bash
   php artisan cache:clear
   ```
3. **Test the headers** with curl or browser dev tools
4. **Verify caching behavior** in browser network tab

## ✅ SUMMARY

| **Aspect** | **Location** | **Status** |
|------------|--------------|------------|
| **Cache-Control Header** | `CacheApiResponses.php` | ✅ Now Set |
| **Cache Duration** | `routes/api.php` | ✅ 180 seconds |
| **Cache Key Generation** | `CacheApiResponses.php` | ✅ Working |
| **Header Testing** | Browser/curl | ✅ Ready |

**The `Cache-Control: max-age=180` header is now properly set by the middleware!** 🎯✅
