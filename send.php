<?php
// ── НАСТРОЙКИ ──────────────────────────────────────────────
// Укажите почту, на которую будут приходить обращения с сайта
$to = 'admin@raypotosno.ru';
// ───────────────────────────────────────────────────────────

header('Content-Type: application/json; charset=utf-8');

// Принимаем только POST-запросы
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method Not Allowed']);
    exit;
}

// Собираем и очищаем данные
$name    = trim(htmlspecialchars($_POST['name']    ?? '', ENT_QUOTES, 'UTF-8'));
$contact = trim(htmlspecialchars($_POST['contact'] ?? '', ENT_QUOTES, 'UTF-8'));
$topic   = trim(htmlspecialchars($_POST['topic']   ?? '', ENT_QUOTES, 'UTF-8'));
$message = trim(htmlspecialchars($_POST['message'] ?? '', ENT_QUOTES, 'UTF-8'));

// Минимальная проверка обязательных полей
if (empty($name) || empty($contact) || empty($message)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Missing required fields']);
    exit;
}

// Тема письма
$subject = '=?UTF-8?B?' . base64_encode("Обращение с сайта: $topic") . '?=';

// Тело письма
$body = "Новое обращение с сайта Тосненского Райпо\n";
$body .= str_repeat('─', 40) . "\n\n";
$body .= "Имя:     $name\n";
$body .= "Контакт: $contact\n";
$body .= "Тема:    $topic\n\n";
$body .= "Сообщение:\n$message\n";
$body .= "\n" . str_repeat('─', 40) . "\n";
$body .= "Отправлено: " . date('d.m.Y H:i') . " (МСК)";

// Заголовки письма
$headers  = "From: =?UTF-8?B?" . base64_encode("Сайт Тосненского Райпо") . "?= <noreply@tosno-raipo.ru>\r\n";
$headers .= "Reply-To: $contact\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
$headers .= "Content-Transfer-Encoding: base64\r\n";

// Отправка
$sent = mail($to, $subject, base64_encode($body), $headers);

if ($sent) {
    echo json_encode(['ok' => true]);
} else {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Mail sending failed']);
}
