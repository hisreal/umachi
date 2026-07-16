<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\AuthService;

class AuthController extends Controller
{
    private AuthService $auth;
    private Request $request;
    private Response $response;

    public function __construct()
    {
        parent::__construct();

        $this->auth = new AuthService();
        $this->request = Request::capture();
        $this->response = new Response();
    }

    /**
     * Load the standalone login page with no dashboard shell.
     */
    public function login(): void
    {
        $this->render('auth/login.php', [
            'authError' => Session::pullFlash('auth_error'),
            'oldUsername' => Session::pullFlash('old_username', ''),
            'oldRole' => Session::pullFlash('old_role', ''),
        ]);
    }

    public function authenticate(): void
    {
        if (!$this->auth->validateCsrf((string) $this->request->post('_csrf_token', ''))) {
            Session::flash('auth_error', 'Your login session expired. Please try again.');
            $this->response->redirect(route_url('auth/login'));
        }

        $username = (string) $this->request->post('username', '');
        $role = (string) $this->request->post('role', '');
        $password = (string) $this->request->post('password', '');

        $result = $this->auth->attempt($username, $password, $role, $this->request);

        if (($result['success'] ?? false) === true) {
            $this->response->redirect(route_url((string) $result['redirect']));
        }

        Session::flash('auth_error', (string) ($result['message'] ?? 'Unable to sign in.'));
        Session::flash('old_username', $username);
        Session::flash('old_role', $role);

        $this->response->redirect(route_url('auth/login'));
    }

    public function logout(): void
    {
        $this->auth->logout();
        Session::start();
        Session::flash('auth_error', 'You have been signed out.');
        $this->response->redirect(route_url('auth/login'));
    }
}
