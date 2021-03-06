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
        $this->name = new \SplFileInfo($name);
        $this->file = new \SplFileInfo($file);
    }

    /**
     * Get the file the client gave us for this file, if any.
     *
     * Do not rely on this file for anything but display, because you
     * cannot trust that it contains safe characters, that the extension
     * matches the contents, etc.
     *
     * @return \SplFileInfo|null
     * @api
     * @since 1.0.8
     */
    public function getClientFile()
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
     * @throws \Haldayne\Customs\ServerProblemException
     * @return void
     * @api
     * @since 1.0.2
     */
    public function moveTo($path)
    {
        // use rename, as we've already asserted the file is an upload
        $ok = rename(
            $this->getServerFile()->getRealPath(),
            $path instanceof \SplFileInfo ? $path->getRealPath() : $path
        ); 
        if (true !== $ok) {
            throw new ServerProblemException(
                $this->getHtmlName(),
                ServerProblemException::MOVE_UPLOAD_FAILED
            );
        }
    }

    // PROTECTED API

    protected $name;
    protected $file;
}
