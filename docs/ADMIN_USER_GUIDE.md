# SarvCast Admin Dashboard User Guide

## Overview
The SarvCast Admin Dashboard is a comprehensive Persian (RTL) interface for managing the children's audio story platform. This guide will help administrators navigate and use all features effectively.

## Accessing the Dashboard

### Login
1. Navigate to `/admin/auth/login`
2. Enter your phone number and password
3. Click "ورود" (Login)

**Default Admin Credentials:**
- Phone: 09123456789
- Password: admin123

## Dashboard Overview

### Main Navigation
The dashboard features a Persian RTL sidebar with the following sections:

- **داشبورد** (Dashboard) - Overview and statistics
- **داستان‌ها** (Stories) - Story management
- **قسمت‌ها** (Episodes) - Episode management
- **دسته‌بندی‌ها** (Categories) - Category management
- **کاربران** (Users) - User management
- **آپلود فایل** (File Upload) - File management
- **اعلان‌ها** (Notifications) - Notification management
- **آمار و تحلیل‌ها** (Analytics) - Analytics and reports

## Story Management

### Viewing Stories
1. Click "داستان‌ها" in the sidebar
2. Use filters to narrow down results:
   - **دسته‌بندی** (Category)
   - **وضعیت** (Status)
   - **سن** (Age Group)
   - **نوع** (Type: Premium/Free)
3. Use search to find specific stories
4. Click on a story title to view details

### Creating a New Story
1. Click "افزودن داستان جدید" (Add New Story)
2. Fill in the required fields:
   - **عنوان** (Title) - Story title
   - **زیرعنوان** (Subtitle) - Optional subtitle
   - **توضیحات** (Description) - Story description
   - **دسته‌بندی** (Category) - Select category
   - **گروه سنی** (Age Group) - Select age group
   - **مدت زمان** (Duration) - Total duration in minutes
   - **تصویر** (Image) - Upload story image
   - **تصویر جلد** (Cover Image) - Upload cover image
3. Select story personnel:
   - **کارگردان** (Director)
   - **نویسنده** (Writer)
   - **نویسنده اصلی** (Author)
   - **گوینده** (Narrator)
4. Set story properties:
   - **وضعیت** (Status) - Draft/Pending/Approved/Published
   - **پولی** (Premium) - Check if premium content
   - **رایگان کامل** (Completely Free) - Check if completely free
5. Click "ذخیره" (Save)

### Editing a Story
1. Find the story in the stories list
2. Click "ویرایش" (Edit) button
3. Make necessary changes
4. Click "به‌روزرسانی" (Update)

### Publishing a Story
1. Edit the story
2. Change status to "منتشر شده" (Published)
3. Set publication date
4. Save changes

## Episode Management

### Viewing Episodes
1. Click "قسمت‌ها" in the sidebar
2. Episodes are organized by story
3. Use filters to find specific episodes
4. Click on episode title to view details

### Creating a New Episode
1. Click "افزودن قسمت جدید" (Add New Episode)
2. Fill in the required fields:
   - **داستان** (Story) - Select parent story
   - **عنوان** (Title) - Episode title
   - **شماره قسمت** (Episode Number) - Episode order
   - **توضیحات** (Description) - Episode description
   - **مدت زمان** (Duration) - Episode duration
   - **فایل صوتی** (Audio File) - Upload audio file
   - **تصویر** (Image) - Upload episode image
3. Set episode properties:
   - **وضعیت** (Status) - Draft/Pending/Approved/Published
   - **رایگان** (Free) - Check if free episode
4. Click "ذخیره" (Save)

### Managing Episode Order
1. Edit the episode
2. Change the "شماره قسمت" (Episode Number)
3. Save changes

## Category Management

### Viewing Categories
1. Click "دسته‌بندی‌ها" in the sidebar
2. View all categories with their statistics
3. Click on category name to view details

### Creating a New Category
1. Click "افزودن دسته‌بندی جدید" (Add New Category)
2. Fill in the required fields:
   - **نام** (Name) - Category name
   - **توضیحات** (Description) - Category description
   - **رنگ** (Color) - Category color (hex code)
   - **آیکون** (Icon) - Upload category icon
