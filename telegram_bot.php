<?php
// Токены для подключения к GLPI
$app_token = 'XXXXXXXXXXXXX';
$user_app_token ='XXXXXXXXXXXXX';
// Данные для ДБ затрат
$pass_costs = "XXXXXXXXXXXXX";
$user_costs = "XXXXXXXXXXXXX";
// Получаю данные, введенные пользователем
$update = json_decode(file_get_contents('php://input'), TRUE);
$txt = serialize($update);
$filename = 'somefile.txt';
file_put_contents($filename, $txt);

// Токен и ссылка на API
$botToken="XXXXXXXX:XXXXXXXXXXXXX";
$website="https://api.telegram.org/bot".$botToken;

// Получаю id чата, имя, фамилию, дату, текст сообщения в телеграме, текст, введенный пользователем
$user_chatId = $update['callback_query']['message']['chat']['id'];
$user_chatId1 = $update['message']['chat']['id'];
$user_first_name = $update['callback_query']['message']['chat']['first_name'];
$user_last_name = $update['callback_query']['message']['chat']['last_name'];
$date = $update['callback_query']['message']['date'];
$str = $update['callback_query']['message']['text'];
$messageText = $update["message"]["text"];
$user_phone = $update["message"]["contact"]["phone_number"];

// Перевожу UNIX в дату
$d = date("Y-m-d\TH:i:s\Z", $date);

// Достаю из текста сообщения текст в скобочках(наименование затраты)
$pattern = '~\(\K.+?(?=\))~';
preg_match_all($pattern, $str, $arr);
$text = $arr[0][0];
//$text = "Общие работы";
// $pieces = explode( "(" , $str);
// $text = $pieces[1];

// Подключаюсь к БД затрат, достаю id пользователя и заявки, где id чата и наименование затраты совпадают
$dbh = new PDO('mysql:host=localhost;dbname=office_glpi_costs', $user_costs, $pass_costs);
$conn = $dbh->prepare('SELECT users_id, number, ticket_id, description FROM users WHERE chat_id=:user_chatId AND description=:text');
$conn->bindParam(":user_chatId", $user_chatId);
$conn->bindParam(":text", $text);
$conn->execute();
$result = $conn->fetch(PDO::FETCH_ASSOC);
$ID_of_user = $result['users_id'];
$ticket_id = $result['ticket_id'];


// Если пользователь нажимает кнопку "Старт", записываем id чата в БД затрат
if($messageText == "/start") {
    $parameters =
        array(
            'chat_id' => $user_chatId1,
            'text' => 'Отправьте ваш номер телефона для дальнейшей работы с ботом',
            'reply_markup' => json_encode([
                'keyboard' => [[['text' => 'Отправить номер телефона', 'request_contact' => true]]]
            ])
        );

    $url = "https://api.telegram.org/bot'.$botToken.'/sendMessage";

    if (!$curld = curl_init()) {
        exit;
    }
    curl_setopt($curld, CURLOPT_POST, true);
    curl_setopt($curld, CURLOPT_POSTFIELDS, $parameters);
    curl_setopt($curld, CURLOPT_URL, $url);
    curl_setopt($curld, CURLOPT_RETURNTRANSFER, true);
    $output = curl_exec($curld);
    curl_close($curld);
}

if(($messageText != "") && ($messageText != "/start")) {
    $params=[
        'chat_id'=>$user_chatId,
        'text'=>'Спасибо! Заявка была отправлена в работу',
    ];
    // Отправляю комментарий в GLPI
    $ch = curl_init($website . '/sendMessage');
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, ($params));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $result = curl_exec($ch);

    if ($result === FALSE) {
        echo 'An error has occured: ' . curl_error($ch) . PHP_EOL;
    }
    else {
        echo $result;
    }
    curl_close($ch);

    $json1 = array(
        'input' => array(
            'itemtype' => 'Ticket',
            'items_id' => $ticket_id,
            'users_id' => $ID_of_user,
            'content' => $user_last_name. " ". $user_first_name. ' отклонила подтверждение выполнения работ. Наименование: '.$text.'. Причина: '. $messageText .'. Время подтверждения: '.$d. 'Text: '. $text)
    );

    // Создаю комментарий в GLPI
    if ($curl = curl_init()) {
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'App-Token: ' . $app_token, 'Session-token: ' . $session_token));
        curl_setopt($curl, CURLOPT_URL, 'https://glpi-test.ru/apirest.php/Ticket/'.$ticket_id.'/ITILFollowup');
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($json1));
        $out = curl_exec($curl);
        curl_close($curl);
    }
}

if(isset($update["message"]["contact"]["phone_number"])) {
    $conn_new_user = $dbh->prepare('INSERT INTO chat_ids (number, chat_id) VALUES (:user_phone, :user_chatId)');
    $conn_new_user->bindParam(":user_phone", $user_phone);
    $conn_new_user->bindParam(":user_chatId", $user_chatId1);
    $conn_new_user->execute();
}

//Инициализация сессии
if ($curl = curl_init()) {
    curl_setopt($curl, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Authorization: user_token ' . $user_app_token,
        'App-Token: ' . $app_token));
    curl_setopt($curl, CURLOPT_URL, 'https://glpi-test.ru/apirest.php/initSession/');
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    $out = curl_exec($curl);
    $session = json_decode($out, true);
    $session_token = $session["session_token"];
    curl_close($curl);
}

