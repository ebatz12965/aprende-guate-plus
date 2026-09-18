<?php
namespace Database\Seeders;
use App\Models\Challenge;
use App\Models\MentorProfile;
use App\Models\User;
use Illuminate\Database\Seeder;
class AiPlatformSeeder extends Seeder {
    public function run(): void {
        User::role('Instructor')->each(fn (User $user) => MentorProfile::firstOrCreate(['user_id'=>$user->id], ['headline'=>'Mentor e instructor','specialties'=>'Orientación profesional, aprendizaje práctico']));
    }
}