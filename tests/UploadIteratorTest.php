<?php
namespace Haldayne\Customs;

use Faker;
use org\bovigo\vfs;

class UploadIteratorTest extends \PHPUnit_Framework_TestCase
{
    public function test_construction()
    {
        $it = new UploadIterator(array ());
        $this->assertInstanceOf('\SeekableIterator', $it);
        $this->assertInstanceOf('\ArrayAccess', $it);
        $this->assertInstanceOf('\Countable', $it);
    }

    public function test_no_upload()
    {
        $it = new UploadIterator(array ());
        $this->assertSame(0, $it->count());
    }

    public function test_single_upload()
    {
        // no mangling
        $n    = static::$faker->numberBetween(1, 3);
        $info = static::provides_single_upload_info($n);
        $it   = new UploadIterator($info);
        $this->assertSame($n, $it->count(), '$info = '. var_export($info, true));

        // with mangling
        $info = $this->mangle_names($info);
        $it   = new UploadIterator($info);
        $this->assertSame($n, $it->count(), '$info = '. var_export($info, true));
    }

    public function test_multiple_upload()
    {
        // no mangling
        $m    = static::$faker->numberBetween(1, 3);
        $n    = static::$faker->numberBetween(1, 3);
        $info = static::provides_multiple_upload_info($m, $n);
        $it   = new UploadIterator($info);
        $this->assertSame($m * $n, $it->count(), '$info = '. var_export($info, true));

        // with mangling
        $info = $this->mangle_names($info);
        $it   = new UploadIterator($info);
        $this->assertSame($m * $n, $it->count(), '$info = '. var_export($info, true));
    }

    // -=-= Setup =-=-

    public static $faker;
    public static $vfs;

    public static function setupBeforeClass()
    {
        static::$faker = Faker\Factory::create();
        static::$vfs   = vfs\vfsStream::setup('tmp');
    }

    // -=-= Data Providers =-=-

    /**
     * Emulates a populated upload from an HTML form like:
     * <input type='file' name='foo' />
     * <input type='file' name='bar' />
     * <input type='file' name='baz' />
     *
     * @param int $n The number of file inputs to include, defaults to 1
     * @param array $defaults
     */
    public static function provides_single_upload_info($n = 1, array $defaults = [])
    {
        $single = [];
        for (; 0 < $n; $n--) {
            $single[static::provides_name()] = static::provides_info($defaults);
        }
        return $single;
    }

    /**
     * Emulates a populated upload from an HTML form like:
     * <input type='file' name='foo[bar]' />
     * <input type='file' name='foo[baz]' />
     * <input type='file' name='foo[2]' />
     * <input type='file' name='bar[0]' />
     * <input type='file' name='bar[x]' />
     *
     * @param int $m The number of outer file inputs to include, defaults to 1
     * @param int $n The number of inner file inputs to include, defaults to 1
     * @param array $defaults
     */
    public static function provides_multiple_upload_info($m = 1, $n = 1, array $defaults = [])
    {
        $multi = [];
        for (; 0 < $m; $m--) {
            $name = static::provides_name(); // outer HTML element
            $multi[$name] = [];
            for (; 0 < $n; $n--) {
                $info = static::provides_info($defaults);
                $subn = static::provides_name(); // and inner
                foreach ($info as $prop => $val) {
                    $multi[$name][$prop][$subn] = $info[$prop];
                }
            }
        }
        return $multi;
    }

    /**
     * Provides data you'd find in a $_FILES entry: name, type, etc.
     *
     * @param array $defaults Override otherwise random values
     */
    public static function provides_info(array $defaults = [])
    {
        $image = static::provides_file();
        $ext   = $image->getExtension();
        return array_replace(
            [
                'name' => static::$faker->catchPhrase . '.' . $ext,
                'type' => 'image/' . $ext,
                'size' => $image->getSize(),
                'tmp_name' => $image->getPathname(),
                'error' => static::$faker->randomElement([
                    UPLOAD_ERR_OK,
                    UPLOAD_ERR_INI_SIZE,
                    UPLOAD_ERR_FORM_SIZE,
                    UPLOAD_ERR_PARTIAL,
                    UPLOAD_ERR_NO_FILE,
                    UPLOAD_ERR_NO_TMP_DIR,
                    UPLOAD_ERR_CANT_WRITE,
                    UPLOAD_ERR_EXTENSION,
                ]),
            ],
            $defaults
        );
    }

    /**
     * Provides a SplFileInfo corresponding to a random file.
     */
    public static function provides_file()
    {
        $w = static::$faker->numberBetween(1, 100);
        $h = static::$faker->numberBetween(1, 100);
        return new \SplFileInfo(
            static::$faker->image(   // make an image
                static::$vfs->url(), //  in vfs memory
                $w,$h                //  and keep it small and fast
            )
        );
    }

    /**
     * Return a random, unique HTML name. Technically, this could be any
     * character from the ISO-10646 standard and PHP is meant to handle these.
     * However, PHP mangles two characters: '.' and ' '.  We omit these
     * characters from this generation so that we can test them specifically.
     * PHP also interprets [] specially, so we omit those here as we test
     * them specifically elsewhere.
     * @see http://stackoverflow.com/q/3424860/2908724
     * @see https://bugs.php.net/bug.php?id=34882
     */
    public static function provides_name($mangle = true)
    {
        return static::$faker->unique()->regexify('[^][. ]{1,30}');
    }

    /**
     * Always returns some kind of unique mangled name.
     */
    public static function provides_mangled_name()
    {
        $count = static::$faker->unique()->numberBetween(1, 9999);
        return str_repeat('_', $count);
    }

    // PRIVATE API

    private function mangle_names(array $info)
    {
        $mangled = [];
        foreach ($info as $name => $value) {
            $mangled[static::provides_mangled_name()] = $value;
        }
        return $mangled;
    }
}
