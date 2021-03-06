<?php 
require_once __DIR__.'/partials/header.php';

/********************
 * DEBUG
 ********************/

// unset($_SESSION['Step1']);
// unset($_SESSION['Step2']);
// unset($_SESSION['Step3']);

// if(isset($_SESSION['Step1'])){
//     echo 'Step1 : '.$_SESSION['Step1'] .'<br>';
// }

// if(isset($_SESSION['Step2'])){
//     echo 'Step2 : '.$_SESSION['Step2'].'<br>';
// }

// if(isset($_SESSION['Step3'])){
//     echo 'Step3 : '.$_SESSION['Step3'].'<br>';
// }

/**************
 * NOTES
 **************/
//A Chaque étape du changement de pass , une variable session est créé en fonction de l'étape. Ce sont ces étapes qui déterminent l'affichage


/*****************************
 * I DEMANDE DU CODE PAR MAIL
 *****************************/

$demandIsSend = false; // la demande par mail pour changer de mot de passe a été effectuée

if(isset($_POST['demandChangePass'])){
    $demandIsSend = true;
}

if($demandIsSend){

$userInfo = getUserAuthenticatedById($_SESSION['authenticatedUserId']); 

$UserName = $userInfo ['user_name'];
$UserEmail = $userInfo ['user_email'];

// 1.1 Code généré pour demande de changement de mot de passe :
$codeChangePass = str_token(5);

// 1.2 Inscription en base du code
$UpdateDbSql = 'UPDATE `users` SET `confirmation_change_pass` = :confirmation_change_pass  
                WHERE id_user = :id_user';
    
$query = $db->prepare($UpdateDbSql);
$query->bindValue(':id_user', $_SESSION['authenticatedUserId'], PDO::PARAM_INT);
$query->bindValue(':confirmation_change_pass', $codeChangePass , PDO::PARAM_STR);
$query->execute(); //On efface en base le token + on met la date Now de validation

$header ="MIME-Version: 1.0\r\n";
$header.='From:"Bot-location.com"'."\n";
$header.='Content-Type:text/html; charset="uft-8"'."\n";
$header.='Content-Transfer-Encoding: 8bit';

$subject = "Demande de changement de mot de passe";

$message='
<html>
    <body>

        <div align="center">
            <br />
            Demande de modification de mot de passe
            <br />
            <hr>
        </div>

        <div align="center">
           Cher abonné : <strong>'.$UserName .' </strong>
           <br />
           Merci de confirmer votrte demande de modification de mot de passe en recopiant ce code : <strong>'.$codeChangePass.'</strong>                           
           <br />
           <hr>
        </div>
    </body>
</html>
';

$SendEmailOK = mail($UserEmail, $subject , $message, $header);
$_SESSION['Step1'] = true;
$_SESSION['flash']['warning'] = "Un code vous a été envoyé sur votre mail";
$db = null;
header('Location: http://localhost/dev-bases/php/projects/bateaux/modifyPassUser.php');
exit();
}

/*************************
 * II VERIFICATION DU CODE
 *************************/

$codeIsTest = $codeIsValid = false;
$codeContent = null;  

if(isset($_POST['CodeIsTest'])){
    $codeIsTest = true;
}

if(!empty($_POST['CodeIsTest'])){
    $codeContent = $_POST['CodeIsTest'];
}

if($codeIsTest){

    $userInfo = getUserAuthenticatedById($_SESSION['authenticatedUserId']); 

    if($codeContent === $userInfo['confirmation_change_pass']){
        $_SESSION['Step1'] = false; // La demande est effectuée . on remet à false (utile pour permettre l'affichage par étapes)
        $_SESSION['Step2'] = true;
        $_SESSION['flash']['success'] = "Le code est correct, vous pouvez changer votre password";
        $db = null;
        header('Location: http://localhost/dev-bases/php/projects/bateaux/modifyPassUser.php');
        exit();
    }
}

/***********************************
 * III VERIFICATION DU NEW PASSWORD
 ***********************************/
//Tableau d'erreurs lors de la vérification du formulaire
$errorsArray = [];
$formIsValid = $formIsSend = false;
$password = $verifPassword = null;  

// 1. Si le formulaire a été envoyé, les variables = les posts
if(isset($_POST['NewPassword']) && isset($_POST['NewPasswordVerif']) ){
    $password = $_POST['NewPassword'];
    $verifPassword =  $_POST['NewPasswordVerif'];
}


// 2 .Si le formualire a été envoyé ,on peut le vérifier
if(isset($password, $verifPassword )){
    $formIsSend = true;
}

if($formIsSend){

    $formIsValid = true;

    if(strlen($password)<3){
        $formIsValid = false;
        $errorsArray['nameLengh'] = "Le passeword doit contenir au moins 3 caratères";  
    }

    if($password != $verifPassword) {
        $formIsValid = false;
        $errorsArray['verifPassword'] = "Le password n'est pas identique au pass envoyé";  
    }
}

if($formIsValid){

    $UpdateDbSql = 'UPDATE `users` SET `user_password` = :user_password , `confirmation_change_pass` = NULL , `last_change_pass_date` = NOW()
                    WHERE id_user = :id_user';

    $query = $db->prepare($UpdateDbSql);
    $query->bindValue(':id_user', $_SESSION['authenticatedUserId'], PDO::PARAM_INT);

    $password = password_hash($password, PASSWORD_BCRYPT);
    $query->bindValue(':user_password', $password, PDO::PARAM_STR);
    
    $query->execute();

    if(isset($_COOKIE['userIdAuth'])){
        
        // comme il y a modification du pass , la clé de l'autolog est changée
        // j'efface le cookie correspondant à l'ancien mot de passe
        setcookie('userIdAuth', '', time() -3600, null,null,false,true);
        $userInfo = getUserAuthenticatedById($_SESSION['authenticatedUserId']); 
        
        $cryptageCookie = $userInfo['id_user'].'---'.sha1($userInfo['user_name'].$userInfo['user_password'].$_SERVER['REMOTE_ADDR']);
        
        // puis je set le newcookie avec la nouvelle clé contenant le new password
        setcookie('userIdAuth', $cryptageCookie , time()+365*24*3600, null, null, false, true);
        
        $_SESSION['flash']['success'] = "Votre mot de passe est modifié";
        header('Location: http://localhost/dev-bases/php/projects/bateaux/accountUser.php');
    }

    $db = null; 
    
    // dans le cas d'un utilsateur sans cookie
    $_SESSION['Step2'] = false;
    $_SESSION['Step3'] = true;
    $_SESSION['flash']['success'] = "Votre mot de passe est modifié";
    unset($_SESSION['Step1']);
    unset($_SESSION['Step2']);
    unset($_SESSION['Step3']);
    header('Location: http://localhost/dev-bases/php/projects/bateaux/accountUser.php');
}


require_once __DIR__.'/view/modifyPassUserView.php';
require_once __DIR__.'/partials/footer.php';