<?php

class SmtpMailer
{
    private string $host;
    private int $port;
    private string $username;
    private string $password;
    private string $fromEmail;
    private string $fromName;
    private $socket = null;
    private array $log = [];

    public function __construct()
    {
        $this->host = get_env_value('MAIL_HOST', 'smtp.gmail.com');
        $this->port = (int) get_env_value('MAIL_PORT', 587);
        $this->username = get_env_value('MAIL_USERNAME', '');
        $this->password = get_env_value('MAIL_PASSWORD', '');
        $this->fromEmail = get_env_value('MAIL_FROM', $this->username);
        $this->fromName = get_env_value('MAIL_FROM_NAME', get_env_value('APP_NAME', 'Pakistan Cable'));
    }

    public function isConfigured(): bool
    {
        return $this->username !== '' && $this->password !== '';
    }

    public function send(string $to, string $subject, string $htmlBody, ?string $textBody = null): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'Mail is not configured'];
        }

        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'error' => 'Invalid recipient email'];
        }

        $textBody = $textBody ?? strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $htmlBody));

        try {
            $this->connect();
            $this->expect(220);
            $this->command('EHLO ' . $this->clientHost(), [250]);
            $this->command('STARTTLS', [220]);
            $this->enableTls();
            $this->command('EHLO ' . $this->clientHost(), [250]);
            $this->authenticate();
            $this->command('MAIL FROM:<' . $this->fromEmail . '>', [250]);
            $this->command('RCPT TO:<' . $to . '>', [250, 251]);
            $this->command('DATA', [354]);
            $this->writeMessage($to, $subject, $htmlBody, $textBody);
            $this->command('.', [250]);
            $this->command('QUIT', [221]);
            $this->disconnect();

            return ['success' => true, 'error' => null];
        } catch (Exception $e) {
            $this->disconnect();

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function connect(): void
    {
        $errno = 0;
        $errstr = '';
        $this->socket = @stream_socket_client(
            'tcp://' . $this->host . ':' . $this->port,
            $errno,
            $errstr,
            30,
            STREAM_CLIENT_CONNECT
        );

        if (!$this->socket) {
            throw new RuntimeException('Could not connect to mail server: ' . $errstr);
        }

        stream_set_timeout($this->socket, 30);
    }

    private function disconnect(): void
    {
        if (is_resource($this->socket)) {
            fclose($this->socket);
        }

        $this->socket = null;
    }

    private function enableTls(): void
    {
        $crypto = @stream_socket_enable_crypto(
            $this->socket,
            true,
            STREAM_CRYPTO_METHOD_TLS_CLIENT
        );

        if ($crypto !== true) {
            throw new RuntimeException('Failed to enable TLS encryption');
        }
    }

    private function authenticate(): void
    {
        $this->command('AUTH LOGIN', [334]);
        $this->command(base64_encode($this->username), [334]);
        $this->command(base64_encode($this->password), [235]);
    }

    private function writeMessage(string $to, string $subject, string $htmlBody, string $textBody): void
    {
        $boundary = 'pkcable_' . bin2hex(random_bytes(8));
        $encodedSubject = $this->encodeHeader($subject);
        $encodedFromName = $this->encodeHeader($this->fromName);

        $headers = [
            'Date: ' . date('r'),
            'From: ' . $encodedFromName . ' <' . $this->fromEmail . '>',
            'To: <' . $to . '>',
            'Subject: ' . $encodedSubject,
            'MIME-Version: 1.0',
            'Content-Type: multipart/alternative; boundary="' . $boundary . '"',
        ];

        $message = implode("\r\n", $headers) . "\r\n\r\n";
        $message .= '--' . $boundary . "\r\n";
        $message .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $message .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
        $message .= $textBody . "\r\n\r\n";
        $message .= '--' . $boundary . "\r\n";
        $message .= "Content-Type: text/html; charset=UTF-8\r\n";
        $message .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
        $message .= $htmlBody . "\r\n\r\n";
        $message .= '--' . $boundary . "--\r\n";

        $this->write($this->dotStuff($message));
    }

    private function command(string $command, array $expectedCodes): void
    {
        $this->write($command . "\r\n");
        $response = $this->read();
        $code = (int) substr($response, 0, 3);

        if (!in_array($code, $expectedCodes, true)) {
            throw new RuntimeException(trim($response));
        }
    }

    private function expect(int $code): void
    {
        $response = $this->read();
        $responseCode = (int) substr($response, 0, 3);

        if ($responseCode !== $code) {
            throw new RuntimeException(trim($response));
        }
    }

    private function write(string $data): void
    {
        if (!is_resource($this->socket)) {
            throw new RuntimeException('Mail socket is not connected');
        }

        $result = fwrite($this->socket, $data);

        if ($result === false) {
            throw new RuntimeException('Failed to write to mail server');
        }
    }

    private function read(): string
    {
        if (!is_resource($this->socket)) {
            throw new RuntimeException('Mail socket is not connected');
        }

        $response = '';

        while (($line = fgets($this->socket, 515)) !== false) {
            $response .= $line;

            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }

        if ($response === '') {
            throw new RuntimeException('No response from mail server');
        }

        return $response;
    }

    private function dotStuff(string $message): string
    {
        $lines = preg_split("/\r\n|\n|\r/", $message);
        $stuffed = [];

        foreach ($lines as $line) {
            if (isset($line[0]) && $line[0] === '.') {
                $line = '.' . $line;
            }

            $stuffed[] = $line;
        }

        return implode("\r\n", $stuffed);
    }

    private function encodeHeader(string $value): string
    {
        if (preg_match('/[^\x20-\x7E]/', $value)) {
            return '=?UTF-8?B?' . base64_encode($value) . '?=';
        }

        return $value;
    }

    private function clientHost(): string
    {
        $host = $_SERVER['SERVER_NAME'] ?? 'localhost';

        return preg_replace('/[^a-zA-Z0-9.-]/', '', $host) ?: 'localhost';
    }
}