3. Set category properties:
   - **وضعیت** (Status) - Active/Inactive
   - **ترتیب** (Order) - Display order
4. Click "ذخیره" (Save)

### Managing Category Order
1. Edit the category
2. Change the "ترتیب" (Order) number
3. Lower numbers appear first
4. Save changes

## User Management

### Viewing Users
1. Click "کاربران" in the sidebar
2. View all users with their information
3. Use filters to find specific users:
   - **نقش** (Role) - Parent/Child/Admin
   - **وضعیت** (Status) - Active/Inactive/Suspended/Pending
4. Click on user name to view details

### User Actions
- **مشاهده** (View) - View user details
- **ویرایش** (Edit) - Edit user information
- **تعلیق** (Suspend) - Suspend user account
- **فعال‌سازی** (Activate) - Activate user account
- **حذف** (Delete) - Delete user (not available for admins)

### Creating a New User
1. Click "افزودن کاربر جدید" (Add New User)
2. Fill in the required fields:
   - **ایمیل** (Email) - User email
   - **شماره تلفن** (Phone Number) - User phone
   - **نام** (First Name) - User first name
   - **نام خانوادگی** (Last Name) - User last name
   - **نقش** (Role) - Parent/Child/Admin
   - **وضعیت** (Status) - Active/Inactive/Pending
3. Click "ذخیره" (Save)

### Managing Child Profiles
1. View user details
2. Click "پروفایل‌های کودک" (Child Profiles)
3. Add, edit, or delete child profiles
4. Set favorite categories for each child

## File Management

### Uploading Files
1. Click "آپلود فایل" in the sidebar
2. Select file type:
   - **تصاویر** (Images) - Story/episode images
   - **فایل‌های صوتی** (Audio Files) - Episode audio
   - **فایل‌های عمومی** (General Files) - Other files
3. Drag and drop files or click to browse
4. Wait for upload to complete
5. Copy file URLs for use in stories/episodes

### File Types Supported
- **Images**: JPG, JPEG, PNG, WebP (max 5MB)
- **Audio**: MP3, M4A, WAV (max 100MB)
- **General**: PDF, DOC, DOCX (max 10MB)

## Notification Management

### Viewing Notifications
1. Click "اعلان‌ها" in the sidebar
2. View all sent notifications
3. See delivery status and statistics

### Creating a New Notification
1. Click "ارسال اعلان جدید" (Send New Notification)
2. Fill in the notification details:
   - **عنوان** (Title) - Notification title
   - **پیام** (Message) - Notification message
   - **نوع** (Type) - Notification type
   - **گیرندگان** (Recipients) - Target users
3. Preview the notification
4. Click "ارسال" (Send)

### Notification Types
- **عمومی** (General) - All users
- **کاربران خاص** (Specific Users) - Selected users
- **کاربران پولی** (Premium Users) - Premium subscribers
- **کاربران جدید** (New Users) - Recently registered users

## Analytics and Reports

### Dashboard Analytics
1. Click "آمار و تحلیل‌ها" in the sidebar
2. View comprehensive analytics:
   - **کاربران** (Users) - User statistics
   - **محتوا** (Content) - Story and episode stats
   - **درآمد** (Revenue) - Subscription and payment data
   - **استفاده** (Usage) - Play history and engagement

### Key Metrics
- **کل کاربران** (Total Users) - Total registered users
- **کاربران فعال** (Active Users) - Users active in last 30 days
- **داستان‌های منتشر شده** (Published Stories) - Total published stories
- **قسمت‌های منتشر شده** (Published Episodes) - Total published episodes
- **اشتراک‌های فعال** (Active Subscriptions) - Current active subscriptions
- **درآمد ماهانه** (Monthly Revenue) - Revenue for current month

### Exporting Reports
1. Select date range for reports
2. Choose report type:
   - **گزارش کاربران** (User Report)
   - **گزارش محتوا** (Content Report)
   - **گزارش درآمد** (Revenue Report)
