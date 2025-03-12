<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Configuration - Use your actual token and chat ID
$telegram_bot_token = '5262241390:AAHaaWYR4e-l6rdHqQDTenKDP9HwJhqRJ-c';
$telegram_chat_id = '-1001674991943';

// Log file for debugging
$logFile = 'telegram_debug.log';
file_put_contents($logFile, "Request received: " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
file_put_contents($logFile, "POST data: " . print_r($_POST, true) . "\n", FILE_APPEND);

// Headers for AJAX response
header('Content-Type: application/json');

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Только POST запросы разрешены']);
    exit;
}

// Get form data
$formType = isset($_POST['form_type']) ? $_POST['form_type'] : '';
$response = ['success' => false, 'message' => 'Произошла ошибка при обработке запроса'];

// Get sender information
$senderInfo = getSenderInfo();

try {
    // Process based on form type
    if ($formType === 'quick_contact') {
        // Quick contact form (phone number)
        $phone = isset($_POST['address']) ? $_POST['address'] : '';
        
        if (empty($phone)) {
            $response = ['success' => false, 'message' => 'Пожалуйста, укажите номер телефона'];
        } else {
            // Format message for Telegram
            $message = formatQuickContactMessage($phone, $senderInfo);
            
            // Send to Telegram
            $result = sendToTelegram($message, $telegram_bot_token, $telegram_chat_id, $logFile);
            
            if ($result) {
                $response = ['success' => true, 'message' => 'Спасибо! Мы свяжемся с вами в ближайшее время.'];
            } else {
                $response = ['success' => false, 'message' => 'Ошибка при отправке сообщения. Пожалуйста, попробуйте позже.'];
            }
        }
    } elseif ($formType === 'main_contact') {
        // Main contact form
        $name = isset($_POST['name']) ? $_POST['name'] : '';
        $surname = isset($_POST['surname']) ? $_POST['surname'] : '';
        $email = isset($_POST['email']) ? $_POST['email'] : '';
        $message = isset($_POST['message']) ? $_POST['message'] : '';
        
        if (empty($name) || empty($surname) || empty($email) || empty($message)) {
            $response = ['success' => false, 'message' => 'Пожалуйста, заполните все поля формы'];
        } else {
            // Format message for Telegram
            $telegramMessage = formatMainContactMessage($name, $surname, $email, $message, $senderInfo);
            
            // Send to Telegram
            $result = sendToTelegram($telegramMessage, $telegram_bot_token, $telegram_chat_id, $logFile);
            
            if ($result) {
                $response = ['success' => true, 'message' => 'Спасибо за вашу заявку! Мы обработаем её и свяжемся с вами в ближайшее время.'];
            } else {
                $response = ['success' => false, 'message' => 'Ошибка при отправке сообщения. Пожалуйста, попробуйте позже.'];
            }
        }
    } else {
        $response = ['success' => false, 'message' => 'Неизвестный тип формы'];
    }
} catch (Exception $e) {
    // Log the exception
    file_put_contents($logFile, date('Y-m-d H:i:s') . ': Exception: ' . $e->getMessage() . "\n", FILE_APPEND);
    $response = ['success' => false, 'message' => 'Ошибка сервера: ' . $e->getMessage()];
}

// Function to get sender information
function getSenderInfo() {
    $info = array();
    
    // Get IP address
    $info['ip'] = getClientIP();
    
    // Get user agent
    $info['user_agent'] = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'Unknown';
    
    // Get browser and OS
    $browser_details = getBrowserDetails($info['user_agent']);
    $info['browser'] = $browser_details['browser'];
    $info['os'] = $browser_details['os'];
    
    // Get referrer
    $info['referrer'] = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'Direct visit';
    
    // Get hostname if possible
    $info['hostname'] = @gethostbyaddr($info['ip']);
    
    // Get language
    $info['language'] = isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? $_SERVER['HTTP_ACCEPT_LANGUAGE'] : 'Unknown';
    
    // Get date and time
    $info['date_time'] = date('Y-m-d H:i:s');
    
    // Get country/city using IP-API (free, no API key needed)
    $geolocation = getGeolocationData($info['ip']);
    $info['geo'] = $geolocation;
    
    return $info;
}

// Function to get client IP address
function getClientIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}

