# Timeline Upload Configuration Setup Guide

## Overview
This guide explains how to configure PHP and Laravel to support uploading up to 300 timeline images at once.

## Current PHP Limits
- `max_file_uploads`: 20 (needs to be increased to 300)
- `post_max_size`: 40M (needs to be increased to 200M)
- `upload_max_filesize`: 40M (needs to be increased to 10M)
- `memory_limit`: 512M (needs to be increased to 1024M)

## Configuration Steps

### 1. PHP Configuration (php.ini)
Update your PHP configuration file with these settings:

```ini
; Maximum number of files that can be uploaded via a single request
max_file_uploads = 300

; Maximum size of POST data that PHP will accept
post_max_size = 200M

; Maximum allowed size for uploaded files
upload_max_filesize = 10M

; Maximum amount of memory a script may consume
memory_limit = 1024M

; Maximum execution time of each script, in seconds
max_execution_time = 300

; Maximum time in seconds a script is allowed to parse input data
max_input_time = 300

; Maximum number of input variables
max_input_vars = 5000
```

### 2. Laravel Configuration
The application now uses `config/timeline.php` for configuration. You can override these settings using environment variables:

```env
# Timeline Upload Configuration
TIMELINE_MAX_FILE_UPLOADS=300
TIMELINE_MAX_FILE_SIZE=10M
TIMELINE_MAX_POST_SIZE=200M
TIMELINE_MEMORY_LIMIT=1024M
TIMELINE_MAX_EXECUTION_TIME=300
TIMELINE_MAX_INPUT_TIME=300
TIMELINE_MAX_INPUT_VARS=5000
```

### 3. Web Server Configuration
Make sure your web server (Apache/Nginx) can handle large uploads:

#### Apache (.htaccess)
```apache
# Increase upload limits
php_value max_file_uploads 300
php_value post_max_size 200M
php_value upload_max_filesize 10M
php_value memory_limit 1024M
php_value max_execution_time 300
php_value max_input_time 300
php_value max_input_vars 5000
```

#### Nginx
```nginx
client_max_body_size 200M;
client_body_timeout 300s;
client_header_timeout 300s;
```

## Automated Setup

### Windows (XAMPP/WAMP)
Run the provided batch script:
```cmd
setup-php-upload-limits.bat
```

### Linux/Mac
Create a script to update php.ini:
```bash
#!/bin/bash
PHPINI=$(php --ini | grep "Loaded Configuration File" | cut -d: -f2 | xargs)
sudo cp "$PHPINI" "$PHPINI.backup.$(date +%Y%m%d_%H%M%S)"
sudo sed -i 's/max_file_uploads = .*/max_file_uploads = 300/' "$PHPINI"
sudo sed -i 's/post_max_size = .*/post_max_size = 200M/' "$PHPINI"
sudo sed -i 's/upload_max_filesize = .*/upload_max_filesize = 10M/' "$PHPINI"
sudo sed -i 's/memory_limit = .*/memory_limit = 1024M/' "$PHPINI"
sudo sed -i 's/max_execution_time = .*/max_execution_time = 300/' "$PHPINI"
sudo sed -i 's/max_input_time = .*/max_input_time = 300/' "$PHPINI"
sudo sed -i 's/max_input_vars = .*/max_input_vars = 5000/' "$PHPINI"
echo "PHP configuration updated. Please restart your web server."
```

## Verification

### Check Current Settings
```bash
php -i | grep -E "(max_file_uploads|post_max_size|upload_max_filesize|memory_limit)"
```

### Test Upload
1. Go to the timeline creation page
2. Try to add more than 20 timeline entries
3. The system should now allow up to 300 entries

## Troubleshooting

### Common Issues

1. **"Too many files" error**
   - Check if `max_file_uploads` is set to 300
   - Restart web server after configuration changes

2. **"POST data too large" error**
   - Check if `post_max_size` is set to 200M
   - Check web server configuration

3. **"File too large" error**
   - Check if `upload_max_filesize` is set to 10M
   - Check individual file sizes

4. **Memory limit exceeded**
   - Check if `memory_limit` is set to 1024M
   - Consider increasing further if needed

### Debug Information
The application logs PHP configuration on each timeline creation attempt. Check the logs for:
- `php_max_file_uploads`
- `php_post_max_size`
- `php_upload_max_filesize`
- `php_memory_limit`

## Performance Considerations

### For Large Uploads (200+ files)
- Consider implementing chunked uploads
- Use background job processing
- Implement progress indicators
- Consider using a CDN for image storage

### Server Resources
- Ensure adequate RAM (2GB+ recommended)
- Use SSD storage for better I/O performance
- Monitor server load during large uploads

## Security Considerations

### File Validation
- All uploaded files are validated for type and size
- Images are processed and optimized
- Malicious files are rejected

### Rate Limiting
- Consider implementing rate limiting for upload endpoints
- Monitor for abuse patterns
- Implement CAPTCHA for large uploads

## Support

If you encounter issues:
1. Check the application logs
2. Verify PHP configuration
3. Test with smaller batches first
4. Contact system administrator for server configuration changes
