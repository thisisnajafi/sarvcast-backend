<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class BaseAdminRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth('web')->check() && auth('web')->user()->role === 'admin';
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator)
    {
        if ($this->expectsJson()) {
            throw new HttpResponseException(
                response()->json([
                    'success' => false,
                    'message' => 'اطلاعات وارد شده معتبر نیست.',
                    'errors' => $validator->errors()
                ], 422)
            );
        }

        parent::failedValidation($validator);
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'required' => 'فیلد :attribute الزامی است.',
            'string' => 'فیلد :attribute باید متن باشد.',
            'numeric' => 'فیلد :attribute باید عدد باشد.',
            'email' => 'فیلد :attribute باید ایمیل معتبر باشد.',
            'url' => 'فیلد :attribute باید آدرس معتبر باشد.',
            'date' => 'فیلد :attribute باید تاریخ معتبر باشد.',
            'date_format' => 'فیلد :attribute باید در فرمت صحیح باشد.',
            'after' => 'فیلد :attribute باید بعد از تاریخ مشخص شده باشد.',
            'before' => 'فیلد :attribute باید قبل از تاریخ مشخص شده باشد.',
            'min' => 'فیلد :attribute باید حداقل :min کاراکتر باشد.',
            'max' => 'فیلد :attribute باید حداکثر :max کاراکتر باشد.',
            'unique' => 'فیلد :attribute قبلاً استفاده شده است.',
            'exists' => 'فیلد :attribute انتخاب شده معتبر نیست.',
            'in' => 'فیلد :attribute انتخاب شده معتبر نیست.',
            'image' => 'فیلد :attribute باید تصویر باشد.',
            'mimes' => 'فیلد :attribute باید از نوع :values باشد.',
            'mimetypes' => 'فیلد :attribute باید از نوع :values باشد.',
            'file' => 'فیلد :attribute باید فایل باشد.',
            'between' => 'فیلد :attribute باید بین :min و :max باشد.',
            'digits' => 'فیلد :attribute باید :digits رقم باشد.',
            'digits_between' => 'فیلد :attribute باید بین :min و :max رقم باشد.',
            'alpha' => 'فیلد :attribute باید فقط حروف باشد.',
            'alpha_dash' => 'فیلد :attribute باید فقط حروف، اعداد، خط تیره و زیرخط باشد.',
            'alpha_num' => 'فیلد :attribute باید فقط حروف و اعداد باشد.',
            'confirmed' => 'تایید فیلد :attribute مطابقت ندارد.',
            'different' => 'فیلد :attribute باید متفاوت از :other باشد.',
            'same' => 'فیلد :attribute باید مشابه :other باشد.',
            'regex' => 'فرمت فیلد :attribute معتبر نیست.',
            'required_if' => 'فیلد :attribute زمانی که :other برابر :value است الزامی است.',
            'required_unless' => 'فیلد :attribute زمانی که :other برابر :value نیست الزامی است.',
            'required_with' => 'فیلد :attribute زمانی که :values موجود است الزامی است.',
            'required_with_all' => 'فیلد :attribute زمانی که :values موجود است الزامی است.',
            'required_without' => 'فیلد :attribute زمانی که :values موجود نیست الزامی است.',
            'required_without_all' => 'فیلد :attribute زمانی که :values موجود نیست الزامی است.',
            'accepted' => 'فیلد :attribute باید پذیرفته شود.',
            'boolean' => 'فیلد :attribute باید true یا false باشد.',
            'integer' => 'فیلد :attribute باید عدد صحیح باشد.',
            'array' => 'فیلد :attribute باید آرایه باشد.',
            'distinct' => 'فیلد :attribute نباید تکراری باشد.',
            'filled' => 'فیلد :attribute باید مقدار داشته باشد.',
            'present' => 'فیلد :attribute باید موجود باشد.',
            'nullable' => 'فیلد :attribute می‌تواند خالی باشد.',
            'sometimes' => 'فیلد :attribute گاهی اوقات الزامی است.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => 'نام',
            'email' => 'ایمیل',
            'password' => 'رمز عبور',
            'password_confirmation' => 'تایید رمز عبور',
            'title' => 'عنوان',
            'description' => 'توضیحات',
            'content' => 'محتوا',
            'status' => 'وضعیت',
            'type' => 'نوع',
            'amount' => 'مبلغ',
            'price' => 'قیمت',
            'date' => 'تاریخ',
            'time' => 'زمان',
            'image' => 'تصویر',
            'file' => 'فایل',
            'url' => 'آدرس',
            'phone' => 'تلفن',
            'address' => 'آدرس',
            'category' => 'دسته‌بندی',
            'tags' => 'برچسب‌ها',
            'is_active' => 'فعال',
            'is_featured' => 'ویژه',
            'sort_order' => 'ترتیب',
            'meta_title' => 'عنوان متا',
            'meta_description' => 'توضیحات متا',
            'meta_keywords' => 'کلمات کلیدی متا',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Custom validation logic can be added here
            $this->customValidation($validator);
        });
    }

    /**
     * Custom validation logic
     */
    protected function customValidation($validator)
    {
        // Override in child classes for specific validation logic
    }

    /**
     * Sanitize input data
     */
    protected function sanitizeInput(array $input): array
    {
        foreach ($input as $key => $value) {
            if (is_string($value)) {
                // Remove HTML tags except allowed ones
                $input[$key] = strip_tags($value, '<p><br><strong><em><ul><ol><li><a><h1><h2><h3><h4><h5><h6>');
                
                // Trim whitespace
                $input[$key] = trim($value);
                
                // Convert special characters
                $input[$key] = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
            }
        }

        return $input;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        $this->merge($this->sanitizeInput($this->all()));
    }
}
