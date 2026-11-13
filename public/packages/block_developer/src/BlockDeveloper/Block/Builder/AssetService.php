<?php

declare(strict_types=1);

namespace Devoda\BlockDeveloper\BlockDeveloper\Block\Builder;

class AssetService
{
    /**
     * @var string
     */
    protected $assetsDirectory;

    public function __construct($assetsDirectory)
    {
        $this->assetsDirectory = $assetsDirectory;
    }

    /**
     * @param $assetsDirectory
     */
    public function setAssetsDirectory($assetsDirectory)
    {
        $this->assetsDirectory = $assetsDirectory;
    }

    /**
     * @return string
     */
    public function getAssetsDirectory()
    {
        return $this->assetsDirectory;
    }

    /**
     * @param array $assets
     *
     * @return array
     */
    protected function prepareAssets($assets = [])
    {
        // We don't want to do a thing like this (it's the Block Developer config file), so please remove that if available
        if (isset($assets['config.php'])) {
            unset($assets['config.php']);
        }

        return $assets;
    }

    /**
     * @param array $assets
     *
     * @return bool
     */
    public function process($assets = [])
    {
        $fileService = new FileService();
        $assetsDirectory = $this->getAssetsDirectory();
        foreach ($this->prepareAssets($assets) as $asset => $assetContent) {
            $this->makeAssetDirectories($asset, $assetsDirectory, $fileService);
            // Write the actual asset
            $fileService->writeFile($assetsDirectory . '/' . $asset, $assetContent);
        }

        return true;
    }

    /**
     * @param             $asset
     * @param             $assetsDirectory
     * @param FileService $fileService
     */
    protected function makeAssetDirectories($asset, $assetsDirectory, $fileService)
    {
        $prevDir = false;
        $exploded = explode('/', $asset);
        if (count($exploded) > 1) {
            // Let's remove the asset itself, that is the last available value
            end($exploded);
            unset($exploded[key($exploded)]);
            foreach ($exploded as $explode) {
                $prevDir = (!$prevDir ? $assetsDirectory : null) . '/' . $explode;
                if (!file_exists($prevDir)) {
                    $fileService->makeDirectory($prevDir);
                }
            }
        }
    }
}
