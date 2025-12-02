<?php
declare(strict_types=1);

namespace FourViewture\Accessibility\Command;

use FourViewture\Accessibility\Service\ContextNormalizationService;
use FourViewture\Accessibility\Service\FileAccessNormalizationService;
use Psy\Context;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Resource\FileRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;

#[AsCommand(
    name: 'accessibility:debug',
)]
final class DoBackendRelatedThingsCommand extends Command
{
    public function __construct(
        protected readonly FileAccessNormalizationService $fileAccessNormalizationService,
        protected readonly ContextNormalizationService $contextNormalizationService
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setHelp('This command accepts arguments')
            ->addArgument(
                'uid',
                InputArgument::REQUIRED,
                'The wizard\'s name'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $fileRepository = GeneralUtility::makeInstance(FileRepository::class);
        $originalFile = $fileRepository->findByUid((int)$input->getArgument('uid'));
        $references = $this->contextNormalizationService->fetchFromReferenceIndex($originalFile);

        echo json_encode($references);

        return Command::SUCCESS;
    }
}
