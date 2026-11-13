<?php

declare(strict_types=1);

namespace Devoda\BlockDeveloper\Console\Command;

use Concrete\Core\Block\BlockType\BlockType;
use Concrete\Core\File\Service\File;
use Concrete\Core\Package\Package;
use Devoda\BlockDeveloper\BlockDeveloper;
use Devoda\BlockDeveloper\BlockDeveloper\Block\Builder;
use Devoda\BlockDeveloper\BlockDeveloper\Block\Builder\FileService;
use Devoda\BlockDeveloper\Console\Command;
use Exception;
use Symfony\Component\Console\Input\InputOption;

class BlockDeveloperCommand extends Command
{
    /**
     * @var string
     */
    protected $blockHandlePattern = '/^([a-z]+)([a-z_]+)[a-z]$/';

    /**
     * @var array
     */
    protected $blockHandleReservedKeywords = ['abstract', 'and', 'array', 'as', 'break', 'callable', 'case', 'catch', 'class', 'clone', 'const', 'continue', 'declare', 'default', 'die', 'do', 'echo', 'else', 'elseif', 'empty', 'enddeclare', 'endfor', 'endforeach', 'endif', 'endswitch', 'endwhile', 'eval', 'exit', 'extends', 'final', 'finally', 'for', 'foreach', 'function', 'global', 'goto', 'if', 'implements', 'include', 'include_once', 'instanceof', 'insteadof', 'interface', 'isset', 'list', 'namespace', 'new', 'or', 'print', 'private', 'protected', 'public', 'require', 'require_once', 'return', 'static', 'switch', 'throw', 'trait', 'try', 'unset', 'use', 'var', 'while', 'xor', 'yield'];

    protected function configure()
    {
        $this->setName('c5:block-developer')
            ->setDescription('Develop/Create Block Types')
            ->addEnvOption() // not available in Legacy
            ->addOption('block-handle', 'b', InputOption::VALUE_REQUIRED, 'Handle of the Block Type')
            ->addOption('block-name', null, InputOption::VALUE_OPTIONAL, 'Name of the Block Type')
            ->addOption('package-handle', 'p', InputOption::VALUE_OPTIONAL, 'Handle of the Package (optional)')
            ->addOption('skip', 's', InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'Which additional options should be skipped [build_view|build_templates|install]')
            ->setHelp(
                <<<EOT
Returns codes:
  0 operation completed successfully
  1 errors occurred

More info at https://www.concrete5.org/marketplace/addons/block-developer
EOT
            );
    }

