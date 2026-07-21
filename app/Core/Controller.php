<?php

declare(strict_types=1);

namespace App\Core;

use App\Services\AssetService;
use App\Services\AuthService;
use App\Services\ViewService;
use RuntimeException;
use Throwable;

abstract class Controller
{
    protected string $assetBaseUrl;
    protected ViewService $view;

    public function __construct()
    {
        $this->assetBaseUrl = AssetService::baseUrl();
        $this->view = new ViewService(VIEW_PATH);
    }

    protected function render(string $view, array $data = []): void
    {
        $this->view->render($view, array_merge([
            'assetBaseUrl' => $this->assetBaseUrl,
        ], $data));
    }
    /** Execute a write operation with one response contract for AJAX and HTML. */
    protected function mutationResponse(Request $request, callable $operation, string $successMessage, string $successUrl, string $fallbackUrl, string $flashKey = 'error'): void
    {
        $response = new Response();
        if (!(new AuthService())->validateCsrf($request->csrfToken())) {
            $message = 'Your session expired. Refresh the page and try again.';
            if ($request->expectsJson()) {
                $response->error($message, [], 419);
            }
            Session::flash($flashKey, $message);
            $response->redirect($fallbackUrl);
        }

        try {
            $result = $operation();
            $data = is_array($result) ? $result : [];
            $resolvedSuccessUrl = (string) ($data['_redirect'] ?? $successUrl);
            $resolvedSuccessMessage = (string) ($data['_message'] ?? $successMessage);
            $notificationType = (string) ($data['_notification'] ?? 'success');
            unset($data['_redirect']);
            unset($data['_message']);
            unset($data['_notification']);
            if ($request->expectsJson()) {
                $response->success($resolvedSuccessMessage, $data, 200, ['redirect' => $resolvedSuccessUrl, 'notification' => $notificationType]);
            }
            Session::flash(str_replace('_error', '_success', $flashKey), $resolvedSuccessMessage);
            $response->redirect($resolvedSuccessUrl);
        } catch (ValidationException $exception) {
            if ($request->expectsJson()) { $response->error($exception->getMessage(), $exception->errors(), 422); }
            Session::flash($flashKey, $exception->getMessage());
            $response->redirect($fallbackUrl);
        } catch (ServiceException|RuntimeException $exception) {
            if ($request->expectsJson()) { $response->error($exception->getMessage(), [], 422); }
            Session::flash($flashKey, $exception->getMessage());
            $response->redirect($fallbackUrl);
        } catch (Throwable $exception) {
            error_log(sprintf('%s: %s in %s:%d', static::class, $exception->getMessage(), $exception->getFile(), $exception->getLine()));
            $message = 'Something went wrong. Please try again later.';
            if ($request->expectsJson()) { $response->error($message, [], 500); }
            Session::flash($flashKey, $message);
            $response->redirect($fallbackUrl);
        }
    }

}