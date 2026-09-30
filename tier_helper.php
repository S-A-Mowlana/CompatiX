<?php
/**
 * Shared Hardware Tier & Comparison Helper
 * 
 * Provides tier classification and compatibility comparison functions for:
 * - CPU (Intel, AMD, Apple Silicon)
 * - GPU (NVIDIA RTX/GTX, AMD RX/Radeon, Integrated)
 * - OS (Windows, macOS, Linux)
 * 
 * Input: Raw specification string values.
 * Output: Tier rating strings ('entry', 'mid', 'mid-high', 'high-end', 'integrated', 'unknown')
 *         or boolean comparison results.
 */

if (!function_exists('compatix_normalize_key')) {
    function compatix_normalize_key($value) {
        $normalized = strtolower(trim((string)$value));
        $normalized = preg_replace('/[^a-z0-9]+/', ' ', $normalized);
        $normalized = preg_replace('/\s+/', ' ', $normalized);
        return trim($normalized);
    }
}

if (!function_exists('compatix_cpu_tier')) {
    function compatix_cpu_tier($cpu_value) {
        $normalized = compatix_normalize_key($cpu_value);
        if ($normalized === '' || $normalized === 'other' || $normalized === 'not listed') {
            return 'unknown';
        }

        $explicit = [
            'intel core i3' => 'entry',
            'intel core i5' => 'mid',
            'intel core i7' => 'mid-high',
            'intel core i9' => 'high-end',
            'intel core 3' => 'entry',
            'intel core 5' => 'mid',
            'intel core 7' => 'mid-high',
            'intel core 9' => 'high-end',
            'intel core ultra 5' => 'mid',
            'intel core ultra 7' => 'high-end',
            'intel core ultra 9' => 'high-end',
            'intel pentium' => 'entry',
            'intel celeron' => 'entry',
            'intel xeon' => 'high-end',
            'amd ryzen 3' => 'entry',
            'amd ryzen 5' => 'mid',
            'amd ryzen 7' => 'mid-high',
            'amd ryzen 9' => 'high-end',
            'amd ryzen threadripper' => 'high-end',
            'amd fx' => 'entry',
            'amd athlon' => 'entry',
            'amd epyc' => 'high-end',
            'apple m1' => 'mid',
            'apple m1 pro' => 'mid-high',
            'apple m1 max' => 'high-end',
            'apple m1 ultra' => 'high-end',
            'apple m2' => 'mid',
            'apple m2 pro' => 'mid-high',
            'apple m2 max' => 'high-end',
            'apple m2 ultra' => 'high-end',
            'apple m3' => 'mid',
            'apple m3 pro' => 'mid-high',
            'apple m3 max' => 'high-end',
            'apple m4' => 'mid',
            'apple m4 pro' => 'mid-high',
            'apple m4 max' => 'high-end',
        ];

        if (isset($explicit[$normalized])) {
            return $explicit[$normalized];
        }

        if (preg_match('/intel\s+core\s+(?:ultra\s+)?(3|5|7|9)/i', $cpu_value, $matches)) {
            $num = (int)$matches[1];
            if ($num === 3) return 'entry';
            if ($num === 5) return 'mid';
            if ($num === 7) return 'mid-high';
            if ($num === 9) return 'high-end';
        }

        if (preg_match('/intel\s+core\s*i?([3579])/i', $cpu_value, $matches)) {
            $gen = (int)$matches[1];
            if ($gen === 3) return 'entry';
            if ($gen === 5) return 'mid';
            if ($gen === 7) return 'mid-high';
            if ($gen === 9) return 'high-end';
        }

        if (preg_match('/apple\s*m([1-4])(?:\s+(pro|max|ultra))?/i', $cpu_value, $matches)) {
            $variant = strtolower($matches[2] ?? '');
            if ($variant === 'pro') return 'mid-high';
            if ($variant === 'max' || $variant === 'ultra') return 'high-end';
            return 'mid';
        }

        if (preg_match('/amd\s+ryzen\s+(3|5|7|9)/i', $cpu_value, $matches)) {
            $tier = (int)$matches[1];
            if ($tier === 3) return 'entry';
            if ($tier === 5) return 'mid';
            if ($tier === 7) return 'mid-high';
            if ($tier === 9) return 'high-end';
        }

        if (preg_match('/(?:xeon|epyc|threadripper)/i', $cpu_value)) {
            return 'high-end';
        }

        if (preg_match('/(?:pentium|celeron|athlon|fx)/i', $cpu_value)) {
            return 'entry';
        }

        return 'unknown';
    }
}

