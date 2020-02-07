<?php

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

require '../vendor/autoload.php';
require '../includes/DbOperations.php';

$app = new \Slim\App;


$app->post('/login', function (Request $request, Response $response) {

    if (!haveEmptyParameters(array('email', 'password'), $response)) {

        $request_data = $request->getParsedBody();
        $email = $request_data['email'];
        $password = $request_data['password'];

        $db = new DbOperations;
        $result = $db->login($email, $password);

        if ($result == AUTHENTICATION_FAILED) {

            $response_data = array();
            $response_data["error"] = true;
            $response_data["message"] = "Invalid username or password";
            $response->write(json_encode($response_data));
            return $response
                ->withHeader('Content-type', 'application/json')
                ->withStatus(422);
        } else if ($result == USER_NOT_FOUND) {

            $response_data = array();
            $response_data["error"] = true;
            $response_data["message"] = "User not found";
            $response->write(json_encode($response_data));
            return $response
                ->withHeader('Content-type', 'application/json')
                ->withStatus(422);
        } else {

            $response_data = array();
            $response_data["error"] = false;
            $response_data["message"] = $result;
            $response->write(json_encode($result));
            return $response
                ->withHeader('Content-type', 'application/json')
                ->withStatus(201);
        }
    }
});



$app->post('/register', function (Request $request, Response $response) {

    if (!haveEmptyParameters(array('name', 'email', 'mobile', 'password'), $response)) {

        $request_data = $request->getParsedBody();

        $name = $request_data['name'];
        $email = $request_data['email'];
        $mobile = $request_data['mobile'];
        $password = $request_data['password'];
        $hash_password = password_hash($password, PASSWORD_DEFAULT);

        $db = new DbOperations;
        $result = $db->register($name, $email, $mobile, $hash_password);
        if ($result == USER_CREATED) {
            $response_data = array();
            $response_data["error"] = false;
            $response_data["message"] = "User registered successfully";
            $response->write(json_encode($response_data));
            return $response
                ->withHeader('Content-type', 'application/json')
                ->withStatus(201);
        } else if ($result == USER_FAILURE) {
            $response_data = array();
            $response_data["error"] = true;
            $response_data["message"] = "User not registered";
            $response->write(json_encode($response_data));
            return $response
                ->withHeader('Content-type', 'application/json')
                ->withStatus(422);
        } else if ($result == USER_EXISTS) {
            $response_data = array();
            $response_data["error"] = true;
            $response_data["message"] = "User already exists";
            $response->write(json_encode($response_data));
            return $response
                ->withHeader('Content-type', 'application/json')
                ->withStatus(422);
        }
    }
});

$app->post('/createRoom', function (Request $request, Response $response) {

    if (!haveEmptyParameters(array('name', 'gameType', 'smallBlind', 'maxPlayers'), $response)) {

        $request_data = $request->getParsedBody();

        $name = $request_data['name'];
        $gameType = $request_data['gameType'];
        $smallBlind = $request_data['smallBlind'];
        $maxPlayers = $request_data['maxPlayers'];

        $db = new DbOperations;
        $result = $db->createRoom($name, $gameType, $smallBlind, $maxPlayers);
        if ($result == ROOM_CREATED) {
            $response_data = array();
            $response_data["error"] = false;
            $response_data["message"] = "Room created successfully";
            $response->write(json_encode($response_data));
            return $response
                ->withHeader('Content-type', 'application/json')
                ->withStatus(201);
        } else if ($result == ROOM_FAILURE) {
            $response_data = array();
            $response_data["error"] = true;
            $response_data["message"] = "Room not created";
            $response->write(json_encode($response_data));
            return $response
                ->withHeader('Content-type', 'application/json')
                ->withStatus(422);
        }
    }
});


$app->get('/deleteUser', function (Request $request, Response $response) {

    if (!haveEmptyParameters(array('user_id'), $response)) {

        $request_data = $request->getParsedBody();

        $user_id = $request_data['user_id'];

        $db = new DbOperations;
        $result = $db->deleteUser($user_id);
        if ($result == USER_DELETED) {
            $response_data = array();
            $response_data["error"] = false;
            $response_data["message"] = "User deleted successfully";
            $response->write(json_encode($response_data));
            return $response
                ->withHeader('Content-type', 'application/json')
                ->withStatus(201);
            header("Location: http://www.amazingkart.com/viewuser.php");
            die();
        } else if ($result == USER_NOT_DELETED) {
            $response_data = array();
            $response_data["error"] = true;
            $response_data["message"] = "User not Deleted";
            $response->write(json_encode($response_data));
            return $response
                ->withHeader('Content-type', 'application/json')
                ->withStatus(422);
        }
    }
});

$app->get('/getPokerRooms', function (Request $request, Response $response) {

    $request_data = $request->getParsedBody();
    $db = new DbOperations;
    $messages = $db->getMessages();
    $response_data = array();
    $response_data["error"] = false;
    $response_data["messages"] = $messages;
    $response->write(json_encode($response_data));

    return $response
        ->withHeader('Content-type', 'application/json')
        ->withStatus(200);
});

$app->get('/sayHello', function (Request $request, Response $response) {

   
    $response->write("Hello");

    return $response
        ->withHeader('Content-type', 'application/json')
        ->withStatus(200);
});



$app->post('/getOrderHistory', function (Request $request, Response $response) {

    $request_data = $request->getParsedBody();
    $user_id = $request_data['user_id'];
    $db = new DbOperations;
    $orders = $db->getOrderHistory($user_id);
    $response_data = array();
    $response_data["error"] = false;
    $response_data["orders"] = $orders;
    $response->write(json_encode($response_data));

    return $response
        ->withHeader('Content-type', 'application/json')
        ->withStatus(200);
});


function getFileExtension($file)
{
    $path_parts = pathinfo($file);
    return $path_parts['extension'];
}

function haveEmptyParameters($required_params, $response)
{
    $error = false;
    $error_params = '';
    $request_params = $_REQUEST;

    foreach ($required_params as $param) {
        if (!isset($request_params[$param]) || strlen($request_params[$param]) <= 0) {
            $error = true;
            $error_params .= $param . ',';
        }
    }

    if ($error) {
        $error_detail = array();
        $error_detail['error'] = true;
        $error_detail['message'] = "Required parameters " . substr($error_params, 0, -1) . " are missing";
        $response->write(json_encode($error_detail));
    }

    return $error;
}

$app->run();
