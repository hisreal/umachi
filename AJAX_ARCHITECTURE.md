# Shared AJAX CRUD architecture

All new CRUD modules must use `public/assets/js/ajax.js` and the controller `mutationResponse()` method. Do not add module-specific `fetch`, notification, validation-error, loading-button, or CSRF implementations.

## Response contract

Every AJAX endpoint returns JSON with the same keys:

```json
{
  "success": true,
  "message": "Record saved successfully.",
  "data": {},
  "errors": {},
  "meta": { "redirect": "/index.php?route=module" }
}
```

Use HTTP 200/201 for success, 422 for validation or safe service errors, 419 for CSRF expiry, 403 for authorization, 404 for missing records, and 500 for unexpected errors.

## New form

Add `data-ajax-form` to existing markup. The global listener submits `FormData`, sends both `X-Requested-With` and `X-CSRF-TOKEN`, renders keyed errors beside matching field names, displays the shared notification, and manages the submit button.

```html
<form method="post" action="..." data-ajax-form data-ajax-loading-text="Saving...">
```

For an inline mutation that should refresh only affected UI, add selectors:

```html
<form method="post" action="..." data-ajax-form data-ajax-refresh="#record-table,.summary-cards">
```

For custom module behavior, call `FuelOpsAjax.request()`, `submitForm()`, `refresh()`, `notify()`, `validation`, or `loading`; do not duplicate their internals.

## Controller

Controller write actions should delegate the operation to a service, then use the inherited responder:

```php
$request = Request::capture();
$this->mutationResponse(
    $request,
    fn (): array => $service->store($request->all()),
    'Record saved successfully.',
    route_url('module'),
    route_url('module/create'),
    'module_error'
);
```

This validates CSRF globally, returns the standard JSON envelope for AJAX, preserves flash/redirect behavior for non-AJAX requests, maps `ValidationException` field errors, exposes safe `ServiceException`/`RuntimeException` messages, logs unexpected exceptions, and hides internal details from users.

## Service and model errors

- Throw `ValidationException($message, ['field_name' => 'Field message'])` for field validation.
- Throw `ServiceException` for safe operation or business-rule failures.
- Let database and unexpected exceptions bubble to the controller responder; it logs them and returns the generic 500 response.
- Never build JSON or show UI notifications inside a model or service.
