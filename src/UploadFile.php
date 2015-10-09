<?php
namespace Haldayne\Customs;

use Haldayne\Mime;

/**
 * Represents a file that has been successfully uploaded.
 */
class UploadFile extends AbstractUploadEntity
{
    /**
     * Create a new object representing a *successfully* uploaded file.
     *
     * @param string $htmlName The HTML variable name corresponding to this file.
     * @param null|string $name The original file name, as given by the client.
     * @param string $file The temporary server file path to this file.
     * @internal
     */
    public function __construct($htmlName, $name, $file)
    {
        parent::__construct($htmlName);
        $this->name = $name;
        $this->file = new \SplFileInfo($file);
    }

    /**
     * Get the file name the client gave us for this file, if any.
     *
     * Do not rely on this file name for anything but display, because you
     * cannot trust that it contains safe characters.
     */
    public function getClientFilename()
    {
        return $this->name;
    }

    /**
     * Get the temporary file holding this upload. You must move this file
     * before the request ends to keep the upload.
     *
     * @see UploadFile::moveTo
     */
    public function getServerFile()
    {
        return $this->file;
    }

    /**
     * Get the MIME analyzer for the file
     */
    public function getMimeAnalyzer()
    {
        return new Mime\Analyzer();
    }

    // PROTECTED API

    protected $name;
    protected $file;
}
