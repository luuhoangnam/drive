<?php

namespace Namest\Drive;

use Carbon\Carbon;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Intervention\Image\Exception\NotReadableException;
use Intervention\Image\Image;
use Intervention\Image\ImageManager;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile as SymfonyUploadedFile;

/**
 * Class UploadedFile
 *
 * @author  Nam Hoang Luu <nam@mbearvn.com>
 * @package Namest\Drive
 *
 * @method UploadedFile backup(string $name = 'default')                                                                                                     Backups current image state as fallback for reset method under an optional name. Overwrites older state on every call, unless a different name is passed.
 * @method UploadedFile blur(integer $amount = 1)                                                                                                            Apply a gaussian blur filter with a optional amount on the current image. Use values between 0 and 100.
 * @method UploadedFile brightness(integer $level)                                                                                                           Changes the brightness of the current image by the given level. Use values between -100 for min. brightness. 0 for no change and +100 for max. brightness.
 * @method UploadedFile cache(\Closure $callback, integer $lifetime = null, boolean $returnObj)                                                              Method to create a new cached image instance from a Closure callback. Pass a lifetime in minutes for the callback and decide whether you want to get an Intervention Image instance as return value or just receive the image stream.
 * @method UploadedFile canvas(integer $width, integer $height, mixed $bgcolor = null)                                                                       Factory method to create a new empty image instance with given width and height. You can define a background-color optionally. By default the canvas background is transparent.
 * @method UploadedFile circle(integer $radius, integer $x, integer $y, \Closure $callback = null)                                                           Draw a circle at given x, y, coordinates with given radius. You can define the appearance of the circle by an optional closure callback.
 * @method UploadedFile colorize(integer $red, integer $green, integer $blue)                                                                                Change the RGB color values of the current image on the given channels red, green and blue. The input values are normalized so you have to include parameters from 100 for maximum color value. 0 for no change and -100 to take out all the certain color on the image.
 * @method UploadedFile contrast(integer $level)                                                                                                             Changes the contrast of the current image by the given level. Use values between -100 for min. contrast 0 for no change and +100 for max. contrast.
 * @method UploadedFile crop(integer $width, integer $height, integer $x = null, integer $y = null)                                                          Cut out a rectangular part of the current image with given width and height. Define optional x,y coordinates to move the top-left corner of the cutout to a certain position.
 * @method UploadedFile destroy()                                                                                                                            Frees memory associated with the current image instance before the PHP script ends. Normally resources are destroyed automatically after the script is finished.
 * @method UploadedFile ellipse(integer $width, integer $height, integer $x, integer $y, \Closure $callback = null)                                          Draw a colored ellipse at given x, y, coordinates. You can define width and height and set the appearance of the circle by an optional closure callback.
 * @method UploadedFile exif(string $key = null)                                                                                                             Read Exif meta data from current image.
 * @method UploadedFile iptc(string $key = null)                                                                                                             Read Iptc meta data from current image.
 * @method UploadedFile fill(mixed $filling, integer $x = null, integer $y = null)                                                                           Fill current image with given color or another image used as tile for filling. Pass optional x, y coordinates to start at a certain point.
 * @method UploadedFile flip(mixed $mode = 'h')                                                                                                              Mirror the current image horizontally or vertically by specifying the mode.
 * @method UploadedFile fit(integer $width, integer $height = null, \Closure $callback = null, string $position = 'center')                                  Combine cropping and resizing to format image in a smart way. The method will find the best fitting aspect ratio of your given width and height on the current image automatically, cut it out and resize it to the given dimension. You may pass an optional Closure callback as third parameter, to prevent possible upsizing and a custom position of the cutout as fourth parameter.
 * @method UploadedFile gamma(float $correction)                                                                                                             Performs a gamma correction operation on the current image.
 * @method UploadedFile greyscale()                                                                                                                          Turns image into a greyscale version.
 * @method UploadedFile heighten(integer $height, \Closure $callback = null)                                                                                 Resizes the current image to new height, constraining aspect ratio. Pass an optional Closure callback as third parameter, to apply additional constraints like preventing possible upsizing.
 * @method UploadedFile insert(mixed $source, string $position = 'top-left', integer $x = 0, integer $y = 0)                                                 Paste a given image source over the current image with an optional position and a offset coordinate. This method can be used to apply another image as watermark because the transparency values are maintained.
 * @method UploadedFile interlace(boolean $interlace = true)                                                                                                 Determine whether an image should be encoded in interlaced or standard mode by toggling interlace mode with a boolean parameter. If an JPEG image is set interlaced the image will be processed as a progressive JPEG.
 * @method UploadedFile invert()                                                                                                                             Reverses all colors of the current image.
 * @method UploadedFile limitColors(integer $count, mixed $matte = null)                                                                                     Method converts the existing colors of the current image into a color table with a given maximum count of colors. The function preserves as much alpha channel information as possible and blends transarent pixels against a optional matte color.
 * @method UploadedFile line(integer $x1, integer $y1, integer $x2, integer $y2, \Closure $callback = null)                                                  Draw a line from x,y point 1 to x,y point 2 on current image. Define color and/or width of line in an optional Closure callback.
 * @method UploadedFile make(mixed $source)                                                                                                                  Universal factory method to create a new image instance from source, which can be a filepath, a GD image resource, an Imagick object or a binary image data.
 * @method UploadedFile mask(mixed $source, boolean $mask_with_alpha)                                                                                        Apply a given image source as alpha mask to the current image to change current opacity. Mask will be resized to the current image size. By default a greyscale version of the mask is converted to alpha values, but you can set mask_with_alpha to apply the actual alpha channel. Any transparency values of the current image will be maintained.
 * @method UploadedFile opacity(integer $transparency)                                                                                                       Set the opacity in percent of the current image ranging from 100% for opaque and 0% for full transparency.
 * @method UploadedFile orientate()                                                                                                                          This method reads the EXIF image profile setting 'Orientation' and performs a rotation on the image to display the image correctly.
 * @method UploadedFile pickColor(integer $x, integer $y, string $format = 'array')                                                                          Pick a color at point x, y out of current image and return in optional given format.
 * @method UploadedFile pixel(mixed $color, integer $x, integer $y)                                                                                          Draw a single pixel in given color on x, y position.
 * @method UploadedFile pixelate(integer $size)                                                                                                              Applies a pixelation effect to the current image with a given size of pixels.
 * @method UploadedFile polygon(array $points, \Closure $callback = null)                                                                                    Draw a colored polygon with given points. You can define the appearance of the polygon by an optional closure callback.
 * @method UploadedFile rectangle(integer $x1, integer $y1, integer $x2, integer $y2, \Closure $callback = null)                                             Draw a colored rectangle on current image with top-left corner on x,y point 1 and bottom-right corner at x,y point 2. Define the overall appearance of the shape by passing a Closure callback as an optional parameter.
 * @method UploadedFile reset(string $name = 'default')                                                                                                      Resets all of the modifications to a state saved previously by backup under an optional name.
 * @method UploadedFile resize(integer $width, integer $height, \Closure $callback = null)                                                                   Resizes current image based on given width and/or height. To contraint the resize command, pass an optional Closure callback as third parameter.
 * @method UploadedFile resizeCanvas(integer $width, integer $height, string $anchor = 'center', boolean $relative = false, mixed $bgcolor = '#000000')      Resize the boundaries of the current image to given width and height. An anchor can be defined to determine from what point of the image the resizing is going to happen. Set the mode to relative to add or subtract the given width or height to the actual image dimensions. You can also pass a background color for the emerging area of the image.
 * @method UploadedFile response(string $format = null, integer $quality = 90)                                                                               Sends HTTP response with current image in given format and quality.
 * @method UploadedFile rotate(float $angle, string $bgcolor = '#000000')                                                                                    Rotate the current image counter-clockwise by a given angle. Optionally define a background color for the uncovered zone after the rotation.
 * @method UploadedFile sharpen(integer $amount = 10)                                                                                                        Sharpen current image with an optional amount. Use values between 0 and 100.
 * @method UploadedFile text(string $text, integer $x = 0, integer $y = 0, \Closure $callback = null)                                                        Write a text string to the current image at an optional x,y basepoint position. You can define more details like font-size, font-file and alignment via a callback as the fourth parameter.
 * @method UploadedFile trim(string $base = 'top-left', array $away = ['top', 'bottom', 'left', 'right'], integer $tolerance = 0, integer $feather = 0) Trim away image space in given color. Define an optional base to pick a color at a certain position and borders that should be trimmed away. You can also set an optional tolerance level, to trim similar colors and add a feathering border around the trimed image.
 * @method UploadedFile widen(integer $width, \Closure $callback = null)
 *
 */