// Function to get geolocation data based on IP
function getGeolocationData($ip) {
    $geolocation = array(
        'country' => 'Unknown',
        'region' => 'Unknown',
        'city' => 'Unknown',
        'isp' => 'Unknown'
    );
    
    // Try to get geolocation data from ip-api.com (free, no API key needed)
    try {
        $url = "http://ip-api.com/json/{$ip}?fields=status,country,regionName,city,isp,org";
        $response = @file_get_contents($url);
        
        if ($response !== false) {
            $data = json_decode($response, true);
            
            if (isset($data['status']) && $data['status'] === 'success') {
                $geolocation['country'] = isset($data['country']) ? $data['country'] : 'Unknown';
                $geolocation['region'] = isset($data['regionName']) ? $data['regionName'] : 'Unknown';
                $geolocation['city'] = isset($data['city']) ? $data['city'] : 'Unknown';
                $geolocation['isp'] = isset($data['isp']) ? $data['isp'] : 'Unknown';
                $geolocation['org'] = isset($data['org']) ? $data['org'] : 'Unknown';
            }
        }
    } catch (Exception $e) {
        // Silently fail and use default values
    }
    
    return $geolocation;
}

// Function to get browser and OS details
function getBrowserDetails($user_agent) {
    $browser = 'Unknown';
    $os = 'Unknown';
    
    // Browser detection
    if (preg_match('/MSIE/i', $user_agent) || preg_match('/Trident/i', $user_agent)) {
        $browser = 'Internet Explorer';
    } elseif (preg_match('/Firefox/i', $user_agent)) {
        $browser = 'Mozilla Firefox';
    } elseif (preg_match('/Chrome/i', $user_agent)) {
        if (preg_match('/Edge/i', $user_agent)) {
            $browser = 'Microsoft Edge';
        } elseif (preg_match('/Edg/i', $user_agent)) {
            $browser = 'Microsoft Edge (Chromium)';
        } elseif (preg_match('/OPR/i', $user_agent)) {
            $browser = 'Opera';
        } else {
            $browser = 'Google Chrome';
        }
    } elseif (preg_match('/Safari/i', $user_agent)) {
        $browser = 'Safari';
    } elseif (preg_match('/Opera/i', $user_agent)) {
        $browser = 'Opera';
    }
    
    // OS detection
    if (preg_match('/Windows NT 10/i', $user_agent)) {
        $os = 'Windows 10';
    } elseif (preg_match('/Windows NT 6.3/i', $user_agent)) {
        $os = 'Windows 8.1';
    } elseif (preg_match('/Windows NT 6.2/i', $user_agent)) {
        $os = 'Windows 8';
    } elseif (preg_match('/Windows NT 6.1/i', $user_agent)) {
        $os = 'Windows 7';
    } elseif (preg_match('/Windows NT 6.0/i', $user_agent)) {
        $os = 'Windows Vista';
    } elseif (preg_match('/Windows NT 5.1/i', $user_agent)) {
        $os = 'Windows XP';
    } elseif (preg_match('/Windows NT 5.0/i', $user_agent)) {
        $os = 'Windows 2000';
    } elseif (preg_match('/Mac/i', $user_agent)) {
        if (preg_match('/iPad/i', $user_agent)) {
            $os = 'iPad';
        } elseif (preg_match('/iPhone/i', $user_agent)) {
            $os = 'iPhone';
        } else {
            $os = 'macOS';
        }
    } elseif (preg_match('/Android/i', $user_agent)) {
        $os = 'Android';
    } elseif (preg_match('/Linux/i', $user_agent)) {
        $os = 'Linux';
    }
    
    return array('browser' => $browser, 'os' => $os);
}

// Format the message for quick contact form
function formatQuickContactMessage($phone, $senderInfo) {
    $message = "🔔 <b>Новая заявка с быстрой формы</b> 🔔\n\n";
    $message .= "📱 <b>Телефон:</b> {$phone}\n\n";
    
    $message .= formatSenderInfo($senderInfo);
    
    return $message;
}

// Format the message for main contact form
function formatMainContactMessage($name, $surname, $email, $userMessage, $senderInfo) {
    $message = "📨 <b>Новая заявка с основной формы</b> 📨\n\n";
    $message .= "👤 <b>Имя:</b> {$name}\n";
    $message .= "👤 <b>Отчество:</b> {$surname}\n";
    $message .= "📧 <b>Контакт:</b> {$email}\n";
    $message .= "💬 <b>Сообщение:</b>\n<i>{$userMessage}</i>\n\n";
    
    $message .= formatSenderInfo($senderInfo);
    
    return $message;
}

