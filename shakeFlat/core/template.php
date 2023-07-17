<?php
/**
 * core/template.php
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
use shakeFlat\Response;
use shakeFlat\Router;
use shakeFlat\AES256;

class Template
{
    const MODE_WEB                  = 1;
    const MODE_WEB_REDIRECT         = 11;
    const MODE_AJAX                 = 2;
    const MODE_AJAX_FOR_DATATABLE   = 3;
    const MODE_API                  = 4;
    const MODE_API_ENCRYPT          = 5;
    const MODE_API_ENCRYPT_ZIP      = 6;
    const MODE_CLI                  = 7;

    private $layoutFile;
    private $layoutFileForError;
    private $templateFile;
    private $mode;
    private $translationLang;
    private $redirectUrl;
    private $redirectMsg;
    private $charset;
    private $aes256key;
    private $aes256iv;
    private $gpath;

    public static function getInstance()
    {
        static $instance = null;
        if ($instance) return $instance;
        $instance = new Template();
        return $instance;
    }

    private function __construct()
    {
        $this->gpath = GPath::getInstance();

        $this->layoutFile           = "layout.html";
        $this->layoutFileForError   = "layout.html";
        $this->mode                 = self::MODE_WEB;
        $this->translationLang      = null;
        $this->redirectUrl          = null;
        $this->redirectMsg          = null;
        $this->charset              = "UTF-8";
        $this->aes256key            = SHAKEFLAT_ENV["aes256"]["key_with_client"] ?? "00000000000000000000000000000000";
        $this->aes256iv             = SHAKEFLAT_ENV["aes256"]["iv_with_client"] ?? "0000000000000000";

        $router = Router::getInstance();
        $this->templateFile = "{$router->module()}/{$router->fnc()}";
    }

    // called from App class
    public function setPathTemplates($pathTemplates)
    {
        $this->gpath->TEMPLATES = rtrim($pathTemplates, " /") . "/";
        return $this;
    }

    // Set the file to be used as layout. (default is layout.html)
    public function setLayoutFile($layoutFile)
    {
        $this->layoutFile = $layoutFile;
        return $this;
    }

    public function setCustomTemplateFile($templateFile)
    {
        $this->templateFile = $templateFile;
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

    public function setCharset($charset)
    {
        $this->charset = $charset;
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

    public function setAES256Key($key, $iv = null)
    {
        $this->aes256key = $key;
        if ($iv !== null) $this->aes256iv = $iv;
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
                if (isset($_SERVER["HTTP_ORIGIN"])) header("Access-Control-Allow-Origin: {$_SERVER["HTTP_ORIGIN"]}");
                header("Content-Security-Policy: default-src 'self'; frame-src 'none'; object-src 'none';");
                header("Content-Type: application/json; charset={$this->charset}");
                $data = $res->data();
                $data = $this->translationOutput($data);
                $data = json_encode($data, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
                if (strtoupper($this->charset) != "UTF-8") $data = iconv("UTF-8", $this->charset, $data);
                echo $data;
                break;
            case self::MODE_AJAX_FOR_DATATABLE :
                header("Content-Type: application/json; charset={$this->charset}");
                $data = $res->data()["data"];
                $data = json_encode($data, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
                if (strtoupper($this->charset) != "UTF-8") $data = iconv("UTF-8", $this->charset, $data);
                echo $data;
                break;
            case self::MODE_API :
                header("Access-Control-Allow-Origin: *");
                header("Access-Control-Allow-Credentials: true");
                header("Access-Control-Allow-Methods: POST");
                header("Access-Control-Allow-Headers: Access-Control-Allow-Headers, Origin,Accept, X-Requested-With, Content-Type, Access-Control-Request-Method, Access-Control-Request-Headers, Auth-Token");
                header("Content-Type: application/json; charset={$this->charset}");
                $data = $res->data();
                $data = $this->translationOutput($data);
                $data = json_encode($data, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
                if (strtoupper($this->charset) != "UTF-8") $data = iconv("UTF-8", $this->charset, $data);
                ob_start();
                echo $data;
                $length=ob_get_length();
                header("Content-Length: $length");
                ob_end_flush();
                break;
            case self::MODE_API_ENCRYPT :
                header("Access-Control-Allow-Origin: *");
                header("Access-Control-Allow-Credentials: true");
                header("Access-Control-Allow-Methods: POST");
                header("Access-Control-Allow-Headers: Access-Control-Allow-Headers, Origin,Accept, X-Requested-With, Content-Type, Access-Control-Request-Method, Access-Control-Request-Headers, Auth-Token");
                header("Content-Type: text/html; charset={$this->charset}");
                $data = $res->data();
                $data = $this->translationOutput($data);
                $data = json_encode($data, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
                if (strtoupper($this->charset) != "UTF-8") $data = iconv("UTF-8", $this->charset, $data);
                $data = AES256::pack($data, $this->aes256key, $this->aes256iv, false);
                ob_start();
                echo $data;
                $length=ob_get_length();
                header("Content-Length: $length");
                ob_end_flush();
                break;
            case self::MODE_API_ENCRYPT_ZIP :
                header("Access-Control-Allow-Origin: *");
                header("Access-Control-Allow-Credentials: true");
                header("Access-Control-Allow-Methods: POST");
                header("Access-Control-Allow-Headers: Access-Control-Allow-Headers, Origin,Accept, X-Requested-With, Content-Type, Access-Control-Request-Method, Access-Control-Request-Headers, Auth-Token");
                header("Content-Type: text/html; charset={$this->charset}");
                $data = $res->data();
                $data = $this->translationOutput($data);
                $data = json_encode($data, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
                if (strtoupper($this->charset) != "UTF-8") $data = iconv("UTF-8", $this->charset, $data);
                $data = AES256::pack($data, $this->aes256key, $this->aes256iv);
                ob_start();
                echo $data;
                $length=ob_get_length();
                header("Content-Length: $length");
                ob_end_flush();
                break;
            case self::MODE_WEB :
                header("Content-Type: text/html; charset={$this->charset}");
                $p = rtrim($this->gpath->TEMPLATES, " /");
                ob_start();
                include("{$p}/{$this->templateFile}.html");  // You can get the value by referring to $res in the template.
                $contentBody = ob_get_clean();
                ob_start();
                include("{$p}/{$this->layoutFile}");        // Insert $contentBody inside layout.html and use it.
                $html = ob_get_clean();

                $html = $this->translationOutput($html);       // translation...
                if (strtoupper($this->charset) != "UTF-8") $html = iconv("UTF-8", "{$this->charset}//TRANSLIT", $html);
                echo $html;
                break;
            case self::MODE_WEB_REDIRECT :
                if ($this->redirectMsg) {
                    $cookie = \shakeFlat\Cookie::getInstance("_rm_");
                    $cookie->msg = $this->redirectMsg;
                }
                header("Location: " . $this->redirectUrl);
                exit;
            case self::MODE_CLI :
                $data = $res->data();
                $data = $this->translationOutput($data);
                if (strtoupper($this->charset) != "UTF-8") $data = iconv("UTF-8", $this->charset, $data);
                echo $data;
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

        if (php_sapi_name() == "cli") $this->mode = self::MODE_CLI;
        elseif (strtolower($_SERVER["HTTP_X_REQUESTED_WITH"] ?? "") == "xmlhttprequest") $this->mode = self::MODE_AJAX;

        switch($this->mode) {
            case self::MODE_AJAX :
                if (isset($_SERVER["HTTP_ORIGIN"])) header("Access-Control-Allow-Origin: {$_SERVER["HTTP_ORIGIN"]}");
                header("Content-Security-Policy: default-src 'self'; frame-src 'none'; object-src 'none';");
                header("Content-Type: application/json; charset={$this->charset}");
                $data = $this->translationOutput($data);
                $data = json_encode($data, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
                if (strtoupper($this->charset) != "UTF-8") $data = iconv("UTF-8", $this->charset, $data);
                echo $data;
                break;
            case self::MODE_AJAX_FOR_DATATABLE :
                header("Content-Type: application/json; charset={$this->charset}");
                $data = json_encode($data, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
                if (strtoupper($this->charset) != "UTF-8") $data = iconv("UTF-8", $this->charset, $data);
                echo $data;
                break;
            case self::MODE_API :
                header("Access-Control-Allow-Origin: *");
                header("Access-Control-Allow-Credentials: true");
                header("Access-Control-Allow-Methods: POST, GET");
                header("Access-Control-Allow-Headers: Access-Control-Allow-Headers, Origin,Accept, X-Requested-With, Content-Type, Access-Control-Request-Method, Access-Control-Request-Headers, Auth-Token");
                header("Content-Type: application/json; charset={$this->charset}");
                $data = $this->translationOutput($data);
                $data = json_encode($data, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
                if (strtoupper($this->charset) != "UTF-8") $data = iconv("UTF-8", $this->charset, $data);
                ob_start();
                echo $data;
                $length=ob_get_length();
                header("Content-Length: $length");
                ob_end_flush();
                break;
            case self::MODE_API_ENCRYPT :
                header("Access-Control-Allow-Origin: *");
                header("Access-Control-Allow-Credentials: true");
                header("Access-Control-Allow-Methods: POST");
                header("Access-Control-Allow-Headers: Access-Control-Allow-Headers, Origin,Accept, X-Requested-With, Content-Type, Access-Control-Request-Method, Access-Control-Request-Headers, Auth-Token");
                header("Content-Type: text/html; charset={$this->charset}");
                $data = $this->translationOutput($data);
                $data = json_encode($data, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
                if (strtoupper($this->charset) != "UTF-8") $data = iconv("UTF-8", $this->charset, $data);
                $data = AES256::pack($data, $this->aes256key, $this->aes256iv, false);
                ob_start();
                echo $data;
                $length=ob_get_length();
                header("Content-Length: $length");
                ob_end_flush();
                break;
            case self::MODE_API_ENCRYPT_ZIP :
                header("Access-Control-Allow-Origin: *");
                header("Access-Control-Allow-Credentials: true");
                header("Access-Control-Allow-Methods: POST");
                header("Access-Control-Allow-Headers: Access-Control-Allow-Headers, Origin,Accept, X-Requested-With, Content-Type, Access-Control-Request-Method, Access-Control-Request-Headers, Auth-Token");
                header("Content-Type: text/html; charset={$this->charset}");
                $data = $this->translationOutput($data);
                $data = json_encode($data, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
                if (strtoupper($this->charset) != "UTF-8") $data = iconv("UTF-8", $this->charset, $data);
                $data = AES256::pack($data, $this->aes256key, $this->aes256iv);
                ob_start();
                echo $data;
                $length=ob_get_length();
                header("Content-Length: $length");
                ob_end_flush();
                break;
            case self::MODE_WEB :
                header("Content-Type: text/html; charset={$this->charset}");
                try {
                    $router = Router::getInstance();
                    $p = rtrim($this->gpath->TEMPLATES, " /");
                    ob_start();
                    include("{$p}/error.html");         // use $message, $code
                    $contentBody = ob_get_clean();

                    ob_start();
                    include("{$p}/{$this->layoutFileForError}");        // Insert $contentBody inside layout.html and use it.
                    $html = ob_get_clean();
                    $html = $this->translationOutput($html);
                    if (strtoupper($this->charset) != "UTF-8") $html = iconv("UTF-8", $this->charset, $html);
                    echo $html;
                } catch (\Exception $e) {
                    echo "
                        <div>
                        error message : {$message}
                        </div>
                        <div>
                            \n<br>\n<font style='font-size:10pt;'>" .
                            str_replace("<span style=\"color: #0000BB\">&lt;?php<br /></span>", "", highlight_string("<?php\n".print_r($context,true), true)) .
                            "</font><br>\n<br>\n
                        </div>
                    ";
                }
                break;
            case self::MODE_CLI :
                echo "\n\n";
                echo "errCode : {$code}\n";
                echo "errMsg  : {$message}\n";
                if ($context && ($context["parameters"] ?? false)) print_r($context);
                echo "\n\n";
                exit;
        }
    }

    private function translationOutput($output)
    {
        $translation = Translation::getInstance();
        if ($this->translationLang) {
            if (is_array($output)) {
                $output = json_decode($translation->convert(json_encode($output, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE), $this->translationLang), true);
            } else {
                $output = $translation->convert($output, $this->translationLang);
            }
            $translation->updateCache($this->translationLang);
            return $output;
        }
        if (is_array($output)) {
            return json_decode($translation->passing(json_encode($output, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)), true);
        } else {
            return $translation->passing($output);
        }
    }
}