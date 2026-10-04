<?php

namespace Tests\Feature;

use App\Models\AiTokenUsage;
use App\Models\PlatformSetting;
use App\Services\AiTutorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiGatewayBudgetTest extends TestCase
{
    use RefreshDatabase;

    public function test_gateway_blocks_provider_calls_once_the_token_budget_is_exhausted(): void
    {
        PlatformSetting::setInteger('ai_token_budget', 1000);
        AiTokenUsage::create(['operation' => 'tutor_reply', 'total_tokens' => 1000, 'successful' => true]);
        Http::fake(['*' => Http::response(['choices' => [['message' => ['content' => 'Respuesta del modelo.']]]])]);

        $reply = app(AiTutorService::class)->reply([['role' => 'user', 'content' => 'Hola']], 'Estudiante');

        Http::assertNothingSent();
        $this->assertStringContainsString('modo demostración', $reply);
        $this->assertDatabaseHas('ai_requests', ['status' => 'failed', 'failure_reason' => 'Se alcanzó el presupuesto de tokens configurado.']);
    }

    public function test_gateway_calls_the_provider_while_budget_remains_or_is_unlimited(): void
    {
        Http::fake(['*' => Http::response(['choices' => [['message' => ['content' => 'Respuesta del modelo.']]]])]);
        AiTokenUsage::create(['operation' => 'tutor_reply', 'total_tokens' => 5000, 'successful' => true]);

        $this->assertSame('Respuesta del modelo.', app(AiTutorService::class)->reply([['role' => 'user', 'content' => 'Hola']], 'Estudiante'));

        PlatformSetting::setInteger('ai_token_budget', 10000);
        $this->assertSame('Respuesta del modelo.', app(AiTutorService::class)->reply([['role' => 'user', 'content' => 'Hola']], 'Estudiante'));
        Http::assertSentCount(2);
    }
}
