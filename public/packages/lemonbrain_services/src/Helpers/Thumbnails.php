<?php

declare(strict_types=1);

namespace Helpers;

use InvalidArgumentException;

class Thumbnails
{
    /**
     * Calculates the thumbnail dimensions based on the original image dimensions and thumbnail settings.
     *
     * @param int      $originalWidth  The width of the original image.
     * @param int      $originalHeight The height of the original image.
     * @param int|null $thumbWidth     The configured thumbnail width (if any).
     * @param int|null $thumbHeight    The configured thumbnail height (if any).
     *
     * @return array{width: int, height: int} An associative array with keys 'width' and 'height' representing the thumbnail dimensions.
     *
     * @throws InvalidArgumentException If the original dimensions are not provided or are not greater than zero.
     */
    public function getThumbnailDimensions(
        int $originalWidth,
        int $originalHeight,
        ?int $thumbWidth = null,
        ?int $thumbHeight = null
    ): array {
        // Validate that the original dimensions are positive integers.
        if ($originalWidth <= 0 || $originalHeight <= 0) {
            throw new InvalidArgumentException('Original image dimensions must be greater than zero.');
        }

        // Case 1: Both thumbnail width and height are provided.
        if ($thumbWidth !== null && $thumbHeight !== null) {
            $thumbnailWidth  = $thumbWidth;
            $thumbnailHeight = $thumbHeight;
        }
        // Case 2: Only the thumbnail width is provided; calculate the height preserving the aspect ratio.
        elseif ($thumbWidth !== null) {
            $thumbnailWidth  = $thumbWidth;
            $thumbnailHeight = (int) round(($originalHeight / $originalWidth) * $thumbWidth);
        }
        // Case 3: Only the thumbnail height is provided; calculate the width preserving the aspect ratio.
        elseif ($thumbHeight !== null) {
            $thumbnailHeight = $thumbHeight;
            $thumbnailWidth  = (int) round(($originalWidth / $originalHeight) * $thumbHeight);
        }
        // Case 4: Neither dimension is provided; use the original dimensions as a fallback.
        else {
            $thumbnailWidth  = $originalWidth;
            $thumbnailHeight = $originalHeight;
        }

        return [
            'width'  => $thumbnailWidth,
            'height' => $thumbnailHeight,
        ];
    }
}
