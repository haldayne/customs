<?php
namespace Haldayne\Customs;

/**
 * Accessor methods for the HTML name of a file upload.
 *
 * Every uploaded file has a name, given to it in the HTML form. The HTML
 * spec says the name qualifies as CDATA, so pretty much any character can
 * comprise an HTML variable name. However, the HTML name we have access
 * to is subject to PHP engine rules' name mangling.
 *
 * In many cases, the HTML name returned matches the HTML name given, but
 * not in all cases:
 *
 * Given in Form     |Returned from getHtmlName()   |Match?
 * ------------------|------------------------------|------
 * foo               |foo                           |Yes
 * foo[bar]          |foo[bar]                      |Yes
 * foo[bar][baz_1]   |foo[bar][baz_1]               |Yes
 * foo_bar           |foo_bar                       |Yes
 * foo.bar           |foo_bar                       |No
 * foo bar           |foo_bar                       |No
 * foo[]             |foo[0]                        |No
 *
 * As a rule of thumb, avoid using "." and " " in your HTML form names.
 * When using array syntax, prefer explicit naming your keys.
 */
trait HtmlNameAccessorTrait
{
    /**
     * Set the HTML variable name.
     *
     * Only UploadIterator should set the name on artifact objects using
     * this trait.
     *
     * @param string $htmlName The HTML variable name.
     * @return self
     * @internal
     */
    public function setHtmlName($htmlName)
    {
        $this->htmlName = $htmlName;
        return $this;
    }

    /**
     * Get the HTML variable name.
     *
     * @return string
     * @api
     * @since 1.0.0
     */
    public function getHtmlName()
    {
        return $this->htmlName;
    }

    // PRIVATE API

    /** @var string $htmlName The HTML name we were given. */
    private $htmlName;
}
