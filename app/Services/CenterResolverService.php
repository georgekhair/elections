<?php

namespace App\Services;

use App\Models\PollingCenter;

class CenterResolverService
{
    public function resolve(?string $centerCode = null, ?string $location = null): ?int
    {
        if ($centerCode) {
            $center = PollingCenter::where('code', trim($centerCode))->first();
            if ($center) {
                return $center->id;
            }
        }

        if ($location) {
            $normalized = $this->normalize($location);

            $center = PollingCenter::get()->first(function ($c) use ($normalized) {
                return $this->normalize($c->name) === $normalized;
            });

            if ($center) {
                return $center->id;
            }
        }

        return null;
    }

    private function normalize(string $value): string
    {
        $value = trim($value);
        $value = preg_replace('/\s+/', ' ', $value);

        return mb_strtolower($value);
    }
}
