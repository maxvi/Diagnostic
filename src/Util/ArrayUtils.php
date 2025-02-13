<?php

/*
 * This file is part of the FiveLab Diagnostic package.
 *
 * (c) FiveLab <mail@fivelab.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace FiveLab\Component\Diagnostic\Util;

use FiveLab\Component\Diagnostic\Result\Failure;

/**
 * Array utils helper.
 */
class ArrayUtils
{
    /**
     * Try to get specific setting from settings
     *
     * @param string                                          $path
     * @param array<string, array|bool|float|int|string|null> $settings
     *
     * @return mixed
     *
     * @throws \UnexpectedValueException
     */
    public static function tryGetSpecificSettingFromSettings(string $path, array $settings)
    {
        $pathParts = \explode('.', $path);

        $processedPath = '';

        while ($pathPart = \array_shift($pathParts)) {
            $processedPath .= $pathPart.'.';

            if (!\array_key_exists($pathPart, $settings)) {
                throw new \UnexpectedValueException(\sprintf(
                    'The setting "%s" is missed.',
                    \rtrim($processedPath, '.')
                ));
            }

            if (\count($pathParts)) {
                // Not last element. Get inner array.
                if (\is_array($settings[$pathPart])) {
                    $settings = $settings[$pathPart];
                } else {
                    throw new \UnexpectedValueException(\sprintf(
                        'The setting "%s%s" is missed.',
                        $processedPath,
                        \rtrim($pathParts[0], '.')
                    ));
                }
            } else {
                // Last element. Get value.
                return $settings[$pathPart];
            }
        }

        throw new \UnexpectedValueException(\sprintf(
            'Cannot get setting by path: "%s".',
            $path
        ));
    }
}
