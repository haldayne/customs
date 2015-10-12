<?php
namespace Haldayne\Customs;

/**
 * A specific kind of UploadException: the upload is suspicious.
 *
 * It's recommended that you also log the session ID, IP address, and other
 * environment for their diagnostic value. The exception message does not
 * include these details for security, but the exception provides a method for
 * extracting them. Example:
 *
 * ```
 * try {
 *     $it = new UploadIterator;
 * } catch (SecurityConcernException $ex) {
 *     error_log($ex->getDiagnosticMessage());
 *     throw $ex;
 * }
 *
 */
class SecurityConcernException extends UploadException
{
    const NOT_UPLOADED = 1;
    const UNKNOWN_CODE = 2;

    /**
     * Return the message specific for this exception code.
     * @api
     * @since 1.0.0
     */
    public function getMessage()
    {
        switch ($this->code) {
        case static::NOT_UPLOADED:
            return 'The file was not uploaded through POST';

        case static::UNKNOWN_CODE:
            return 'The file had an unknown PHP upload error code';

        default:
            return $this->message;
        }
    }

    /**
     * Returns a string containing details of the remote connection at the
     * time the security concern was raised.
     *
     * Knowing the remote connection and environment helps frame the context
     * of the problem: patterns of attack, duration, payload sizes. From this
     * data, you can better adapt your environment.
     *
     * This method captures the session ID, IP address, and other environment
     * details into a string for foresnic diagnostics.
     *
     * @return string
     * @api
     * @since 1.0.0
     */
    public function getDiagnosticMessage()
    {
        return sprintf(
            '%s: session_id=[%s], remote_addr=[%s]',
            __CLASS__,
            session_id(),
            $_SERVER['REMOTE_ADDR']
        );
    }
}
