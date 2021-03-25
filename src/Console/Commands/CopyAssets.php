<?php

namespace MethodicalWorks\Console\Commands;

use Illuminate\Console\Command;

abstract class CopyAssets extends Command {
    /**
     *
     * @var string
     */
    protected $signature = 'copy:assets';
    
    /**
     * The folder in the public folder
     * @var string
     */
    protected $publicVendorFolder = 'vendor';
    
    /**
     * The composer's vendor folder
     * @var string
     */
    protected $vendorFolder = 'vendor';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public abstract function handle();

    /**
     * Copy the files in the package to the specified location
     * @param string $package
     * @param string[] $files (optional)
     * @param string $packageName (optional)
     */
    protected function install($package, $files = null, $packageName = null) {
        // try to guess package name
        if (empty($packageName)) {
            $packageTokens = explode('/', $package);
            $isSubDirectory = count($packageTokens) >= 3;
            $packageName = $isSubDirectory? $packageTokens[1] : basename($package);
        }

        $dir = public_path($this->publicVendorFolder . '/' . $packageName);

        if ($files) {
            if (!file_exists($dir)) {
                mkdir($dir);
            }

            foreach ($files as $file) {
                $origin = base_path($this->vendorFolder . '/' . $package . '/' . $file);

                if (is_dir($origin)) {
                    $destination = $dir . '/' . $file;
                    $this->deleteFile($destination);
                    symlink($origin, $destination);

                } else if (is_file($origin)) {
                    $fileHasPath = strpos($file, '/') !== false;
                    $destination = $dir . '/' . ($fileHasPath? basename($file) : $file);
                    copy($origin, $destination);
                }
            }
        } else {
            $sourcePath = base_path($this->vendorFolder . '/' . $package);

            if (file_exists($sourcePath)) {
                $this->deleteFile($dir);
                symlink($sourcePath, $dir);
            }
        }
    }

    /**
     *
     * @param string $path
     * @return bool
     */
    protected function deleteFile($path) {
        if (is_file($path)) {
            chmod($path, 666);
            return unlink($path);
        }

        if (is_link($path)) {
            try {
                return unlink($path);
            } catch (\Throwable $error) {
                return rmdir($path);
            }
        }

        if (is_dir($path)) {
            $this->deleteFolderFiles($path);
            return rmdir($path);
        }
    }

    /**
     *
     * @param string $path
     * @param string[] $excludes (optional)
     */
    protected function deleteFolderFiles($path, $excludes = []) {
        $files = scandir($path);
        foreach ($files as &$file) {
            if ($file != '.' && $file != '..' && !in_array($file, $excludes)) {
                $this->deleteFile($path . '/' . $file);
            }
        }
    }
}
