<?php
// ── НАСТРОЙКИ ──────────────────────────────────────────────
// Укажите почту, на которую будут приходить обращения с сайта
$to = 'admin@raypotosno.ru';
// Разрешённые источники (Origin/Referer) для запросов формы.
// Пустой массив = проверка отключена (для локальной разработки).
$allowedHosts = ['tosno-raipo.ru', 'www.tosno-raipo.ru'];
// Лимит запросов с одного IP (штук в час)
$rateLimit       = 5;
$rateLimitWindow = 3600;
// ───────────────────────────────────────────────────────────

// Безопасные заголовки ответа
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
header('Cache-Control: no-store');
header('X-Frame-Options: DENY');
header('Content-Security-Policy: default-src \'none\'; frame-ancestors \'none\'');

// Никаких PHP-ошибок в ответе клиенту
ini_set('display_errors', '0');
error_reporting(E_ALL);

// Принимаем только POST-запросы
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    echo json_encode(['ok' => false, 'error' => 'Method Not Allowed']);
    exit;
}

// Базовая проверка Origin/Referer (минимальная защита от CSRF/удалённой отправки)
if (!empty($allowedHosts)) {
    $origin  = $_SERVER['HTTP_ORIGIN']  ?? '';
    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    $sourceHost = '';
    if ($origin !== '') {
        $sourceHost = parse_url($origin, PHP_URL_HOST) ?? '';
    } elseif ($referer !== '') {
        $sourceHost = parse_url($referer, PHP_URL_HOST) ?? '';
    }
    if ($sourceHost === '' || !in_array(strtolower($sourceHost), array_map('strtolower', $allowedHosts), true)) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'Forbidden']);
        exit;
    }
}

// Простой rate-limit по IP через файловое хранилище.
// Открываем файл с эксклюзивной блокировкой на весь цикл read-modify-write,
// чтобы исключить состояние гонки при параллельных запросах.
$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
if (filter_var($ip, FILTER_VALIDATE_IP)) {
    $rlDir = sys_get_temp_dir() . '/rptsn_rl';
    if (!is_dir($rlDir)) { @mkdir($rlDir, 0700, true); }
    $rlFile = $rlDir . '/' . hash('sha256', $ip);
    $now = time();
    $fp = @fopen($rlFile, 'c+');
    if ($fp !== false) {
        if (flock($fp, LOCK_EX)) {
            $raw = stream_get_contents($fp);
            $entries = [];
            if (is_string($raw) && $raw !== '') {
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) { $entries = $decoded; }
            }
            $entries = array_values(array_filter(
                $entries,
                fn($t) => is_int($t) && ($now - $t) < $rateLimitWindow
            ));
            if (count($entries) >= $rateLimit) {
                flock($fp, LOCK_UN);
                fclose($fp);
                http_response_code(429);
                header('Retry-After: ' . $rateLimitWindow);
                echo json_encode(['ok' => false, 'error' => 'Too Many Requests']);
                exit;
            }
            $entries[] = $now;
            ftruncate($fp, 0);
            rewind($fp);
            fwrite($fp, json_encode($entries));
            fflush($fp);
            flock($fp, LOCK_UN);
        }
        fclose($fp);
    }
}

// Защита от ботов: honeypot-поле должно быть пустым
if (!empty($_POST['website'] ?? '')) {
    // Делаем вид, что всё хорошо, но ничего не отправляем
    echo json_encode(['ok' => true]);
    exit;
}

// Ограничение размера тела (защита от DoS)
$contentLength = (int)($_SERVER['CONTENT_LENGTH'] ?? 0);
if ($contentLength > 32768) {
    http_response_code(413);
    echo json_encode(['ok' => false, 'error' => 'Payload Too Large']);
    exit;
}

// Хелперы
function clean_line(string $v, int $max): string {
    // Убираем управляющие символы и переводы строк — критично для заголовков письма
    $v = preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $v) ?? '';
    $v = trim($v);
    return mb_substr($v, 0, $max, 'UTF-8');
}
function clean_text(string $v, int $max): string {
    // Для тела письма: оставляем переводы строк, нормализуем CRLF, режем NUL и прочее
    $v = str_replace(["\r\n", "\r"], "\n", $v);
    $v = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/u', '', $v) ?? '';
    $v = trim($v);
    return mb_substr($v, 0, $max, 'UTF-8');
}

// Разрешённые темы обращения. Любое иное значение сводится к «Иное» —
// чтобы в теме письма не оказалось произвольно подобранного клиентом текста.
$allowedTopics = [
    'Вопрос о работе магазина / аптеки',
    'Предложение о поставках / сотрудничестве',
    'Жалоба или претензия',
    'Трудоустройство',
    'Запрос документов',
    'Иное',
];

// Сбор входных данных
$name    = clean_line((string)($_POST['name']    ?? ''), 100);
$contact = clean_line((string)($_POST['contact'] ?? ''), 120);
$topic   = clean_line((string)($_POST['topic']   ?? ''), 120);
$message = clean_text((string)($_POST['message'] ?? ''), 4000);

if ($topic !== '' && !in_array($topic, $allowedTopics, true)) {
    $topic = 'Иное';
}

// Минимальная проверка обязательных полей
if ($name === '' || $contact === '' || $message === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Missing required fields']);
    exit;
}
if (mb_strlen($message, 'UTF-8') < 5) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Message too short']);
    exit;
}

// Reply-To используем только если контакт — валидный e-mail.
// Иначе CRLF и любые другие символы могли бы влезть в заголовки.
$replyTo = '';
if (filter_var($contact, FILTER_VALIDATE_EMAIL)) {
    $replyTo = $contact;
}

// Тема письма (кодируем в base64, что само по себе нейтрализует CRLF)
$subjectRaw = 'Обращение с сайта' . ($topic !== '' ? ': ' . $topic : '');
$subject    = '=?UTF-8?B?' . base64_encode($subjectRaw) . '?=';

// Тело письма — обычный plain-text, никаких HTML-сущностей
$sep = str_repeat('-', 40);
$body  = "Новое обращение с сайта Тосненского Райпо\n";
$body .= $sep . "\n\n";
$body .= "Имя:     " . $name    . "\n";
$body .= "Контакт: " . $contact . "\n";
$body .= "Тема:    " . ($topic !== '' ? $topic : '—') . "\n\n";
$body .= "Сообщение:\n" . $message . "\n";
$body .= "\n" . $sep . "\n";
$body .= "Отправлено: " . date('d.m.Y H:i') . " (МСК)\n";
$body .= "IP: " . $ip . "\n";

// Заголовки письма. Все динамические значения уже очищены от CRLF.
$fromAddr = 'noreply@tosno-raipo.ru';
$fromName = '=?UTF-8?B?' . base64_encode('Сайт Тосненского Райпо') . '?=';
$headers  = "From: $fromName <$fromAddr>\r\n";
if ($replyTo !== '') {
    $headers .= "Reply-To: $replyTo\r\n";
}
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
$headers .= "Content-Transfer-Encoding: 8bit\r\n";
$headers .= "X-Mailer: rptsn-form\r\n";

// Отправка. Пятый параметр '-f' подменяет envelope sender только при
// безопасном фиксированном адресе; пользовательский ввод сюда не попадает.
$sent = @mail($to, $subject, $body, $headers, '-f' . $fromAddr);

if ($sent) {
    echo json_encode(['ok' => true]);
} else {
    http_response_code(500);
    // Никаких подробностей наружу
    echo json_encode(['ok' => false, 'error' => 'Send failed']);
}
