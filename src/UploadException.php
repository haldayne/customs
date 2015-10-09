<?php
namespace Haldayne\Customs;

/**
 * Represents an exception during upload.
 *
 * Occurs when the *server* had a problem, or the *client* appears to be
 * attempting to bypass the normal PHP file upload safeguards.
 *
 * @see UploadError which occurs when the client uploads anything other than a
 * complete file.
 */
class UploadException extends \RuntimeException
{
    use HtmlNameAccessorTrait;

    /**
     * Create a new upload exception.
     * @internal
     */
    public function __construct($htmlName, $code, \Exception $ex = null)
    {
        parent::__construct('There was a problem with your upload', $code, $ex);
        $this->setHtmlName($htmlName);
    }
}
