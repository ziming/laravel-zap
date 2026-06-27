<?php

/**
 * Tests for Issue #76: Biweekly forDate() ignores week parity, returning every matching weekday
 * instead of every other one relative to the schedule's start date.
 *
 * Scenario from the issue: Sundays only, starting June 23, 2026.
 * Expected occurrences: June 28, July 12, July 26 (every other Sunday).
 * Bug produced:          July 5, July 19 (wrong — off by one week).
 *
 * @see https://github.com/ludoguenet/laravel-zap/issues/76
 */

use Zap\Facades\Zap;
use Zap\Models\Schedule;

describe('Issue #76 — Biweekly forDate() must respect week parity', function () {

    it('returns the first biweekly Sunday (June 28) that falls in the anchor week', function () {
        $user = createUser();

        Zap::for($user)
            ->availability()
            ->biweekly(['sunday'])
            ->from('2026-06-23')
            ->addPeriod('09:00', '17:00')
            ->save();

        $schedules = Schedule::forDate('2026-06-28')->get();

        expect($schedules)->toHaveCount(1);
    });

    it('does NOT return the following Sunday (July 5) which is an off-week', function () {
        $user = createUser();

        Zap::for($user)
            ->availability()
            ->biweekly(['sunday'])
            ->from('2026-06-23')
            ->addPeriod('09:00', '17:00')
            ->save();

        $schedules = Schedule::forDate('2026-07-05')->get();

        expect($schedules)->toBeEmpty('July 5 is an off-week Sunday and must not appear');
    });

    it('returns the second biweekly Sunday (July 12)', function () {
        $user = createUser();

        Zap::for($user)
            ->availability()
            ->biweekly(['sunday'])
            ->from('2026-06-23')
            ->addPeriod('09:00', '17:00')
            ->save();

        $schedules = Schedule::forDate('2026-07-12')->get();

        expect($schedules)->toHaveCount(1);
    });

    it('does NOT return July 19 (off-week)', function () {
        $user = createUser();

        Zap::for($user)
            ->availability()
            ->biweekly(['sunday'])
            ->from('2026-06-23')
            ->addPeriod('09:00', '17:00')
            ->save();

        $schedules = Schedule::forDate('2026-07-19')->get();

        expect($schedules)->toBeEmpty('July 19 is an off-week Sunday and must not appear');
    });

    it('returns the third biweekly Sunday (July 26)', function () {
        $user = createUser();

        Zap::for($user)
            ->availability()
            ->biweekly(['sunday'])
            ->from('2026-06-23')
            ->addPeriod('09:00', '17:00')
            ->save();

        $schedules = Schedule::forDate('2026-07-26')->get();

        expect($schedules)->toHaveCount(1);
    });

    it('correctly alternates across a six-week window', function () {
        $user = createUser();

        Zap::for($user)
            ->availability()
            ->biweekly(['sunday'])
            ->from('2026-06-23')
            ->to('2026-08-31')
            ->addPeriod('09:00', '17:00')
            ->save();

        $shouldMatch = ['2026-06-28', '2026-07-12', '2026-07-26', '2026-08-09', '2026-08-23'];
        $shouldNotMatch = ['2026-07-05', '2026-07-19', '2026-08-02', '2026-08-16', '2026-08-30'];

        foreach ($shouldMatch as $date) {
            expect(Schedule::forDate($date)->count())->toBe(1, "Expected biweekly Sunday on {$date}");
        }

        foreach ($shouldNotMatch as $date) {
            expect(Schedule::forDate($date)->count())->toBe(0, "Expected no biweekly Sunday on off-week {$date}");
        }
    });

});
