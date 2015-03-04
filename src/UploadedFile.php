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
 */
class UploadedFile
{
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
    private $temporaryFile;

    /**
     * @var string
     */
    private $temporaryName;

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

        // Move file to my own temporary directory
        $this->temporaryFileName = $this->makeTemporaryFile()->getRealPath();
    }

    /**
     * @return File
     * @throws \Exception
     */
    private function makeTemporaryFile()
    {
        $root = $this->getRootPath();
        $temp = $this->config->get('drive.temporary');
        $this->filesystem->makeDirectory($temp);
        $this->temporaryName = $name = Str::random();

        return $this->temporaryFile = $this->file->move("{$root}/{$temp}", $name);
    }

    /**
     * @param string $suffix
     *
     * @return string
     */
    public function save($suffix = null)
    {
        // Process file
        $this->processFile($this->file);

        // Find appropriate file name (avoid duplicate file name)
        $filename = $this->getAppropriateFileName($this->file, $suffix);

        // Save the file
        $this->moveUploadedFile($filename);

        // Return relative file path
        return $filename;
    }

    /**
     * @param SymfonyUploadedFile $fileRequest
     */
    private function processFile(SymfonyUploadedFile $fileRequest)
    {
        $this->processImage($fileRequest);
    }

    /**
     * @return Image|null
     */
    private function processImage()
    {
        try {
            $image = $this->imageManager->make($this->temporaryFile->getRealPath());

            $profiles = $this->getDefaultImageProfiles();

            foreach ($profiles as $profile) {
                foreach ($profile as $method => $parameters) {
                    $image = call_user_func_array([$image, $method], $parameters);
                }
            }

            return $image->save();
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
     * @param string $filename
     *
     * @return array
     */
    private function extractPath($filename)
    {
        $segments = explode('/', $filename);
        $filename = array_pop($segments);

        return [implode('/', $segments), $filename];
    }

    /**
     * @param string $filename
     *
     * @return File
     * @throws \Exception
     */
    private function moveUploadedFile($filename)
    {
        $temp     = $this->config->get('drive.temporary');
        $location = $this->config->get('drive.location');
        list($directory, $name) = $this->extractPath($filename);

        $this->filesystem->makeDirectory("{$location}/{$directory}");

        return $this->filesystem->move("{$temp}/{$this->temporaryName}", "{$location}/{$directory}/{$name}");
    }
}
