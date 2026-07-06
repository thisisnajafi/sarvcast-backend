<?php

namespace App\Bootstrap;

use Illuminate\Filesystem\LocalFilesystemAdapter;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\Filesystem as Flysystem;
use League\Flysystem\Local\LocalFilesystemAdapter as FlysystemLocalAdapter;
use League\Flysystem\PathPrefixing\PathPrefixedAdapter;
use League\Flysystem\ReadOnly\ReadOnlyFilesystemAdapter;
use League\Flysystem\UnixVisibility\PortableVisibilityConverter;
use League\Flysystem\Visibility;
use League\MimeTypeDetection\ExtensionMimeTypeDetector;

/**
 * Shared hosting (LiteSpeed) often ships web PHP without ext-fileinfo while CLI has it.
 * Flysystem defaults to FinfoMimeTypeDetector which fatals when finfo is missing.
 */
final class HostingFilesystemBootstrap
{
    public static function register(): void
    {
        if (class_exists(\finfo::class, false) || extension_loaded('fileinfo')) {
            return;
        }

        Storage::extend('local', function ($app, array $config) {
            $visibility = PortableVisibilityConverter::fromArray(
                $config['permissions'] ?? [],
                $config['directory_visibility'] ?? $config['visibility'] ?? Visibility::PRIVATE
            );

            $links = ($config['links'] ?? null) === 'skip'
                ? FlysystemLocalAdapter::SKIP_LINKS
                : FlysystemLocalAdapter::DISALLOW_LINKS;

            $adapter = new FlysystemLocalAdapter(
                $config['root'],
                $visibility,
                $config['lock'] ?? LOCK_EX,
                $links,
                new ExtensionMimeTypeDetector(),
            );

            return (new LocalFilesystemAdapter(
                self::createFlysystem($adapter, $config),
                $adapter,
                $config,
            ))->shouldServeSignedUrls(
                $config['serve'] ?? false,
                fn () => $app['url'],
            );
        });
    }

    private static function createFlysystem(FlysystemLocalAdapter $adapter, array $config): Flysystem
    {
        if (($config['read-only'] ?? false) === true) {
            $adapter = new ReadOnlyFilesystemAdapter($adapter);
        }

        if (! empty($config['prefix'])) {
            $adapter = new PathPrefixedAdapter($adapter, $config['prefix']);
        }

        if (str_contains($config['endpoint'] ?? '', 'r2.cloudflarestorage.com')) {
            $config['retain_visibility'] = false;
        }

        return new Flysystem($adapter, Arr::only($config, [
            'directory_visibility',
            'disable_asserts',
            'retain_visibility',
            'temporary_url',
            'url',
            'visibility',
        ]));
    }
}
