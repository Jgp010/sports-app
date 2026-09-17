<?php

namespace Tests\Feature;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use RuntimeException;
use Tests\TestCase;

class ApiErrorResponseTest extends TestCase
{
    public function test_api_rate_limit_returns_a_fixed_chinese_error(): void
    {
        for ($attempt = 1; $attempt <= 60; $attempt++) {
            $this->getJson('/api/v1/health')->assertOk();
        }

        $this->getJson('/api/v1/health')
            ->assertTooManyRequests()
            ->assertJsonPath('error.code', 'TOO_MANY_REQUESTS')
            ->assertJsonPath('error.message', '請求次數過多，請稍後再試。');
    }

    public function test_api_validation_error_does_not_expose_backend_exception_text(): void
    {
        Route::post('/api/test-validation-error', function (Request $request) {
            $request->validate(['name' => ['required', 'string']]);
        });

        $this->postJson('/api/test-validation-error')
            ->assertUnprocessable()
            ->assertJsonPath('error.code', 'VALIDATION_ERROR')
            ->assertJsonPath('error.message', '輸入資料驗證失敗。')
            ->assertJsonStructure(['error' => ['details' => ['name']]]);
    }

    public function test_unexpected_api_exception_is_replaced_with_a_safe_message(): void
    {
        Route::get('/api/test-internal-error', function () {
            throw new RuntimeException('SQLSTATE backend secret /var/www/app.php:123');
        });

        $response = $this->getJson('/api/test-internal-error')
            ->assertInternalServerError()
            ->assertJsonPath('error.code', 'SERVER_ERROR')
            ->assertJsonPath('error.message', '系統忙碌中，請稍後再試。');

        $this->assertStringNotContainsString('SQLSTATE', $response->getContent());
        $this->assertStringNotContainsString('app.php', $response->getContent());
    }

    public function test_expected_api_http_error_keeps_its_safe_message(): void
    {
        Route::get('/api/test-expected-error', function () {
            abort(422, '此報名無法取消。');
        });

        $this->getJson('/api/test-expected-error')
            ->assertUnprocessable()
            ->assertJsonPath('error.code', 'UNPROCESSABLE_CONTENT')
            ->assertJsonPath('error.message', '此報名無法取消。');
    }

    public function test_unknown_api_route_returns_a_safe_not_found_message(): void
    {
        $this->getJson('/api/route-that-does-not-exist')
            ->assertNotFound()
            ->assertJsonPath('error.code', 'RESOURCE_NOT_FOUND')
            ->assertJsonPath('error.message', '查無資料。');
    }
}