function app_base_url(): string
{
    $configured = get_env_value('APP_URL');

    if ($configured) {
        return rtrim($configured, '/');
    }

    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    $basePath = preg_replace('#/controller(?:/.*)?$#', '', $scriptDir);

    return rtrim($scheme . '://' . $host . ($basePath === '/' ? '' : $basePath), '/');
}

function mail_format_rs($value): string
{
    $numeric = is_numeric($value) ? (float) $value : (float) preg_replace('/[^0-9.]/', '', (string) $value);
    $decimals = fmod($numeric, 1.0) === 0.0 ? 0 : 2;

    return 'Rs.' . number_format($numeric, $decimals);
}

function mail_normalize_package_details(array $details = []): array
{
    $packageName = trim((string) ($details['package_name'] ?? ''));
    $packagePrice = $details['package_price'] ?? 0;
    $discount = $details['discount'] ?? 0;
    $paidAmount = $details['paid_amount'] ?? null;

    if ($paidAmount === null && $packagePrice !== '') {
        $priceNumeric = is_numeric($packagePrice)
            ? (float) $packagePrice
            : (float) preg_replace('/[^0-9.]/', '', (string) $packagePrice);
        $discountNumeric = is_numeric($discount)
            ? (float) $discount
            : (float) preg_replace('/[^0-9.]/', '', (string) $discount);
        $paidAmount = max(0, $priceNumeric - $discountNumeric);
    }

    return [
        'package_name' => $packageName,
        'package_price' => mail_format_rs($packagePrice),
        'discount' => mail_format_rs($discount),
        'paid_amount' => mail_format_rs($paidAmount ?? 0),
        'start_date' => trim((string) ($details['start_date'] ?? '')),
        'end_date' => trim((string) ($details['end_date'] ?? '')),
        'has_package' => $packageName !== '',
    ];
}

