<?php
namespace Haldayne\Customs;

/**
 * A specific kind of UploadException: the server had a problem.
 *
 * It's recommended that you also log the temporary directory and currently
 * installed extensions for their diagnostic value. Example:
 *
 * ```
 * try {
 *     $it = new UploadIterator;
 * } catch (ServerProblemException $ex) {
 *     error_log(sprintf(
 *         'free space=%s, cwd=%s, tmpdir=%s',
 *         disk_free_space(), getcwd(), sys_get_temp_dir()
 *     ));
 *     throw $ex;
 * }
 *
 */
class ServerProblemException extends UploadException
{
    public function __construct($htmlName, $code = 0, \Exception $pex = null)
    {
        parent::__construct($htmlName, $code, $pex);

        switch ($code) {
        case UPLOAD_ERR_NO_TMP_DIR:
            $this->message = "No temporary folder in which to hold the upload";
            break;

        case UPLOAD_ERR_CANT_WRITE:
            $this->message = "Failed to write upload to temporary location";
            break;

        case UPLOAD_ERR_EXTENSION:
            $this->message = "An extension blocked the upload";
            break;
        }
    }
}
