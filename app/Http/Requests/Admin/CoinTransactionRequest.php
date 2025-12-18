<?php

namespace App\Http\Requests\Admin;

use App\Models\User;

class CoinTransactionRequest extends BaseAdminRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $rules = [
            'user_id' => 'required|exists:users,id',
            'amount' => 'required|integer|min:1|max:1000000',
            'type' => 'required|in:earned,purchased,gift,refund,admin_adjustment',
            'description' => 'nullable|string|max:500',
        ];

        // Add status validation for updates
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules['status'] = 'required|in:pending,completed,failed';
        }

        return $rules;
    }

    /**
     * Custom validation logic
     */
    protected function customValidation($validator)
    {
        $validator->after(function ($validator) {
            // Check if user has sufficient coins for negative transactions
            if ($this->amount < 0) {
                $user = User::find($this->user_id);
                if ($user && $user->coins < abs($this->amount)) {
                    $validator->errors()->add('amount', 'کاربر سکه کافی ندارد.');
                }
            }

            // Validate admin adjustment type
            if ($this->type === 'admin_adjustment' && !auth('web')->user()->hasRole('super_admin')) {
                $validator->errors()->add('type', 'تنها ادمین کل می‌تواند تنظیمات ادمین انجام دهد.');
            }
        });
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'user_id.required' => 'انتخاب کاربر الزامی است.',
            'user_id.exists' => 'کاربر انتخاب شده معتبر نیست.',
            'amount.required' => 'مبلغ سکه الزامی است.',
            'amount.integer' => 'مبلغ سکه باید عدد صحیح باشد.',
            'amount.min' => 'مبلغ سکه باید حداقل 1 باشد.',
            'amount.max' => 'مبلغ سکه نمی‌تواند بیش از 1,000,000 باشد.',
            'type.required' => 'نوع تراکنش الزامی است.',
            'type.in' => 'نوع تراکنش انتخاب شده معتبر نیست.',
            'description.max' => 'توضیحات نمی‌تواند بیش از 500 کاراکتر باشد.',
            'status.required' => 'وضعیت تراکنش الزامی است.',
            'status.in' => 'وضعیت تراکنش انتخاب شده معتبر نیست.',
        ]);
    }
}
