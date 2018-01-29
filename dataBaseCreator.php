<?php
//jsonファイル新規発行モード 初回に実行して下さい。
function execCreateNewList(){

    $array = [];

    for ($i=0; $i < 10; $i++) { 

        $array["user" .$i]["userName"]      = "サンプル株式会社";
        $array["user" .$i]["userID"]        = "SampleCorp";
        $array["user" .$i]["password"]      = "password";
        $array["user" .$i]["status"]        = "false";
        $array["user" .$i]["status_date"]   = "null";
        $array["user" .$i]["other"]         = "その他備考";

    }

    header('content-type: application/json; charset=utf-8');

    echo json_encode($array, JSON_UNESCAPED_UNICODE);

}

execCreateNewList();