<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
// Токены для подключения к GLPI
$app_token = 'XXXXXXXX';
$user_app_token ='XXXXXXXX';
// Данные для ДБ glpi-test
$pass = "XXXXXXXX";
$user = "XXXXXXXX";
// Данные для ДБ затрат
$pass_costs = "XXXXXXXX";
$user_costs = "XXXXXXXX";

//Подключение к БД затрат, получение id последней отправленной затраты
$dbh_id = new PDO('mysql:host=localhost;dbname=office_glpi_costs', $user_costs, $pass_costs);
$conn_id = $dbh_id->prepare('SELECT id, id_of_last_cost FROM id_of_last_cost');
$conn_id->execute();
$result_id = $conn_id->fetch(PDO::FETCH_ASSOC);
$data_for_id_new = $result_id['id_of_last_cost'];

// Подключение к БД glpi-test, получение id затраты и заявки
$dbh = new PDO('mysql:host=localhost;dbname=office_glpi_test', $user, $pass);
$conn = $dbh->prepare('SELECT id, tickets_id, name, comment, actiontime, cost_time, cost_fixed, cost_material, budgets_id FROM glpi_ticketcosts');
$conn->execute();
$result = $conn->fetchAll();

// Открываем foreach, чтобы пройти по всем затратам
foreach($result as $key => $value) {
    $newID = $value['id'];
    $ticket_id = $value['tickets_id'];

    // Проверка, если id обрабатываемой заявки меньше id последней обработанной заявки из БД, переходим к след.id
    if ($newID > $data_for_id_new) {

        // Инициализация пользовательской сессии
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

        $name = $result[$key]['name'];
        $comment = $result[$key]['comment'];
        $actiontime = $result[$key]['actiontime'];
        $cost_time = $result[$key]['cost_time'];
        $cost_fixed = $result[$key]['cost_fixed'];
        $cost_material = $result[$key]['cost_material'];
        $budgets_id = $result[$key]['budgets_id'];

        // Обновляю id последней обработанной заявки в БД
        $conn_new_id = $dbh_id->prepare('UPDATE id_of_last_cost SET id_of_last_cost =:id WHERE id = "1"');
        $conn_new_id->bindParam(":id", $newID);
        $conn_new_id->execute();
        $result_new_id = $conn_new_id->fetch(PDO::FETCH_ASSOC);
        $new_id_cost = $result_new_id['id_of_last_cost'];

        //Получаю имя инициатора заявки
        if ($curl = curl_init()) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'App-Token: ' . $app_token,
                'Session-token: ' . $session_token));
            curl_setopt($curl, CURLOPT_URL, 'https://glpi-test.ru/apirest.php/Ticket/' . $ticket_id . '?expand_dropdowns=true');
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            $out = curl_exec($curl);
            $ticket_info = json_decode($out, true);
            $user = $ticket_info['users_id_recipient'];
            curl_close($curl);
        }

        // Достаю из БД id пользователя инициатора заявки
        $conn_users = $dbh->prepare('SELECT id, name, phone FROM glpi_users WHERE name=:user');
        $conn_users->bindParam(":user", $user);
        $conn_users->execute();
        $result_users = $conn_users->fetchAll();
        $user_ids = $result_users[0]['id'];

        // Номер телефона инициатора заявки
        if ($curl = curl_init()) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'App-Token: ' . $app_token,
                'Session-token: ' . $session_token));
            curl_setopt($curl, CURLOPT_URL, 'https://glpi-test.ru/apirest.php/User/' . $user_ids);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            $out = curl_exec($curl);
            $ticket_info = json_decode($out, true);
            $user_phone = $ticket_info['phone'];
            $user_id = $ticket_info['id'];
            curl_close($curl);
        }

        // Записываю содержимое комментария
        $json1 = array(
            'input' => array(
                'itemtype' => 'Ticket',
                'items_id' => $ticket_id,
                'users_id' => $user_id,
                'requesttypes_id' => '2',
                'content' => 'Новая затрата: <br>' . 'Наименование: ' . $name . '<br>' . 'Комментарий: ' . $comment . '<br>' . 'Продолжительность: ' . $actiontime . "<br>" . "Стоимость 1 часа: " . $cost_time . "<br>"
                    . "Фиксированная стоимость: " . $cost_fixed . "<br>" . "Стоимость материалов: " . $cost_material)
        );

        // Подключаеюсь к БД затрат
        $dbh_costs = new PDO('mysql:host=localhost;dbname=office_glpi_costs', $user_costs, $pass_costs);
        // Получаю id чата для номера телефона
        $conn_chat_ids = $dbh_costs->prepare('SELECT number, chat_id FROM chat_ids WHERE number =:user_phone');
        $conn_chat_ids->bindParam(":user_phone", $user_phone);
        $conn_chat_ids->execute();
        $result_chat_ids = $conn_chat_ids->fetchAll();
        $phone_number = $result_chat_ids[0]['number'];

        $conn_email = $dbh->prepare('SELECT users_id, email FROM glpi_useremails WHERE users_id =:user_id');
        $conn_email->bindParam(":user_id", $user_id);
        $conn_email->execute();
        $result_email = $conn_email->fetchAll();
        $user_email = $result_email[0]['email'];

        //Если телефон есть, записываем id чата
        if (($phone_number == $user_phone) && ($phone_number != '')) {
            $chatId = $result_chat_ids[0]['chat_id'];
            // Записываю содержимое сообщения в телеграм
            $response = "Подтвердите затрату:
            Наименование: " . $name . ". " .
                "Комментарий: " . $comment . ". " .
                "Продолжительность: " . $actiontime . ". " .
                "Стоимость 1 часа: " . $cost_time . ". " .
                "Фиксированная стоимость: " . $cost_fixed . ". " .
                "Стоимость материалов: " . $cost_material. '(' . $name . ')';

            // Формирую кнопки
            $keyboard = [
                'inline_keyboard' => [
                    [
                        ['text' => 'Подтвердить выполнение работы (' . $name . ') ', 'callback_data' => 'Подтвердить выполнение работы'],
                        ['text' => 'Необходимы доработки (' . $name . ') ', 'callback_data' => 'Необходимы доработки']
                    ]
                ]
            ];
            $encodedKeyboard = json_encode($keyboard);

            // Отправляю сообщение в телеграм о новой затрате
            $parameters =
                array(
                    'chat_id' => $chatId,
                    'text' => $response,
                    'reply_markup' => $encodedKeyboard
                );

            $url = "https://api.telegram.org/botXXXXX:XXXXXXXXX/sendMessage";

            if (!$curld = curl_init()) {
                exit;
            }
            curl_setopt($curld, CURLOPT_POST, true);
            curl_setopt($curld, CURLOPT_POSTFIELDS, $parameters);
            curl_setopt($curld, CURLOPT_URL, $url);
            curl_setopt($curld, CURLOPT_RETURNTRANSFER, true);
            $output = curl_exec($curld);
            curl_close($curld);

            // Создаю комментарий в GLPI
            if ($curl = curl_init()) {
                curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'App-Token: ' . $app_token, 'Session-token: ' . $session_token));
                curl_setopt($curl, CURLOPT_URL, 'https://glpi-test.ru/apirest.php/Ticket/' . $ticket_id . '/ITILFollowup');
                curl_setopt($curl, CURLOPT_POST, true);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($json1));
                $out = curl_exec($curl);
                curl_close($curl);
            }

            // Записываю в БД данные о заявке и пользователе
            $conn = $dbh_costs->prepare('INSERT INTO users (users_id, number, email, chat_id, ticket_id, description) VALUES (:user_id, :user_phone, :user_email, :chatId, :tickets_id, :name)');
            $conn->bindParam(":user_id", $user_id);
            $conn->bindParam(":user_phone", $user_phone);
            $conn->bindParam(":user_email", $user_email);
            $conn->bindParam(":chatId", $chatId);
            $conn->bindParam(":tickets_id", $ticket_id);
            $conn->bindParam(":name", $name);
            $conn->execute();

            // Закрытие сессии
            if ($curl = curl_init()) {
                curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'App-Token: ' . $app_token, 'Session-token: ' . $session_token));
                curl_setopt($curl, CURLOPT_URL, 'https://glpi-test.ru/apirest.php/killSession');
                curl_setopt($curl, CURLOPT_POST, false);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                $out = curl_exec($curl);
                curl_close($curl);
            }
            $count++;
        } // Если есть email, но нет номера
        elseif ($user_email != '') {
            // Записываю в БД данные о заявке и пользователе
            $conn = $dbh_costs->prepare('INSERT INTO email_users (users_id, email, ticket_id, description) VALUES (:user_id, :user_email, :tickets_id, :name)');
            $conn->bindParam(":user_id", $user_id);
            $conn->bindParam(":user_email", $user_email);
            $conn->bindParam(":tickets_id", $ticket_id);
            $conn->bindParam(":name", $name);
            $conn->execute();

            $conn_email_cost = $dbh_costs->prepare('SELECT id FROM email_users WHERE email=:user_email AND description=:name');
            $conn_email_cost->bindParam(":user_email", $user_email);
            $conn_email_cost->bindParam(":name", $name);
            $conn_email_cost->execute();
            $result_email_cost = $conn_email_cost->fetchAll();
            $cost_id = $result_email_cost[0]['id'];
            $hash = md5($cost_id);

            $conn_email_cost = $dbh_costs->prepare('UPDATE email_users SET hash=:hash WHERE email=:user_email AND description=:name');
            $conn_email_cost->bindParam(":user_email", $user_email);
            $conn_email_cost->bindParam(":name", $name);
            $conn_email_cost->bindParam(":hash", $hash);
            $conn_email_cost->execute();

            try {
                $max_work_time_in_second = 60;
                $start_time = time();
                require __DIR__.'/phpmailer/src/Exception.php';
                require __DIR__.'/phpmailer/src/PHPMailer.php';
                require __DIR__.'/phpmailer/src/SMTP.php';

                $mail = new PHPMailer(true);
                try {
                    $mail->SMTPDebug = SMTP::DEBUG_SERVER;                      //Enable verbose debug output
                    $mail->isSMTP();                                            //Send using SMTP
                    $mail->Host = 'mail@gmail.com';                     //Set the SMTP server to send through
                    $mail->SMTPAuth = true;                                   //Enable SMTP authentication
                    $mail->Username = 'mail@gmail.com';                     //SMTP username
                    $mail->Password = 'XXXXXXX';                               //SMTP password
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;            //Enable implicit TLS encryption
                    $mail->Port = 465;                                    //TCP port to connect to; use 587 if you have set `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`

                    $mail->setFrom('mail@gmail.com', 'mail@gmail.com');
                    $mail->addAddress($user_email, 'User');     //Add a recipient
                    $mail->addReplyTo($user_email, 'Information');
                    $mail->isHTML(true);                                  //Set email format to HTML
                    $mail->Subject = 'Новое письмо';
                    $body = '<html>
                        <head><title>Подтверждение затраты</title>
                        <meta charset="utf-8">
                            <meta name="viewport" content="width=device-width, initial-scale=1">
                            <link rel="stylesheet" href="style.css">
                            <style>
                            .button_approved {
                            color: #fff; 
                            text-decoration: none; 
                            user-select: none; 
                            background: rgb(127, 206, 81); 
                            padding: .7em 1.5em; 
                            outline: none;
                            border:rgb(127, 206, 81);
                            cursor: pointer;
                        }
                        
                        .button_declined {
                            color: #fff; 
                            text-decoration: none; 
                            user-select: none; 
                            background: rgb(206, 106, 81); 
                            padding: .7em 1.5em; 
                            outline: none;
                            border:rgb(206, 106, 81); 
                            cursor: pointer;
                        }
                        </style>
                        </head>
                        <body>
                            <form method="POST" action="send_answer.php">
                                <p>Подтвердите затрату: <br>
                                    Наименование: '.$name.'. <br>Комментарий: '.$comment.'. <br>Продолжительность: '.$actiontime.'. <br>Стоимость 1 часа:'. $cost_time.'.<br>Фиксированная стоимость: '. $cost_fixed.' . <br>Стоимость материалов: '. $cost_material.' </p>
                            <input type="submit" id="btn_approved" class="button_approved" name="cost_is_approved" onclick="cost_is_approved('.$name.')" value="Подтвердить выполнение работы">
                            <input type="submit" id="btn_declined" class="button_declined" name="cost_is_declined" onclick="cost_is_declined('.$name.')" value="Необходимы доработки">
                            Если вы не видите кнопки, перейдите по ссылке:
                            <a href="https://glpi-test.ru/costs/mail.php?'.$cost_id.'">Ссылка для подтверждения/отклонения затраты</a>
                        </body>
                        </html>';

                    $mail->Body = $body;
                    $mail->send();
                    $mail->ClearAllRecipients();
                } catch (Exception $e) {

                }
            } catch(PDOException $e) {

            }
        }
    }
}
