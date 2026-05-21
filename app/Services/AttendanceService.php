<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

class AttendanceService
{
    private const LATE_GRACE_MINUTES   = 0;
    private const ANOMALY_THRESHOLD_HOURS = 2;

    public function checkIn(User $user, Carbon $checkInTime): Attendance
    {
        $shift  = $user->activeShift();
        $status = 'on_time';

        if ($shift) {
            $shiftStart = Carbon::parse($checkInTime->toDateString().' '.$shift->shift->start_time);
            if ($checkInTime->greaterThan($shiftStart->addMinutes(self::LATE_GRACE_MINUTES))) {
                $status = 'late';
            }
        }

        return Attendance::create([
            'user_id'  => $user->id,
            'check_in' => $checkInTime,
            'status'   => $status,
        ]);
    }

    public function checkOut(Attendance $attendance, Carbon $checkOutTime): Attendance
    {
        $overtimeMinutes = 0;
        $shift = $attendance->user->activeShift();

        if ($shift) {
            $shiftEnd = Carbon::parse($checkOutTime->toDateString().' '.$shift->shift->end_time);
            if ($checkOutTime->greaterThan($shiftEnd)) {
                $overtimeMinutes = (int) $checkOutTime->diffInMinutes($shiftEnd);
            }
        }

        $attendance->update([
            'check_out'        => $checkOutTime,
            'overtime_minutes' => $overtimeMinutes,
        ]);

        return $attendance->fresh();
    }

    public function isAnomaly(User $user, Carbon $checkInTime): bool
    {
        $shift = $user->activeShift();
        if (!$shift) {
            return false;
        }

        $shiftStart = Carbon::parse($checkInTime->toDateString().' '.$shift->shift->start_time);

        return abs($checkInTime->diffInHours($shiftStart)) > self::ANOMALY_THRESHOLD_HOURS;
    }

    public function getLateToday(): Collection
    {
        return Attendance::with('user.department')
            ->whereDate('check_in', today())
            ->where('status', 'late')
            ->get();
    }

    public function getAnomalies(): Collection
    {
        return User::with(['employeeShifts.shift', 'attendances' => function ($q) {
            $q->whereDate('check_in', today());
        }])
        ->whereHas('employeeShifts')
        ->get()
        ->filter(function (User $user) {
            $attendance = $user->attendances->first();
            return $attendance && $this->isAnomaly($user, Carbon::parse($attendance->check_in));
        });
    }

    public function getDashboard(): array
    {
        $today          = today();
        $totalEmployees = User::where('role', 'employee')->count();
        $present        = Attendance::whereDate('check_in', $today)->distinct('user_id')->count('user_id');
        $late           = Attendance::whereDate('check_in', $today)->where('status', 'late')->count();
        $onTime         = Attendance::whereDate('check_in', $today)->where('status', 'on_time')->count();

        return [
            'total_employees' => $totalEmployees,
            'total_present'   => $present,
            'total_on_time'   => $onTime,
            'total_late'      => $late,
            'total_absent'    => max(0, $totalEmployees - $present),
            'weekly'          => $this->getWeeklyData(),
        ];
    }

    /**
     * Returns daily attendance counts for the current week (Mon–Sun).
     */
    private function getWeeklyData(): array
    {
        $startOfWeek = today()->startOfWeek(Carbon::MONDAY);
        $endOfWeek   = today()->endOfWeek(Carbon::SUNDAY);

        // Fetch all this-week attendances in one query
        $rows = Attendance::selectRaw('DATE(check_in) as date, status, COUNT(*) as count')
            ->whereBetween('check_in', [$startOfWeek->startOfDay(), $endOfWeek->endOfDay()])
            ->groupByRaw('DATE(check_in), status')
            ->get()
            ->groupBy('date');

        $weekly = [];
        $period = CarbonPeriod::create($startOfWeek, $endOfWeek);

        foreach ($period as $day) {
            $dateKey    = $day->toDateString();
            $dayRecords = $rows->get($dateKey, collect());

            $weekly[] = [
                'date'    => $dateKey,
                'day'     => $day->format('D'),   // Mon, Tue, …
                'present' => (int) $dayRecords->sum('count'),
                'on_time' => (int) $dayRecords->where('status', 'on_time')->sum('count'),
                'late'    => (int) $dayRecords->where('status', 'late')->sum('count'),
            ];
        }

        return $weekly;
    }
}
