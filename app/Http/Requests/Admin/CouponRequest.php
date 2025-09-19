<?php

namespace App\Http\Requests\Admin;

class CouponRequest extends BaseAdminRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $couponId = $this->route('coupon') ? $this->route('coupon')->id : null;

        return [
            'code' => [
                'required',
                'string',
                'max:50',
                'regex:/^[A-Z0-9_-]+$/',
                'unique:coupon_codes,code,' . $couponId
            ],
            'type' => 'required|in:percentage,fixed_amount,free_coins',
            'value' => 'required|numeric|min:0',
            'description' => 'nullable|string|max:500',
            'usage_limit' => 'nullable|integer|min:1|max:1000000',
            'expires_at' => 'nullable|date|after:now',
            'status' => 'required|in:active,inactive',
        ];
    }

    /**
     * Custom validation logic
     */
    protected function customValidation($validator)
    {
        $validator->after(function ($validator) {
            // Validate value based on type
            if ($this->type === 'percentage' && $this->value > 100) {
                $validator->errors()->add('value', 'درصد تخفیف نمی‌تواند بیش از 100 باشد.');
            }

            if ($this->type === 'fixed_amount' && $this->value > 1000000) {
                $validator->errors()->add('value', 'مبلغ ثابت نمی‌تواند بیش از 1,000,000 تومان باشد.');
            }

            if ($this->type === 'free_coins' && $this->value > 10000) {
                $validator->errors()->add('value', 'سکه رایگان نمی‌تواند بیش از 10,000 باشد.');
            }

            // Validate expiration date
            if ($this->expires_at && $this->expires_at < now()->addDay()) {
                $validator->errors()->add('expires_at', 'تاریخ انقضا باید حداقل یک روز آینده باشد.');
            }
        });
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        $this->merge([
            'code' => strtoupper($this->code),
        ]);
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'code.required' => 'کد تخفیف الزامی است.',
            'code.regex' => 'کد تخفیف باید شامل حروف بزرگ، اعداد، خط تیره و زیرخط باشد.',
            'code.unique' => 'این کد تخفیف قبلاً استفاده شده است.',
            'type.required' => 'نوع تخفیف الزامی است.',
            'type.in' => 'نوع تخفیف انتخاب شده معتبر نیست.',
            'value.required' => 'مقدار تخفیف الزامی است.',
            'value.numeric' => 'مقدار تخفیف باید عدد باشد.',
            'value.min' => 'مقدار تخفیف نمی‌تواند منفی باشد.',
            'description.max' => 'توضیحات نمی‌تواند بیش از 500 کاراکتر باشد.',
            'usage_limit.integer' => 'محدودیت استفاده باید عدد صحیح باشد.',
            'usage_limit.min' => 'محدودیت استفاده باید حداقل 1 باشد.',
            'usage_limit.max' => 'محدودیت استفاده نمی‌تواند بیش از 1,000,000 باشد.',
            'expires_at.date' => 'تاریخ انقضا باید تاریخ معتبر باشد.',
            'expires_at.after' => 'تاریخ انقضا باید بعد از امروز باشد.',
            'status.required' => 'وضعیت کد تخفیف الزامی است.',
            'status.in' => 'وضعیت کد تخفیف انتخاب شده معتبر نیست.',
        ]);
    }
}
