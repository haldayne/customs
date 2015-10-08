<?php
namespace Haldayne\Customs;

/**
 * Common methods shared amongst uploaded files.
 */
abstract class AbstractUploadEntity
{
    public function __construct($htmlName)
    {
        $this->htmlName = $htmlName;
    }

    /**
     * Get the HTML variable name for this upload.
     *
     * This name approximates the originally given name, like:
     *   <input type='file' name='file[a]' />
     *
     * But in some cases the exact name cannot be returned.  First, because
     * PHP mangles HTML names containing '.' or ' ', these will map:
     *   * foo.bar returned as "foo_bar"
     *   * foo bar returned as "foo_bar"
     *   * foo_bar returned as "foo_bar"
     *   * foo[bar.baz] returned as "foo[bar.baz]"
     *
     * Second, if the auto-append syntax was given:
     *   * foo[] returned as "foo[0]", etc.
     */
    public function getHtmlName()
    {
        return $this->htmlName;
    }

    // PROTECTED API

    protected $htmlName;
}
