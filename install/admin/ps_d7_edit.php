<?php

$path = '/modules/ps.d7/admin/edit.php';
$folders = ['local', 'bitrix'];

foreach ($folders as $folder) {
    if (file_exists($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . $folder . $path)) {
        require_once $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . $folder . $path;
        break;
    }
}