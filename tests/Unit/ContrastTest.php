<?php

/**
 * WCAG 2.1 contrast maths plus the design-token pairs the Monaralk UI actually
 * renders. Body text needs 4.5:1, non-text UI boundaries (form borders, focus
 * rings) need 3:1. Brand tokens are read straight out of the stylesheet so a
 * palette change fails this test; the slate values are Tailwind's published
 * default theme and are recorded here as an upstream contract.
 */
function contrastOkLchToRgb(string $oklch): array
{
    preg_match('/oklch\(\s*([\d.]+)%?\s+([\d.]+)\s+(-?[\d.]+)/', $oklch, $matches);

    if (count($matches) !== 4) {
        throw new InvalidArgumentException("Not an oklch colour: {$oklch}");
    }

    $lightness = str_contains($oklch, '%') || (float) $matches[1] > 1
        ? (float) $matches[1] / 100
        : (float) $matches[1];

    $chroma = (float) $matches[2];
    $hue = (float) $matches[3] * M_PI / 180;

    $a = $chroma * cos($hue);
    $b = $chroma * sin($hue);

    $lPrime = $lightness + 0.3963377774 * $a + 0.2158037573 * $b;
    $mPrime = $lightness - 0.1055613458 * $a - 0.0638541728 * $b;
    $sPrime = $lightness - 0.0894841775 * $a - 1.2914855480 * $b;

    $l = $lPrime ** 3;
    $m = $mPrime ** 3;
    $s = $sPrime ** 3;

    $toUnit = function (float $value): float {
        return max(0.0, min(1.0, $value < 0.0031308
            ? 12.92 * $value
            : 1.055 * ($value ** (1 / 2.4)) - 0.055));
    };

    return [
        $toUnit(4.0767416621 * $l - 3.3077115913 * $m + 0.2309699292 * $s),
        $toUnit(-1.2684380046 * $l + 2.6097574011 * $m - 0.3413193965 * $s),
        $toUnit(-0.0041960863 * $l - 0.7034186147 * $m + 1.7076147010 * $s),
    ];
}

function contrastHexToRgb(string $hex): array
{
    $hex = ltrim($hex, '#');

    if (strlen($hex) === 3) {
        $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
    }

    return array_map(
        fn (int $channel): float => $channel / 255,
        [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))],
    );
}

function contrastRelativeLuminance(array $rgb): float
{
    $linear = array_map(
        fn (float $channel): float => $channel <= 0.04045
            ? $channel / 12.92
            : (($channel + 0.055) / 1.055) ** 2.4,
        $rgb,
    );

    return 0.2126 * $linear[0] + 0.7152 * $linear[1] + 0.0722 * $linear[2];
}

function contrastRatio(string $foreground, string $background): float
{
    $toRgb = fn (string $colour): array => str_starts_with($colour, 'oklch')
        ? contrastOkLchToRgb($colour)
        : contrastHexToRgb($colour);

    $lighter = contrastRelativeLuminance($toRgb($foreground));
    $darker = contrastRelativeLuminance($toRgb($background));

    return (max($lighter, $darker) + 0.05) / (min($lighter, $darker) + 0.05);
}

function contrastBrandToken(string $name): string
{
    static $tokens = null;

    if ($tokens === null) {
        $css = file_get_contents(__DIR__.'/../../resources/css/app.css');
        preg_match_all('/--color-((?:brand|accent)-\d+):\s*(#[0-9a-fA-F]{3,8})/', $css, $matches, PREG_SET_ORDER);

        $tokens = [];
        foreach ($matches as $match) {
            $tokens[$match[1]] = $match[2];
        }
    }

    if (! isset($tokens[$name])) {
        throw new InvalidArgumentException("Design token {$name} is not declared in resources/css/app.css");
    }

    return $tokens[$name];
}

function contrastSlate(string $step): string
{
    return match ($step) {
        '50' => 'oklch(98.4% .003 247.858)',
        '100' => 'oklch(96.8% .007 247.896)',
        '200' => 'oklch(92.9% .013 255.508)',
        '300' => 'oklch(86.9% .022 252.894)',
        '400' => 'oklch(70.4% .04 256.788)',
        '500' => 'oklch(55.4% .046 257.417)',
        '600' => 'oklch(44.6% .043 257.281)',
        '700' => 'oklch(37.2% .044 257.287)',
        '800' => 'oklch(27.9% .041 260.031)',
        '900' => 'oklch(20.8% .042 265.755)',
        '950' => 'oklch(12.9% .042 264.695)',
        default => throw new InvalidArgumentException("Unknown slate step {$step}"),
    };
}

