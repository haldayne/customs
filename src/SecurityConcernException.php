<?php
namespace Haldayne\Customs;

/**
 * A specific kind of UploadException: the upload is suspicious.
 *
 * It's recommended that you also log the session ID, IP address, and other
 * environment for their diagnostic value.
 *
 * ```
 * try {
 *     $it = new UploadIterator;
 * } catch (SecurityConcernException $ex) {
 *     error_log(sprintf('session_id=%s', session_id()));
 *     throw $ex;
 * }
 *
 */
class SecurityConcernException extends UploadException
{
    const NOT_UPLOADED = 1;
    const UNKNOWN_CODE = 2;

    public function __construct($htmlName, $code = 0, \Exception $pex = null)
    {
        parent::__construct($htmlName, $code, $pex);

        if (SecurityConcernException::NOT_UPLOADED === $code) {
            $this->message = 'The file was not uploaded through POST';
        } else if (SecurityConcernException::UNKNOWN_CODE <= $code) {
            $code -= SecurityConcernException::UNKNOWN_CODE;
            $this->message = "The file had an unknown PHP upload error code: $code";
        }
    }
}
