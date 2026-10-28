<?php

namespace Tests\Unit;

use Carbon\Carbon;
use Illuminate\Console\Scheduling\CallbackEvent;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Console\Kernel as ConsoleKernelContract;
use Tests\TestCase;

/**
 * Schemat i app/Console/Kernel.php som ögonblicksbild (todo #15).
 *
 * Utskriften från schedule:list ser olika ut i Laravel 8–12, så den går
 * inte att diffa mellan versionerna. Här sparas i stället det som
 * avgör när och hur varje jobb körs: cron-uttryck, kommando eller namn,
 * lås, bakgrund och om tidsfiltren (between/unlessBetween) släpper
 * igenom jobbet mitt på dagen och mitt i natten.
 *
 * Avsiktlig ändring: kör med UPDATE_SNAPSHOTS=1 och granska diffen.
 */
class ScheduleTest extends TestCase
{
    public function test_schedule_snapshot()
    {
        $events = collect($this->scheduleAt('12:00'))->map(function ($event) {
            return [
                'expression' => $event->expression,
                'job' => $this->jobName($event),
                'withoutOverlapping' => $event->withoutOverlapping,
                'expiresAt' => $event->expiresAt,
                'runInBackground' => $event->runInBackground,
                'timezone' => $event->timezone,
                'evenInMaintenanceMode' => $event->evenInMaintenanceMode,
                'onOneServer' => $event->onOneServer,
                'environments' => $event->environments,
                // Låsnyckeln för withoutOverlapping. Ändras den mellan
                // versionerna ignoreras gamla lås en gång vid deployen.
                // Bara för call(): för command() ingår PHP-sökvägen, som
                // skiljer mellan miljöer.
                'mutex' => $event instanceof CallbackEvent ? $event->mutexName() : null,
            ];
        })->values()->all();

        // Tidsfiltren mitt på dagen och mitt i natten.
        foreach (['12:00', '03:00'] as $time) {
            foreach ($this->scheduleAt($time) as $i => $event) {
                $events[$i]['filtersPass' . str_replace(':', '', $time)] = $event->filtersPass($this->app);
            }
        }

        Carbon::setTestNow();

        $actual = json_encode($events, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
        $snapshotFile = __DIR__ . '/../TestPages/snapshots/schedule.json';

        if (getenv('UPDATE_SNAPSHOTS')) {
            file_put_contents($snapshotFile, $actual);
            $this->markTestIncomplete('Ögonblicksbilden skrevs om. Granska med git diff.');
        }

        $this->assertStringEqualsFile($snapshotFile, $actual);
    }

    /**
     * Schemat byggt på nytt med klockan satt till $time. I Laravel 8 läser
     * between()/unlessBetween() klockan när schemat definieras, inte när
     * filtret körs (i prod är varje schedule:run en ny process).
     */
    protected function scheduleAt(string $time): array
    {
        // Samma kärna och tidszon som schedule:run använder.
        $kernel = $this->app->make(ConsoleKernelContract::class);
        $timezone = $this->callProtected($kernel, 'scheduleTimezone');

        Carbon::setTestNow(Carbon::parse("2026-10-28 {$time}", $timezone));

        $schedule = new Schedule($timezone);
        $this->callProtected($kernel, 'schedule', $schedule);

        return $schedule->events();
    }

    protected function callProtected(object $object, string $method, ...$args)
    {
        $reflection = new \ReflectionMethod($object, $method);
        $reflection->setAccessible(true);

        return $reflection->invoke($object, ...$args);
    }

    /**
     * Kommandot utan PHP-sökvägen (den skiljer mellan miljöer), eller namnet
     * för call()-jobben.
     */
    protected function jobName($event): string
    {
        if ($event instanceof CallbackEvent) {
            return 'call: ' . $event->description;
        }

        return 'command: ' . preg_replace('/^.*?artisan[\'"]?\s+/', '', $event->command);
    }
}