test('the contrast maths matches the wcag reference values', function () {
    expect(round(contrastRatio('#000000', '#ffffff'), 2))->toBe(21.00)
        ->and(round(contrastRatio('#ffffff', '#ffffff'), 2))->toBe(1.00)
        ->and(round(contrastRatio('#767676', '#ffffff'), 2))->toBe(4.54)
        ->and(round(contrastRatio('#ffffff', '#000000'), 2))->toBe(21.00);
});

test('oklch colours convert to the hex values tailwind publishes', function () {
    $toHex = function (string $oklch): string {
        $rgb = contrastOkLchToRgb($oklch);

        return sprintf('#%02x%02x%02x', ...array_map(fn (float $c): int => (int) round($c * 255), $rgb));
    };

    expect($toHex(contrastSlate('50')))->toBe('#f8fafc')
        ->and($toHex(contrastSlate('100')))->toBe('#f1f5f9')
        ->and($toHex(contrastSlate('200')))->toBe('#e2e8f0');
});

test('body and muted text meet wcag aa in light mode', function (string $foreground, string $background) {
    expect(contrastRatio($foreground, $background))->toBeGreaterThanOrEqual(4.5);
})->with([
    'slate-900 headings on white' => fn () => ['#0f172b', '#ffffff'],
    'slate-600 body copy on white' => fn () => [contrastSlate('600'), '#ffffff'],
    'slate-500 muted text on white' => fn () => [contrastSlate('500'), '#ffffff'],
    'slate-700 on a slate-50 band' => fn () => [contrastSlate('700'), contrastSlate('50')],
    'brand-700 eyebrow on white' => fn () => [contrastBrandToken('brand-700'), '#ffffff'],
    'brand-700 chip label on brand-50' => fn () => [contrastBrandToken('brand-700'), contrastBrandToken('brand-50')],
    'brand-800 badge text on brand-50' => fn () => [contrastBrandToken('brand-800'), contrastBrandToken('brand-50')],
    'white label on a brand-700 button' => fn () => ['#ffffff', contrastBrandToken('brand-700')],
    'white label on a hovered brand-800 button' => fn () => ['#ffffff', contrastBrandToken('brand-800')],
    'slate-900 label on an accent-500 featured badge' => fn () => [contrastSlate('900'), contrastBrandToken('accent-500')],
    'slate-900 label on an accent-400 badge' => fn () => [contrastSlate('900'), contrastBrandToken('accent-400')],
]);

test('body and muted text meet wcag aa in dark mode', function (string $foreground, string $background) {
    expect(contrastRatio($foreground, $background))->toBeGreaterThanOrEqual(4.5);
})->with([
    'slate-100 body copy on slate-900' => fn () => [contrastSlate('100'), contrastSlate('900')],
    'slate-300 muted text on slate-900' => fn () => [contrastSlate('300'), contrastSlate('900')],
    'white headings on slate-950' => fn () => ['#ffffff', contrastSlate('950')],
    'brand-300 links on slate-900' => fn () => [contrastBrandToken('brand-300'), contrastSlate('900')],
    'brand-400 links on slate-950' => fn () => [contrastBrandToken('brand-400'), contrastSlate('950')],
    'accent-400 badge text on slate-900' => fn () => [contrastBrandToken('accent-400'), contrastSlate('900')],
]);

test('form control borders and focus rings meet the 3 to 1 non-text minimum', function (string $label, string $foreground, string $background) {
    expect(contrastRatio($foreground, $background))->toBeGreaterThanOrEqual(3.0);
})->with([
    'light mode field border on white' => fn () => ['field border', contrastSlate('500'), '#ffffff'],
    'dark mode field border on slate-900' => fn () => ['field border', contrastSlate('400'), contrastSlate('900')],
    'brand-600 focus ring on white' => fn () => ['focus ring', contrastBrandToken('brand-600'), '#ffffff'],
    'brand-600 focus ring on slate-100' => fn () => ['focus ring', contrastBrandToken('brand-600'), contrastSlate('100')],
    'brand-300 focus ring on slate-900' => fn () => ['focus ring', contrastBrandToken('brand-300'), contrastSlate('900')],
    'brand-600 active nav border on brand-50' => fn () => ['nav border', contrastBrandToken('brand-600'), contrastBrandToken('brand-50')],
    'brand-600 active nav underline on white' => fn () => ['nav border', contrastBrandToken('brand-600'), '#ffffff'],
    'primary button boundary on white' => fn () => ['button fill', contrastBrandToken('brand-700'), '#ffffff'],
]);

test('the design tokens the ui depends on are all declared in the stylesheet', function (string $token) {
    expect(contrastBrandToken($token))->toBeString()->toMatch('/^#[0-9a-fA-F]{6}$/');
})->with([
    'brand-50', 'brand-100', 'brand-300', 'brand-400', 'brand-500',
    'brand-600', 'brand-700', 'brand-800', 'brand-900', 'brand-950',
    'accent-400', 'accent-500', 'accent-600',
]);
