<?php

// ARTICLE API

include('../config/connection.php');
include('../functions.php');

header("Access-Control-Allow-Origin: *");
header('Content-Type: application/json');

// FETCHING POST
if ($_SERVER['REQUEST_METHOD']=="GET"){
    // SPECIFIC ARTICLE
    if (isset($_GET['article_id'])){
        $article_id = htmlspecialchars($conn->real_escape_string($_GET['article_id']));
        $sql = "SELECT * FROM op_articles WHERE status='published' AND id='$article_id' ORDER BY up_timestamp DESC";
    }
    // START AND LIMIT GETTING ARTICLE
    elseif ((isset($_GET['start']))&&(isset($_GET['limit']))) {
        $start = $conn->real_escape_string($_GET['start']);
        $limit = $conn->real_escape_string($_GET['limit']);
        $sql = "SELECT * FROM op_articles WHERE status='published' AND ".$start." >= up_timestamp ORDER BY up_timestamp DESC LIMIT ".$limit;
    }
    // ALL ARTICLE
    else {
        $sql = "SELECT * FROM op_articles WHERE status='published' ORDER BY up_timestamp DESC";
    }
    $result = $conn->query($sql);

    // CHECK IF THERE IS ARTICLES
    if ($result->num_rows > 0){
        while($row = $result->fetch_assoc()){
            $user_id = $row['user_id'];
            $readableDateTime = date("F d, Y g:i A",$row['up_timestamp']);
            $row['date_time'] = $readableDateTime;
            $row['body'] = htmlspecialchars_decode($row['body']);
            $getUserSql = "SELECT id,fname,lname,course,dept FROM op_users WHERE id='$user_id' LIMIT 1";
            $getUserResult = $conn->query($getUserSql) or die($conn->error());
            if ($getUserResult->num_rows > 0){
                unset($row['user_id']);
                $user_details = $getUserResult->fetch_assoc();
                $row['user_details'] = $user_details;
            }
            $response[] = $row;
        }
    }
    else {
        $response = array("message" => "No Articles Available", "status" => "no_content");
    }

    if (isset($_GET['count'])){
        if ($_GET['count']=="true") {
            $response[] = count($response);
        }
        elseif ($_GET['count']=="only_count") {
            $response = array("article_count" => count($response));
        }
    }
    showResponse($response);
}

// NEW POST
elseif ($_SERVER['REQUEST_METHOD']=="POST"){
    if ((isset($_POST['access_token']))&&(isset($_POST['title']))&&(isset($_POST['body'])&&(isset($_POST['status']))&&(isset($_POST['category'])))){
        $access_token = htmlspecialchars($conn->real_escape_string($_POST['access_token']));
        $sql = "SELECT * FROM op_tokens WHERE token='$access_token' LIMIT 1";
        $result = $conn->query($sql);
        if ($result->num_rows > 0){
            $token = $result->fetch_assoc();
            $user_id = $token['user_id'];
            $title = htmlspecialchars($conn->real_escape_string($_POST['title']));
            $category = htmlspecialchars($conn->real_escape_string($_POST['category']));
            $status = htmlspecialchars($conn->real_escape_string($_POST['status']));
            $body = htmlspecialchars($conn->real_escape_string($_POST['body']));
            $time = time();
            $sql = "INSERT INTO op_articles (user_id,title,body,category,status,up_timestamp) VALUES ('$user_id','$title','$body','$category','$status','$time')";
            if ($conn->query($sql) or die ($conn->error)) {
                $response = array("message" => "Added Successfully", "status" => "success_add");
            }
            else {
                $response = array("message" => "Failed to Add due to Server Error", "status" => "server_error");
            }
        }
        else {
            $response = array("message" => "Denied Access", "status" => "no_access");
        }
    }
    else{
        $response = array("message" => "Required Parameters (access_token,title,body,category,status)", "status" => "no_parameters");
    }
    showResponse($response);
}