function send_customer_welcome_email(string $name, string $email, string $password, array $packageDetails = []): array
{
    $appName = get_env_value('APP_NAME', 'Pakistan Cable');
    $baseUrl = app_base_url();
    $dashboardUrl = $baseUrl . '/index.php';
    $package = mail_normalize_package_details($packageDetails);

    $safeName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
    $safeEmail = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
    $safePassword = htmlspecialchars($password, ENT_QUOTES, 'UTF-8');
    $safeDashboardUrl = htmlspecialchars($dashboardUrl, ENT_QUOTES, 'UTF-8');
    $safeAppName = htmlspecialchars($appName, ENT_QUOTES, 'UTF-8');
    $safePackageName = htmlspecialchars($package['package_name'], ENT_QUOTES, 'UTF-8');
    $safePackagePrice = htmlspecialchars($package['package_price'], ENT_QUOTES, 'UTF-8');
    $safeDiscount = htmlspecialchars($package['discount'], ENT_QUOTES, 'UTF-8');
    $safePaidAmount = htmlspecialchars($package['paid_amount'], ENT_QUOTES, 'UTF-8');
    $safeStartDate = htmlspecialchars($package['start_date'], ENT_QUOTES, 'UTF-8');
    $safeEndDate = htmlspecialchars($package['end_date'], ENT_QUOTES, 'UTF-8');

    $packageHtml = '';
    $packageText = '';

    if ($package['has_package']) {
        $subscriptionPeriodHtml = '';
        $subscriptionPeriodText = '';

        if ($package['start_date'] !== '' && $package['end_date'] !== '') {
            $subscriptionPeriodHtml = '<p style="margin: 0 0 8px;"><strong>Package Period:</strong> '
                . $safeStartDate . ' to ' . $safeEndDate . '</p>';
            $subscriptionPeriodText = "Package Period: {$package['start_date']} to {$package['end_date']}\n";
        }
        $discountLine = '';

        if (
            isset($safeDiscount) &&
            $safeDiscount !== '' &&
            $safeDiscount !== null &&
            trim(str_replace(['Rs.', 'rs.', 'RS.'], '', $safeDiscount)) !== '' &&
            floatval(str_replace(['Rs.', ',', ' '], '', $safeDiscount)) > 0
        ) {
            $discountLine = "<p style='margin: 0 0 8px;'><strong>Discount:</strong> {$safeDiscount}</p>
           <p style='margin: 0 0 8px'><strong>Paid Amount:</strong> {$safePaidAmount}</p> ";
        }
        $packageHtml = <<<HTML
        <div style="background: #eff6ff; border-radius: 8px; padding: 16px; margin: 20px 0;">
            <h3 style="margin: 0 0 12px; color: #1d4ed8; font-size: 16px;">Package Details</h3>
            <p style="margin: 0 0 8px;"><strong>Package Name:</strong> {$safePackageName}</p>
            <p style="margin: 0 0 8px;"><strong>Package Price:</strong> {$safePackagePrice}</p>
            {$discountLine}
            {$subscriptionPeriodHtml}
        </div>
HTML;

        $discount = trim($package['discount']); // Faltu spaces khatam krne k liye
        $paidAmount = trim($package['paid_amount']); // Faltu spaces khatam krne k liye
        $packageText = "Package Details:\n"
            . "Package Name: {$package['package_name']}\n"
            . "Package Price: {$package['package_price']}\n"
            // Agar discount khali ho, 0 ho, ya Rs.0 ho to show na ho
            . ($discount !== '0' && $discount !== 'Rs.0' && !empty($discount) ? "Discount: {$package['discount']}\n" : "")
            . ($paidAmount !== '0' && $paidAmount !== 'Rs.0' && !empty($paidAmount) ? "Paid Amount: {$package['paid_amount']}\n" : "")
            . $subscriptionPeriodText;
    }

    $subject = 'Your ' . $appName . ' account is ready';

    $htmlBody = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>{$safeAppName} Account</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #111827; background: #f9fafb; margin: 0; padding: 24px;">
    <div style="max-width: 600px; margin: 0 auto; background: #ffffff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 24px;">
        <h2 style="margin-top: 0; color: #111827;">Welcome to {$safeAppName}</h2>
        <p>Assalam O Alaikum {$safeName},</p>
        <p>Your account has been created successfully. You can use the details below to sign in.</p>

        <div style="background: #f3f4f6; border-radius: 8px; padding: 16px; margin: 20px 0;">
            <h3 style="margin: 0 0 12px; color: #111827; font-size: 16px;">Login Details</h3>
            <p style="margin: 0 0 8px;"><strong>Dashboard URL:</strong> <a href="{$safeDashboardUrl}">{$safeDashboardUrl}</a></p>
            <p style="margin: 0 0 8px;"><strong>Email:</strong> {$safeEmail}</p>
            <p style="margin: 0;"><strong>Password:</strong> {$safePassword}</p>
        </div>

        {$packageHtml}

        <p>Please sign in and change your password after your first login.</p>
        <p style="margin-bottom: 0;">Regards,<br>{$safeAppName} Team</p>
    </div>
</body>
</html>
HTML;

    $textBody = "Welcome to {$appName}\n\n"
        . "Assalam O Alaikum {$name},\n\n"
        . "Your Account is Registered in Pakistan cable.\n\n"
        . "Login Details:\n"
        . "Dashboard URL: {$dashboardUrl}\n"
        . "Email: {$email}\n"
        . "Password: {$password}\n\n"
        . $packageText
        . "\nPlease sign in and change your password after your first login.\n\n"
        . "Regards,\n{$appName} Team";

    $mailer = new SmtpMailer();

    return $mailer->send($email, $subject, $htmlBody, $textBody);
}

