<?php
namespace Haldayne\Customs;

/**
 * Represents a file that has been successfully uploaded.
 */
class UploadFile
{
    use HtmlNameAccessorTrait;

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
        $this->setHtmlName($htmlName);
        $this->name = $name;
        $this->file = new \SplFileInfo($file);
    }

    /**
     * Get the file name the client gave us for this file, if any.
     *
     * Do not rely on this file name for anything but display, because you
     * cannot trust that it contains safe characters.
     *
     * @return string
     * @api
     * @since 1.0.0
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
     * @return \SplFileInfo
     * @api
     * @since 1.0.0
     */
    public function getServerFile()
    {
        return $this->file;
    }

    /**
     * Move the temporary file holding the upload to a final destination.
     *
     * @throws \Haldayne\Customs\UploadException
     * @return void
     * @api
     * @since 1.0.2
     */
    public function moveTo($path)
    {
        $ok = move_uploaded_file($this->getServerFile()->getRealPath(), $path); 
        if (true !== $ok) {
            throw new UploadException('Cannot move file');
        }
    }

    // PROTECTED API

    protected $name;
    protected $file;
}
