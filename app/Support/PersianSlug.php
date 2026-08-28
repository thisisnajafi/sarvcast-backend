<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Transliterates Persian text into URL-safe Latin slugs.
 *
 * Why not `Str::slug()`: it routes through an Arabic phonetic map, which
 * mangles Persian. Measured on real category names:
 *
 *   قصه شب            Str::slug => 'ksh-shb'              here => 'ghesse-shab'
 *   آموزشی            Str::slug => 'amozshy'              here => 'amuzeshi'
 *   ماجراجویی         Str::slug => 'magragoyy'            here => 'majarajuyi'
 *
 * A hard limitation applies regardless of the map: Persian does not write short
 * vowels, so `قصه` contains no letter for the "e" sounds in "ghesse". Pure
 * letter-mapping cannot recover them. `OVERRIDES` therefore supplies known
 * words, and callers should treat generated slugs as a *suggestion* an editor
 * can correct rather than a final answer.
 */
class PersianSlug
{
    /**
     * Whole-word replacements applied before letter mapping. These carry the
     * short vowels that the script omits, so they are the only way to get
     * genuinely readable output for common vocabulary.
     */
    private const OVERRIDES = [
        'داستان' => 'dastan',
        'داستان‌ها' => 'dastanha',
        'داستان‌های' => 'dastanhaye',
        'قصه' => 'ghesse',
        'قصه‌ها' => 'ghessehha',
        'شب' => 'shab',
        'کودک' => 'kudak',
        'کودکان' => 'kudakan',
        'کودکانه' => 'kudakane',
        'نوجوان' => 'nojavan',
        'نوجوانان' => 'nojavanan',
        'آموزشی' => 'amuzeshi',
        'آموزش' => 'amuzesh',
        'ماجراجویی' => 'majarajuyi',
        'ماجرا' => 'majara',
        'کلاسیک' => 'classic',
        'فانتزی' => 'fantasy',
        'تخیلی' => 'takhayoli',
        'حیوانات' => 'heyvanat',
        'حیوان' => 'heyvan',
        'تاریخی' => 'tarikhi',
        'تاریخ' => 'tarikh',
        'علمی' => 'elmi',
        'اخلاقی' => 'akhlaghi',
        'طنز' => 'tanz',
        'ایرانی' => 'irani',
        'ایران' => 'iran',
        'بین‌المللی' => 'beynolmelali',
        'شیر' => 'shir',
        'کوچولو' => 'kuchulu',
        'خواب' => 'khab',
        'مادر' => 'madar',
        'پدر' => 'pedar',
        'خانواده' => 'khanevade',
        'دوستی' => 'dusti',
        'شاهنامه' => 'shahname',
        'افسانه' => 'afsane',
        'رایگان' => 'rayegan',
        'جدید' => 'jadid',
        'محبوب' => 'mahbub',
        // Function words and short vowels the script omits entirely.
        'و' => 'va',
        'در' => 'dar',
        'با' => 'ba',
        'از' => 'az',
        'به' => 'be',
        'که' => 'ke',
        'این' => 'in',
        'آن' => 'an',
        'برای' => 'baraye',
        'جنگل' => 'jangal',
        'خاله' => 'khale',
        'خرسه' => 'kherse',
        'خرس' => 'khers',
        'گرگ' => 'gorg',
        'روباه' => 'rubah',
        'خرگوش' => 'khargush',
        'پرنده' => 'parande',
        'ماه' => 'mah',
        'ستاره' => 'setare',
        'باغ' => 'bagh',
        'مدرسه' => 'madrese',
        'معلم' => 'moalem',
        'دوست' => 'dust',
        'دختر' => 'dokhtar',
        'پسر' => 'pesar',
        'شهر' => 'shahr',
        'سفر' => 'safar',
        'گنج' => 'ganj',
        'پادشاه' => 'padeshah',
        'جادو' => 'jadu',
        'جادویی' => 'jaduyi',
    ];