function send_subscription_notification_email(string $name, string $email, string $actionType, array $packageDetails = []): array
{
    $appName = get_env_value('APP_NAME', 'Pakistan Cable');
    $package = mail_normalize_package_details($packageDetails);

    $safeName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
    $safeEmail = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
    $safeAppName = htmlspecialchars($appName, ENT_QUOTES, 'UTF-8');
    $safePackageName = htmlspecialchars($package['package_name'], ENT_QUOTES, 'UTF-8');
    $safePackagePrice = htmlspecialchars($package['package_price'], ENT_QUOTES, 'UTF-8');
    $safeDiscount = htmlspecialchars($package['discount'], ENT_QUOTES, 'UTF-8');
    $safePaidAmount = htmlspecialchars($package['paid_amount'], ENT_QUOTES, 'UTF-8');
    $safeStartDate = htmlspecialchars($package['start_date'], ENT_QUOTES, 'UTF-8');
    $safeEndDate = htmlspecialchars($package['end_date'], ENT_QUOTES, 'UTF-8');

    $actionWord = ($actionType === 'activated') ? 'activated' : 'activated';
    $subject = 'Your ' . $appName . ' Package has been ' . $actionWord;

    $subscriptionPeriodHtml = '';
    $subscriptionPeriodText = '';

    if ($package['start_date'] !== '' && $package['end_date'] !== '') {
        $subscriptionPeriodHtml = '<p style="margin: 0 0 8px;"><strong>Package Period:</strong> '
            . $safeStartDate . ' to ' . $safeEndDate . '</p>';
        $subscriptionPeriodText = "Package Period: {$package['start_date']} to {$package['end_date']}\n";
    }
    $discountLine = '';

    if (
        isset($safeDiscount) &&
        $safeDiscount !== '' &&
        $safeDiscount !== null &&
        trim(str_replace(['Rs.', 'rs.', 'RS.'], '', $safeDiscount)) !== '' &&
        floatval(str_replace(['Rs.', ',', ' '], '', $safeDiscount)) > 0
    ) {
        $discountLine = "<p style='margin: 0 0 8px;'><strong>Discount:</strong> {$safeDiscount}</p>";
    }
    $packageHtml = <<<HTML
        <div style="background: #eff6ff; border-radius: 8px; padding: 16px; margin: 20px 0;">
            <h3 style="margin: 0 0 12px; color: #1d4ed8; font-size: 16px;">Package Details</h3>
            <p style="margin: 0 0 8px;"><strong>Package Name:</strong> {$safePackageName}</p>
            <p style="margin: 0 0 8px;"><strong>Package Price:</strong> {$safePackagePrice}</p>
            {$discountLine}
            <p style="margin: 0 0 8px;"><strong>Paid Amount:</strong> {$safePaidAmount}</p>
            {$subscriptionPeriodHtml}
        </div>
HTML;

    $discount = trim($package['discount']);

    $packageText = "Package Details:\n"
        . "Package Name: {$package['package_name']}\n"
        . "Package Price: {$package['package_price']}\n"
        . ($discount !== '0' && $discount !== 'Rs.0' && !empty($discount) ? "Discount: {$package['discount']}\n" : "")
        . "Paid Amount: {$package['paid_amount']}\n"
        . $subscriptionPeriodText;

    $htmlBody = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>{$safeAppName} Package</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #111827; background: #f9fafb; margin: 0; padding: 24px;">
    <div style="max-width: 600px; margin: 0 auto; background: #ffffff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 24px;">
        <h2 style="margin-top: 0; color: #111827;">Package {$actionWord}</h2>
        <p>Assalam O Alaikum {$safeName},</p>
        <p>Your package to <strong>{$safeAppName}</strong> has been successfully {$actionWord}.</p>

        {$packageHtml}

        <p>Thank you for choosing {$safeAppName}!</p>
        <p style="margin-bottom: 0;">Regards,<br>{$safeAppName} Team</p>
    </div>
</body>
</html>
HTML;

    $textBody = "Package {$actionWord}\n\n"
        . "Assalam O Alaikum {$name},\n\n"
        . "Your package to {$appName} has been successfully {$actionWord}.\n\n"
        . $packageText
        . "\nThank you for choosing {$appName}!\n\n"
        . "Regards,\n{$appName} Team";

    $mailer = new SmtpMailer();

    return $mailer->send($email, $subject, $htmlBody, $textBody);
}