class UploadedFile
{
    /**
     * @var array
     */
    protected $useProfiles = [];

    /**
     * @var SymfonyUploadedFile
     */
    private $file;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var array
     */
    private $profiles;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var ImageManager
     */
    private $imageManager;

    /**
     * @var File
     */
    private $temporaryOriginalFile;

    /**
     * @var string
     */
    private $temporaryOriginalName;

    /**
     * @var File
     */
    private $temporaryEditedFile;

    /**
     * @var string
     */
    private $temporaryEditedName;

    /**
     * @param SymfonyUploadedFile $file
     * @param Filesystem          $filesystem
     * @param Config              $config
     * @param ImageManager        $imageManager
     */
    public function __construct(
        SymfonyUploadedFile $file,
        Filesystem $filesystem,
        Config $config,
        ImageManager $imageManager
    ) {
        $this->file         = $file;
        $this->filesystem   = $filesystem;
        $this->config       = $config;
        $this->imageManager = $imageManager;
    }

    /**
     * Clean up
     *
     * @throws \Exception
     */
    public function __destruct()
    {
        $this->deleteTemporaryFiles();
    }

    /**
     * @param bool $original
     *
     * @return File
     * @throws \Exception
     */
    private function makeTemporaryFile($original = true)
    {
        $root = $this->getRootPath();
        $temp = $this->config->get('drive.temporary');
        $name = Str::random();

        $this->filesystem->makeDirectory($temp);

        if ($original) {
            $this->temporaryOriginalName = $name;

            return $this->file->move("{$root}/{$temp}", $name);
        }

        if ( ! $this->filesystem->copy("{$temp}/{$this->temporaryOriginalName}", "{$temp}/{$name}"))
            throw new \Exception("Can not copy file [{$temp}/{$this->temporaryOriginalName}] for editing.");

        $this->temporaryEditedName = $name;

        return new File("{$root}/{$temp}/{$this->temporaryOriginalName}");
    }

