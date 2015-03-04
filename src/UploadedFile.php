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

        // Move file to my own temporary directory
        $this->temporaryOriginalFile = $this->makeTemporaryFile();
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
        // Process file
        $this->processFile();

        // Find appropriate file name (avoid duplicate file name)
        $filename = $this->getAppropriateFileName($this->file, $suffix);

        // Save the file
        if ($this->moveUploadedFile($filename))
            return $filename;

        return null;
    }

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
}
