<?php

declare(strict_types=1);

namespace App\Form\DataTransformer;

use App\Util\DateTimeUtil;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class TextTimeIntervalSecondsTransformer implements DataTransformerInterface
{
    /**
     * Transforms seconds to a string of form 2h7m.
     *
     * @param int|null $interval
     */
    public function transform($intervalRaw): string
    {
        if (null === $intervalRaw || '' === $intervalRaw) {
            return '';
        }

        $totalSeconds = intval($intervalRaw);

        $interval = DateTimeUtil::dateIntervalFromSeconds($totalSeconds);

        return $interval->format('%hh%im%ss');
    }

    /**
     * Transforms a string (time interval 2h5m9s) to integer seconds.
     *
     * @throws TransformationFailedException if time interval is invalid
     */
    public function reverseTransform($textInterval): ?int
    {
        // no interval? It's optional, so that's ok
        if (!$textInterval) {
            return null;
        }

        // Remove whitespace between characters
        $textInterval = str_replace(' ', '', $textInterval);

        if (str_starts_with($textInterval, '-')) {
            throw new TransformationFailedException('Time Interval can not be negative');
        }

        // Capitalize format characters for consistency
        $textInterval = str_replace('h', 'H', $textInterval);
        $textInterval = str_replace('m', 'M', $textInterval);
        $textInterval = str_replace('s', 'S', $textInterval);

        $hours = null;
        $minutes = null;
        $seconds = null;

        $value = $textInterval;
        $parts = explode('H', $value);
        if (2 === count($parts)) {
            $hours = $parts[0];
            $value = $parts[1];
        }

        $parts = explode('M', $value);
        if (2 === count($parts)) {
            $minutes = $parts[0];
            $value = $parts[1];
        }

        $parts = explode('S', $value);
        if (0 !== count($parts) && is_integer($parts[0])) {
            $seconds = $parts[0];
        }

        if (null === $hours && null === $minutes && null === $seconds) {
            throw new TransformationFailedException('No valid hours, minutes, or seconds found');
        }

        $totalSeconds = 0;
        if (null !== $hours) {
            $totalSeconds += intval($hours) * 3600;
        }

        if (null !== $minutes) {
            $totalSeconds += intval($minutes) * 60;
        }

        if (null !== $seconds) {
            $totalSeconds += $seconds;
        }

        return $totalSeconds;
    }
}
