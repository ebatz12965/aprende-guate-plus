<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    /** Los CV pasan del disco público (accesible por URL) al privado; solo su dueño los descarga. */
    public function up(): void
    {
        DB::table('student_profiles')->whereNotNull('cv_path')->orderBy('id')->each(function ($profile) {
            if (! Storage::disk('public')->exists($profile->cv_path)) {
                return;
            }
            $target = 'cvs/'.$profile->user_id.'/'.basename($profile->cv_path);
            Storage::disk('local')->put($target, Storage::disk('public')->get($profile->cv_path));
            Storage::disk('public')->delete($profile->cv_path);
            DB::table('student_profiles')->where('id', $profile->id)->update(['cv_path' => $target]);
        });
    }

    public function down(): void
    {
        DB::table('student_profiles')->whereNotNull('cv_path')->orderBy('id')->each(function ($profile) {
            if (! Storage::disk('local')->exists($profile->cv_path)) {
                return;
            }
            $target = 'cvs/'.basename($profile->cv_path);
            Storage::disk('public')->put($target, Storage::disk('local')->get($profile->cv_path));
            Storage::disk('local')->delete($profile->cv_path);
            DB::table('student_profiles')->where('id', $profile->id)->update(['cv_path' => $target]);
        });
    }
};