    /**
     * Persian letter → Latin. Digraphs must be listed before single letters so
     * `str_replace` does not consume their first character early.
     */
    private const LETTERS = [
        'خ' => 'kh', 'چ' => 'ch', 'ش' => 'sh', 'ژ' => 'zh', 'غ' => 'gh', 'ق' => 'gh',
        'آ' => 'a', 'ا' => 'a', 'أ' => 'a', 'إ' => 'e', 'ب' => 'b', 'پ' => 'p',
        'ت' => 't', 'ث' => 's', 'ج' => 'j', 'ح' => 'h', 'د' => 'd', 'ذ' => 'z',
        'ر' => 'r', 'ز' => 'z', 'س' => 's', 'ص' => 's', 'ض' => 'z', 'ط' => 't',
        'ظ' => 'z', 'ع' => 'a', 'ف' => 'f', 'ک' => 'k', 'ك' => 'k', 'گ' => 'g',
        'ل' => 'l', 'م' => 'm', 'ن' => 'n', 'و' => 'v', 'ه' => 'h', 'ة' => 'h',
        'ی' => 'i', 'ي' => 'i', 'ئ' => 'i', 'ؤ' => 'o', 'ء' => '',
        // Diacritics: dropped rather than voiced, since they are rarely typed.
        'َ' => '', 'ِ' => '', 'ُ' => '', 'ّ' => '', 'ٌ' => '', 'ٍ' => '', 'ً' => '', 'ْ' => '',
        // Persian and Arabic-Indic digits.
        '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
        '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
        '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
        '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
    ];

    /** Zero-width non-joiner: a word separator in Persian, not a space. */
    private const ZWNJ = "\u{200C}";

    public static function make(?string $text, int $maxLength = 90): string
    {
        $text = trim((string) $text);
        if ($text === '') {
            return '';
        }

        // Normalise Arabic variants so overrides and the map both hit.
        $text = str_replace(['ي', 'ك', 'ﻻ'], ['ی', 'ک', 'لا'], $text);

        // Split on whitespace only. ZWNJ must stay inside the token: it joins
        // Persian compounds such as `داستان‌های`, and splitting on it first
        // would reduce that to `داستان` + `های` and miss the override entirely.
        $words = preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $parts = [];

        foreach ($words as $word) {
            $bare = preg_replace('/[^\p{L}\p{N}\x{200C}]+/u', '', $word) ?? $word;
            if ($bare === '') {
                continue;
            }
            $parts[] = self::OVERRIDES[$bare]
                ?? self::OVERRIDES[str_replace(self::ZWNJ, '', $bare)]
                ?? self::transliterate($bare);
        }

        $slug = implode('-', array_filter($parts, fn ($p) => $p !== ''));
        $slug = Str::lower($slug);
        $slug = preg_replace('/[^a-z0-9-]+/', '-', $slug) ?? $slug;
        $slug = preg_replace('/-+/', '-', $slug) ?? $slug;
        $slug = trim($slug, '-');

        if (mb_strlen($slug) > $maxLength) {
            $slug = mb_substr($slug, 0, $maxLength);
            // Never end mid-word.
            $lastDash = mb_strrpos($slug, '-');
            if ($lastDash !== false && $lastDash > $maxLength * 0.5) {
                $slug = mb_substr($slug, 0, $lastDash);
            }
            $slug = trim($slug, '-');
        }

        return $slug;
    }

    private static function transliterate(string $word): string
    {
        $word = str_replace(self::ZWNJ, '', $word);

        // A trailing `ه` is the silent-h/-e ending (خانه → khane, نامه → name).
        // Mapping it to a bare `h` produces unreadable output like `khalh`.
        if (mb_strlen($word) > 1 && mb_substr($word, -1) === 'ه') {
            // Latin `e` passes through the letter map untouched.
            $word = mb_substr($word, 0, -1) . 'e';
        }

        return str_replace(
            array_keys(self::LETTERS),
            array_values(self::LETTERS),
            $word
        );
    }

    /**
     * Appends `-2`, `-3`, … until `$exists` reports the slug free. Used at write
     * time so a unique index can never reject a save.
     */
    public static function unique(?string $text, callable $exists, int $maxLength = 90): string
    {
        $base = self::make($text, $maxLength) ?: 'item';
        $slug = $base;
        $suffix = 2;

        while ($exists($slug)) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
