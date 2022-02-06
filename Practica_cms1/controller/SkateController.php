<?php
namespace App\Controller;

use App\Helper\ViewHelper;
use App\Helper\DbHelper;
use App\Model\Skate;


class SkateController
{
    var $db;
    var $view;

    function __construct()
    {
        //Conexión a la BBDD
        $dbHelper = new DbHelper();
        $this->db = $dbHelper->db;

        //Instancio el ViewHelper
        $viewHelper = new ViewHelper();
        $this->view = $viewHelper;
    }

    //Listado de skates
    public function index(){
        //Permisos
        $this->view->permisos("skates");

        //Recojo las skates de la base de datos
        $rowset = $this->db->query("SELECT * FROM skates ORDER BY fecha DESC");

        //Asigno resultados a un array de instancias del modelo
        $skate = array();
        while ($row = $rowset->fetch(\PDO::FETCH_OBJ)){
            array_push($skate,new Skate($row));
        }

        $this->view->vista("admin","skates/index", $skate);

    }



    //Para activar o desactivar
    public function activar($id){

        //Permisos
        $this->view->permisos("skates");

        //Obtengo la skate
        $rowset = $this->db->query("SELECT * FROM skates WHERE id='$id' LIMIT 1");
        $row = $rowset->fetch(\PDO::FETCH_OBJ);
        $skate = new Skate($row);

        if ($skate->activo == 1){

            //Desactivo la skate
            $consulta = $this->db->exec("UPDATE skates SET activo=0 WHERE id='$id'");

            //Mensaje y redirección
            ($consulta > 0) ? //Compruebo consulta para ver que no ha habido errores
                $this->view->redireccionConMensaje("admin/skates","green","La skate <strong>$skate->titulo</strong> se ha desactivado correctamente.") :
                $this->view->redireccionConMensaje("admin/skates","red","Hubo un error al guardar en la base de datos.");
        }

        else{

            //Activo la skate
            $consulta = $this->db->exec("UPDATE skates SET activo=1 WHERE id='$id'");

            //Mensaje y redirección
            ($consulta > 0) ? //Compruebo consulta para ver que no ha habido errores
                $this->view->redireccionConMensaje("admin/skates","green","La skate <strong>$skate->titulo</strong> se ha activado correctamente.") :
                $this->view->redireccionConMensaje("admin/skates","red","Hubo un error al guardar en la base de datos.");
        }

    }

    //Para mostrar o no en la home
    public function home($id){

        //Permisos
        $this->view->permisos("skates");

        //Obtengo la skate
        $rowset = $this->db->query("SELECT * FROM skates WHERE id='$id' LIMIT 1");
        $row = $rowset->fetch(\PDO::FETCH_OBJ);
        $skate = new Skate($row);

        if ($skate->home == 1){

            //Quito la skate de la home
            $consulta = $this->db->exec("UPDATE skates SET home=0 WHERE id='$id'");

            //Mensaje y redirección
            ($consulta > 0) ? //Compruebo consulta para ver que no ha habido errores
                $this->view->redireccionConMensaje("admin/skates","green","La skate <strong>$skate->titulo</strong> ya no se muestra en la home.") :
                $this->view->redireccionConMensaje("admin/skates","red","Hubo un error al guardar en la base de datos.");
        }

        else{

            //Muestro la skate en la home
            $consulta = $this->db->exec("UPDATE skates SET home=1 WHERE id='$id'");

            //Mensaje y redirección
            ($consulta > 0) ? //Compruebo consulta para ver que no ha habido errores
                $this->view->redireccionConMensaje("admin/skates","green","La skate <strong>$skate->titulo</strong> ahora se muestra en la home.") :
                $this->view->redireccionConMensaje("admin/skates","red","Hubo un error al guardar en la base de datos.");
        }

    }

    public function borrar($id){

        //Permisos
        $this->view->permisos("skates");

        //Obtengo la skate
        $rowset = $this->db->query("SELECT * FROM skates WHERE id='$id' LIMIT 1");
        $row = $rowset->fetch(\PDO::FETCH_OBJ);
        $skate = new Skate($row);

        //Borro la skate
        $consulta = $this->db->exec("DELETE FROM skates WHERE id='$id'");

        //Borro la imagen asociada
        $archivo = $_SESSION['public']."img/".$skate->imagen;
        $texto_imagen = "";
        if (is_file($archivo)){
            unlink($archivo);
            $texto_imagen = " y se ha borrado la imagen asociada";
        }

        //Mensaje y redirección
        ($consulta > 0) ? //Compruebo consulta para ver que no ha habido errores
            $this->view->redireccionConMensaje("admin/skates","green","La skate se ha borrado correctamente$texto_imagen.") :
            $this->view->redireccionConMensaje("admin/skates","red","Hubo un error al guardar en la base de datos.");

    }

