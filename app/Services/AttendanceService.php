<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\EmployeeShift;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AttendanceService
{
    private const LATE_GRACE_MINUTES = 0;
    private const ANOMALY_THRESHOLD_HOURS = 2;

    public function checkIn(User $user, Carbon $checkInTime): Attendance
    {
        $shift = $user->activeShift();
        $status = 'on_time';

        if ($shift) {
            $shiftStart = Carbon::parse($checkInTime->toDateString().' '.$shift->shift->start_time);
            if ($checkInTime->greaterThan($shiftStart->addMinutes(self::LATE_GRACE_MINUTES))) {
                $status = 'late';
            }
        }

        return Attendance::create([
            'user_id' => $user->id,
            'check_in' => $checkInTime,
            'status' => $status,
        ]);
    }

    public function checkOut(Attendance $attendance, Carbon $checkOutTime): Attendance
    {
        $overtimeMinutes = 0;
        $user = $attendance->user;
        $shift = $user->activeShift();

        if ($shift) {
            $shiftEnd = Carbon::parse($checkOutTime->toDateString().' '.$shift->shift->end_time);
            if ($checkOutTime->greaterThan($shiftEnd)) {
                $overtimeMinutes = (int) $checkOutTime->diffInMinutes($shiftEnd);
            }
        }

        $attendance->update([
            'check_out' => $checkOutTime,
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
        $diffHours = abs($checkInTime->diffInHours($shiftStart));

        return $diffHours > self::ANOMALY_THRESHOLD_HOURS;
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
        return User::with(['employeeShifts.shift', 'attendances' => function ($query) {
            $query->whereDate('check_in', today());
        }])
        ->whereHas('employeeShifts')
        ->get()
        ->filter(function (User $user) {
            $attendance = $user->attendances->first();
            if (!$attendance) {
                return false;
            }
            return $this->isAnomaly($user, Carbon::parse($attendance->check_in));
        });
    }

    public function getDashboard(): array
    {
        $today = today();

        return [
            'total_present' => Attendance::whereDate('check_in', $today)->count(),
            'total_on_time' => Attendance::whereDate('check_in', $today)->where('status', 'on_time')->count(),
            'total_late' => Attendance::whereDate('check_in', $today)->where('status', 'late')->count(),
            'total_absent' => $this->countAbsentToday(),
        ];
    }

    private function countAbsentToday(): int
    {
        $totalEmployees = User::where('role', 'employee')->count();
        $present = Attendance::whereDate('check_in', today())->distinct('user_id')->count('user_id');
        return max(0, $totalEmployees - $present);
    }
}
