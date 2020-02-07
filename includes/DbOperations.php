<?php

class DbOperations
{

    private $con;

    public function __construct()
    {
        require_once dirname(__FILE__) . "/DbConnect.php";
        $db = new DbConnect;
        $this->con = $db->connect();
    }

    public function saveFile($file, $extension, $captcha_type, $captcha_text)
    {
        $name = round(microtime(true) * 1000) . '.' . $extension;
        $filedest = dirname(__FILE__) . UPLOAD_PATH . $name;
        move_uploaded_file($file, $filedest);

        $stmt = $this->con->prepare("INSERT INTO captchas (image, captcha_type, captcha_text) VALUES (?,?,?)");
        $stmt->bind_param("sss", $name, $captcha_type, $captcha_text);
        if ($stmt->execute()) {
            return true;
        }

        return false;
    }

    public function login($email, $password)
    {


        if ($this->isUserExist($email)) {

            $stmt = $this->con->prepare("select * from users where email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();

            if (password_verify($password, $row["password"])) {

                $user = array();
                $user['userId'] = $row["userId"];
                $user['name'] = $row["name"];
                $user['email'] = $row["email"];
                $user['mobile'] = $row["mobile"];
                $user['message'] = "Login Successfull";
                return $user;
            } else {
                return AUTHENTICATION_FAILED;
            }
        } else {
            return USER_NOT_FOUND;
        }
    }


    public function register($name, $email, $mobile, $password)
    {

        if (!$this->isUserExist($email)) {
            date_default_timezone_set("Asia/Kolkata");
            $createdOn = date('Y-m-d H:i:s');
            $stmt = $this->con->prepare("insert into users (name, email, mobile, password, createdOn) values (?,?,?,?,?)");
            $stmt->bind_param("sssss", $name, $email, $mobile, $password, $createdOn);
            if ($stmt->execute()) {
                return USER_CREATED;
            } else {
                return USER_FAILURE;
            }
        } else {
            return USER_EXISTS;
        }
    }

    public function createRoom($name, $gameType, $smallBlind, $maxPlayers)
    {

        date_default_timezone_set("Asia/Kolkata");
        $createdOn = date('Y-m-d H:i:s');
        $stmt = $this->con->prepare("insert into poker_rooms (name, gameType, smallBlind, maxPlayers, createdOn) values (?,?,?,?,?)");
        $stmt->bind_param("sssss", $name, $gameType, $smallBlind, $maxPlayers, $createdOn);
        if ($stmt->execute()) {
            return ROOM_CREATED;
        } else {
            return ROOM_FAILURE;
        }
    }



    public function getOrderHistory($user_id)
    {

        $stmt = $this->con->prepare("select id, order_date, approval_date, total_earning, paid_amount, status from order_history where user_id = ? order by id desc");
        $stmt->bind_param("s", $user_id);
        $stmt->execute();
        $stmt->bind_result($id, $order_date, $approval_date, $total_earning, $paid_amount, $status);
        $orders = array();
        while ($stmt->fetch()) {
            $order = array();
            $order['id'] = $id;
            $order['order_date'] = $order_date;
            $order['approval_date'] = $approval_date;
            $order['total_earning'] = $total_earning;
            $order['paid_amount'] = $paid_amount;
            $order['status'] = $status;
            array_push($orders, $order);
        }
        return $orders;
    }

    public function getMessages()
    {

        $stmt = $this->con->prepare("select id, title, body, date_time from messages order by id desc limit 10");
        $stmt->execute();
        $stmt->bind_result($id, $title, $body, $date_time);
        $messages = array();
        while ($stmt->fetch()) {
            $message = array();
            $message['id'] = $id;
            $message['title'] = $title;
            $message['body'] = $body;
            $message['date_time'] = $date_time;
            array_push($messages, $message);
        }
        return $messages;
    }


    public function getCaptcha($user_id, $is_right = 0)
    {

        $sql = $this->con->prepare("select terminal, total_earning, right_count from users where user_id = ?");
        $sql->bind_param("s", $user_id);
        $sql->execute();
        $result = $sql->get_result();
        $row = $result->fetch_assoc();
        $terminal = $row['terminal'];
        $total_earning = $row['total_earning'];
        $temp_right_count = $row['right_count'];

        if ($total_earning <= 2) {
            $terminal = "1";
        }
        if ($total_earning > 2 && $total_earning <= 5) {
            $terminal = 1;
            if ($temp_right_count % 3 == 0) {
                $terminal = "2";
            }
        }

        $stmt = $this->con->prepare("select id, image, captcha_type from captchas order by RAND() limit 1");

        //$stmt = $this->con->prepare("SELECT * FROM captchas AS r1 JOIN(SELECT CEIL(RAND() * (SELECT MAX(id) FROM captchas where terminal in (?))) AS id) AS r2 WHERE r1.id >= r2.id ORDER BY r1.id ASC LIMIT 1");

        $stmt->execute();
        $result2 = $stmt->get_result();
        $row2 = $result2->fetch_assoc();

        $sql2 = $this->con->prepare("select right_count, wrong_count, skip_count from users where user_id = ?");
        $sql2->bind_param("s", $user_id);
        $sql2->execute();
        $result3 = $sql2->get_result();
        $row3 = $result3->fetch_assoc();

        $captcha = array();

        $captcha['id'] = $row2["id"];
        $captcha['image'] = $row2["image"];
        $captcha['captcha_type'] = $row2["captcha_type"];
        $captcha['right_count'] = $row3["right_count"];
        $captcha['wrong_count'] = $row3["wrong_count"];
        $captcha['skip_count'] = $row3["skip_count"];
        $captcha['is_right'] = $is_right;

        return $captcha;
    }

    public function skipCaptcha($user_id)
    {

        $stmt = $this->con->prepare("update users set skip_count = skip_count+1 where user_id = ?");
        $stmt->bind_param("s", $user_id);
        if ($stmt->execute()) {
        }

        return $this->getCaptcha($user_id);
    }

    public function submitCaptcha($user_id, $captcha_id, $captcha_text, $captcha_type)
    {
        $is_right = 0;

        $sql = $this->con->prepare("select captcha_text from captchas where id = ?");
        $sql->bind_param("s", $captcha_id);
        $sql->execute();
        $result = $sql->get_result();
        $row = $result->fetch_assoc();

        $captcha_text_orig = $row['captcha_text'];

        if ($captcha_type == "Case Sensitive") {

            if ($captcha_text_orig == $captcha_text) {

                $stmt = $this->con->prepare("update users set right_count = right_count+1 where user_id = ?");
                $stmt->bind_param("s", $user_id);
                if ($stmt->execute()) {

                    $is_right = 1;
                }
            } else {
                $stmt = $this->con->prepare("update users set wrong_count = wrong_count+1 where user_id = ?");
                $stmt->bind_param("s", $user_id);
                if ($stmt->execute()) {
                }
            }
        } else {

            if (!strcasecmp($captcha_text_orig, $captcha_text)) {

                $stmt = $this->con->prepare("update users set right_count = right_count+1 where user_id = ?");
                $stmt->bind_param("s", $user_id);
                if ($stmt->execute()) {

                    $is_right = 1;
                }
            } else {
                $stmt = $this->con->prepare("update users set wrong_count = wrong_count+1 where user_id = ?");
                $stmt->bind_param("s", $user_id);
                if ($stmt->execute()) {
                }
            }
        }

        return $this->getCaptcha($user_id, $is_right);
    }


    public function deleteUser($user_id)
    {
        $stmt = $this->con->prepare("delete from users where user_id = ?");
        $stmt->bind_param("s", $user_id);
        if ($stmt->execute()) {
            return USER_DELETED;
        } else {
            return USER_NOT_DELETED;
        }
    }


    private function isUserExist($user_id)
    {

        $stmt = $this->con->prepare("select userId from users where email = ?");
        $stmt->bind_param("s", $user_id);
        $stmt->execute();
        $stmt->store_result();
        return $stmt->num_rows > 0;
    }

    private function isAppLocked()
    {

        $stmt = $this->con->prepare("select web_status from websitestatus");
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        if ($row['web_status'] == 0)
            return true;
        else return false;
    }
}