    /**
     * @throws Exception
     */
    protected function handle()
    {
        BlockDeveloper::clearOverrideCache();
        if ($this->isInteractive()) {
            $blockHandle = $this->getBlockHandle();
            if (trim($blockHandle) == '' || $this->blockHandleError($blockHandle) !== false) {
                $blockHandle = $this->askWithCompletion($this->getBlockHandleQuestion(), $this->getBlockHandleQuestionChoices());
                while ($this->blockHandleError($blockHandle) !== false) {
                    $this->output->writeln('<error>Please use at least 3 lowercase a-z characters for the Block Handle and do not start/end with an underscore</error>');
                    $blockHandle = $this->askWithCompletion($this->getBlockHandleQuestion(), $this->getBlockHandleQuestionChoices());
                }
            }
            $packageHandle = null;
            if ($blockTypeController = BlockDeveloper::getBlockTypeController($blockHandle)) {
                $packageHandle = BlockDeveloper::getBlockTypePackageHandle($blockHandle);
            }
            if (trim($packageHandle) == '') {
                $packageHandle = $this->getPackageHandle();
            }
            if ((trim($packageHandle) == '' || $this->packageHandleError($packageHandle)) && !($blockTypeController)) {
                $packageHandleQuestionChoices = $this->getPackageHandleQuestionChoices();
                $packageHandles = [];
                foreach ($packageHandleQuestionChoices as $packageHandleQuestionChoiceKey => $packageHandleQuestionChoiceValue) {
                    $t = $this->getBlockTypeDir($this->getBlocksDir($packageHandleQuestionChoiceKey), $blockHandle);
                    if (file_exists($t)) {
                        $packageHandles[] = $packageHandleQuestionChoiceKey;
                    }
                }
                // We have a Block Type with the given handle in one or more (installed) packages
                if (!empty($packageHandles)) {
                    // Since there is only 1 package, we can just use that one
                    if (count($packageHandles) == 1) {
                        $packageHandle = $packageHandles[key($packageHandles)];
                    } else {
                        // We have more choices, let the user pick one of 'em
                        foreach ($packageHandleQuestionChoices as $packageHandleQuestionChoiceKey => $packageHandleQuestionChoiceValue) {
                            if (!in_array($packageHandleQuestionChoiceKey, $packageHandles)) {
                                unset($packageHandleQuestionChoices[$packageHandleQuestionChoiceKey]);
                            }
                        }
                        $packageHandle = $this->choice($this->getPackageHandleQuestion(), $packageHandleQuestionChoices);
                    }
                } else {
                    if ($this->confirm('Do you want to place this Block Type within an already installed Package')) {
                        $packageHandle = $this->choice($this->getPackageHandleQuestion(), $packageHandleQuestionChoices);
                    } else {
                        $packageHandle = null;
                    }
                }
            }
        }
        if (!isset($blockHandle)) {
            $blockHandle = $this->getBlockHandle();
            if (trim($blockHandle) == '') {
                throw new Exception('Entering a Block Type handle is required.');
            } elseif ($this->blockHandleError($blockHandle) !== false) {
                throw new Exception($this->getBlockHandleQuestion());
            }
        }
        if ($blockTypeController = BlockDeveloper::getBlockTypeController($blockHandle)) {
            $packageHandle = BlockDeveloper::getBlockTypePackageHandle($blockHandle);
        } else {
            if (!isset($packageHandle)) {
                $packageHandle = $this->getPackageHandle();
            }
            $blockTypeController = BlockDeveloper::getBlockTypeController($blockHandle, $packageHandle);
        }
        $blocksDirectory = $this->getBlocksDir($packageHandle);
        $targetDirectory = $this->getBlockTypeDir($blocksDirectory, $blockHandle);
        if (in_array($blockHandle, $this->getCoreBlockTypeHandles())) {
            throw new Exception(sprintf('You may not use the Block Handle %s. Please try again with a different handle', $blockHandle));
        }
        $new = false;
        if (!file_exists($targetDirectory)) {
            $new = true;
            if (trim($packageHandle) != '') {
                $this->checkPackageDirectory($packageHandle, $blocksDirectory);
            }
            $this->createBlockType($targetDirectory, $packageHandle, $blockHandle);
            BlockDeveloper::clearOverrideCache();
            $blockTypeController = BlockDeveloper::getBlockTypeController($blockHandle, $packageHandle);
        } else {
            // Just a message to let the user know the directory exists, and we are not creating it here
            $this->output->write(sprintf('Block Type directory <info>%s</info> already exists, skipping writing initial files...', $targetDirectory));
            $this->output->writeln('<info>good.</info>');
        }
        // A check for the controller, since we need this later on
        if (!$blockTypeController) {
            throw new Exception('Could not continue processing, probably Block Cache is enabled (please disable and clear cache) or this Block Type is generated in a non-installed package (please install).');
        }
        $blockBuilder = new Builder($blockTypeController);
        $this->buildXmlSchema($blockBuilder);
        $this->refreshBlockType($blockHandle, $packageHandle);
        $this->doAdditionalProcesses($blockBuilder, $blockHandle, $packageHandle);
        if ($new) {
            $this->output->writeln(sprintf('Please edit the <info>%s</info> file to include fields and perhaps change other values to your likings. Run this same command again after changing this file, to apply the changes you made.', $targetDirectory . '/config.php'));
        }
        $this->output->writeln('<info>task(s) completed.</info>');
    }

    /**
     * @param null $blockHandle
     *
     * @return bool|string
     */
    protected function blockHandleError($blockHandle = null)
    {
        $error = false;
        preg_match($this->blockHandlePattern, $blockHandle, $matches);
        if ($matches) {
            if (in_array($blockHandle, $this->blockHandleReservedKeywords)) {
                $error = sprintf(
                    '%s is in the blacklist of to be used block handles. Please try another handle.',
                    $blockHandle
                );
            }
        } else {
            $error = sprintf('Block handle %s does not match our pattern.', $blockHandle);
        }

        return $error;
    }

    /**
     * @param null $packageHandle
     *
     * @return bool|string
     */
    protected function packageHandleError($packageHandle = null)
    {
        $error = false;
        if (trim($packageHandle) != '' && !array_key_exists($packageHandle, $this->getPackageHandleQuestionChoices())) {
            $error = sprintf('The Package Handle %s does not seem to be an installed package.', $packageHandle);
        }

        return $error;
    }

    /**
     * @return string
     */
    protected function getBlockHandle()
    {
        return ($bh = $this->getInputOption('block-handle')) && trim($bh) != '' ? $bh : null;
    }

    /**
     * @param null $default
     *
     * @return string
     */
    protected function getBlockName($default = null)
    {
        return ($bn = $this->getInputOption('block-name')) && trim($bn) != '' ? $bn : $default;
    }

