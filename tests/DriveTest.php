<?php

use Namest\Drive\Drive;

/**
 * DriveTest Test Case
 *
 * @author  Nam Hoang Luu <nam@mbearvn.com>
 *
 */
class DriveTest extends PHPUnit_Framework_TestCase
{
    public function test_it_should_accept_file_upload()
    {
        $request      = $this->mockRequest();
        $validator    = $this->mockValidator();
        $config       = $this->mockConfig();
        $imageManager = $this->mockImageManager();
        $filesystem   = $this->mockFilesystem();

        $drive  = new Drive($request, $validator, $config, $imageManager, $filesystem);
        $result = $drive->accept('whatever');

        $this->assertInstanceOf('\Namest\Drive\UploadedFile', $result);
    }

    /**
     * @return \Illuminate\Http\Request
     */
    private function mockRequest()
    {
        $uploadedFile = Mockery::mock('\Symfony\Component\HttpFoundation\File\UploadedFile');

        /** @var \Illuminate\Http\Request $request */
        $request = Mockery::mock('\Illuminate\Http\Request');
        $request->shouldReceive('hasFile')->once()->with('whatever')->andReturn(true);
        $request->shouldReceive('file')->once()->with('whatever')->andReturn($uploadedFile);

        return $request;
    }

    /**
     * @return \Illuminate\Contracts\Config\Repository
     */
    private function mockConfig()
    {
        /** @var \Illuminate\Contracts\Config\Repository $config */
        $config = Mockery::mock('\Illuminate\Contracts\Config\Repository');
        $config->shouldReceive('get')->once()->with('drive.rules', [])->andReturn(['max:2048']);
        $config->shouldReceive('get')->once()->with('drive.temporary')->andReturn('temp');

        return $config;
    }

    /**
     * @param bool $fails
     *
     * @return \Illuminate\Contracts\Validation\Factory
     */
    private function mockValidator($fails = false)
    {
        /** @var \Illuminate\Contracts\Validation\Validator $validation */
        $validation = Mockery::mock('\Illuminate\Contracts\Validation\Validator');
        $validation->shouldReceive('fails')->once()->andReturn($fails);

        /** @var \Illuminate\Contracts\Validation\Factory $validator */
        $validator = Mockery::mock('\Illuminate\Contracts\Validation\Factory');
        $validator->shouldReceive('make')->once()->andReturn($validation);

        return $validator;
    }

    /**
     * @return \Illuminate\Contracts\Filesystem\Filesystem
     */
    private function mockFilesystem()
    {
        /** @var \Illuminate\Contracts\Filesystem\Filesystem $filesystem */
        $filesystem = Mockery::mock('\Illuminate\Contracts\Filesystem\Filesystem');
        $filesystem->shouldReceive('deleteDirectory')->once()->andReturn(true);

        return $filesystem;
    }

    /**
     * @return \Intervention\Image\ImageManager
     */
    private function mockImageManager()
    {
        /** @var \Intervention\Image\ImageManager $imageManager */
        $imageManager = Mockery::mock('\Intervention\Image\ImageManager');

        return $imageManager;
    }
}
