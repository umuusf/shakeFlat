<?php
/**
 * core/template.inc
 *
 * The final output of the processing result of each module.
 * Depending on the output mode, the appropriate processing is performed.
 *
 * Folders with layout names are listed under the template path.
 * Each layout must have layout.html and error.html.
 * And there are folders with each module name, and individual module (function) files exist under each folder.
 * All extensions are html.
 *
 * layout.html contains the entire code of the web page,
 * and the contents of each module page should be included in the $contentBody variable. ex) <?php echo $contentBody; ?>
 *
 * error.html is the content that is output when an error occurs, and is included in layout.html.
 * In error.html, you can use $message(string) with error messages and $context(array) with debugging information.
 */

namespace shakeFlat;
use shakeFlat\Translation;
use shakeFlat\libs\Response;
use shakeFlat\libs\Router;
use shakeFlat\libs\AES256;
use shakeFlat\libs\Util;

class Template
{
    const MODE_WEB                  = 1;
    const MODE_WEB_REDIRECT         = 11;
    const MODE_AJAX                 = 2;
    const MODE_AJAX_FOR_DATATABLE   = 3;
    const MODE_API                  = 4;
    const MODE_API_ENCRYPT          = 5;

    private $pathTemplate;
    private $template;
    private $layoutFile;
    private $layoutFileForError;
    private $mode;
    private $translationLang;
    private $redirectUrl;
    private $redirectMsg;

    public static function getInstance()
    {
        static $instance = null;
        if ($instance) return $instance;
        $instance = new Template();
        return $instance;
    }

    private function __construct()
    {
        $this->pathTemplate         = SHAKEFLAT_PATH . "templates/";
        $this->template             = "default";
        $this->layoutFile           = "layout.html";
        $this->layoutFileForError   = "layout.html";
        $this->mode                 = self::MODE_WEB;
        $this->translationLang      = null;
        $this->redirectUrl          = null;
        $this->redirectMsg          = null;
    }

    // called from App class
    public function setPathTemplate($pathTemplate)
    {
        $this->pathTemplate = $pathTemplate;
        return $this;
    }

    // called from App class
    public function setTemplate($template)
    {
        $this->template = $template;
        return $this;
    }

    // Set the file to be used as layout. (default is layout.html)
    public function setLayoutFile($layoutFile)
    {
        $this->layoutFile = $layoutFile;
        return $this;
    }

    // Set the file to be used as layout on the screen where the error will be displayed. (default is layout.html)
    public function setLayoutFileForError($layoutFileForError)
    {
        $this->layoutFileForError = $layoutFileForError;
        return $this;
    }

    public function setRedirect($url, $msg = null)
    {
        $this->redirectUrl = $url;
        if ($msg) $this->redirectMsg = $msg;
        $this->setMode(self::MODE_WEB_REDIRECT);
        return $this;
    }

    // called from App class
    public function setMode($mode)
    {
        $this->mode = $mode;
        return $this;
    }

    public function isAjax()
    {
        return ($this->mode === self::MODE_AJAX);
    }

    // If a tag for translation ([:...:]) is applied to the text written in the template, but translation is not desired, this function is called without parameters.
    // If it is called without parameters (when it is called as null ), the passing method of the Translation class is called.
    public function setTranslationLang($lang = null)
    {
        $this->translationLang = $lang;
        return $this;
    }

    public function getTranslationLang()
    {
        return $this->translationLang;
    }

