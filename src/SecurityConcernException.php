<?php
namespace Haldayne\Customs;

/**
 * A specific kind of UploadException: the upload is suspicious.
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
}