    /**
     * @throws \Exception
     */
    private function deleteTemporaryFiles()
    {
        $directory = $this->config->get('drive.temporary');

        $this->filesystem->deleteDirectory($directory);
    }

    /**
     * @param string $suffix
     *
     * @return string|null
     */
    public function save($suffix = null)
    {
        // Move file to my own temporary directory
        $this->temporaryOriginalFile = $this->makeTemporaryFile();

        // Process file
        $this->processFile();

        // Find appropriate file name (avoid duplicate file name)
        $filename = $this->getAppropriateFileName($this->file, $suffix);

        // Save the file
        if ($this->moveUploadedFile($filename))
            return $filename;

        return null;
    }

    /**
     * @throws \Exception
     */
    private function processFile()
    {
        $profiles = $this->useProfiles ?: array_keys($this->getDefaultImageProfiles());

        if ( ! $profiles)
            return;

        $this->temporaryEditedFile = $this->makeTemporaryFile(false);

        foreach ($profiles as $name) {
            $profile = $this->getProfile($name);

            if ($profile['type'] == 'image')
                $this->processImage($profile);
        }

        $this->useProfiles         = [];
        $this->temporaryEditedFile = null;
    }

    /**
     * @param array $profile
     *
     * @return Image
     */
    private function processImage(array $profile)
    {
        try {
            $image = $this->imageManager->make($this->temporaryEditedFile->getRealPath());

            foreach ($profile as $method => $parameters) {
                if ($method === 'type')
                    continue;

                $image = call_user_func_array([$image, $method], $parameters);
            }

            $root = $this->getRootPath();
            $temp = $this->config->get('drive.temporary');

            return $image->save("{$root}/{$temp}/{$this->temporaryEditedName}");
        } catch ( NotReadableException $e ) {
            return null;
        }
    }

