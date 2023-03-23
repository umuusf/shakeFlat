<?php
/**
 * core/global_code.php
 *
 * Defines globally used code in shakeFlat.
 *
 */

namespace shakeFlat;

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
}