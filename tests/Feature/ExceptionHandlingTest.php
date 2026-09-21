<?php

namespace Tests\Feature;

use App\Constants\AuthorizationMessage;
use App\Constants\ErrorMessage;
use App\Constants\FlashDataVariable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Tests\TestCase;

class ExceptionHandlingTest extends TestCase
{
    public function test_web_page_not_found_redirects_back_with_flash_message(): void
    {
        $appHost = config('domain.app', 'app.sollu.test');

        $response = $this->withServerVariables(['HTTP_HOST' => $appHost])
            ->from("http://{$appHost}/login")
            ->get("http://{$appHost}/this-page-definitely-does-not-exist-12345");

        $response->assertRedirect("http://{$appHost}/login");
        $response->assertSessionHas(FlashDataVariable::FAILED->value, ErrorMessage::PAGE_NOT_FOUND);
    }

    public function test_api_page_not_found_returns_json_error(): void
    {
        $apiHost = config('domain.api', 'api.sollu.test');

        $response = $this->withServerVariables(['HTTP_HOST' => $apiHost])
            ->get("http://{$apiHost}/this-endpoint-does-not-exist");

        $response->assertStatus(404);
        $response->assertJson([
            'message' => ErrorMessage::PAGE_NOT_FOUND,
        ]);
    }

    public function test_web_access_denied_redirects_back_with_indonesian_message(): void
    {
        $appHost = config('domain.app', 'app.sollu.test');

        Route::middleware('web')->get('/test-forbidden-web', function () {
            throw new AccessDeniedHttpException('This action is unauthorized.');
        });

        $response = $this->withServerVariables(['HTTP_HOST' => $appHost])
            ->from("http://{$appHost}/login")
            ->get("http://{$appHost}/test-forbidden-web");

        $response->assertRedirect("http://{$appHost}/login");
        $response->assertSessionHas(FlashDataVariable::FAILED->value, AuthorizationMessage::CANT_ACCESS_PAGE);
    }

    public function test_api_access_denied_returns_json_with_indonesian_message(): void
    {
        $apiHost = config('domain.api', 'api.sollu.test');

        Route::middleware('api')->get('/test-forbidden-api', function () {
            throw new AccessDeniedHttpException('This action is unauthorized.');
        });

        $response = $this->withServerVariables(['HTTP_HOST' => $apiHost])
            ->get("http://{$apiHost}/test-forbidden-api");

        $response->assertStatus(403);
        $response->assertJson([
            'message' => AuthorizationMessage::CANT_ACCESS_PAGE,
        ]);
    }

    public function test_web_database_error_redirects_back_with_flash_message(): void
    {
        $appHost = config('domain.app', 'app.sollu.test');

        Route::middleware('web')->get('/test-db-error-web', function () {
            throw new QueryException('test_connection', 'SELECT * FROM non_existing_table', [], new \Exception('Table not found'));
        });

        $response = $this->withServerVariables(['HTTP_HOST' => $appHost])
            ->from("http://{$appHost}/login")
            ->get("http://{$appHost}/test-db-error-web");

        $response->assertRedirect("http://{$appHost}/login");
        $response->assertSessionHas(FlashDataVariable::FAILED->value, ErrorMessage::DATABASE_ERROR);
    }

    public function test_api_database_error_returns_json_server_error(): void
    {
        $apiHost = config('domain.api', 'api.sollu.test');

        Route::middleware('api')->get('/test-db-error-api', function () {
            throw new QueryException('test_connection', 'SELECT * FROM non_existing_table', [], new \Exception('Table not found'));
        });

        $response = $this->withServerVariables(['HTTP_HOST' => $apiHost])
            ->get("http://{$apiHost}/test-db-error-api");

        $response->assertStatus(500);
        $response->assertJson([
            'message' => ErrorMessage::DATABASE_ERROR,
        ]);
    }

    public function test_web_model_not_found_redirects_back_with_flash_message(): void
    {
        $appHost = config('domain.app', 'app.sollu.test');

        Route::middleware('web')->get('/test-model-not-found-web', function () {
            throw new ModelNotFoundException('No query results for model [App\Models\User].');
        });

        $response = $this->withServerVariables(['HTTP_HOST' => $appHost])
            ->from("http://{$appHost}/login")
            ->get("http://{$appHost}/test-model-not-found-web");

        $response->assertRedirect("http://{$appHost}/login");
        $response->assertSessionHas(FlashDataVariable::FAILED->value, ErrorMessage::DATA_NOT_FOUND);
    }

    public function test_api_model_not_found_returns_json_not_found(): void
    {
        $apiHost = config('domain.api', 'api.sollu.test');

        Route::middleware('api')->get('/test-model-not-found-api', function () {
            throw new ModelNotFoundException('No query results for model [App\Models\User].');
        });

        $response = $this->withServerVariables(['HTTP_HOST' => $apiHost])
            ->get("http://{$apiHost}/test-model-not-found-api");

        $response->assertStatus(404);
        $response->assertJson([
            'message' => ErrorMessage::DATA_NOT_FOUND,
        ]);
    }

    public function test_web_throttle_on_guest_login_converts_to_validation_exception(): void
    {
        $appHost = config('domain.app', 'app.sollu.test');

        Route::getRoutes()->getByName('login.attempt')?->setAction([
            'middleware' => ['web'],
            'uses' => fn () => throw new ThrottleRequestsException('Too Many Attempts.'),
        ]);

        $response = $this->withServerVariables(['HTTP_HOST' => $appHost])
            ->from("http://{$appHost}/login")
            ->post("http://{$appHost}/login");

        $response->assertRedirect("http://{$appHost}/login");
        $response->assertSessionHasErrors([
            'email' => ErrorMessage::TOO_MANY_REQUESTS,
        ]);
    }

    public function test_api_throttle_returns_json_too_many_requests(): void
    {
        $apiHost = config('domain.api', 'api.sollu.test');

        Route::middleware('api')->get('/test-throttle-api', function () {
            throw new ThrottleRequestsException('Too Many Attempts.');
        });

        $response = $this->withServerVariables(['HTTP_HOST' => $apiHost])
            ->get("http://{$apiHost}/test-throttle-api");

        $response->assertStatus(429);
        $response->assertJson([
            'message' => ErrorMessage::TOO_MANY_REQUESTS,
        ]);
    }

    public function test_web_unprocessable_entity_redirects_back_with_flash_message(): void
    {
        $appHost = config('domain.app', 'app.sollu.test');

        Route::middleware('web')->get('/test-unprocessable-web', function () {
            abort(422, 'Penerimaan barang ini memiliki riwayat retur aktif.');
        });

        $response = $this->withServerVariables(['HTTP_HOST' => $appHost])
            ->from("http://{$appHost}/login")
            ->get("http://{$appHost}/test-unprocessable-web");

        $response->assertRedirect("http://{$appHost}/login");
        $response->assertSessionHas(FlashDataVariable::FAILED->value, 'Penerimaan barang ini memiliki riwayat retur aktif.');
    }

    public function test_api_unprocessable_entity_returns_json_error(): void
    {
        $apiHost = config('domain.api', 'api.sollu.test');

        Route::middleware('api')->get('/test-unprocessable-api', function () {
            abort(422, 'Penerimaan barang ini memiliki riwayat retur aktif.');
        });

        $response = $this->withServerVariables(['HTTP_HOST' => $apiHost])
            ->get("http://{$apiHost}/test-unprocessable-api");

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'Penerimaan barang ini memiliki riwayat retur aktif.',
        ]);
    }
}
