<?php
namespace Lcn\FileUploaderBundle\Command;

use Lcn\FileUploaderBundle\Services\FileUploader;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CleanupCommand extends Command
{
    /**
     * @var FileUploader
     */
    protected $fileUploader;

    public function __construct(FileUploader $fileUploader) {
        $this->fileUploader = $fileUploader;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('lcn:file-uploader:cleanup')
            ->setDescription('Cleanup uploaded temporary files which are older than a given minimum age')
            ->addOption('min-age-in-minutes', 'm', InputOption::VALUE_OPTIONAL, 'specifies the minimum age in minutes for temporary files to delete', 24*60)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $minAgeInMinutes = intval($input->getOption('min-age-in-minutes'));
        $output->writeln('Deleting temporary uploads older than '.$minAgeInMinutes.' minutes');
        $this->fileUploader->removeOldTemporaryFiles($minAgeInMinutes);
        $output->writeln('Done.');
    }
}