3. Click "دانلود گزارش" (Download Report)
4. Reports are generated in Excel format

## System Settings

### Application Configuration
- **نام اپلیکیشن** (App Name) - Application name
- **نسخه** (Version) - Application version
- **وضعیت نگهداری** (Maintenance Mode) - Enable/disable maintenance mode

### Payment Settings
- **درگاه پرداخت** (Payment Gateway) - ZarinPal configuration
- **کلید مرچنت** (Merchant Key) - ZarinPal merchant ID
- **URL بازگشت** (Callback URL) - Payment callback URL

### SMS Settings
- **سرویس SMS** (SMS Service) - SMS.ir configuration
- **کلید API** (API Key) - SMS service API key
- **شماره فرستنده** (Sender Number) - SMS sender number

### Notification Settings
- **فایربیس** (Firebase) - Push notification configuration
- **ایمیل** (Email) - Email service settings
- **SMS** (SMS) - SMS notification settings

## Security Features

### Access Control
- **احراز هویت دو مرحله‌ای** (Two-Factor Authentication) - Enhanced security
- **کنترل دسترسی** (Access Control) - Role-based permissions
- **لاگ فعالیت‌ها** (Activity Logs) - Track admin actions

### Data Protection
- **رمزگذاری داده‌ها** (Data Encryption) - Sensitive data encryption
- **پشتیبان‌گیری خودکار** (Automatic Backups) - Regular data backups
- **مانیتورینگ امنیتی** (Security Monitoring) - Real-time security monitoring

## Troubleshooting

### Common Issues

#### Login Problems
- **مشکل ورود** (Login Issues)
  - Check phone number format
  - Verify password
  - Contact system administrator

#### File Upload Issues
- **مشکل آپلود فایل** (File Upload Issues)
  - Check file size limits
  - Verify file format
  - Check server storage space

#### Performance Issues
- **مشکلات عملکرد** (Performance Issues)
  - Clear browser cache
  - Check internet connection
  - Contact technical support

### Getting Help
- **پشتیبانی فنی** (Technical Support) - support@sarvcast.com
- **مستندات** (Documentation) - https://docs.sarvcast.com
- **وضعیت سیستم** (System Status) - https://status.sarvcast.com

## Best Practices

### Content Management
1. **کیفیت محتوا** (Content Quality)
   - Ensure high-quality audio files
   - Use appropriate images
   - Write engaging descriptions

2. **سازماندهی** (Organization)
   - Use consistent naming conventions
   - Properly categorize content
   - Maintain episode order

3. **بروزرسانی** (Updates)
   - Regularly update content
   - Monitor user feedback
   - Keep descriptions current

### User Management
1. **نظارت بر کاربران** (User Monitoring)
   - Monitor user activity
   - Respond to user reports
   - Maintain user privacy

2. **پشتیبانی** (Support)
   - Respond to user inquiries
   - Provide helpful guidance
   - Escalate complex issues

### System Maintenance
1. **پشتیبان‌گیری** (Backups)
   - Verify backup integrity
   - Test restore procedures
   - Maintain backup schedules

2. **به‌روزرسانی** (Updates)
   - Keep system updated
   - Monitor security patches
   - Test updates in staging

## Keyboard Shortcuts

- **Ctrl + S** - Save current form
- **Ctrl + N** - Create new item
- **Ctrl + F** - Search/filter
- **Ctrl + R** - Refresh page
- **Esc** - Close modal/cancel action

## Mobile Responsiveness

The admin dashboard is fully responsive and works on:
- **دسکتاپ** (Desktop) - Full functionality
- **تبلت** (Tablet) - Optimized layout
- **موبایل** (Mobile) - Essential features

## Browser Compatibility

Supported browsers:
- **Chrome** 90+
- **Firefox** 88+
- **Safari** 14+
- **Edge** 90+

## Conclusion

This guide covers all major features of the SarvCast Admin Dashboard. For additional help or advanced features, please contact the technical support team or refer to the API documentation.

Remember to:
- Keep your login credentials secure
- Regularly backup important data
- Monitor system performance
- Stay updated with new features
- Follow security best practices
