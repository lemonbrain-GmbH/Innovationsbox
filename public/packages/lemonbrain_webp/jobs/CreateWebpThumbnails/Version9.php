<?php

declare(strict_types=1);

namespace Concrete\Package\LemonbrainWebp\Job\CreateWebpThumbnails;

defined('C5_EXECUTE') or die('Access Denied.');

use Concrete\Core\Application\Application;
use Concrete\Core\Entity\File\File;
use Concrete\Core\Entity\File\Image\Thumbnail\Type\Type as ThumbnailTypeEntity;
use Concrete\Core\File\FileList;
use Concrete\Core\File\Image\Thumbnail\Path\Resolver as ThumbnailPathResolver;
use Concrete\Core\File\Type\Type as FileType;
use Concrete\Core\Job\JobQueue;
use Concrete\Core\Job\JobQueueMessage;
use Concrete\Core\Job\QueueableJob;
use Concrete\Core\Support\Facade\Application as App;
use Doctrine\ORM\EntityManagerInterface;
use LemonbrainWebp\Handler\ThumbnailEventHandler;

class Version9 extends QueueableJob
{
    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * @var \Concrete\Core\File\Image\Thumbnail\Path\Resolver
     */
    protected $thumbnailPathResolver;

    /**
     * @var \Concrete\Core\File\Image\Thumbnail\Type\Version[]|null
     */
    private $thumbnailTypeVersions;

    /**
     * The container instance to be used to build dependencies.
     *
     * @var \Concrete\Core\Application\Application
     */
    protected $app;

    /**
     * Initialize the instance.
     *
     * @param EntityManagerInterface $entityManager
     * @param ThumbnailPathResolver $thumbnailPathResolver
     * @param Application $app the application instance
     */
    public function __construct(EntityManagerInterface $entityManager, ThumbnailPathResolver $thumbnailPathResolver, Application $app)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->thumbnailPathResolver = $thumbnailPathResolver;
        $this->app = $app;
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Job\Job::getJobName()
     */
    public function getJobName()
    {
        return t('Create webp Thumbnails');
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Job\Job::getJobDescription()
     */
    public function getJobDescription()
    {
        return t('Recreate all webp thumbnails.');
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Job\QueueableJob::start()
     */
    public function start(JobQueue $queue)
    {
        $list = new FileList();
        $list->filterByType(FileType::T_IMAGE);
        $q = $list->deliverQueryObject()->execute();
        while (false !== ($row = $q->fetch())) {
            $queue->send((int) $row['fID']);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Job\QueueableJob::processQueueItem()
     */
    public function processQueueItem(JobQueueMessage $msg)
    {
        $fID = (int) $msg->body;
        if ($fID > 0) {
            $file = $this->entityManager->find(File::class, $fID);
            if ($file !== null) {
                $fileVersion = $file->getApprovedVersion();
                if ($fileVersion !== null) {
                    if ($fileVersion->getTypeObject()->supportsThumbnails()) {
                        $imageWidth = (int) $fileVersion->getAttribute('width');
                        $imageHeight = (int) $fileVersion->getAttribute('height');
                        foreach ($this->getThumbnailTypeVersions() as $thumbnailTypeVersion) {
                            if ($thumbnailTypeVersion->shouldExistFor($imageWidth, $imageHeight, $file)) {
                                $thumbnailPath = $thumbnailTypeVersion->getFilePath($fileVersion);
                                $event = new \Concrete\Core\File\Event\ThumbnailGenerate($thumbnailPath, $thumbnailTypeVersion);
                                $this->app->make(ThumbnailEventHandler::class)->handleGenerate($event);
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Job\QueueableJob::finish()
     */
    public function finish(JobQueue $q)
    {
        return t('All thumbnail have been recreated.');
    }

    /**
     * @return \Concrete\Core\File\Image\Thumbnail\Type\Version[]
     */
    protected function getThumbnailTypeVersions()
    {
        if ($this->thumbnailTypeVersions === null) {
            $this->thumbnailTypeVersions = $this->getWebpThumbnailsVersionList();
        }

        return $this->thumbnailTypeVersions;
    }

    /**
     * @return \Concrete\Core\File\Image\Thumbnail\Type\Version[]
     */
    private function getWebpThumbnailsVersionList()
    {
        $app = App::getFacadeApplication();
        $config = $app->make('config');
        $createHightDPIVersions = (bool) $config->get('concrete.file_manager.images.create_high_dpi_thumbnails');

        $types = $this->getWebpList();
        $versions = [];

        foreach ($types as $type) {
            $versions[] = $type->getBaseVersion();
            if ($createHightDPIVersions) {
                $versions[] = $type->getDoubledVersion();
            }
        }

        return $versions;
    }

    /**
     * Get the list of all the available webp thumbnail types.
     *
     * @return \Concrete\Core\Entity\File\Image\Thumbnail\Type\Type[]
     */
    private function getWebpList()
    {
        $app = App::getFacadeApplication();
        $em = $app->make(EntityManagerInterface::class);
        $handles = [];

        foreach (ThumbnailEventHandler::getThumbnails() as $thumbnail) {
            $handles[] = $thumbnail['handler'];
        }

        return $em->getRepository(ThumbnailTypeEntity::class)
            ->findBy(['ftTypeHandle' => $handles], ['ftTypeWidth' => 'asc']);
    }
}
