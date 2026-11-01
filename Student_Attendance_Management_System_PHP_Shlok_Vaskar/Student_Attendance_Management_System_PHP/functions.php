<?php
if(session_status()===PHP_SESSION_NONE) session_start();
function h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function token(){if(empty($_SESSION['csrf']))$_SESSION['csrf']=bin2hex(random_bytes(32));return $_SESSION['csrf'];}
function check_token(){if(!hash_equals($_SESSION['csrf']??'',$_POST['csrf']??'')){http_response_code(403);exit('Invalid form token');}}
function msg($type,$text){$_SESSION['flash']=[$type,$text];}
function go($url){header("Location: $url");exit;}
