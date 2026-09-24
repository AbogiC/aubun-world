<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Repositories\UserRepository;
use App\Repositories\VoucherRepository;
use App\Repositories\WelcomeVoucherRepository;
use App\Services\AuthService;
use App\Services\EmailService;
use RuntimeException;

final class AuthController
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly AuthService $auth,
        private readonly EmailService $email,
        private readonly ?VoucherRepository $vouchers = null,
        private readonly ?WelcomeVoucherRepository $welcomeVouchers = null,
        private readonly string $googleClientId = ''
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

        $welcomeVoucher = $this->grantWelcomeVoucher((int) $user['id'], $role);

        $verificationToken = $this->auth->generateVerificationToken();
        $this->users->setVerificationToken((int) $user['id'], $verificationToken);

        $emailDeliveryFailed = false;

        try {
            $this->email->sendVerificationEmail($user['email'], $user['name'], $verificationToken, $welcomeVoucher);
        } catch (\Throwable $exception) {
            $emailDeliveryFailed = true;
            error_log(sprintf(
                'Verification email failed for user %d (%s): %s',
                (int) $user['id'],
                $user['email'],
                $exception->getMessage()
            ));
        }

        $response = [
            'message' => 'Account created successfully.',
            'token' => $token,
            'user' => $this->users->sanitize($user),
        ];

        if ($welcomeVoucher !== null) {
            $response['welcomeVoucher'] = $welcomeVoucher;
        }

        if ($emailDeliveryFailed) {
            $response['emailNotice'] = 'Account created, but the verification email could not be sent from this server.';
        }

        return $response;
    }

    public function login(Request $request): array
    {
        $email = strtolower(trim((string) $request->input('email')));
        $password = (string) $request->input('password');
        $user = $this->users->findByEmail($email);

        if (!$user || empty($user['password']) || !password_verify($password, (string) $user['password'])) {
            if ($user && empty($user['password']) && !empty($user['google_id'])) {
                throw new RuntimeException('This account was created with Google. Please use "Continue with Google".', 401);
            }
            throw new RuntimeException('Invalid credentials.', 401);
        }

        if (isset($user['is_active']) && (int) $user['is_active'] === 0) {
            throw new RuntimeException('This account is currently inactive.', 403);
        }

        return [
            'message' => 'Login successful.',
            'token' => $this->auth->issueToken((int) $user['id'], $user['email']),
            'user' => $this->users->sanitize($user),
        ];
    }

    /**
     * Login / register with a Google ID token (from Google Identity Services).
     * Body: { credential | id_token }
     */
    public function googleLogin(Request $request): array
    {
        $idToken = (string) ($request->input('credential') ?? $request->input('id_token') ?? '');
        if ($idToken === '') {
            throw new RuntimeException('Google credential is required.', 422);
        }

        $googleUser = $this->verifyGoogleIdToken($idToken);

        $googleId = (string) ($googleUser['sub'] ?? '');
        $email = strtolower(trim((string) ($googleUser['email'] ?? '')));
        $name = trim((string) ($googleUser['name'] ?? ''));
        $picture = (string) ($googleUser['picture'] ?? '');
        $emailVerified = filter_var($googleUser['email_verified'] ?? false, FILTER_VALIDATE_BOOLEAN);

        if ($googleId === '' || $email === '') {
            throw new RuntimeException('Google account did not return an email address.', 401);
        }

        if (!$emailVerified) {
            throw new RuntimeException('Your Google email address is not verified.', 403);
        }

        if ($name === '') {
            $name = explode('@', $email)[0];
        }

        // 1. Existing Google-linked account -> login.
        $user = $this->users->findByGoogleId($googleId);

        // 2. Existing email account (registered with password) -> link Google and login.
        if (!$user) {
            $user = $this->users->findByEmail($email);
            if ($user) {
                if (isset($user['is_active']) && (int) $user['is_active'] === 0) {
                    throw new RuntimeException('This account is currently inactive.', 403);
                }
                if (empty($user['google_id'])) {
                    $user = $this->users->linkGoogleAccount((int) $user['id'], $googleId, $picture ?: null);
                }
            }
        }

        // 3. Brand-new customer -> create Google user (marked login by google).
        $isNew = false;
        if (!$user) {
            $user = $this->users->createGoogleUser($name, $email, $googleId, $picture ?: null);
            $isNew = true;
        }

        if (isset($user['is_active']) && (int) $user['is_active'] === 0) {
            throw new RuntimeException('This account is currently inactive.', 403);
        }

        $welcomeVoucher = null;
        if ($isNew) {
            $welcomeVoucher = $this->grantWelcomeVoucher((int) $user['id'], 'customer');
        }

        $response = [
            'message' => $isNew ? 'Account created with Google.' : 'Login with Google successful.',
            'token' => $this->auth->issueToken((int) $user['id'], $user['email']),
            'user' => $this->users->sanitize($user),
            'isNewUser' => $isNew,
        ];

        if ($welcomeVoucher !== null) {
            $response['welcomeVoucher'] = $welcomeVoucher;
        }

        return $response;
    }

    /**
     * Verifies a Google ID token via Google's tokeninfo endpoint.
     * Returns the token payload (sub, email, name, picture, ...).
     */
    private function verifyGoogleIdToken(string $idToken): array
    {
        $configured = array_filter(array_map(
            static fn (string $id): string => trim($id),
            explode(',', $this->googleClientId)
        ));

        $payload = null;

        // Authoritative check against Google.
        $url = 'https://oauth2.googleapis.com/tokeninfo?id_token=' . urlencode($idToken);
        $raw = $this->httpGet($url);

        if ($raw !== null) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded) && !isset($decoded['error']) && !isset($decoded['error_description'])) {
                $payload = $decoded;
            } elseif (isset($decoded['error_description'])) {
                throw new RuntimeException('Google verification failed: ' . (string) $decoded['error_description'], 401);
            }
        }

        // Fallback: decode payload locally (still checks aud/iss/exp below).
        if ($payload === null) {
            $parts = explode('.', $idToken);
            if (count($parts) !== 3) {
                throw new RuntimeException('Invalid Google credential.', 401);
            }
            $decoded = json_decode(base64_decode(strtr($parts[1], '-_', '+/')) ?: '', true);
            if (!is_array($decoded)) {
                throw new RuntimeException('Invalid Google credential.', 401);
            }
            $payload = $decoded;
        }

        if (($payload['exp'] ?? 0) !== 0 && (int) $payload['exp'] < time() - 30) {
            throw new RuntimeException('Google credential has expired. Please try again.', 401);
        }

        $issuer = (string) ($payload['iss'] ?? '');
        if ($issuer !== 'accounts.google.com' && $issuer !== 'https://accounts.google.com') {
            throw new RuntimeException('Invalid Google credential issuer.', 401);
        }

        if ($configured !== []) {
            $audience = (string) ($payload['aud'] ?? '');
            if (!in_array($audience, $configured, true)) {
                throw new RuntimeException('Google credential was not issued for this app.', 401);
            }
        }

        return $payload;
    }

    private function httpGet(string $url): ?string
    {
        if (function_exists('curl_init')) {
            $handle = curl_init($url);
            curl_setopt_array($handle, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 10,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_SSL_VERIFYPEER => true,
            ]);
            $body = curl_exec($handle);
            curl_close($handle);

            return is_string($body) && $body !== '' ? $body : null;
        }

        $context = stream_context_create(['http' => ['timeout' => 10]]);
        $body = @file_get_contents($url, false, $context);

        return is_string($body) && $body !== '' ? $body : null;
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
        $this->users->setVerificationToken($userId, $verificationToken);

        $welcomeVoucher = $this->welcomeVouchers?->findByUserId($userId);
        $this->email->sendVerificationEmail($user['email'], $user['name'], $verificationToken, $welcomeVoucher);

        return [
            'message' => 'Verification email sent.',
        ];
    }

    public function subscribeNewsletter(Request $request): array
    {
        $email = strtolower(trim((string) $request->input('email')));

        if ($email === '') {
            throw new RuntimeException('Email is required.', 422);
        }

        $user = $this->users->findByEmail($email);

        if (!$user) {
            throw new RuntimeException('User with this email was not found, please create an account first.', 404);
        }

        if (isset($user['isSubscribed']) && (int) $user['isSubscribed'] === 1) {
            return [
                'message' => 'This email is already subscribed to the newsletter.',
                'user' => $this->users->sanitize($user),
            ];
        }

        $this->email->sendNewsletterSubscriptionEmail($email);
        $updatedUser = $this->users->markAsSubscribed((int) $user['id']);

        return [
            'message' => 'Newsletter subscription successful, please check your email for confirmation.',
            'user' => $this->users->sanitize($updatedUser),
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
        $emailChanged = $user['email'] !== $email;

        if ($emailChanged) {
            $existing = $this->users->findByEmail($email);
            if ($existing && (int) $existing['id'] !== $userId) {
                throw new RuntimeException('Email address is already taken.', 409);
            }
            // The new address must be verified before it can be used.
            $this->users->resetEmailVerification($userId);
        }

        $updatedUser = $this->users->updateProfile($userId, $name, $email);

        if ($emailChanged) {
            $verificationToken = $this->auth->generateVerificationToken();
            $this->users->setVerificationToken($userId, $verificationToken);

            try {
                $this->email->sendVerificationEmail($email, (string) $updatedUser['name'], $verificationToken);
            } catch (\Throwable $exception) {
                error_log(sprintf(
                    'Verification email failed for user %d (%s): %s',
                    $userId,
                    $email,
                    $exception->getMessage()
                ));
            }
        }

        return [
            'message' => $emailChanged
                ? 'Profile updated. Please verify your new email address before it can be used.'
                : 'Profile updated successfully.',
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

        // Google-only accounts have no password yet: allow setting one without current password.
        if (empty($user['password'])) {
            if ($currentPassword !== '' && $currentPassword !== '__google__') {
                throw new RuntimeException('This account uses Google sign-in. Leave current password empty to set a new password.', 403);
            }
        } elseif (!password_verify($currentPassword, (string) $user['password'])) {
            throw new RuntimeException('Current password is incorrect.', 403);
        }

        $this->users->updatePassword($userId, password_hash($newPassword, PASSWORD_DEFAULT));

        // Account can now log in with both password and Google.
        if (empty($user['password']) && !empty($user['google_id'])) {
            try {
                $this->users->getPdo()->prepare(
                    "UPDATE users SET auth_provider = 'both', updated_at = NOW() WHERE id = :id"
                )->execute(['id' => $userId]);
            } catch (\Throwable $exception) {
                error_log('Failed to mark auth_provider=both for user ' . $userId . ': ' . $exception->getMessage());
            }
        }

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

    /**
     * New customers get 1 free voucher on first registration.
     * Value (discount %) + validity comes from admin welcome settings.
     */
    private function grantWelcomeVoucher(int $userId, string $role): ?array
    {
        if ($role !== 'customer') {
            return null;
        }

        if ($this->vouchers === null || $this->welcomeVouchers === null) {
            return null;
        }

        try {
            $settings = $this->welcomeVouchers->getSettings();

            if (!($settings['isEnabled'] ?? true)) {
                return null;
            }

            // Safety: one voucher per account only.
            if ($this->welcomeVouchers->findByUserId($userId)) {
                return $this->welcomeVouchers->findByUserId($userId);
            }

            $discountPercent = max(0.01, min(100.0, (float) ($settings['discountPercent'] ?? 10)));
            $validityDays = max(1, (int) ($settings['validityDays'] ?? 30));

            $code = $this->generateWelcomeCode();
            $expiresAt = (new \DateTimeImmutable(sprintf('+%d days', $validityDays)))->format('Y-m-d H:i:s');

            $voucher = $this->vouchers->create([
                'code' => $code,
                'discountPercent' => round($discountPercent, 2),
                'scopeType' => 'all',
                'categoryName' => null,
                'productIds' => [],
                'expiresAt' => $expiresAt,
                'isActive' => true,
            ]);

            return $this->welcomeVouchers->assignWelcomeVoucher($userId, $voucher);
        } catch (\Throwable $exception) {
            error_log(sprintf(
                'Welcome voucher grant failed for user %d: %s',
                $userId,
                $exception->getMessage()
            ));

            return null;
        }
    }

    private function generateWelcomeCode(): string
    {
        for ($attempt = 0; $attempt < 10; $attempt++) {
            $code = 'WELCOME-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));

            if ($this->vouchers !== null && !$this->vouchers->codeExists($code)) {
                return $code;
            }
        }

        return 'WELCOME-' . strtoupper(substr(bin2hex(random_bytes(6)), 0, 8)) . time() % 1000;
    }
}
