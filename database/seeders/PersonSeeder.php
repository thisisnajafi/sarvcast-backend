<?php

namespace Database\Seeders;

use App\Models\Person;
use Illuminate\Database\Seeder;

class PersonSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $people = [
            // Directors
            [
                'name' => 'احمد رضایی',
                'role' => 'director',
                'bio' => 'کارگردان با تجربه در زمینه تولید محتوای کودکان',
                'status' => 'active',
            ],
            [
                'name' => 'فاطمه محمدی',
                'role' => 'director',
                'bio' => 'متخصص در کارگردانی داستان‌های آموزشی کودکان',
                'status' => 'active',
            ],
            [
                'name' => 'علی حسینی',
                'role' => 'director',
                'bio' => 'کارگردان خلاق با تخصص در داستان‌های فانتزی',
                'status' => 'active',
            ],

            // Narrators
            [
                'name' => 'مریم کریمی',
                'role' => 'narrator',
                'bio' => 'گوینده با صدای گرم و جذاب برای کودکان',
                'status' => 'active',
            ],
            [
                'name' => 'حسن احمدی',
                'role' => 'narrator',
                'bio' => 'گوینده حرفه‌ای با تجربه در داستان‌های ماجراجویی',
                'status' => 'active',
            ],
            [
                'name' => 'زهرا نوری',
                'role' => 'narrator',
                'bio' => 'گوینده با صدای کودکانه و شاد',
                'status' => 'active',
            ],
            [
                'name' => 'محمد رضایی',
                'role' => 'narrator',
                'bio' => 'گوینده با تجربه در داستان‌های کلاسیک',
                'status' => 'active',
            ],

            // Authors
            [
                'name' => 'سارا احمدی',
                'role' => 'author',
                'bio' => 'نویسنده متخصص در داستان‌های کودکان',
                'status' => 'active',
            ],
            [
                'name' => 'علی محمدی',
                'role' => 'author',
                'bio' => 'نویسنده خلاق با تخصص در داستان‌های آموزشی',
                'status' => 'active',
            ],
            [
                'name' => 'فاطمه کریمی',
                'role' => 'author',
                'bio' => 'نویسنده با تجربه در داستان‌های اخلاقی',
                'status' => 'active',
            ],

            // Writers
            [
                'name' => 'حسن نوری',
                'role' => 'writer',
                'bio' => 'نویسنده فیلمنامه متخصص در محتوای کودکان',
                'status' => 'active',
            ],
            [
                'name' => 'مریم حسینی',
                'role' => 'writer',
                'bio' => 'نویسنده خلاق با تخصص در داستان‌های فانتزی',
                'status' => 'active',
            ],
            [
                'name' => 'احمد رضایی',
                'role' => 'writer',
                'bio' => 'نویسنده با تجربه در داستان‌های تاریخی',
                'status' => 'active',
            ],
        ];

        foreach ($people as $personData) {
            Person::create($personData);
        }
    }
}