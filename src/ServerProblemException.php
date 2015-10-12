<?php
namespace Haldayne\Customs;

/**
 * A specific kind of UploadException: the server had a problem.
 *
 * It's recommended that you also log the temporary directory and currently
 * installed extensions for their diagnostic value. The exception message
 * does not include these details for security, but the exception provides
 * a method for extracting them. Example:
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
     * Returns a string containing deeper diagnostics of the system state
     * at the time the server problem occurred.
     *
     * Server problems may not last long (a lot of simultaneous uploads
     * temporarily eats all the upload space) or may be semi-permanent (an
     * extension is preventing all uploads). It's therefore important to
     * know as much as possible about the system at the time of the problem.
     *
     * This method captures the free space available, loaded extensions, and
     * session id into a string for foresnic diagnostics.
     *
     * @return string
     * @api
     * @since 1.0.0
     */
    public function getDiagnosticMessage()
    {
        $uploadWorkingPath = Config::uploadWorkingPath();
        return sprintf(
            '%s: working_dir=[%s], free_disk=[%s], extensions=[%s], session_id=[%s], remote_addr=[%s]',
            __CLASS__,
            $uploadWorkingPath,
            disk_free_space($uploadWorkingPath),
            implode(',', get_loaded_extensions()),
            session_id(),
            $_SERVER['REMOTE_ADDR']
        );
    }
}
