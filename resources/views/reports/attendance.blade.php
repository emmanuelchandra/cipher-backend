<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Attendance Report - {{ $monthName }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        h1 { font-size: 18px; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { border: 1px solid #ccc; padding: 6px 10px; text-align: left; }
        th { background: #f0f0f0; }
        .header { display: flex; justify-content: space-between; align-items: center; }
        .summary { margin-top: 16px; }
        .summary td { border: none; padding: 2px 8px; }
    </style>
</head>
<body>
    <div class="header">
        <div>
            @if($settings && $settings->company_stamp_path)
                <img src="{{ \Storage::disk('r2')->url($settings->company_stamp_path) }}" height="60" alt="Stamp">
            @endif
            <strong>{{ $settings->company_name ?? 'CIPHER Corp' }}</strong><br>
            {{ $settings->company_address ?? '' }}
        </div>
        <div>
            <h1>Attendance Report</h1>
            <p>{{ $monthName }}</p>
        </div>
    </div>

    <hr>

    <p><strong>Employee:</strong> {{ $employee->name }}</p>
    <p><strong>Department:</strong> {{ $employee->department->name ?? 'N/A' }}</p>

    <table class="summary">
        <tr><td><strong>Total Present:</strong></td><td>{{ $summary['total_present'] }}</td></tr>
        <tr><td><strong>Total Absent:</strong></td><td>{{ $summary['total_absent'] }}</td></tr>
        <tr><td><strong>On Time:</strong></td><td>{{ $summary['total_on_time'] }}</td></tr>
        <tr><td><strong>Late:</strong></td><td>{{ $summary['total_late'] }}</td></tr>
        <tr><td><strong>Total Overtime:</strong></td><td>{{ $summary['total_overtime_minutes'] }} minutes</td></tr>
    </table>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Date</th>
                <th>Check In</th>
                <th>Check Out</th>
                <th>Status</th>
                <th>Overtime (min)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($summary['attendances'] as $i => $att)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ \Carbon\Carbon::parse($att['check_in'])->format('d M Y') }}</td>
                <td>{{ \Carbon\Carbon::parse($att['check_in'])->format('H:i') }}</td>
                <td>{{ $att['check_out'] ? \Carbon\Carbon::parse($att['check_out'])->format('H:i') : '-' }}</td>
                <td>{{ ucfirst(str_replace('_', ' ', $att['status'])) }}</td>
                <td>{{ $att['overtime_minutes'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    @if($employee->signature_path)
    <div style="margin-top: 40px;">
        <p>Employee Signature:</p>
        <img src="{{ \Storage::disk('r2')->url($employee->signature_path) }}" height="60" alt="Signature">
    </div>
    @endif
</body>
</html>