    /**
     * @param null $default
     *
     * @return string
     */
    protected function getBlockNameDefault($default)
    {
        return ucwords(implode(' ', explode('_', $default)));
    }

    /**
     * @return string
     */
    protected function getPackageHandle()
    {
        return ($ph = $this->getInputOption('package-handle')) && trim($ph) != '' ? $ph : null;
    }

    /**
     * @param null $packageHandle
     *
     * @return string
     */
    protected function getBlocksDir($packageHandle = null)
    {
        return (trim($packageHandle) == '' ? DIR_APPLICATION : DIR_PACKAGES . '/' . $packageHandle) . '/' . DIRNAME_BLOCKS;
    }

    /**
     * @return array
     */
    protected function getCoreBlockTypeHandles()
    {
        return (new File())->getDirectoryContents(DIR_FILES_BLOCK_TYPES_CORE);
    }

    protected function getBlockTypeDir($blocksDirectory, $blockHandle)
    {
        return $blocksDirectory . '/' . $blockHandle;
    }

    /**
     * @return string
     */
    protected function getBlockNameQuestion()
    {
        return 'Enter your desired Block Name';
    }

    /**
     * @return string
     */
    protected function getBlockHandleQuestion()
    {
        return 'Enter your desired Block Handle - Lowercase letters and underscores only (without starting/ending with underscores) and a minimum of 3 characters';
    }

    /**
     * @return array
     */
    protected function getBlockHandleQuestionChoices()
    {
        return [];
    }

    /**
     * @return string
     */
    protected function getPackageHandleQuestion()
    {
        return 'Choose a Package for the Block Type';
    }

    /**
     * @return array
     */
    protected function getPackageHandleQuestionChoices()
    {
        $pkgArray = Package::getInstalledList();
        $packageHandleChoices = [];
        foreach ($pkgArray as $pkg) {
            $packageHandleChoices[$pkg->getPackageHandle()] = t($pkg->getPackageName());
        }

        return $packageHandleChoices;
    }

    /**
     * @param $blockBuilder Builder
     */
    protected function buildXmlSchema($blockBuilder)
    {
        $this->output->write('Building database file (db.xml)...');
        if ($buildXmlSchema = $blockBuilder->XmlSchema()) {
            $this->output->writeln('<info>done.</info>');
        } else {
            $this->output->writeln('<error>failed.</error>');
        }
    }

    /**
     * @param $blockHandle
     * @param $packageHandle
     */
    protected function refreshBlockType($blockHandle, $packageHandle)
    {
        $this->output->write('Refreshing the Block Type...');
        if ($refreshBlockType = BlockDeveloper::refreshBlockType($blockHandle, $packageHandle)) {
            $this->output->writeln('<info>done.</info>');
        } else {
            $this->output->writeln('<error>failed.</error>');
        }
    }

    /**
     * @param $fileService File
     * @param $targetDirectory
     */
    protected function writeBlockTypeFiles($fileService, $targetDirectory)
    {
        $this->output->write('Writing files to new Block Type directory...');
        $fileService->copyAll(DIR_PACKAGES . '/block_developer/blocks_template', $targetDirectory);
        $this->output->writeln('<info>done.</info>');
    }

    /**
     * @param $fileService File
     * @param $targetDirectory
     * @param $packageHandle
     * @param $blockHandle
     */
    protected function setBlockTypeNameSpace($fileService, $targetDirectory, $packageHandle, $blockHandle)
    {
        $this->output->write('Setting correct namespace for the Block Type controller.php file...');
        $controllerFileName = $targetDirectory . '/controller.php';
        $blockHandleNameSpace = 'namespace ' . (trim($packageHandle) == '' ? 'Application' : 'Concrete\Package\\' . BlockDeveloper::_nameSpace($packageHandle)) . '\Block\\' . BlockDeveloper::_nameSpace($blockHandle) . ';';
        $controllerContents = str_replace('/* @namespace */', $blockHandleNameSpace, $fileService->getContents($controllerFileName));
        $controllerContents = str_replace('defined(\'C5_EXECUTE\') or die("Access Denied.");', '', $controllerContents);
        $fileService->clear($controllerFileName);
        $fileService->append($controllerFileName, $controllerContents);
        $this->output->writeln('<info>done.</info>');
    }

    /**
     * @param $fileService File
     * @param $targetDirectory
     * @param $blockName
     */
    protected function setBlockTypeName($fileService, $targetDirectory, $blockName)
    {
        $this->output->write('Setting name for the Block Type...');
        $configFile = $targetDirectory . '/config.php';
        $controllerContents = str_replace('\'/* @name */\'', sprintf('t(\'%s\')', str_replace('\\', '', $blockName)), $fileService->getContents($configFile));
        $fileService->clear($configFile);
        $fileService->append($configFile, $controllerContents);
        $this->output->writeln('<info>done.</info>');
    }