if (!function_exists('compatix_gpu_tier')) {
    function compatix_gpu_tier($gpu_value) {
        $normalized = compatix_normalize_key($gpu_value);
        if ($normalized === '' || $normalized === 'other' || $normalized === 'not listed') {
            return 'unknown';
        }

        $explicit = [
            'nvidia geforce rtx 5090' => 'high-end',
            'nvidia geforce rtx 5080' => 'high-end',
            'nvidia geforce rtx 5070 ti' => 'high-end',
            'nvidia geforce rtx 5070' => 'mid-high',
            'nvidia geforce rtx 5060 ti' => 'mid-high',
            'nvidia geforce rtx 5060' => 'mid',
            'nvidia geforce rtx 4090' => 'high-end',
            'nvidia geforce rtx 4080' => 'high-end',
            'nvidia geforce rtx 4070 ti' => 'high-end',
            'nvidia geforce rtx 4070' => 'mid-high',
            'nvidia geforce rtx 4060 ti' => 'mid-high',
            'nvidia geforce rtx 4060' => 'mid',
            'nvidia geforce rtx 3090' => 'high-end',
            'nvidia geforce rtx 3080' => 'high-end',
            'nvidia geforce rtx 3070' => 'mid-high',
            'nvidia geforce rtx 3060 ti' => 'mid',
            'nvidia geforce rtx 3060' => 'mid',
            'nvidia geforce rtx 3050' => 'mid',
            'nvidia geforce gtx 1660 ti' => 'mid',
            'nvidia geforce gtx 1660' => 'mid',
            'nvidia geforce gtx 1650' => 'mid',
            'nvidia geforce gtx 1080 ti' => 'mid-high',
            'nvidia geforce gtx 1080' => 'mid-high',
            'nvidia geforce gtx 1070' => 'mid',
            'nvidia geforce gtx 1060' => 'mid',
            'nvidia geforce gtx 1050 ti' => 'mid',
            'nvidia geforce gtx 1050' => 'mid',
            'amd radeon rx 9070 xt' => 'high-end',
            'amd radeon rx 9070' => 'high-end',
            'amd radeon rx 7900 xtx' => 'high-end',
            'amd radeon rx 7900 xt' => 'high-end',
            'amd radeon rx 7800 xt' => 'high-end',
            'amd radeon rx 7700 xt' => 'mid-high',
            'amd radeon rx 7600' => 'mid',
            'amd radeon rx 6900 xt' => 'high-end',
            'amd radeon rx 6800 xt' => 'high-end',
            'amd radeon rx 6700 xt' => 'mid-high',
            'amd radeon rx 6600 xt' => 'mid',
            'amd radeon rx 6600' => 'mid',
            'amd radeon rx 6500 xt' => 'mid',
            'amd radeon rx 590' => 'mid',
            'amd radeon rx 580' => 'mid',
            'amd radeon rx 570' => 'mid',
            'amd radeon rx 560' => 'mid',
            'intel uhd graphics' => 'integrated',
            'intel iris xe' => 'integrated',
            'amd radeon graphics apu' => 'integrated',
            'apple silicon gpu integrated' => 'integrated',
        ];

        if (isset($explicit[$normalized])) {
            return $explicit[$normalized];
        }

        if (preg_match('/(?:rtx|gtx)\s*(5090|5080|5070|5060|4090|4080|4070|4060|3090|3080|3070|3060|3050|1660|1080|1070|1060|1050)/i', $gpu_value, $matches)) {
            $number = (int)$matches[1];
            if (in_array($number, [5090, 5080, 4090, 4080, 3090, 3080, 4070], true)) {
                return 'high-end';
            }
            if (in_array($number, [5070, 4060, 3070, 3060, 3050, 1660, 1080, 1070, 1060, 1050], true)) {
                return in_array($number, [5070, 3070, 1080], true) ? 'mid-high' : 'mid';
            }
        }

        if (preg_match('/rx\s*(9070|7900|7800|7700|6900|6800|6700|6600|6500|590|580|570|560)/i', $gpu_value, $matches)) {
            $number = (int)$matches[1];
            if (in_array($number, [9070, 7900, 7800, 6900, 6800], true)) return 'high-end';
            if (in_array($number, [7700, 6700], true)) return 'mid-high';
            return 'mid';
        }

        if (preg_match('/integrated|iris|uhd|apu/i', $gpu_value)) {
            return 'integrated';
        }

        return 'unknown';
    }
}

if (!function_exists('compatix_os_tier')) {
    function compatix_os_tier($os_value) {
        $normalized = compatix_normalize_key($os_value);
        if ($normalized === '' || $normalized === 'other' || $normalized === 'not listed') {
            return 'unknown';
        }

        if (strpos($normalized, 'windows 11') !== false) return 'windows 11';
        if (strpos($normalized, 'windows 10') !== false) return 'windows 10';
        if (strpos($normalized, 'windows 8') !== false) return 'windows 8';
        if (strpos($normalized, 'windows 7') !== false) return 'windows 7';
        if (strpos($normalized, 'macos') !== false) return 'macos';
        if (strpos($normalized, 'ubuntu') !== false || strpos($normalized, 'fedora') !== false || strpos($normalized, 'debian') !== false || strpos($normalized, 'linux mint') !== false || strpos($normalized, 'arch linux') !== false || strpos($normalized, 'linux') !== false) {
            return 'linux';
        }

        return 'unknown';
    }
}

if (!function_exists('compatix_os_satisfies_requirement')) {
    function compatix_os_satisfies_requirement($user_os, $required_os) {
        $user_tier = compatix_os_tier($user_os);
        $required_tier = compatix_os_tier($required_os);

        if ($user_tier === 'unknown' || $required_tier === 'unknown') {
            return false;
        }

        if ($user_tier === $required_tier) {
            return true;
        }

        if (strpos($required_tier, 'windows') !== false && strpos($user_tier, 'windows') !== false) {
            $windows_order = ['windows 7' => 1, 'windows 8' => 2, 'windows 10' => 3, 'windows 11' => 4];
            return ($windows_order[$user_tier] ?? 0) >= ($windows_order[$required_tier] ?? 0);
        }

        if ($required_tier === 'macos' && $user_tier === 'macos') {
            return true;
        }

        if ($required_tier === 'linux' && $user_tier === 'linux') {
            return true;
        }

        return false;
    }
}
