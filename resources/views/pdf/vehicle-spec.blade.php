<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $vehicle->title ?? trim(($vehicle->make?->name ?? '').' '.($vehicle->model?->name ?? '').' '.($vehicle->year ?? '')) }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1f2937; margin: 0; }
        .sheet { padding: 28px; }
        .brand { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 3px solid #0f766e; padding-bottom: 14px; margin-bottom: 18px; }
        .brand h1 { font-size: 22px; margin: 0; color: #0f766e; letter-spacing: .5px; }
        .brand .meta { text-align: right; font-size: 10px; color: #6b7280; line-height: 1.6; }
        h2 { font-size: 13px; margin: 22px 0 8px; color: #0f766e; text-transform: uppercase; letter-spacing: .8px; border-bottom: 1px solid #e5e7eb; padding-bottom: 4px; }
        .headline { font-size: 18px; font-weight: bold; margin: 0 0 4px; }
        .price { font-size: 20px; font-weight: bold; color: #0f766e; }
        .muted { color: #6b7280; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 4px; }
        td { padding: 5px 8px; border-bottom: 1px solid #e5e7eb; vertical-align: top; }
        td.label { width: 34%; color: #6b7280; }
        td.value { font-weight: bold; }
        .two-col { display: flex; gap: 24px; }
        .two-col > div { flex: 1; }
        .features { margin: 0; padding-left: 16px; }
        .features li { margin-bottom: 2px; }
        .description { line-height: 1.6; white-space: pre-wrap; }
        .foot { margin-top: 26px; padding-top: 10px; border-top: 1px solid #e5e7eb; font-size: 9px; color: #9ca3af; text-align: center; }
    </style>
</head>
<body>
<div class="sheet">
    <div class="brand">
        <div>
            <h1>MONARALK</h1>
            <div class="muted">Vehicle Specification Sheet</div>
        </div>
        <div class="meta">
            {{ $vehicle->slug }}<br>
            Generated {{ now()->format('d M Y H:i') }}<br>
            {{ strtoupper($vehicle->status->label()) }} &middot; {{ $vehicle->condition->label() }}
        </div>
    </div>

    <p class="headline">{{ trim(($vehicle->make?->name ?? '').' '.($vehicle->model?->name ?? '').' '.($vehicle->trim ?? '')) }}</p>
    <p class="price">LKR {{ number_format((float) $vehicle->price, 2) }}</p>

    <h2>Identity</h2>
    <table>
        <tr><td class="label">Make</td><td class="value">{{ $vehicle->make?->name }}</td></tr>
        <tr><td class="label">Model</td><td class="value">{{ $vehicle->model?->name }}</td></tr>
        <tr><td class="label">Trim</td><td class="value">{{ $vehicle->trim ?? '—' }}</td></tr>
        <tr><td class="label">Body type</td><td class="value">{{ $vehicle->bodyType?->name ?? '—' }}</td></tr>
        <tr><td class="label">Year</td><td class="value">{{ $vehicle->year }}</td></tr>
        <tr><td class="label">Location</td><td class="value">{{ $vehicle->location ?? '—' }}</td></tr>
    </table>

    <div class="two-col">
        <div>
            <h2>Specifications</h2>
            <table>
                <tr><td class="label">Mileage</td><td class="value">{{ number_format($vehicle->mileage_km) }} km</td></tr>
                <tr><td class="label">Fuel</td><td class="value">{{ $vehicle->fuelType?->name ?? '—' }}</td></tr>
                <tr><td class="label">Transmission</td><td class="value">{{ $vehicle->transmission?->name ?? '—' }}</td></tr>
                <tr><td class="label">Exterior colour</td><td class="value">{{ $vehicle->exteriorColor?->name ?? '—' }}</td></tr>
                <tr><td class="label">Interior colour</td><td class="value">{{ $vehicle->interiorColor?->name ?? '—' }}</td></tr>
            </table>
        </div>
        <div>
            <h2>Provenance</h2>
            <table>
                <tr><td class="label">VIN</td><td class="value">{{ $vehicle->vin ?? '—' }}</td></tr>
                <tr><td class="label">Registration</td><td class="value">{{ $vehicle->registration_number ?? '—' }}</td></tr>
                <tr><td class="label">Owners</td><td class="value">{{ $vehicle->owners_count ?? '—' }}</td></tr>
                <tr><td class="label">Accident history</td><td class="value">{{ $vehicle->accident_history ?? '—' }}</td></tr>
                <tr><td class="label">Warranty</td><td class="value">{{ $vehicle->warranty ?? '—' }}</td></tr>
            </table>
        </div>
    </div>

    @if ($vehicle->features->isNotEmpty())
        <h2>Features</h2>
        <ul class="features">
            @foreach ($vehicle->features as $feature)
                <li>{{ $feature->name }}</li>
            @endforeach
        </ul>
    @endif

    @if (filled($vehicle->description))
        <h2>Description</h2>
        <p class="description">{{ $vehicle->description }}</p>
    @endif

    <div class="foot">
        Monaralk &middot; Specification sheet is generated from the live listing and may change without notice.
    </div>
</div>
</body>
</html>
