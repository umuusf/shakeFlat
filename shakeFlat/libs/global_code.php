<?php
/**
 * libs/global_code.php
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

namespace shakeFlat\libs;

class GCode
{
    // Auth
    const NEED_LOGIN                        = -9999;

    // Parameter
    const MISSING_PARAM                     = -1001;
    const PARAM_EMPTY                       = -1002;
    const PARAM_TYPE_INCORRECT              = -1003;
    const PARAM_TYPE_JSON                   = -1004;
    const PARAM_FILE_UPLOAD_FAILURE         = -1005;
    const PARAM_FILE_DIR_CREATION_FAILURE   = -1006;
    const PARAM_FILE_SAVE_FAILURE           = -1007;

    // Write your code here...
}