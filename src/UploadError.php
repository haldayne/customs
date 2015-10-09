<?php
/**
 * Represents a file that did not successfully upload.
 *
 * Errors occur when the *client* did something wrong. The possible causes are:
 * - The client did not upload a file.
 * - The client uploaded a partial, incomplete file.
 * - The client sent more bytes than either the form or the server allowed.
 *
 * @see UploadException which occurs when the server went wrong or the client
 * appears to be circumventing PHP file upload safeguards.
 */
namespace Haldayne\Customs;

class UploadError
{
    use HtmlNameAccessorTrait;

    /**
     * Create a new upload error object.
     *
     * @param string $htmlName The HTML name for this entity.
     * @param int $code The PHP error code.
     * @param int $size The size of the uploaded file.
     * @internal Instantiated by UploadIterator.
     */
    public function __construct($htmlName, $code, $size)
    {
        $this->setHtmlName($htmlName);
        $this->code = intval($code);
        $this->size = intval($size);
    }

    /**
     * Change the text associated with a PHP upload error code.
     *
     * You may want to localize or otherwise change the generic error
     * associated with one of the upload error codes. You may only change
     * messages for the following upload error codes:
     *
     * - UPLOAD_ERR_INI_SIZE (1) : The upload exceeds the `upload_max_filesize`
     *   directive.
     * - UPLOAD_ERR_FORM_SIZE (2) : The upload exceeds the `MAX_FILE_SIZE`
     *   directive in the HTML form.
     * - UPLOAD_ERR_PARTIAL (3) : The upload was incomplete.
     * - UPLOAD_ERR_NO_FILE (4) : No file was uploaded.
     *
     * @param int $code The code whose message you want to change.
     * @param string $message The new message for that code.
     * @throws \OutOfBoundsException If the given code does not have a message
     * @api
     * @since 1.0.0
     */
    public static function changeErrorMessage($code, $message)
    {
        if (array_key_exists($code, static::$messages)) {
            static::$messages[$code] = $message;
        } else {
            throw new \OutOfBoundsException('Code does not have a message');
        }
    }

    /**
     * Return a description of why the error occurred.
     *
     * @see UploadError::changeErrorMessage To adjust the message globally.
     * @api
     * @since 1.0.0
     */
    public function getErrorMessage()
    {
        return static::$messages[$this->code];
    }

    /**
     * Is the error because the uploaded file was too big?
     *
     * An upload may exceed either the `upload_max_filesize` setting or the
     * `MAX_FILE_SIZE` input. This method handles both cases and returns true
     * if either of these limits was reached. In such a case, the optional
     * pass-by-reference `$maximum` will indicate the maximum possible size.
     *
     * @param int $maximum The server or form allowed maximum file size, in bytes.
     * @return bool
     * @api
     * @since 1.0.0
     */
    public function isTooBig(&$maximum = null)
    {
        if (UPLOAD_ERR_INI_SIZE === $this->code) {
            $maximum = UploadConfig::systemMaxUploadBytes();
            return true;
        } else if (UPLOAD_ERR_FORM_SIZE === $this->code) {
            $maximum = UploadConfig::formMaxUploadBytes();
            return true;
        } else {
            return false;
        }
    }

    /**
     * Is the error because the uploaded file was only partially received?
     *
     * @param int $received The number of bytes received.
     * @return bool
     * @api
     * @since 1.0.0
     */
    public function isPartial(&$received = null)
    {
        if (UPLOAD_ERR_PARTIAL === $this->code) {
            $received = $this->size;
            return true;
        } else {
            return false;
        }
    }

    /**
     * Is the error because no file was uploaded?
     *
     * @return bool
     * @api
     * @since 1.0.0
     */
    public function notUploaded()
    {
        return UPLOAD_ERR_NO_FILE === $this->code;
    }

    // PRIVATE API

    /** @var int $code The error code. */
    private $code;

    /** @var int $size The size of the file uploaded. */
    private $size;

    /**
     * The possible failure codes and an English description.
     */
    private static $messages = [
        UPLOAD_ERR_INI_SIZE  => 'The file size exceeds the server-allowed limit.',
        UPLOAD_ERR_FORM_SIZE => 'The file size exceeds the form-allowed upload limit.',
        UPLOAD_ERR_PARTIAL   => 'The file was only partially uploaded.',
        UPLOAD_ERR_NO_FILE   => 'No file was uploaded.',
    ];
}
