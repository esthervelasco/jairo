<?php
namespace App;

//Inicializo sesión para poder traspasar variables entre páginas
session_start();

//echo password_hash("1234Abcd!",  PASSWORD_BCRYPT, ['cost'=>12]);


//Incluyo los controladores que voy a utilizar para que seran cargados por Autoload
use App\Controller\AppController;
use App\Controller\SkateController;
use App\Controller\PersonaController;

/*
 * Asigno a sesión las rutas de las carpetas public y home, necesarias tanto para las rutas como para
 * poder enlazar imágenes y archivos css, js
 * cambiamos la rutas
 */
$_SESSION['public'] = '/Practica_cms1/public/';
$_SESSION['home'] = $_SESSION['public'].'index.php/';

//Defino y llamo a la función que autocargará las clases cuando se instancien
spl_autoload_register('App\autoload');

function autoload($clase,$dir=null){

    //Directorio raíz de mi proyecto
    if (is_null($dir)){
        $dirname = str_replace('/public', '', dirname(__FILE__));
        $dir = realpath($dirname);
    }

    //Escaneo en busca de la clase de forma recursiva
    foreach (scandir($dir) as $file){
        //Si es un directorio (y no es de sistema) accedo y
        //busco la clase dentro de él
        if (is_dir($dir."/".$file) AND substr($file, 0, 1) !== '.'){
            autoload($clase, $dir."/".$file);
        }
        //Si es un fichero y el nombr conicide con el de la clase
        else if (is_file($dir."/".$file) AND $file == substr(strrchr($clase, "\\"), 1).".php"){
            require($dir."/".$file);
        }
    }

}

//Para invocar al controlador en cada ruta
function controlador($nombre=null){

    switch($nombre){
        default: return new AppController;//front end
        case "skates": return new SkateController;// back end skates
        case "personas": return new PersonaController;//autenticación y back-end de personas
    }

}

//Quito la ruta de la home a la que me están pidiendo
$ruta = str_replace($_SESSION['home'], '', $_SERVER['REQUEST_URI']);


//Encamino cada ruta al controlador y acción correspondientes
switch ($ruta){

    //Front-end
    case "":
    case "/":
        controlador()->index();
        break;
    case "acerca-de":
        controlador()->acercade();
        break;
    case "skates":
        controlador()->skates();
        break;
    case (strpos($ruta,"skate/") === 0)://si la ruta empieza por "skate"
        controlador()->skate(str_replace("skate/","",$ruta));//el parametro es lo que haya despues de "nombrarlo"
        break;

    //Back-end
    case "admin":
    case "admin/entrar":
        controlador("personas")->entrar();
        break;
    case "admin/salir":
        controlador("personas")->salir();
        break;
    case "admin/personas":
        controlador("personas")->index();
        break;
    case "admin/personas/crear":
        controlador("personas")->crear();
        break;
    case (strpos($ruta,"admin/personas/editar/") === 0):
        controlador("personas")->editar(str_replace("admin/personas/editar/","",$ruta));
        break;
    case (strpos($ruta,"admin/personas/activar/") === 0):
        controlador("personas")->activar(str_replace("admin/personas/activar/","",$ruta));
        break;
    case (strpos($ruta,"admin/personas/borrar/") === 0):
        controlador("personas")->borrar(str_replace("admin/personas/borrar/","",$ruta));
        break;
    case "admin/skates":
        controlador("skates")->index();
        break;
    case "admin/skates/crear":
        controlador("skates")->crear();
        break;
    case (strpos($ruta,"admin/skates/editar/") === 0):
        controlador("skates")->editar(str_replace("admin/skates/editar/","",$ruta));
        break;
    case (strpos($ruta,"admin/skates/activar/") === 0):
        controlador("skates")->activar(str_replace("admin/skates/activar/","",$ruta));
        break;
    case (strpos($ruta,"admin/skates/home/") === 0):
        controlador("skates")->home(str_replace("admin/skates/home/","",$ruta));
        break;
    case (strpos($ruta,"admin/skates/borrar/") === 0):
        controlador("skates")->borrar(str_replace("admin/skates/borrar/","",$ruta));
        break;
    case (strpos($ruta,"admin/") === 0):
        controlador("personas")->entrar();
        break;

    //Resto de rutas
    default:
        controlador()->index();

}