    public function displayResult()
    {
        $res = Response::getInstance();
        switch($this->mode) {
            case self::MODE_AJAX :
                header("Content-Type: application/json");
                $data = $res->data();
                $data = $this->translationOutput($data);
                echo json_encode($data, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
                return;
            case self::MODE_AJAX_FOR_DATATABLE :
                header("Content-Type: application/json");
                $data = $res->data()["data"];
                $data = $this->translationOutput($data);
                echo json_encode($data, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
                return;
            case self::MODE_API :
                header("Access-Control-Allow-Origin: *");
                header("Access-Control-Allow-Credentials: true");
                header("Access-Control-Allow-Methods: POST");
                header("Access-Control-Allow-Headers: Access-Control-Allow-Headers, Origin,Accept, X-Requested-With, Content-Type, Access-Control-Request-Method, Access-Control-Request-Headers, Auth-Token");
                header("Content-Type: application/json");
                $data = $res->data();
                $data = $this->translationOutput($data);
                echo json_encode($data, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
                return;
            case self::MODE_API_ENCRYPT :
                header("Access-Control-Allow-Origin: *");
                header("Access-Control-Allow-Credentials: true");
                header("Access-Control-Allow-Methods: POST");
                header("Access-Control-Allow-Headers: Access-Control-Allow-Headers, Origin,Accept, X-Requested-With, Content-Type, Access-Control-Request-Method, Access-Control-Request-Headers, Auth-Token");
                $data = $res->data();
                $data = $this->translationOutput($data);
                echo AES256::packObject($data, SHAKEFLAT_ENV["aes256"]["key_with_client"] ?? "00000000000000000000000000000000");
                return;
            case self::MODE_WEB :
                $router = Router::getInstance();
                $p = rtrim($this->pathTemplate, " /") . "/" . trim($this->template, " /");
                ob_start();
                include("{$p}/{$router->module()}/{$router->fnc()}.html");  // You can get the value by referring to $res in the template.
                $contentBody = ob_get_clean();
                ob_start();
                include("{$p}/{$this->layoutFile}");        // Insert $contentBody inside layout.html and use it.
                $html = ob_get_clean();

                $html = $this->translationOutput($html);       // translation...
                echo $html;
                return;
            case self::MODE_WEB_REDIRECT :
                if ($this->redirectMsg) {
                    $cookie = shakeFlat\libs\Cookie::getInstance("_rm_");
                    $cookie->msg = $this->redirectMsg;
                }
                header("Location: " . $this->redirectUrl);
                exit;
        }
    }

    // In case of publishing the error contents and closing the app.
    public function displayError($message, $context, $code = -1)
    {
        $data = array(
            "common" => array ( "time" => time() ),
            "error"  => array ( "errCode" => $code, "errMsg" => $message ),
            "data"   => array (),
        );

        switch($this->mode) {
            case self::MODE_AJAX :
                header("Content-Type: application/json");
                $data = $this->translationOutput($data);
                echo json_encode($data, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
                return;
            case self::MODE_AJAX_FOR_DATATABLE :
                header("Content-Type: application/json");
                $data = $this->translationOutput($data["data"]);
                echo json_encode($data, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
                return;
            case self::MODE_API :
                header("Access-Control-Allow-Origin: *");
                header("Access-Control-Allow-Credentials: true");
                header("Access-Control-Allow-Methods: POST");
                header("Access-Control-Allow-Headers: Access-Control-Allow-Headers, Origin,Accept, X-Requested-With, Content-Type, Access-Control-Request-Method, Access-Control-Request-Headers, Auth-Token");
                header("Content-Type: application/json");
                $data = $this->translationOutput($data);
                echo json_encode($data, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
                return;
            case self::MODE_API_ENCRYPT :
                header("Access-Control-Allow-Origin: *");
                header("Access-Control-Allow-Credentials: true");
                header("Access-Control-Allow-Methods: POST");
                header("Access-Control-Allow-Headers: Access-Control-Allow-Headers, Origin,Accept, X-Requested-With, Content-Type, Access-Control-Request-Method, Access-Control-Request-Headers, Auth-Token");
                $data = $this->translationOutput($data);
                echo AES256::packObject($data, SHAKEFLAT_ENV["aes256"]["key_with_client"] ?? "00000000000000000000000000000000");
                return;
            case self::MODE_WEB :
                $router = Router::getInstance();
                $p = rtrim($this->pathTemplate, " /") . "/" . trim($this->template, " /");
                ob_start();
                include("{$p}/error.html");         // use $message, $code
                $contentBody = ob_get_clean();
                ob_start();
                include("{$p}/{$this->layoutFileForError}");        // Insert $contentBody inside layout.html and use it.
                $html = ob_get_clean();
                $html = $this->translationOutput($html);
                echo $html;
                return;
        }
    }

    private function translationOutput($output)
    {
        if ($this->translationLang) {
            $router = Router::getInstance();
            $translation = new Translation("{$router->module()}/{$router->fnc()}", $this->translationLang);
            $output = $translation->convert($output);
            $translation->updateCache();
        }
        if (is_array($output)) {
            $output_json = json_encode($output, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
            $t = Translation::passing($output_json);
            return json_decode($t);
        } else {
            return Translation::passing($output);
        }
    }
}