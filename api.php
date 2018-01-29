<?php

header('Content-type: text/plain; charset=utf-8');

// データベースの読み出し
$userProperties = file_get_contents("./dat/userProperties.json");
$up = json_decode($userProperties, true);
if($up == "NULL"){ die("null"); }

// userIDの取得
$userID 		= $_GET["userID"];

// 文法のチェック
$userIDCheck	= preg_match("/^[1-9]$/", $userID);

// 返り値を渡す -> エラー:null
if(isset($userID) && $userIDCheck){
	echo $up["user".$userID]["status"];
	exit();
}else{
	echo "null";
	exit();
}