<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

final class CapabilityRules
{
    /** Add every prerequisite declared by extension manifests. */
    public static function expand(array $capabilities): array
    {
        if ($capabilities === ['*'] || in_array('*', $capabilities, true)) {
            return ['*'];
        }

        $rules = [];
        foreach (DB::table('core_extensions')->pluck('manifest') as $json) {
            $manifest = json_decode((string) $json, true);
            foreach ((array) ($manifest['capabilities'] ?? []) as $capability) {
                if (is_array($capability) && isset($capability['name'])) {
                    $rules[(string) $capability['name']] = array_values(array_filter((array) ($capability['implies'] ?? []), 'is_string'));
                }
            }
        }

        $expanded = array_values(array_unique($capabilities));
        for ($index = 0; $index < count($expanded); $index++) {
            foreach ($rules[$expanded[$index]] ?? [] as $required) {
                if (! in_array($required, $expanded, true)) {
                    $expanded[] = $required;
                }
            }
        }

        return $expanded;
    }
}
