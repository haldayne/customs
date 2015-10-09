<?php
namespace Haldayne\Customs;

/**
 * A specific kind of UploadException: the server had a problem.
 *
 * It's recommended that you also log the temporary directory and currently
 * installed extensions for their diagnostic value. The exception message
 * does not include these details for security.
 *
 * ```
 * try {
 *     $it = new UploadIterator;
 * } catch (ServerProblemException $ex) {
 *     error_log($ex->getDiagnosticMessage());
 *     throw $ex;
 * }
 *
 */
class ServerProblemException extends UploadException
{
    /**
     * Return the message specific for this exception code.
     *
     * @return string
     * @api
     * @since 1.0.0
     */
    public function getMessage()
    {
        switch ($this->code) {
        case UPLOAD_ERR_NO_TMP_DIR:
            return 'Missing a temporary folder in which to hold the upload';

        case UPLOAD_ERR_CANT_WRITE:
            return 'Failed to write upload to temporary location';

        case UPLOAD_ERR_EXTENSION:
            return 'An extension blocked the upload';

        default:
            return $this->message;
        }
    }

    /**
     * Returns a string containing deeper diagnostics of the temporary
     * directory and installed extensions.
     *
     * @return string
     * @api
     * @since 1.0.0
     */
    public function getDiagnosticMessage()
    {
        $uploadWokringPath = Config::uploadWorkingPath();
        return sprintf(
            '%s: tempdir=[%s], free space=[%s], extensions=[%s]',
            __CLASS__,
            $uploadWorkingPath,
            disk_free_space($uploadWorkingPath),
            implode(',', get_loaded_extensions())
        );
    }
}
