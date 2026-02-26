<?php
session_start();

if(isset($_SESSION['user'])){
    
    switch($_SESSION['user']['role']){
        case 'admin':
            header("Location: admin/admin_dashboard.php");
            break;
        case 'front':
            header("Location: front/front_dashboard.php");
            break;
        case 'kitchen':
            header("Location: kitchen/kitchen_dashboard.php");
            break;
    }

} else {
    header("Location: login.php");
}

exit();