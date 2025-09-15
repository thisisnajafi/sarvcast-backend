<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'اعلان سروکست' }}</title>
    <style>
        body {
            font-family: 'IranSansWeb', 'IRANSans', 'Tahoma', Arial, sans-serif;
            direction: rtl;
            text-align: right;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .header {
            background: linear-gradient(135deg, #4A90E2 0%, #357ABD 100%);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: bold;
        }
        .content {
            padding: 30px 20px;
        }
        .title {
            font-size: 20px;
            font-weight: bold;
            color: #333;
            margin-bottom: 15px;
        }
        .message {
            font-size: 16px;
            color: #666;
            line-height: 1.6;
            margin-bottom: 25px;
        }
        .button {
            display: inline-block;
            background-color: #4A90E2;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
            margin: 10px 0;
        }
        .footer {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            font-size: 14px;
            color: #666;
            border-top: 1px solid #e9ecef;
        }
        .footer a {
            color: #4A90E2;
            text-decoration: none;
        }
        .icon {
            width: 60px;
            height: 60px;
            margin: 0 auto 20px;
            background-color: #4A90E2;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>سروکست</h1>
            <p>پلتفرم داستان‌های صوتی کودکان</p>
        </div>
        
        <div class="content">
            <div class="icon">
                @if(isset($type))
                    @if($type === 'success')
                        ✓
                    @elseif($type === 'warning')
                        ⚠
                    @elseif($type === 'error')
                        ✗
                    @else
                        ℹ
                    @endif
                @else
                    ℹ
                @endif
            </div>
            
            <div class="title">{{ $title ?? 'اعلان جدید' }}</div>
            
            <div class="message">
                {{ $message ?? 'اعلان جدیدی برای شما ارسال شده است.' }}
            </div>
            
            @if(isset($user))
                <p style="color: #888; font-size: 14px;">
                    سلام {{ $user->first_name }} عزیز،
                </p>
            @endif
            
            @if(isset($action_url))
                <div style="text-align: center; margin: 25px 0;">
                    <a href="{{ $action_url }}" class="button">مشاهده بیشتر</a>
                </div>
            @endif
        </div>
        
        <div class="footer">
            <p>این ایمیل از طرف سروکست ارسال شده است.</p>
            <p>
                <a href="{{ url('/') }}">سروکست</a> | 
                <a href="{{ url('/privacy') }}">حریم خصوصی</a> | 
                <a href="{{ url('/contact') }}">تماس با ما</a>
            </p>
            <p style="margin-top: 15px; font-size: 12px; color: #999;">
                اگر نمی‌خواهید این ایمیل‌ها را دریافت کنید، 
                <a href="{{ url('/unsubscribe') }}">اینجا کلیک کنید</a>
            </p>
        </div>
    </div>
</body>
</html>
