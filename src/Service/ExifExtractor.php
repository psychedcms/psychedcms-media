<?php

declare(strict_types=1);

namespace PsychedCms\Media\Service;

class ExifExtractor implements ExifExtractorInterface
{
    private const SUPPORTED_MIME_TYPES = [
        'image/jpeg',
        'image/tiff',
    ];

    public function extract(string $filePath, string $mimeType): ?array
    {
        if (!\in_array($mimeType, self::SUPPORTED_MIME_TYPES, true)) {
            return null;
        }

        if (!\function_exists('exif_read_data')) {
            return null;
        }

        $raw = @exif_read_data($filePath, 'ANY_TAG', true);
        if ($raw === false) {
            return null;
        }

        return $this->normalize($raw);
    }

    /**
     * @param array<string, mixed> $raw
     * @return array<string, mixed>
     */
    private function normalize(array $raw): array
    {
        $data = [];

        // Camera info
        $ifd0 = $raw['IFD0'] ?? [];
        if (isset($ifd0['Make'])) {
            $data['cameraMake'] = $this->sanitizeString($ifd0['Make']);
        }
        if (isset($ifd0['Model'])) {
            $data['cameraModel'] = $this->sanitizeString($ifd0['Model']);
        }
        if (isset($ifd0['Software'])) {
            $data['software'] = $this->sanitizeString($ifd0['Software']);
        }

        // Exposure info
        $exif = $raw['EXIF'] ?? [];
        if (isset($exif['ExposureTime'])) {
            $data['exposureTime'] = $this->sanitizeString((string) $exif['ExposureTime']);
        }
        if (isset($exif['FNumber'])) {
            $data['fNumber'] = $this->rationalToFloat($exif['FNumber']);
        }
        if (isset($exif['ISOSpeedRatings'])) {
            $data['iso'] = (int) $exif['ISOSpeedRatings'];
        }
        if (isset($exif['FocalLength'])) {
            $data['focalLength'] = $this->rationalToFloat($exif['FocalLength']);
        }
        if (isset($exif['DateTimeOriginal'])) {
            $data['dateTimeOriginal'] = $this->sanitizeString($exif['DateTimeOriginal']);
        }
        if (isset($exif['Flash'])) {
            $data['flash'] = (int) $exif['Flash'];
        }

        // GPS
        $gps = $raw['GPS'] ?? [];
        $latitude = $this->extractGpsCoordinate($gps, 'GPSLatitude', 'GPSLatitudeRef');
        $longitude = $this->extractGpsCoordinate($gps, 'GPSLongitude', 'GPSLongitudeRef');
        if ($latitude !== null && $longitude !== null) {
            $data['gps'] = [
                'latitude' => $latitude,
                'longitude' => $longitude,
            ];
        }

        if (isset($gps['GPSAltitude'])) {
            $data['gps']['altitude'] = $this->rationalToFloat($gps['GPSAltitude']);
        }

        return $data !== [] ? $data : null;
    }

    /**
     * @param array<string, mixed> $gps
     */
    private function extractGpsCoordinate(array $gps, string $coordKey, string $refKey): ?float
    {
        if (!isset($gps[$coordKey]) || !\is_array($gps[$coordKey])) {
            return null;
        }

        $parts = $gps[$coordKey];
        if (\count($parts) < 3) {
            return null;
        }

        $degrees = $this->rationalToFloat($parts[0]);
        $minutes = $this->rationalToFloat($parts[1]);
        $seconds = $this->rationalToFloat($parts[2]);

        if ($degrees === null || $minutes === null || $seconds === null) {
            return null;
        }

        $decimal = $degrees + ($minutes / 60) + ($seconds / 3600);

        $ref = $gps[$refKey] ?? null;
        if ($ref === 'S' || $ref === 'W') {
            $decimal = -$decimal;
        }

        return round($decimal, 6);
    }

    private function rationalToFloat(mixed $value): ?float
    {
        if (\is_numeric($value)) {
            return (float) $value;
        }

        if (!\is_string($value)) {
            return null;
        }

        $parts = explode('/', $value);
        if (\count($parts) !== 2) {
            return null;
        }

        $denominator = (float) $parts[1];
        if ($denominator == 0) {
            return null;
        }

        return (float) $parts[0] / $denominator;
    }

    private function sanitizeString(string $value): string
    {
        // Strip null bytes and non-printable characters
        return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', trim($value)) ?? trim($value);
    }
}