// Format sender information
function formatSenderInfo($info) {
    $message = "📊 <b>ИНФОРМАЦИЯ ОБ ОТПРАВИТЕЛЕ</b> 📊\n\n";
    
    // Add date and time
    $message .= "🕒 <b>Дата и время:</b> {$info['date_time']}\n\n";
    
    // Add IP and location information
    $message .= "🌐 <b>IP адрес:</b> {$info['ip']}\n";
    if ($info['hostname'] != $info['ip']) {
        $message .= "🖥️ <b>Хост:</b> {$info['hostname']}\n";
    }
    
    // Add geolocation data
    $message .= "🗺️ <b>Местоположение:</b>\n";
    $message .= "  • <b>Страна:</b> {$info['geo']['country']}\n";
    $message .= "  • <b>Регион:</b> {$info['geo']['region']}\n";
    $message .= "  • <b>Город:</b> {$info['geo']['city']}\n";
    $message .= "  • <b>Провайдер:</b> {$info['geo']['isp']}\n";
    if (isset($info['geo']['org']) && !empty($info['geo']['org'])) {
        $message .= "  • <b>Организация:</b> {$info['geo']['org']}\n";
    }
    
    // Add device information
    $message .= "\n📱 <b>Устройство:</b>\n";
    $message .= "  • <b>ОС:</b> {$info['os']}\n";
    $message .= "  • <b>Браузер:</b> {$info['browser']}\n";
    $message .= "  • <b>Язык:</b> " . substr($info['language'], 0, 50) . "\n";
    
    // Add referrer
    $message .= "\n🔍 <b>Источник перехода:</b> " . substr($info['referrer'], 0, 100) . "\n";
    
    return $message;
}

    // Function to send message to Telegram using file_get_contents
    function sendToTelegram($message, $botToken, $chatId, $logFile) {
        // Log the attempt
        file_put_contents($logFile, date('Y-m-d H:i:s') . ": Attempting to send message to Telegram\n", FILE_APPEND);
        
        // Build the URL for the Telegram API
        $url = "https://api.telegram.org/bot{$botToken}/sendMessage";
        
        // Set up the POST data
        $postData = [
            'chat_id' => $chatId,
            'text' => $message,
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => true
        ];
        
        // Log the request details
        file_put_contents($logFile, "Request URL: $url\n", FILE_APPEND);
        file_put_contents($logFile, "Post data: " . print_r($postData, true) . "\n", FILE_APPEND);
        
        // Create the context for the request
        $options = [
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/x-www-form-urlencoded',
                'content' => http_build_query($postData),
                'timeout' => 15
            ]
        ];
        
        // Create the context stream
        $context = stream_context_create($options);
        
        // Make the request
        $result = @file_get_contents($url, false, $context);
        
        // Check result
        if ($result === FALSE) {
            $error = error_get_last();
            file_put_contents($logFile, "Error: " . print_r($error, true) . "\n", FILE_APPEND);
            
            // Try alternate method with direct URL
            file_put_contents($logFile, "Trying alternate method...\n", FILE_APPEND);
            
            // URL-encode the message for GET request
            $encodedMessage = urlencode($message);
            $altUrl = "https://api.telegram.org/bot{$botToken}/sendMessage?chat_id={$chatId}&text={$encodedMessage}&parse_mode=HTML&disable_web_page_preview=true";
            
            $result = @file_get_contents($altUrl);
            
            if ($result === FALSE) {
                $error = error_get_last();
                file_put_contents($logFile, "Alternate method error: " . print_r($error, true) . "\n", FILE_APPEND);
                return false;
            }
        }
        
        // Log the result
        file_put_contents($logFile, "API Response: $result\n", FILE_APPEND);
        
        // Parse the response
        $response = json_decode($result, true);
        
        // Check if successful
        $success = isset($response['ok']) && $response['ok'] === true;
        file_put_contents($logFile, "Success: " . ($success ? "Yes" : "No") . "\n\n", FILE_APPEND);
        
        return $success;
    }

    // Return JSON response
    echo json_encode($response);
?>
