<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Repositories\UserRepository;
use App\Services\AuthService;
use App\Services\EmailService;
use RuntimeException;

final class AuthController
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly AuthService $auth,
        private readonly EmailService $email
    ) {
    }

    public function register(Request $request): array
    {
        $name = trim((string) $request->input('name'));
        $email = strtolower(trim((string) $request->input('email')));
        $password = (string) $request->input('password');
        $role = strtolower(trim((string) ($request->input('role') ?? 'customer')));

        if ($name === '' || $email === '' || $password === '') {
            throw new RuntimeException('Name, email, and password are required.', 422);
        }

        if (!in_array($role, self::ALLOWED_ROLES, true)) {
            throw new RuntimeException('Role must be one of: customer, manager, admin.', 422);
        }

        if ($this->users->findByEmail($email)) {
            throw new RuntimeException('Email address is already registered.', 409);
        }

        $user = $this->users->create($name, $email, password_hash($password, PASSWORD_DEFAULT), $role);
        $token = $this->auth->issueToken((int) $user['id'], $user['email']);

        // Send verification email
        $verificationToken = $this->auth->generateVerificationToken();
        $expiresAt = $this->auth->generateTokenExpiry();
        $this->users->setVerificationToken((int) $user['id'], $verificationToken, $expiresAt);
        $this->email->sendVerificationEmail($user['email'], $user['name'], $verificationToken);

        return [
            'message' => 'Account created successfully.',
            'token' => $token,
            'user' => $this->users->sanitize($user),
        ];
    }

    public function login(Request $request): array
    {
        $email = strtolower(trim((string) $request->input('email')));
        $password = (string) $request->input('password');
        $user = $this->users->findByEmail($email);

        if (!$user || !password_verify($password, $user['password'])) {
            throw new RuntimeException('Invalid credentials.', 401);
        }

        return [
            'message' => 'Login successful.',
            'token' => $this->auth->issueToken((int) $user['id'], $user['email']),
            'user' => $this->users->sanitize($user),
        ];
    }

    public function me(Request $request): array
    {
        return [
            'user' => $request->attribute('user'),
        ];
    }

    public function verifyEmail(Request $request): array
    {
        $token = (string) $request->queryParam('token', '');

        if ($token === '') {
            throw new RuntimeException('Verification token is required.', 400);
        }

        $user = $this->users->findByVerificationToken($token);

        if (!$user) {
            throw new RuntimeException('Invalid or expired verification token.', 400);
        }

        $this->users->verifyEmail((int) $user['id']);

        return [
            'message' => 'Email verified successfully.',
        ];
    }

    public function resendVerification(Request $request): array
    {
        $userId = (int) $request->attribute('user')['id'];
        $user = $this->users->findById($userId);

        if (!$user) {
            throw new RuntimeException('User not found.', 404);
        }

        if ($this->users->hasVerifiedEmail($userId)) {
            throw new RuntimeException('Email is already verified.', 400);
        }

        // Clear old token and generate new one
        $this->users->clearVerificationToken($userId);

        $verificationToken = $this->auth->generateVerificationToken();
        $expiresAt = $this->auth->generateTokenExpiry();
        $this->users->setVerificationToken($userId, $verificationToken, $expiresAt);
        $this->email->sendVerificationEmail($user['email'], $user['name'], $verificationToken);

        return [
            'message' => 'Verification email sent.',
        ];
    }

    public function updateProfile(Request $request): array
    {
        $userId = (int) $request->attribute('user')['id'];
        $name = trim((string) $request->input('name'));
        $email = strtolower(trim((string) $request->input('email')));

        if ($name === '' || $email === '') {
            throw new RuntimeException('Name and email are required.', 422);
        }

        $user = $this->users->findById($userId);

        // Check if email is being changed
        if ($user['email'] !== $email) {
            $existing = $this->users->findByEmail($email);
            if ($existing && (int) $existing['id'] !== $userId) {
                throw new RuntimeException('Email address is already taken.', 409);
            }
            // If email is changed, reset verification
            $this->users->clearVerificationToken($userId);
        }

        $updatedUser = $this->users->updateProfile($userId, $name, $email);

        return [
            'message' => 'Profile updated successfully.',
            'user' => $this->users->sanitize($updatedUser),
        ];
    }

    public function changePassword(Request $request): array
    {
        $userId = (int) $request->attribute('user')['id'];
        $currentPassword = (string) $request->input('current_password');
        $newPassword = (string) $request->input('new_password');

        if ($currentPassword === '' || $newPassword === '') {
            throw new RuntimeException('Current and new passwords are required.', 422);
        }

        if (strlen($newPassword) < 6) {
            throw new RuntimeException('New password must be at least 6 characters.', 422);
        }

        $user = $this->users->findById($userId);

        if (!password_verify($currentPassword, (string) $user['password'])) {
            throw new RuntimeException('Current password is incorrect.', 403);
        }

        $this->users->updatePassword($userId, password_hash($newPassword, PASSWORD_DEFAULT));

        return [
            'message' => 'Password changed successfully.',
        ];
    }

    public function updateShippingAddress(Request $request): array
    {
        $userId = (int) $request->attribute('user')['id'];

        $address = trim((string) $request->input('address'));
        $city = trim((string) $request->input('city'));
        $country = trim((string) $request->input('country'));
        $postalCode = trim((string) $request->input('postal_code'));

        if ($address === '' || $city === '' || $country === '' || $postalCode === '') {
            throw new RuntimeException('All address fields are required.', 422);
        }

        $user = $this->users->updateShippingAddress($userId, $address, $city, $country, $postalCode);

        return [
            'message' => 'Shipping address updated successfully.',
            'shippingAddress' => $user['shipping_address'],
        ];
    }

    private const ALLOWED_ROLES = ['customer', 'manager', 'admin'];
}