    public function crear(){

        //Permisos
        $this->view->permisos("skates");

        //Creo un nuevo usuario vacío
        $skate = new Skate();

        //Llamo a la ventana de edición
        $this->view->vista("admin","skates/editar", $skate);

    }

    public function editar($id){

        //Permisos
        $this->view->permisos("skates");

        //Si ha pulsado el botón de guardar
        if (isset($_POST["guardar"])){

            //Recupero los datos del formulario
            $titulo = filter_input(INPUT_POST, "titulo", FILTER_SANITIZE_STRING);
            $entradilla = filter_input(INPUT_POST, "entradilla", FILTER_SANITIZE_STRING);
            $precio = filter_input(INPUT_POST, "precio", FILTER_SANITIZE_STRING);
            $fecha = filter_input(INPUT_POST, "fecha", FILTER_SANITIZE_STRING);
            $texto = filter_input(INPUT_POST, "texto", FILTER_SANITIZE_FULL_SPECIAL_CHARS);

            //Formato de fecha para SQL
            $fecha = \DateTime::createFromFormat("d-m-Y", $fecha)->format("Y-m-d H:i:s");

            //Genero slug (url amigable)
            $slug = $this->view->getSlug($titulo);

            //Imagen
            $imagen_recibida = $_FILES['imagen'];
            $imagen = ($_FILES['imagen']['name']) ? $_FILES['imagen']['name'] : "";
            $imagen_subida = ($_FILES['imagen']['name']) ? '/var/www/html'.$_SESSION['public']."img/".$_FILES['imagen']['name'] : "";
            $texto_img = ""; //Para el mensaje

            if ($id == "nuevo"){

                //Creo una nueva skate
                $consulta = $this->db->exec("INSERT INTO skates 
                    (titulo, entradilla, precio, fecha, texto, slug, imagen) VALUES 
                    ('$titulo','$entradilla','$precio','$fecha','$texto','$slug','$imagen')");

                //Subo la imagen
                if ($imagen){
                    if (is_uploaded_file($imagen_recibida['tmp_name']) && move_uploaded_file($imagen_recibida['tmp_name'], $imagen_subida)){
                        $texto_img = " La imagen se ha subido correctamente.";
                    }
                    else{
                        $texto_img = " Hubo un problema al subir la imagen.";
                    }
                }

                //Mensaje y redirección
                ($consulta > 0) ?
                    $this->view->redireccionConMensaje("admin/skates","green","La skate <strong>$titulo</strong> se creado correctamente.".$texto_img) :
                    $this->view->redireccionConMensaje("admin/skates","red","Hubo un error al guardar en la base de datos.");
            }
            else{

                //Actualizo la skate
                $this->db->exec("UPDATE skates SET 
                    titulo='$titulo',entradilla='$entradilla',precio='$precio',
                    fecha='$fecha',texto='$texto',slug='$slug' WHERE id='$id'");

                //Subo y actualizo la imagen
                if ($imagen){
                    if (is_uploaded_file($imagen_recibida['tmp_name']) && move_uploaded_file($imagen_recibida['tmp_name'], $imagen_subida)){
                        $texto_img = " La imagen se ha subido correctamente.";
                        $this->db->exec("UPDATE skates SET imagen='$imagen' WHERE id='$id'");
                    }
                    else{
                        $texto_img = " Hubo un problema al subir la imagen.";
                    }
                }

                //Mensaje y redirección
                $this->view->redireccionConMensaje("admin/skates","green","La skate <strong>$titulo</strong> se guardado correctamente.".$texto_img);

            }
        }

        //Si no, obtengo skate y muestro la ventana de edición
        else{

            //Obtengo la skate
            $rowset = $this->db->query("SELECT * FROM skates WHERE id='$id' LIMIT 1");
            $row = $rowset->fetch(\PDO::FETCH_OBJ);
            $skate = new Skate($row);

            //Llamo a la ventana de edición
            $this->view->vista("admin","skates/editar", $skate);
        }

    }

}