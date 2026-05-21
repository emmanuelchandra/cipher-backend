<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\CompanySetting;
use App\Models\Department;
use App\Models\EmployeeShift;
use App\Models\FaceDescriptor;
use App\Models\Shift;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AttendanceSeeder extends Seeder
{
    // Employees: [name, email, department, shift (morning|evening), absent_chance %]
    private array $employees = [
        ['Budi Santoso',    'budi@cipher.com',    'Engineering',  'morning', 5],
        ['Siti Rahayu',     'siti@cipher.com',    'Engineering',  'morning', 10],
        ['Andi Pratama',    'andi@cipher.com',    'Marketing',    'morning', 15],
        ['Dewi Lestari',    'dewi@cipher.com',    'Marketing',    'evening', 5],
        ['Rizky Firmansyah','rizky@cipher.com',   'Engineering',  'morning', 20],
        ['Nurul Hidayah',   'nurul@cipher.com',   'Finance',      'morning', 8],
        ['Fajar Setiawan',  'fajar@cipher.com',   'Finance',      'evening', 12],
        ['Mega Wulandari',  'mega@cipher.com',    'HR',           'morning', 5],
        ['Dimas Ardiansyah','dimas@cipher.com',   'Engineering',  'morning', 10],
        ['Fitri Anggraini', 'fitri@cipher.com',   'Marketing',    'evening', 7],
    ];

    public function run(): void
    {
        // ── Departments ──────────────────────────────────────────────────────
        $deptNames = collect($this->employees)->pluck(2)->unique();
        $departments = [];
        foreach ($deptNames as $name) {
            $departments[$name] = Department::firstOrCreate(['name' => $name]);
        }

        // Management dept for admin/HR (created in DatabaseSeeder — get or create)
        $mgmt = Department::firstOrCreate(['name' => 'Management']);

        // ── Admin & HR ───────────────────────────────────────────────────────
        $adminExists = User::where('email', 'admin@cipher.com')->exists();
        if (!$adminExists) {
            User::create([
                'name'          => 'Admin CIPHER',
                'email'         => 'admin@cipher.com',
                'password'      => Hash::make('password'),
                'role'          => 'admin',
                'department_id' => $mgmt->id,
            ]);
            User::create([
                'name'          => 'HR Manager',
                'email'         => 'hr@cipher.com',
                'password'      => Hash::make('password'),
                'role'          => 'hr',
                'department_id' => $mgmt->id,
            ]);
        }

        // ── Shifts ───────────────────────────────────────────────────────────
        $morning = Shift::firstOrCreate(
            ['name' => 'Morning'],
            ['start_time' => '08:00:00', 'end_time' => '17:00:00']
        );
        $evening = Shift::firstOrCreate(
            ['name' => 'Evening'],
            ['start_time' => '14:00:00', 'end_time' => '22:00:00']
        );

        // ── Company Settings ─────────────────────────────────────────────────
        CompanySetting::firstOrCreate(
            ['id' => 1],
            ['company_name' => 'CIPHER Corp', 'company_address' => '123 Main Street, Jakarta, Indonesia']
        );

        // ── Date range: last 30 working days (Mon–Sat) ───────────────────────
        $workingDays = $this->getWorkingDays(30);

        // ── Create employees + attendance ────────────────────────────────────
        foreach ($this->employees as [$name, $email, $deptName, $shiftKey, $absentChance]) {
            $shift = $shiftKey === 'morning' ? $morning : $evening;

            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name'          => $name,
                    'password'      => Hash::make('password'),
                    'role'          => 'employee',
                    'department_id' => $departments[$deptName]->id,
                ]
            );

            // Assign shift from the earliest working day
            EmployeeShift::firstOrCreate(
                ['user_id' => $user->id, 'shift_id' => $shift->id],
                ['effective_date' => $workingDays->last()->toDateString()]
            );

            // Fake face descriptor (128 random floats between -1 and 1)
            FaceDescriptor::firstOrCreate(
                ['user_id' => $user->id],
                ['descriptor' => [array_map(fn () => round((mt_rand(-1000, 1000) / 1000), 6), range(0, 127))]]
            );

            // Generate attendance for each working day
            foreach ($workingDays as $day) {
                // Skip if absent (random chance)
                if (mt_rand(1, 100) <= $absentChance) {
                    continue;
                }

                [$checkIn, $checkOut, $status, $overtime] = $this->generateRecord($day, $shift);

                Attendance::create([
                    'user_id'          => $user->id,
                    'check_in'         => $checkIn,
                    'check_out'        => $checkOut,
                    'status'           => $status,
                    'overtime_minutes' => $overtime,
                ]);
            }
        }
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Return the last $count working days (Mon–Sat) including today.
     *
     * @return Carbon[]
     */
    private function getWorkingDays(int $count): \Illuminate\Support\Collection
    {
        $days   = collect();
        $cursor = Carbon::today();

        while ($days->count() < $count) {
            // Skip Sunday only (Mon–Sat are working days in Indonesia)
            if ($cursor->dayOfWeek !== Carbon::SUNDAY) {
                $days->push($cursor->copy());
            }
            $cursor->subDay();
        }

        return $days->reverse()->values();
    }

    /**
     * Generate realistic check-in / check-out times with randomised behaviour.
     *
     * Probability breakdown:
     *  60% on-time   → 0–15 min early
     *  30% late      → 1–45 min late
     *  10% very late → 46–90 min late (still within anomaly window)
     *
     * @return array{Carbon, Carbon, string, int}
     */
    private function generateRecord(Carbon $day, Shift $shift): array
    {
        $shiftStart = Carbon::parse($day->toDateString() . ' ' . $shift->start_time);
        $shiftEnd   = Carbon::parse($day->toDateString() . ' ' . $shift->end_time);

        $roll = mt_rand(1, 100);

        if ($roll <= 60) {
            // On time: up to 15 min early
            $checkIn = $shiftStart->copy()->subMinutes(mt_rand(0, 15));
            $status  = 'on_time';
        } elseif ($roll <= 90) {
            // Late: 1–45 min
            $checkIn = $shiftStart->copy()->addMinutes(mt_rand(1, 45));
            $status  = 'late';
        } else {
            // Very late: 46–90 min (but < 2 hr anomaly threshold)
            $checkIn = $shiftStart->copy()->addMinutes(mt_rand(46, 90));
            $status  = 'late';
        }

        // Check-out: shift end ± 0–60 min
        $checkOutVariance = mt_rand(-10, 60);
        $checkOut         = $shiftEnd->copy()->addMinutes($checkOutVariance);

        // Cap check-out at now() if today (don't generate future timestamps)
        if ($day->isToday() && $checkOut->isFuture()) {
            $checkOut = Carbon::now();
        }

        // Overtime only when leaving after shift end
        $overtime = $checkOut->greaterThan($shiftEnd)
            ? (int) $checkOut->diffInMinutes($shiftEnd)
            : 0;

        return [$checkIn, $checkOut, $status, $overtime];
    }
}
