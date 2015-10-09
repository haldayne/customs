<?php
namespace Haldayne\Customs;

/**
 * Return information about the upload configuration.
 *
 * Some values come directly from the PHP configuration. Others are calculated
 * using the PHP configuration and run-time information.
 *
 * @see http://php.net/manual/en/ini.core.php#ini.sect.file-uploads
 */
class UploadConfig
{
    /**
     * Are file uploads enabled?
     *
     * Specified at the system level by the `file_uploads` ini directive.
     * 
     * @return bool
     * @api
     * @since 1.0.0
     */
    public static function isEnabled()
    {
        return (bool)ini_get('file_uploads');
    }

    /**
     * In what directory will uploads be held for processing?
     *
     * Specified at the system level by the `upload_tmp_dir` ini directive.
     * If that directory isn't writeable, then PHP falls back to the system
     * temporary directory, which is specified at the system level by the
     * `sys_temp_dir` ini directive (and we can just ask `sys_get_temp_dir`).
     *
     * @return string|null
     * @api
     * @since 1.0.0
     */
    public static function uploadWorkingPath()
    {
        $dir = ini_get('upload_tmp_dir');
        if (is_dir($dir) && is_writeable($dir)) {
            return $dir;
        }

        return sys_get_temp_dir();
    }

    /**
     * What is the maximum file size supported system-wide?
     *
     * Controlled by the system-level directive `upload_max_filesize`, but
     * also constrained by the system-level directive `post_max_size`. The
     * *lower* of the two values defines the *upper* limit of the maximum
     * file size.
     *
     * If the number of returned bytes equals `PHP_INT_MAX`, there is
     * effectively no limit.
     *
     * @return int Bytes
     * @api
     * @since 1.0.0
     */
    public static function systemMaxUploadBytes()
    {
        // get both limits as bytes
        $post_max   = ini_size_to_bytes(ini_get('post_max_size'));
        $upload_max = ini_size_to_bytes(ini_get('upload_max_filesize'));

        return static::limit($post_max, $upload_max);
    }

    /**
     * What is the maximum file size reported for the submitted form?
     *
     * If you include a hidden input element named `MAX_FILE_SIZE` in your
     * form, then all file inputs that come *after* that hidden input element
     * will be limited to the number of bytes given in the `MAX_FILE_SIZE`.
     *
     * If the number of returned bytes equals `PHP_INT_MAX`, there is
     * effectively no limit.
     *
     * @return int Bytes
     * @api
     * @since 1.0.0
     */
    public static function formMaxUploadBytes()
    {
        if (array_key_exists('MAX_FILE_SIZE', $_POST)) {
            return intval($_POST['MAX_FILE_SIZE']);
        } else {
            return PHP_INT_MAX;
        }
    }

    /**
     * How many simultaneous file uploads do we support?
     *
     * If the number of returned uploads equals `PHP_INT_MAX`, there is
     * effectively no limit.
     *
     * @return int Number of uploads
     * @api
     * @since 1.0.0
     */
    public static function maxFileUploads()
    {
        // we have to consider Suhosin
        $php_max     = ini_get('max_file_uploads');
        $suhosin_max = ini_get('suhosin.upload.max_uploads');
        $limit = static::limit($php_max, $suhosin_max);

        // we also have to consider max_input_vars
        $input_max = ini_get('max_input_vars');
        return static::limit($limit, $input_max);
    }

    // PRIVATE API

    /**
     * Given two values which follow the "0 or fewer is unlimited" pattern,
     * return the limiting number or PHP_INT_MAX if neither would limit.
     */
    private static function limit($a, $b)
    {
        // if both are limited, the minimum is the limit
        if (0 < $a && 0 < $b) {
            return min($a, $b);

        // if both are unlimited, there is no limit
        } else if ($a <= 0 && $b <= 0) {
            return PHP_INT_MAX;

        // if one is limited, the maximum is the limit
        } else {
            return max($a, $b);
        }
    }
}