// EDIT POST
elseif ($_SERVER['REQUEST_METHOD']=="PUT"){
    $data = file_get_contents('php://input');
    $_PUT = array();
    parse_str($data,$_PUT);

    if ((isset($_PUT['access_token']))&&(isset($_PUT['title']))&&(isset($_PUT['body'])&&(isset($_PUT['status']))&&(isset($_PUT['category']))&&(isset($_PUT['article_id'])))){
        $access_token = htmlspecialchars($conn->real_escape_string($_PUT['access_token']));
        $sql = "SELECT * FROM op_tokens WHERE token='$access_token' LIMIT 1";
        $result = $conn->query($sql);
        if ($result->num_rows > 0){
            $token = $result->fetch_assoc();
            $user_id = $token['user_id'];
            $article_id = htmlspecialchars($conn->real_escape_string($_PUT['article_id']));
            $sql = "SELECT * FROM op_articles WHERE id='$article_id' AND user_id='$user_id' LIMIT 1";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                $title = htmlspecialchars($conn->real_escape_string($_PUT['title']));
                $status = htmlspecialchars($conn->real_escape_string($_PUT['status']));
                $category = htmlspecialchars($conn->real_escape_string($_PUT['category']));
                $body = htmlspecialchars($conn->real_escape_string($_PUT['body']));
                $time = time();
                $sql = "UPDATE op_articles SET title='$title',status='$status',body='$body',category='$category',up_timestamp='$time' WHERE id='$article_id'";
                if ($conn->query($sql)) {
                    $response = array("message" => "Updated Successfully", "status" => "success_update");
                }
                else {
                    $response = array("message" => "Failed to Update due to Server Error", "status" => "server_error");
                }
            }
            else {
                $response = array("message" => "Denied Access", "status" => "no_access");
            }
        }
        else {
            $response = array("message" => "Denied Access", "status" => "no_access");
        }
    }
    else{
        $response = array("message" => "Required Parameters (access_token,article_id,title,body,status)", "status" => "no_parameters");
    }
    showResponse($response);
}

// DELETE POST
elseif ($_SERVER['REQUEST_METHOD']=="DELETE"){
    // GET DELETE REQUEST DATA
    $data = file_get_contents('php://input');
    $_DELETE = array();
    parse_str($data,$_DELETE);

    // CHECK IF article_id AND access_token IS EXISTING
    if ((isset($_DELETE['article_id']))&&(isset($_DELETE['access_token']))){
        $access_token = htmlspecialchars($conn->real_escape_string($_DELETE['access_token']));
        $article_id = htmlspecialchars($conn->real_escape_string($_DELETE['article_id']));
        // CHECK ARTICLE IF EXISTING
        $sql = "SELECT * FROM op_articles WHERE id='$article_id' LIMIT 1";
        $result = $conn->query($sql);
        if ($result->num_rows > 0){
            // CHECK TOKEN IF EXISTING
            $article = $result->fetch_assoc();
            $sql = "SELECT * FROM op_tokens WHERE token='$access_token' LIMIT 1";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                $token = $result->fetch_assoc();
                // CHECK IF IT HAS ACCESS TO THE ARTICLE
                if ($token['user_id']==$article['user_id']){
                    $sql = "DELETE FROM op_articles WHERE id='$article_id'";
                    if ($conn->query($sql)){
                        $response = array("message" => "Deleted Successfully", "status" => "success_delete");
                    }
                    else {
                        $response = array("message" => "Failed to Delete due to Server Error", "status" => "server_error");
                    }
                }
                else {
                    $response = array("message" => "Denied Access", "status" => "no_access");
                }
            }
            else {
                $response = array("message" => "Denied Access", "status" => "no_access");
            }
        }
        else {
            $response = array("message" => "No Article Found", "status" => "no_article");
        }
    }

    else {
        if (isset($_DELETE['article_id'])){
            $response = array("message" => "Required Access Token", "status" => "no_access_token");
        }
        elseif (isset($_DELETE['access_token'])){
            $response = array("message" => "Required Article ID", "status" => "no_target_article_id");
        }
        else {
            $response = array("message" => "Required Parameters (article_id,access_token)", "status" => "no_parameters");
        }
    }
    showResponse($response);
}


$conn->close();
?>