<?php

namespace Namest\Drive;

use Carbon\Carbon;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Contracts\Validation\ValidationException;
use Illuminate\Http\Request;
use Illuminate\Contracts\Validation\Factory as Validator;
use Intervention\Image\Exception\NotReadableException;
use Intervention\Image\Image;
use Intervention\Image\ImageManager;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Illuminate\Contracts\Config\Repository as Config;
use Namest\Drive\Contracts\Drive as DriveContract;

/**
 * Class Drive
 *
 * @author  Nam Hoang Luu <nam@mbearvn.com>
 * @package Namest\Drive
 *
 */
class Drive implements DriveContract
{
    /**
     * @var Request
     */
    private $request;

    /**
     * @var Validator
     */
    private $validator;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var ImageManager
     */
    private $imageManager;

    /**
     * @var array
     */
    private $profiles;
    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @param Request      $request
     * @param Validator    $validator
     * @param Config       $config
     * @param ImageManager $imageManager
     * @param Filesystem   $filesystem
     */
    public function __construct(
        Request $request,
        Validator $validator,
        Config $config,
        ImageManager $imageManager,
        Filesystem $filesystem
    ) {
        $this->request      = $request;
        $this->validator    = $validator;
        $this->config       = $config;
        $this->imageManager = $imageManager;
        $this->filesystem   = $filesystem;
    }

    /**
     * @param string $filename upload file name
     *
     * @return string
     */
    public function store($filename)
    {
        if ( ! $this->request->hasFile($filename))
            throw new \InvalidArgumentException("Field [{$filename}] does not exists as upload file.");

        // Get file request
        $file = $this->request->file($filename);

        // Validation
        $this->validate($file);

        // Process file
        $this->processFile($file);

        // Find appropriate file name (avoid duplicate file name)
        $filename = $this->getAppropriateFileName($file);

        // Save the file
        $this->moveUploadedFile($file, $filename);

        // Return relative file path
        return $filename;
    }

    /**
     * @param UploadedFile $fileRequest
     */
    private function validate(UploadedFile $fileRequest)
    {
        $rules      = $this->config->get('drive.rules', []);
        $validation = $this->validator->make(['file' => $fileRequest], ['file' => $rules]);

        if ($validation->fails())
            throw new ValidationException($validation);
    }

    /**
     * @param UploadedFile $fileRequest
     */
    private function processFile(UploadedFile $fileRequest)
    {
        $this->processImage($fileRequest);
    }

    /**
     * @param UploadedFile $file
     *
     * @return Image|null
     */
    private function processImage(UploadedFile $file)
    {
        try {
            $image = $this->imageManager->make($file->getRealPath());

            $profiles = $this->getImageProfiles();

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
    private function getImageProfiles()
    {
        $profiles = [];

        foreach ($this->getProfiles() as $name => $profile) {
            if ($profile['type'] != 'image')
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
     * @param UploadedFile $file
     *
     * @return string
     * @throws \Exception
     */
    private function getAppropriateFileName(UploadedFile $file)
    {
        $location  = $this->config->get('drive.location');
        $structure = $this->config->get('drive.structure');

        if ( ! strstr($structure, '{name}'))
            throw new \Exception("File structure [{$structure}] invalid. At least {name} is exists in structure.");

        $now = Carbon::now();

        $originalFileName = $file->getClientOriginalName();
        $ext              = $file->getClientOriginalExtension();
        $name             = substr($originalFileName, 0, strlen($originalFileName) - strlen($ext) - 1);

        $suffix = '';

        do {
            $filename = $structure;

            $replaces = [
                '{year}'  => $now->year,
                '{month}' => $now->month,
                '{name}'  => "{$name}{$suffix}",
                '{ext}'   => $ext,
            ];

            foreach ($replaces as $search => $replace) {
                $filename = str_replace($search, $replace, $filename);
            }

            $suffix = $suffix ? mt_rand(0, 9) : '-' . mt_rand(0, 9);
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
     * @param UploadedFile $file
     * @param string       $filename
     *
     * @return File
     */
    private function moveUploadedFile(UploadedFile $file, $filename)
    {
        $root     = $this->getRootPath();
        $location = $this->config->get('drive.location');
        list($directory, $name) = $this->extractPath($filename);

        return $file->move("{$root}/{$location}/{$directory}", $name);
    }
}