function send_expiry_alert_email(string $name, string $email, string $packageName, int $leftDays, string $endDate): array
{
    $appName = get_env_value('APP_NAME', 'Pakistan Cable');
    $baseUrl = app_base_url();
    $renewUrl = $baseUrl . '/index.php';

    $safeName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
    $safeEmail = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
    $safeAppName = htmlspecialchars($appName, ENT_QUOTES, 'UTF-8');
    $safePackageName = htmlspecialchars($packageName, ENT_QUOTES, 'UTF-8');
    $safeEndDate = htmlspecialchars($endDate, ENT_QUOTES, 'UTF-8');
    $safeRenewUrl = htmlspecialchars($renewUrl, ENT_QUOTES, 'UTF-8');

    $daysText = $leftDays === 1 ? '1 day' : $leftDays . ' days';
    $subject = 'Action Required: Your ' . $appName . ' package is expiring soon';

    $htmlBody = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>{$safeAppName} Package Expiring Soon</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #111827; background: #f9fafb; margin: 0; padding: 24px;">
    <div style="max-width: 600px; margin: 0 auto; background: #ffffff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 24px;">
        <div style="text-align: center; margin-bottom: 20px;">
            <div style="display: inline-block; background: #fffbeb; border-radius: 50%; padding: 12px; color: #d97706;">
                <span style="font-size: 30px; font-weight: bold;">⚠️</span>
            </div>
        </div>
        
        <h2 style="margin-top: 0; color: #111827; text-align: center;">Package Expiring Soon</h2>
        <p>Assalam O Alaikum {$safeName},</p>
        <p>This is a friendly reminder that your package for <strong>{$safePackageName}</strong> Internet Package is expiring in <strong style="color: #d97706;">{$daysText}</strong> on <strong>{$safeEndDate}</strong>.</p>

        <div style="background: #fdf2f8; border-left: 4px solid #db2777; border-radius: 4px; padding: 16px; margin: 20px 0;">
            <p style="margin: 0; color: #9d174d; font-size: 14px;">
                To avoid any service interruption, please contact our support team to activate your package before it expires.
            </p>
        </div>

        <p>If you have already renewed or paid for your next package, please disregard this email.</p>
        <p style="margin-bottom: 0;">Regards,<br>{$safeAppName} Team</p>
    </div>
</body>
</html>
HTML;

    $textBody = "Package Expiring Soon\n\n"
        . "Assalam O Alaikum {$name},\n\n"
        . "This is a friendly reminder that your package for {$packageName} is expiring in {$daysText} on {$endDate}.\n\n"
        . "To avoid any service interruption, please log in to your dashboard at {$renewUrl} and renew/activate your package before it expires.\n\n"
        . "Regards,\n{$appName} Team";

    $mailer = new SmtpMailer();

    return $mailer->send($email, $subject, $htmlBody, $textBody);
}
