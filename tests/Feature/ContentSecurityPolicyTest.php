<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ContentSecurityPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_scripts_with_the_request_nonce_can_run(): void
    {
        config(['app.debug' => false]);
        $user = User::factory()->create();

        foreach (['/', route('profile.edit')] as $url) {
            $response = $this->actingAs($user)->get($url)->assertOk();
            $policy = (string) $response->headers->get('Content-Security-Policy');

            $this->assertMatchesRegularExpression("/script-src 'self' 'nonce-([A-Za-z0-9]+)' 'unsafe-eval'/", $policy, $url);
            preg_match("/'nonce-([A-Za-z0-9]+)'/", $policy, $match);
            preg_match_all('/<script\b(?![^>]*type="application\/json")[^>]*>/', (string) $response->getContent(), $scripts);
            $this->assertNotEmpty($scripts[0], $url);
            foreach ($scripts[0] as $tag) {
                $this->assertStringContainsString('nonce="'.$match[1].'"', $tag, "{$url}: {$tag}");
            }
            $this->assertStringNotContainsString("'unsafe-inline'", $policy);
        }
    }

    public function test_each_request_gets_a_different_nonce(): void
    {
        config(['app.debug' => false]);
        $first = $this->get('/')->headers->get('Content-Security-Policy');
        $second = $this->get('/')->headers->get('Content-Security-Policy');

        $this->assertNotSame($first, $second);
    }

    public function test_views_have_no_inline_event_handlers_or_scripts_without_nonce(): void
    {
        foreach (File::allFiles(resource_path('views')) as $file) {
            if (str_contains($file->getPathname(), DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR)) {
                continue;
            }
            $html = $file->getContents();
            $this->assertDoesNotMatchRegularExpression('/\son[a-z]+="/', $html, $file->getRelativePathname().' tiene un manejador en línea (onclick, onsubmit…): el CSP lo bloquea.');
            $this->assertDoesNotMatchRegularExpression('/<script>/', $html, $file->getRelativePathname().' tiene un <script> sin nonce.');
        }
    }
}
