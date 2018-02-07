<?php
session_start();
ini_set('display_errors','on');
error_reporting(-1);
require 'vendor/autoload.php';
function multi_isset(array $check_me,array $values) : bool {
	foreach($values as $check_value){
		if(!in_array($check_value,array_keys($check_me))){
			return false;
		}
	}
	return true;
}
function create_auth_object() : Auth {
	return new Auth([
		'john' => 'password1234',
		'mary' => 'password4321',
		'jane' => 'doedoedoe'
	]);
}
if(Auth::isLoggedIn()){
	Page::redirect('home');
}

if(multi_isset($_POST,['login','user','password'])){
	die(json_encode(
		[
			'login' => create_auth_object()->login($_POST['user'],$_POST['password']) 
		]
		)
	);
}
include('login-page.php');