    /**
     * @param $packageHandle
     * @param $blocksDirectory
     *
     * @throws Exception
     */
    protected function checkPackageDirectory($packageHandle, $blocksDirectory)
    {
        $this->output->write('Checking if Package directory exists...');
        if (file_exists(DIR_PACKAGES . '/' . $packageHandle)) {
            $this->output->writeln('<info>good.</info>');
            $this->output->write(sprintf('Checking if Package /blocks directory exists (%s)...', $blocksDirectory));
            if (!file_exists($blocksDirectory)) {
                $this->output->writeln('<info>not yet.</info>');
                $this->output->write('Package /blocks directory does not exist yet, trying to create...');
                $fileService = new FileService();
                if ($fileService->makeDirectory($blocksDirectory)) {
                    $this->output->writeln('<info>created.</info>');
                } else {
                    throw new Exception('could not create the blocks directory, please check permissions for this package directory.');
                }
            } else {
                $this->output->writeln('<info>good.</info>');
            }
        } else {
            throw new Exception('package does not exist yet, please create the package first.');
        }
    }

    /**
     * @param $blockHandle
     * @param $packageHandle
     */
    protected function installBlockType($blockHandle, $packageHandle)
    {
        $this->output->write('Checking if needs to be installed...');
        if (!BlockType::getByHandle($blockHandle)) {
            $this->output->writeln('<info>done.</info>');
            $this->output->write('Installing the Block Type...');
            $pkg = trim($packageHandle) != '' ? Package::getByHandle($packageHandle) : false;
            if (BlockType::installBlockType($blockHandle, $pkg)) {
                $this->output->writeln('<info>done.</info>');
            } else {
                $this->output->writeln('<error>failed.</error>');
            }
        } else {
            $this->output->writeln('<info>already installed.</info>');
        }
    }

    /**
     * @param $blockBuilder Builder
     */
    protected function buildTemplates($blockBuilder)
    {
        $this->output->write('Building additional template(s) for the Block Type in progress...');
        if ($blockBuilder->templates()) {
            $this->output->writeln('<info>done.</info>');
        } else {
            $this->output->writeln('<error>failed.</error>');
        }
    }

    /**
     * @param $blockBuilder Builder
     */
    protected function buildView($blockBuilder)
    {
        $this->output->write('Building view template for the Block Type in progress...');
        if ($blockBuilder->view()) {
            $this->output->writeln('<info>done.</info>');
        } else {
            $this->output->writeln('<error>failed.</error>');
        }
    }

    /**
     * @param $targetDirectory
     * @param $packageHandle
     * @param $blockHandle
     * @param $blockName
     */
    protected function createBlockType($targetDirectory, $packageHandle, $blockHandle)
    {
        $fileService = new File();
        $blockNameDefault = $this->getBlockNameDefault($blockHandle);
        $blockName = $this->isInteractive() ? h($this->ask($this->getBlockNameQuestion(), $blockNameDefault)) : $this->getBlockName($blockNameDefault);
        $this->writeBlockTypeFiles($fileService, $targetDirectory);
        $this->setBlockTypeNameSpace($fileService, $targetDirectory, $packageHandle, $blockHandle);
        $this->setBlockTypeName($fileService, $targetDirectory, $blockName);
    }

    /**
     * @param $blockBuilder
     * @param $blockHandle
     * @param $packageHandle
     *
     * @return array
     */
    protected function doAdditionalProcesses($blockBuilder, $blockHandle, $packageHandle)
    {
        $additionalProcesses = [];
        $skip = ($s = $this->getInputOption('skip')) && is_array($s) ? $s : [];
        if (!in_array('build_view', $skip)) {
            $additionalProcesses[] = 'build_view';
        }
        if (!in_array('build_templates', $skip)) {
            $additionalProcesses[] = 'build_templates';
        }
        if (!in_array('install', $skip)) {
            $additionalProcesses[] = 'install';
        }
        if (!empty($additionalProcesses)) {
            foreach ($additionalProcesses as $additionalProcess) {
                switch ($additionalProcess) {
                    case 'build_view':
                        $this->buildView($blockBuilder);
                        break;
                    case 'build_templates':
                        $this->buildTemplates($blockBuilder);
                        break;
                    case 'install':
                        $this->installBlockType($blockHandle, $packageHandle);
                        break;
                }
            }
        }

        return $additionalProcesses;
    }
}
