<?php

require_once __DIR__.'/config/database.php'; 

/****************************************
 * VARIABLES
 *****************************************/
// On récupère l'id et le token renvoyé via le mail de l'utilisateur
$idToken = $_GET['id'];
$tokenSerial = $_GET['token'];

//A défaut les cookies sont désactivés.le user les active durant la création de compte avec la check-box
$cookieIsAccepted = false;

/****************************************
 * TRAITEMENTS
 ****************************************/
// Selection en base de l'id user
$queryUserSql = 'SELECT * FROM `users`
                 WHERE id_user = :id_user';

$query = $db->prepare($queryUserSql);
$query->bindValue(':id_user', $idToken, PDO::PARAM_INT);
$query->execute();

$result = $query->fetch();
if($result['accept_cookie'] == 1){$cookieIsAccepted = true;} //Si en base accept_cookie = 1 alors c'est que l'utilisateur l'a accpeté lors de la création de compte

// Si les parametres en get === ceux en base, c'est la même personne
if($result['id_user'] === $idToken && $result['confirmation_token'] === $tokenSerial ){
    
    // ici on efface le token et on rajoute la date d'authnetification : le user est authentifié
    $UpdateDbSql = 'UPDATE `users` SET `confirmation_token` = NULL , `confirmed_at` = NOW() 
                    WHERE id_user = :id_user';
    
    $query = $db->prepare($UpdateDbSql);
    $query->bindValue(':id_user', $idToken, PDO::PARAM_INT);
    $query->execute(); //On efface en base le token + on met la date Now de validation

    //les cookies
    if($cookieIsAccepted){

        if(isset($_COOKIE['userIdAuth'])){        
            // comme il y a modification du pass , la clé de l'autolog est changée
            // j'efface les éventuels cookies correspondant à l'ancien mot de passe
            setcookie('userIdAuth', '', time() -3600, null,null,false,true);
        }
        
        //cryptage des cookies avec en parametre son Id + hachage de son nom + pass + adressseIP (utile lors de la connexion auto cf. autologinUser.php)
        $cryptageCookie = $result['id_user'].'---'.sha1($result['user_name'].$result['user_password'].$_SERVER['REMOTE_ADDR']);
        
        //définition du cookie
        setcookie('userIdAuth', $cryptageCookie , time()+365*24*3600, null, null, false, true);
    }

    session_start(); //On demarre la session
    $_SESSION['authenticatedUserId'] = $result['id_user'];
    $_SESSION['flash']['success'] = "Votre inscription est désormais entièrement validée";
    header('Location: http://localhost/dev-bases/php/projects/bateaux/accountUser.php'); // on redirige l'utilisateur avec en session son Id   
    $db = null; // Fermeture de la connextion à la base de données
    exit();

        } else { // Si il y un souci , l'utilisateur est redirigé vers la page de login
            $_SESSION['flash']['danger'] = "Ce token n'est plus valide";
            header('Location: http://localhost/dev-bases/php/projects/bateaux/index.php');
            exit();
    }