// Проверяю, нажал ли пользователь кнопку
if (isset($update['callback_query'])) {
    // Если пользователь нажал кнопку "Подтвердить выполнение работы"
    if ($update['callback_query']['data'] == 'Подтвердить выполнение работы') {

        // Записываю содержимое комментария
        $json1 = array(
            'input' => array(
                'itemtype' => 'Ticket',
                'items_id' => $ticket_id,
                'users_id' => $ID_of_user,
                'content' => $user_last_name. " ". $user_first_name. ' подтвердила выполнение работ. Наименование: '.$text.' Время подтверждения: '.$d. 'Text: '. $text)
        );

        // Создаю комментарий в GLPI
        if ($curl = curl_init()) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'App-Token: ' . $app_token, 'Session-token: ' . $session_token));
            curl_setopt($curl, CURLOPT_URL, 'https://glpi-test.ru/apirest.php/Ticket/'.$ticket_id.'/ITILFollowup');
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($json1));
            $out = curl_exec($curl);
            curl_close($curl);
        }

        $syt = $update['callback_query']['text'];
        // Формирование сообщения для телеграма
        $params=[
            'chat_id'=>$user_chatId,
            'text'=>'Спасибо за обратную связь!'.$find_text,
        ];

        // Отправляю сообщение в телеграм
        $ch = curl_init($website . '/sendMessage');
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, ($params));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $result = curl_exec($ch);

        if ($result === FALSE) {
            echo 'An error has occured: ' . curl_error($ch) . PHP_EOL;
        }
        else {
            echo $result;
        }
        curl_close($ch);
    }
    // Если нажата кнопка "Необходимы доработки"
    elseif ($update['callback_query']['data'] == 'Необходимы доработки') {
        // Формирование текста сообщения и кнопок
        $response = 'Выберите причину отказа/доработки: (' . $text . ')';
        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => 'Причина 1', 'callback_data' => 'Причина 1'],
                    ['text' => 'Причина 2', 'callback_data' => 'Причина 2']
                ]
            ]
        ];
        $encodedKeyboard = json_encode($keyboard);

        //  Отправляю сообщение в телеграм
        $parameters =
            array(
                'chat_id' => $user_chatId,
                'text' => $response,
                'reply_markup' => $encodedKeyboard
            );

        $url = "https://api.telegram.org/bot'.$botToken.'/sendMessage";

        if (!$curld = curl_init()) {
            exit;
        }
        curl_setopt($curld, CURLOPT_POST, true);
        curl_setopt($curld, CURLOPT_POSTFIELDS, $parameters);
        curl_setopt($curld, CURLOPT_URL, $url);
        curl_setopt($curld, CURLOPT_RETURNTRANSFER, true);
        $output = curl_exec($curld);
        curl_close($curld);
    }
    // Если нажата кнопка "Причина 1"
    elseif ($update['callback_query']['data'] == 'Причина 1') {
        //  Формирую сообщение для телеграма
        $params=[
            'chat_id'=>$user_chatId,
            'text'=>'Спасибо! Заявка была отправлена в работу',
        ];
        //  Отправляю сообщение в телеграм
        $ch = curl_init($website . '/sendMessage');
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, ($params));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $result = curl_exec($ch);

        if ($result === FALSE) {
            echo 'An error has occured: ' . curl_error($ch) . PHP_EOL;
        }
        else {
            echo $result;
        }
        curl_close($ch);
        // Формирование комментария для GLPI
        $json1 = array(
            'input' => array(
                'itemtype' => 'Ticket',
                'items_id' => $ticket_id,
                'users_id' => $ID_of_user,
                'content' => $user_last_name. " ". $user_first_name. ' отклонила подтверждение выполнения работ. Наименование: '.$text.'. Причина: '. $update['callback_query']['data'].'. Время подтверждения: '.$d)
        );

        // Отправляю комментарий в GLPI
        if ($curl = curl_init()) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'App-Token: ' . $app_token, 'Session-token: ' . $session_token));
            curl_setopt($curl, CURLOPT_URL, 'https://glpi-test.ru/apirest.php/Ticket/'.$ticket_id.'/ITILFollowup');
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($json1));
            $out = curl_exec($curl);
            curl_close($curl);
        }
    }
    // Если нажата кнопка "Причина 2"
    elseif ($update['callback_query']['data'] == 'Причина 2') {
        //  Формирую сообщение для телеграма
        $params=[
            'chat_id'=>$user_chatId,
            'text'=>'Спасибо! Заявка была отправлена в работу',
        ];
        // Отправляю комментарий в GLPI
        $ch = curl_init($website . '/sendMessage');
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, ($params));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $result = curl_exec($ch);

        if ($result === FALSE) {
            echo 'An error has occured: ' . curl_error($ch) . PHP_EOL;
        }
        else {
            echo $result;
        }
        curl_close($ch);

        // Формирование комментария для GLPI
        $json1 = array(
            'input' => array(
                'itemtype' => 'Ticket',
                'items_id' => $ticket_id,
                'users_id' => $ID_of_user,
                'content' => $user_last_name. " ". $user_first_name. ' отклонила подтверждение выполнения работ. Наименование: '.$text.'. Причина: '. $update['callback_query']['data'].'. Время подтверждения: '.$d. 'Text: '. $text)
        );

        // Создаю комментарий в GLPI
        if ($curl = curl_init()) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'App-Token: ' . $app_token, 'Session-token: ' . $session_token));
            curl_setopt($curl, CURLOPT_URL, 'https://glpi-test.ru/apirest.php/Ticket/'.$ticket_id.'/ITILFollowup');
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($json1));
            $out = curl_exec($curl);
            curl_close($curl);
        }
    }
}

// Закрытие сессии
if ($curl = curl_init()) {
    curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'App-Token: ' . $app_token, 'Session-token: ' . $session_token));
    curl_setopt($curl, CURLOPT_URL, 'https://glpi-test.ru/apirest.php/killSession');
    curl_setopt($curl, CURLOPT_POST, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    $out = curl_exec($curl);
    curl_close($curl);
}
