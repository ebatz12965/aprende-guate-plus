<?php

namespace Tests\Feature;

use App\Models\MentorProfile;
use App\Models\MentorshipSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TimezoneTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_form_dates_are_interpreted_in_guatemala_time(): void
    {
        $this->assertSame('America/Guatemala', config('app.timezone'));
        $this->assertSame('2026-10-04T21:00:00+00:00', Carbon::parse('2026-10-04T15:00')->utc()->toIso8601String());
    }

    public function test_a_session_a_few_hours_ahead_in_local_time_is_accepted(): void
    {
        // 10:00 en Guatemala equivale a 16:00 UTC: con la app en UTC, pedir las 13:00 locales se rechazaba como "pasado".
        Carbon::setTestNow(Carbon::parse('2026-10-05 10:00', 'America/Guatemala'));
        $mentor = User::factory()->create();
        $mentor->assignRole(Role::findOrCreate('Instructor'));
        $profile = MentorProfile::create(['user_id' => $mentor->id]);

        $this->actingAs(User::factory()->create())
            ->post(route('adaptive.mentorship.request', $profile), ['topic' => 'Excel', 'preferred_at' => '2026-10-05T13:00'])
            ->assertSessionHasNoErrors();

        $this->assertSame('2026-10-05 13:00', MentorshipSession::firstOrFail()->preferred_at->format('Y-m-d H:i'));
    }

    public function test_migration_shifts_system_timestamps_but_not_dates_entered_by_people(): void
    {
        $student = User::factory()->create();
        $mentor = User::factory()->create();
        DB::table('users')->where('id', $student->id)->update(['created_at' => '2026-10-01 18:00:00']);
        $session = MentorshipSession::create(['student_id' => $student->id, 'mentor_id' => $mentor->id, 'topic' => 'Excel', 'status' => 'scheduled']);
        DB::table('mentorship_sessions')->where('id', $session->id)->update(['scheduled_at' => '2026-10-02 15:00:00', 'responded_at' => '2026-10-01 20:30:00']);

        $migration = require database_path('migrations/2026_10_04_030000_shift_stored_timestamps_from_utc_to_guatemala.php');
        $migration->up();

        $this->assertSame('2026-10-01 12:00:00', DB::table('users')->where('id', $student->id)->value('created_at'));
        $row = DB::table('mentorship_sessions')->where('id', $session->id)->first();
        $this->assertSame('2026-10-01 14:30:00', $row->responded_at);
        $this->assertSame('2026-10-02 15:00:00', $row->scheduled_at);

        $migration->down();
        $this->assertSame('2026-10-01 18:00:00', DB::table('users')->where('id', $student->id)->value('created_at'));
    }
}