    /**
     * @return array
     */
    private function getProfiles()
    {
        return $this->profiles ?: $this->config->get('drive.profiles') ?: [];
    }

    /**
     * @return array
     */
    private function getDefaultImageProfiles()
    {
        $defaults = $this->config->get('drive.default_profiles.image', []);

        $profiles = [];

        foreach ($this->getProfiles() as $name => $profile) {
            if ($profile['type'] != 'image' || ! in_array($name, $defaults))
                continue;

            unset($profile['type']);
            $profiles[$name] = $profile;
        }

        return $profiles;
    }

    /**
     * @param string $name
     *
     * @return null
     */
    private function getProfile($name)
    {
        $profiles = $this->getProfiles();

        if (array_key_exists($name, $profiles))
            return $profiles[$name];

        throw new \InvalidArgumentException("Profile [{$name}] does not exists.");
    }

    /**
     * @param SymfonyUploadedFile $file
     * @param string              $suffix
     *
     * @return string
     * @throws \Exception
     */
    private function getAppropriateFileName(SymfonyUploadedFile $file, $suffix = null)
    {
        $location  = $this->config->get('drive.location');
        $structure = $this->config->get('drive.structure');

        if ( ! strstr($structure, '{name}'))
            throw new \Exception("File structure [{$structure}] invalid. At least {name} is exists in structure.");

        $now = Carbon::now();

        $originalFileName = $file->getClientOriginalName();
        $ext              = $file->getClientOriginalExtension();
        $name             = substr($originalFileName, 0, strlen($originalFileName) - strlen($ext) - 1) . $suffix;

        $tail = '';

        do {
            $filename = $structure;

            $replaces = [
                '{year}'  => $now->year,
                '{month}' => $now->month,
                '{name}'  => "{$name}{$tail}",
                '{ext}'   => $ext,
            ];

            foreach ($replaces as $search => $replace) {
                $filename = str_replace($search, $replace, $filename);
            }

            $tail = $tail ? mt_rand(0, 9) : '-' . mt_rand(0, 9);
        } while ($this->filesystem->exists("{$location}/{$filename}"));

        return $filename;
    }

    /**
     * @return string
     * @throws \Exception
     */
    private function getRootPath()
    {
        $default = $this->config->get("filesystems.default");

        $disk = $this->config->get("filesystems.disks.{$default}");

        switch ($disk['driver']) {
            case 'local':
                return $disk['root'];
            case 's3':
                return $disk['bucket'];
            case 'rackspace':
                return $disk['container'];
            default:
                throw new \Exception("Not supported disk driver for upload");
        }
    }

    /**
     * @param string $path
     *
     * @return array
     */
    private function extractPath($path)
    {
        $segments = explode('/', $path);
        $filename = array_pop($segments);

        return [implode('/', $segments), $filename];
    }

    /**
     * @param string $filename
     *
     * @return bool
     * @throws \Exception
     */
    private function moveUploadedFile($filename)
    {
        $temp     = $this->config->get('drive.temporary');
        $location = $this->config->get('drive.location');
        list($directory, $name) = $this->extractPath($filename);

        $this->filesystem->makeDirectory("{$location}/{$directory}");

        return $this->filesystem->copy("{$temp}/{$this->temporaryEditedName}", "{$location}/{$directory}/{$name}");
    }

    /**
     * @param string $name
     *
     * @return $this
     */
    public function profile($name)
    {
        $this->useProfiles[] = $name;

        return $this;
    }

    /**
     * @param string $name
     * @param array  $arguments
     *
     * @return $this
     */
    public function __call($name, $arguments)
    {
        $this->profiles = $this->getProfiles();

        if ($this->profiles && array_key_exists('editing', $this->profiles))
            $profile = $this->profiles['editing'];
        else
            $profile = ['type' => 'image'];

        $profile[$name] = $arguments;

        $this->profiles['editing'] = $profile;
        $this->useProfiles[]       = 'editing';

        return $this;
    }

}
