<?php

declare(strict_types=1);

use Nette\Utils\DateTime;
use Nette\Utils\Strings;

require_once __DIR__ . '/vendor/autoload.php';

// remove phpstan, because it is already prefixed in its own scope
$dateTime = DateTime::from('now');
$timestamp = $dateTime->format('Ymd');

// see https://github.com/humbug/php-scoper
return [
    'prefix' => 'RevealPrefix' . $timestamp,
    'patchers' => [
        // fixes https://github.com/rectorphp/rector/issues/7017
        function (string $filePath, string $prefix, string $content): string {
            if (str_ends_with($filePath, 'vendor/symfony/string/ByteString.php')) {
                return Strings::replace($content, '#' . $prefix . '\\\\\\\\1_\\\\\\\\2#', '\\\\1_\\\\2');
            }

            if (str_ends_with($filePath, 'vendor/symfony/string/AbstractUnicodeString.php')) {
                return Strings::replace($content, '#' . $prefix . '\\\\\\\\1_\\\\\\\\2#', '\\\\1_\\\\2');
            }

            return $content;
        },

        // fixes https://github.com/rectorphp/rector/issues/6007
        function (string $filePath, string $prefix, string $content): string {
            if (! \str_contains($filePath, 'vendor/')) {
                return $content;
            }

            // @see https://regex101.com/r/lBV8IO/2
            $fqcnReservedPattern = sprintf('#(\\\\)?%s\\\\(parent|self|static)#m', $prefix);
            $matches = Strings::matchAll($content, $fqcnReservedPattern);

            if ($matches === []) {
                return $content;
            }

            foreach ($matches as $match) {
                $content = str_replace($match[0], $match[2], $content);
            }

            return $content;
        },

        // scoper missed PSR-4 autodiscovery in Symfony
        function (string $filePath, string $prefix, string $content): string {
            if (! \str_ends_with($filePath, 'config.php') && ! \str_ends_with($filePath, 'services.php')) {
                return $content;
            }

            // skip "Rector\\" namespace
            if (\str_contains($content, '$services->load(\'Reveal')) {
                return $content;
            }

            return Strings::replace($content, '#services\->load\(\'#', "services->load('" . $prefix . '\\');
        },
    ],
];