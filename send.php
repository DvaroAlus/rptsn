<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') exit;

$name    = htmlspecialchars($_POST['name'] ?? '');
$contact = htmlspecialchars($_POST['contact'] ?? '');
$topic   = htmlspecialchars($_POST['topic'] ?? '');
$message = htmlspecialchars($_POST['message'] ?? '');

$to      = 'admin@raypotosno.ru'; // ← ваша почта
$subject = "Обращение с сайта: $topic";
$body    = "Имя: $name\nКонтакт: $contact\nТема: $topic\n\nСообщение:\n$message";
$headers = "From: noreply@tosno-raipo.ru\r\nContent-Type: text/plain; charset=UTF-8";

mail($to, $subject, $body, $headers);
echo json_encode(['ok' => true]);