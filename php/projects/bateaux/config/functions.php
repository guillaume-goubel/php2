<?php


//For Token
function str_token($length){
    $string = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';    
    return substr (str_shuffle(str_repeat($string,$length)) , 0 ,$length ); //1 on repete la chaine , 2 on la mélange , on sélectionne 3 avec sustr offset + nombre de caratères 
}

//For GetUserInfo ( An authenticatedc User)
function getUserAuthenticatedById($userId){

    // require_once __DIR__.'/database.php'; pourquoi cela ne marche pas ?
    
    try{
        $db = new PDO ('mysql:host=localhost;dbname=bateaux;charset=UTF8', 'root' , '');
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE , PDO::FETCH_ASSOC); 
        $db->setAttribute(PDO::ATTR_ERRMODE , PDO::ERRMODE_WARNING); 
    } 
     
     catch(Exception $e){
        echo $e ->getMessage();
        //redirection vers GOOGLE
        header('https://www.google.com/search?q='.$e->getMessage());
    } 
    
     catch(PDOException $e){
        echo "Database connection failed: " . $e->getMessage();
        echo '<img src="assets/images/giphy.gif">';
        die('Aie Aie Aie');
    }

    $queryUserSql = 'SELECT * FROM `users`
                 WHERE id_user = :id_user';

    $query = $db->prepare($queryUserSql);
    $query->bindValue(':id_user', $userId, PDO::PARAM_INT);
    $query->execute();

    return $result = $query->fetch();
    $db = NULL;
}



//For GetUserInfo ( An authenticatedc User)
function getUserAuthenticatedByEmail($userEmail){

    require_once __DIR__.'/database.php'; 
    
    try{
        $db = new PDO ('mysql:host=localhost;dbname=bateaux;charset=UTF8', 'root' , '');
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE , PDO::FETCH_ASSOC); 
        $db->setAttribute(PDO::ATTR_ERRMODE , PDO::ERRMODE_WARNING); 
    } 
     
     catch(Exception $e){
        echo $e ->getMessage();
        //redirection vers GOOGLE
        header('https://www.google.com/search?q='.$e->getMessage());
    } 
    
     catch(PDOException $e){
        echo "Database connection failed: " . $e->getMessage();
        echo '<img src="assets/images/giphy.gif">';
        die('Aie Aie Aie');
    }

    $queryUserSql = 'SELECT * FROM `users`
                 WHERE user_email = :user_email';

    $query = $db->prepare($queryUserSql);
    $query->bindValue(':user_email', $userEmail, PDO::PARAM_STR);
    $query->execute();

    return $result = $query->fetch();
    $db = NULL;
}



function frenchDate(){
    
    date_default_timezone_set('Europe/Paris'); // précision du fuseau horaire de PAris
    $usDate  = new \Datetime();
    return $frenchDate = date("d/m/Y H:i:s");
}