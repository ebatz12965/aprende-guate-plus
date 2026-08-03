<?php

namespace App\Jobs;

use App\Models\SchoolClass;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;
use Throwable;

class ProcessVideoJob implements ShouldQueue
{
    use Queueable;

    public $timeout = 7200;

    public function __construct(public int $classId)
    {
    }

    public function handle(): void
    {
        $class = SchoolClass::find($this->classId);

        if (! $class || ! $class->source_video_path || ! Storage::disk('courses')->exists($class->source_video_path)) {
            return;
        }

        $disk = Storage::disk('courses');
        $outputDirectory = "hls/classes/{$class->id}";
        $disk->deleteDirectory($outputDirectory);
        $disk->makeDirectory($outputDirectory);
        $class->update(['video_status' => 'processing', 'video_progress' => 5, 'video_error' => null, 'hls_path' => null]);

        try {
            $input = $disk->path($class->source_video_path);
            $output = $disk->path($outputDirectory);
            $variants = [];

            foreach (config('video.qualities') as $index => $quality) {
                $playlist = "{$quality['name']}.m3u8";
                $process = new Process([
                    config('video.ffmpeg_binary'), '-y', '-i', $input,
                    '-map', '0:v:0', '-map', '0:a?',
                    '-vf', "scale=-2:{$quality['height']}",
                    '-c:v', 'libx264', '-preset', 'veryfast', '-b:v', $quality['video_bitrate'],
                    '-c:a', 'aac', '-b:a', $quality['audio_bitrate'],
                    '-hls_time', '6', '-hls_playlist_type', 'vod',
                    '-hls_segment_filename', "{$output}/{$quality['name']}_%03d.ts",
                    "{$output}/{$playlist}",
                ]);
                $process->setTimeout(7100);
                $process->mustRun();
                $variants[] = ['playlist' => $playlist, 'bandwidth' => (int) rtrim($quality['video_bitrate'], 'k') * 1000, 'height' => $quality['height']];
                $class->update(['video_progress' => [35, 65, 95][$index] ?? 95]);
            }

            $master = "#EXTM3U\n#EXT-X-VERSION:3\n";
            foreach ($variants as $variant) {
                $width = (int) round($variant['height'] * (16 / 9));
                $master .= "#EXT-X-STREAM-INF:BANDWIDTH={$variant['bandwidth']},RESOLUTION={$width}x{$variant['height']}\n{$variant['playlist']}\n";
            }
            $disk->put("{$outputDirectory}/master.m3u8", $master);
            $disk->delete($class->source_video_path);
            $class->update(['source_video_path' => null, 'hls_path' => "{$outputDirectory}/master.m3u8", 'video_status' => 'ready', 'video_progress' => 100]);
        } catch (Throwable $exception) {
            report($exception);
            $class->update(['video_status' => 'failed', 'video_error' => 'No se pudo procesar el video. Verifica FFmpeg y revisa el registro del sistema.']);
        }
    }
}
