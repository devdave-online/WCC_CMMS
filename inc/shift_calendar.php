<?php
/**
 * ShiftCalendar Engine
 * Calculates working minutes between timestamps, taking into account
 * shift hours, working days, and configured holidays.
 */
class ShiftCalendar {
    private string $shiftStart;
    private string $shiftEnd;
    private array  $workingDays;
    private array  $holidays;

    public function __construct(string $shiftStart, string $shiftEnd, array $workingDays, array $holidays = []) {
        $this->shiftStart  = $shiftStart;
        $this->shiftEnd    = $shiftEnd;
        $this->workingDays = $workingDays;
        $this->holidays    = $holidays; // Array of 'YYYY-MM-DD' strings
    }

    /**
     * Calculate working minutes between two timestamps.
     * Skips weekends/holidays and clamps to shift boundaries.
     * Handles multi-day spans correctly.
     */
    public function getWorkingMinutes(int $startStamp, int $endStamp): int {
        if ($endStamp <= $startStamp) return 0;

        $minutes = 0;
        $current = $startStamp;

        while ($current < $endStamp) {
            $dayOfWeek   = (int)date('N', $current);
            $currentDate = date('Y-m-d', $current);
            $shiftStart  = strtotime($currentDate . ' ' . $this->shiftStart);
            $shiftEnd    = strtotime($currentDate . ' ' . $this->shiftEnd);

            if (in_array($dayOfWeek, $this->workingDays) && !in_array($currentDate, $this->holidays)) {
                // Clamp current to shift start if before it
                $effectiveStart = max($current, $shiftStart);

                if ($effectiveStart < $shiftEnd) {
                    $effectiveEnd = min($endStamp, $shiftEnd);
                    if ($effectiveEnd > $effectiveStart) {
                        $minutes += (int)round(($effectiveEnd - $effectiveStart) / 60);
                    }
                }
                // Advance to next day's shift start
                $current = strtotime('+1 day', $shiftStart);
            } else {
                // Skip to next day's shift start
                $current = strtotime('+1 day', $shiftStart);
            }
        }
        return $minutes;
    }

    /**
     * Get the number of scheduled working minutes in a given date range.
     * Useful for calculating total calendar working time.
     */
    public function getScheduledMinutesInDateRange(string $startDate, string $endDate): int {
        $startStamp = strtotime($startDate . ' 00:00:00');
        $endStamp = strtotime($endDate . ' 23:59:59');
        $endStamp = min($endStamp, time()); // Never schedule future minutes beyond today

        if ($endStamp <= $startStamp) return 0;
        
        $minutes = 0;
        $d = $startStamp;
        $shiftLengthMins = (strtotime('1970-01-01 ' . $this->shiftEnd) - strtotime('1970-01-01 ' . $this->shiftStart)) / 60;
        
        while ($d <= $endStamp) {
            $dayOfWeek = (int)date('N', $d);
            $currentDate = date('Y-m-d', $d);
            
            if (in_array($dayOfWeek, $this->workingDays) && !in_array($currentDate, $this->holidays)) {
                // Check if today is the current day (partial day calculation)
                if ($currentDate == date('Y-m-d')) {
                    $shiftStartStamp = strtotime($currentDate . ' ' . $this->shiftStart);
                    $shiftEndStamp = strtotime($currentDate . ' ' . $this->shiftEnd);
                    
                    if (time() >= $shiftEndStamp) {
                        $minutes += $shiftLengthMins;
                    } elseif (time() > $shiftStartStamp) {
                        $minutes += (int)round((time() - $shiftStartStamp) / 60);
                    }
                } else {
                    $minutes += $shiftLengthMins;
                }
            }
            $d = strtotime('+1 day', $d);
        }
        return (int)$minutes;
    }

    /**
     * Merge overlapping time intervals (Gaps & Islands algorithm).
     * Input:  array of ['start' => int, 'end' => int]
     * Output: array of merged, non-overlapping intervals
     */
    public function mergeIntervals(array $intervals): array {
        if (empty($intervals)) return [];

        usort($intervals, fn($a, $b) => $a['start'] <=> $b['start']);
        $merged = [$intervals[0]];

        for ($i = 1; $i < count($intervals); $i++) {
            $last = count($merged) - 1;
            if ($intervals[$i]['start'] <= $merged[$last]['end']) {
                $merged[$last]['end'] = max($merged[$last]['end'], $intervals[$i]['end']);
            } else {
                $merged[] = $intervals[$i];
            }
        }
        return $merged;
    }
}
