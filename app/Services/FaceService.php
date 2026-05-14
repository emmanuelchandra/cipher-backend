<?php

namespace App\Services;

use App\Models\FaceDescriptor;
use App\Models\User;

class FaceService
{
    // Strict threshold: < 0.4 = high-confidence same person in face-api.js embeddings.
    // 0.6 (the library default) is too loose for attendance — similar-looking people often score 0.45–0.58.
    private const MATCH_THRESHOLD = 0.4;

    // Maximum number of face samples stored per user.
    private const MAX_SAMPLES = 10;

    /**
     * Add a new face sample for the user.
     * Stores up to MAX_SAMPLES descriptors; older samples are dropped from the front.
     */
    public function register(User $user, array $newDescriptor): FaceDescriptor
    {
        $record = FaceDescriptor::firstOrNew(['user_id' => $user->id]);

        $samples = $this->extractSamples($record->exists ? $record->descriptor : []);
        $samples[] = array_values($newDescriptor);

        // Keep only the most recent MAX_SAMPLES samples
        if (count($samples) > self::MAX_SAMPLES) {
            $samples = array_slice($samples, -self::MAX_SAMPLES);
        }

        $record->descriptor = $samples;
        $record->save();

        return $record;
    }

    /**
     * Find the user whose stored samples best match the input descriptor.
     * Compares against every stored sample for every user; the minimum
     * distance across all samples determines the winner.
     * Returns null when no user scores below MATCH_THRESHOLD.
     */
    public function findMatchingUser(array $inputDescriptor): ?User
    {
        $records = FaceDescriptor::with('user')->get();

        $bestRecord   = null;
        $bestDistance = PHP_FLOAT_MAX;

        foreach ($records as $record) {
            $minDistance = $this->minDistanceToSamples($inputDescriptor, $record->descriptor);

            if ($minDistance < $bestDistance) {
                $bestDistance = $minDistance;
                $bestRecord   = $record;
            }
        }

        if ($bestRecord === null || $bestDistance > self::MATCH_THRESHOLD) {
            return null;
        }

        return $bestRecord->user;
    }

    /**
     * Return the minimum Euclidean distance between the input and any stored sample.
     */
    public function minDistanceToSamples(array $input, array $storedDescriptor): float
    {
        $samples = $this->extractSamples($storedDescriptor);
        $min     = PHP_FLOAT_MAX;

        foreach ($samples as $sample) {
            $d = $this->euclideanDistance($input, $sample);
            if ($d < $min) {
                $min = $d;
            }
        }

        return $min;
    }

    public function euclideanDistance(array $a, array $b): float
    {
        $a = array_values($a);
        $b = array_values($b);

        if (count($a) !== count($b)) {
            return PHP_FLOAT_MAX;
        }

        $sum = 0.0;
        foreach ($a as $i => $val) {
            $diff = (float) $val - (float) $b[$i];
            $sum += $diff * $diff;
        }

        return sqrt($sum);
    }

    /**
     * Handle both the old format (flat 128-float array) and the new format
     * (array of 128-float arrays) so existing records are not broken.
     *
     * @return float[][]
     */
    private function extractSamples(array $descriptor): array
    {
        if (empty($descriptor)) {
            return [];
        }

        // New format: first element is itself an array → already multi-sample
        if (is_array($descriptor[0] ?? null)) {
            return $descriptor;
        }

        // Old format: single flat descriptor — wrap it
        return [$descriptor];
    }
}
