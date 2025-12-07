<?php

namespace App\Helpers;

/**
 * Flavor Helper
 * 
 * Provides flavor detection and flag utilities for the Laravel backend.
 * This helper is flavor-aware and helps determine which billing platform
 * should be used based on request context.
 */
class FlavorHelper
{
    /**
     * Check if the request is from CafeBazaar flavor
     * 
     * @param \Illuminate\Http\Request|null $request
     * @return bool
     */
    public static function isCafeBazaar(?\Illuminate\Http\Request $request = null): bool
    {
        if (!$request) {
            $request = request();
        }
        
        // Check billing_platform parameter
        $billingPlatform = $request->input('billing_platform');
        if ($billingPlatform === 'cafebazaar') {
            return true;
        }
        
        // Check User-Agent or custom header if available
        $userAgent = $request->header('User-Agent', '');
        if (str_contains(strtolower($userAgent), 'cafebazaar')) {
            return true;
        }
        
        // Check package name from request if available
        $packageName = $request->input('package_name');
        if ($packageName && str_contains($packageName, '.cafebazaar')) {
            return true;
        }
        
        // Check payment metadata if available
        $paymentMetadata = $request->input('payment_metadata');
        if (is_array($paymentMetadata) && isset($paymentMetadata['billing_platform'])) {
            return $paymentMetadata['billing_platform'] === 'cafebazaar';
        }
        
        return false;
    }
    
    /**
     * Check if the request is from Myket flavor
     * 
     * @param \Illuminate\Http\Request|null $request
     * @return bool
     */
    public static function isMyket(?\Illuminate\Http\Request $request = null): bool
    {
        if (!$request) {
            $request = request();
        }
        
        $billingPlatform = $request->input('billing_platform');
        if ($billingPlatform === 'myket') {
            return true;
        }
        
        $packageName = $request->input('package_name');
        if ($packageName && str_contains($packageName, '.myket')) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Check if the request is from Website flavor
     * 
     * @param \Illuminate\Http\Request|null $request
     * @return bool
     */
    public static function isWebsite(?\Illuminate\Http\Request $request = null): bool
    {
        if (!$request) {
            $request = request();
        }
        
        $billingPlatform = $request->input('billing_platform');
        if ($billingPlatform === 'website') {
            return true;
        }
        
        // Default to website if not explicitly set
        return !self::isCafeBazaar($request) && !self::isMyket($request);
    }
    
    /**
     * Get the current flavor from request
     * 
     * @param \Illuminate\Http\Request|null $request
     * @return string 'cafebazaar'|'myket'|'website'
     */
    public static function getFlavor(?\Illuminate\Http\Request $request = null): string
    {
        if (self::isCafeBazaar($request)) {
            return 'cafebazaar';
        }
        
        if (self::isMyket($request)) {
            return 'myket';
        }
        
        return 'website';
    }
    
    /**
     * Check if payment should use in-app purchase flow
     * 
     * @param \Illuminate\Http\Request|null $request
     * @return bool
     */
    public static function shouldUseInAppPurchase(?\Illuminate\Http\Request $request = null): bool
    {
        return self::isCafeBazaar($request) || self::isMyket($request);
    }
    
    /**
     * Check if payment should use web view flow
     * 
     * @param \Illuminate\Http\Request|null $request
     * @return bool
     */
    public static function shouldUseWebView(?\Illuminate\Http\Request $request = null): bool
    {
        return self::isWebsite($request);
    }
    
    /**
     * Validate that request is from CafeBazaar flavor
     * Throws exception if not
     * 
     * @param \Illuminate\Http\Request|null $request
     * @throws \InvalidArgumentException
     * @return void
     */
    public static function requireCafeBazaar(?\Illuminate\Http\Request $request = null): void
    {
        if (!self::isCafeBazaar($request)) {
            throw new \InvalidArgumentException('This endpoint only accepts CafeBazaar flavor requests');
        }
    }
}

