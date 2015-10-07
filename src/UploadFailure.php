<?php
namespace Haldayne\UploadIterator;

/**
 * Represents a file that did not successfully upload.
 */
class UploadFailure
{
    /**
     * The possible failure codes and an English description.
     */
    public static $failure_codes = [
        UPLOAD_ERR_INI_SIZE => 'The file size exceeds the server-allowed limit.',
        UPLOAD_ERR_FORM_SIZE => 'The file size exceeds the form-allowed upload limit.',
        UPLOAD_ERR_PARTIAL => 'The file was only partially uploaded.',
        UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
        UPLOAD_ERR_CANT_WRITE => 'The server failed to write the file.',
        UPLOAD_ERR_NO_TMP_DIR => 'A server misconfiguration .',
        UPLOAD_ERR_EXTENSION => 'A PHP server extension cancelled the upload.',
    ];

    /**
     * Return the part of the file we got, if any.
     */
    public function getFile()
    {
    }

    public function getFailureReason()
    {
    }
}
