<?php

namespace Namest\Drive;

use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Contracts\Validation\Factory as Validator;
use Illuminate\Contracts\Validation\ValidationException;
use Illuminate\Http\Request;
use Intervention\Image\ImageManager;
use Namest\Drive\Contracts\Drive as DriveContract;
use Symfony\Component\HttpFoundation\File\UploadedFile as SymfonyUploadedFile;

/**
 * Class Drive
 *
 * @author  Nam Hoang Luu <nam@mbearvn.com>
 * @package Namest\Drive
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
     * @return UploadedFile
     */
    public function accept($filename)
    {
        if ( ! $this->request->hasFile($filename))
            throw new \InvalidArgumentException("Field [{$filename}] does not exists as upload file.");

        // Get file request
        $uploadedFile = $this->request->file($filename);

        // Validation
        $this->validate($uploadedFile);

        // Return
        return new UploadedFile(
            $uploadedFile,
            $this->filesystem,
            $this->config,
            $this->imageManager
        );
    }

    /**
     * @param SymfonyUploadedFile $file
     */
    private function validate(SymfonyUploadedFile $file)
    {
        $rules      = $this->config->get('drive.rules', []);
        $validation = $this->validator->make(['file' => $file], ['file' => $rules]);

        if ($validation->fails())
            throw new ValidationException($validation);
    }
}
