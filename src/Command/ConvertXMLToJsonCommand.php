<?php

namespace App\Command;

use Doctrine\ODM\MongoDB\DocumentManager;
use FilesystemIterator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpKernel\KernelInterface;
use \App\Document\RawPatent;
use ZipArchive;

#[AsCommand(
    name: 'xml-to-json',
    description: 'Transform XML files inside a given folder to JSON',
)]
class ConvertXMLToJsonCommand extends Command
{
    private int $yearToProcess;
    private readonly SymfonyStyle $io;
    private bool $checkDuplicates;

    private DocumentManager $documentManager;

    public function __construct(private readonly KernelInterface $kernel)
    {
        $this->documentManager = $this->kernel->getContainer()->get('doctrine_mongodb')?->getManager();
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('xmlFolderPath', InputArgument::REQUIRED, 'The path for the XML files')
            ->addArgument('year', InputArgument::REQUIRED, 'Year to process')
            ->addOption('check-duplicates', '-c', null, 'Check for duplicates before inserting the document');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        ini_set('memory_limit', '11G');
        $this->io = new SymfonyStyle($input, $output);
        $xmlFolder = $input->getArgument('xmlFolderPath');
        $this->yearToProcess = $input->getArgument('year');
        $this->checkDuplicates = $input->getOption('check-duplicates');

        $this->process($xmlFolder);

        $this->io->success('XML files converted to JSON');

        return Command::SUCCESS;
    }

    /**
     * @throws \JsonException
     */
    private function process(string $folderPath): void
    {
        $fullFolderPath = $this->kernel->getProjectDir() . '/var/data/' . $folderPath . $this->yearToProcess;
        if (is_dir($fullFolderPath)) {
            $zipFiles = glob($fullFolderPath . '/*.zip');

            foreach ($zipFiles as $zipFile) {
                $zip = new ZipArchive();
                if ($zip->open($zipFile) === true) {
                    $extractPath = $fullFolderPath . '/extracted';
                    $processedZipFilesPath = $extractPath . '/processed';
                    $zip->extractTo($extractPath);
                    $zip->close();

                    $extractedFiles = new \RecursiveIteratorIterator(
                        new \RecursiveDirectoryIterator($extractPath, FilesystemIterator::SKIP_DOTS),
                        \RecursiveIteratorIterator::LEAVES_ONLY
                    );

                    $xmlFiles = new \RegexIterator($extractedFiles, '/\.xml$/i');

                    foreach ($xmlFiles as $file) {
                        $this->io->note(sprintf('Extracted file: %s', $file->getPathname()));
                        $this->processXml($file->getPath(), $file->getPathname(), $file->getFilename());
                        $this->createDirectory($extractPath);
                        $this->createDirectory($processedZipFilesPath);
                        @rename($zipFile, $processedZipFilesPath . '/' . basename($zipFile));
                        unlink($file->getPathname());
                        $this->io->note(sprintf('File: %s processed and deleted', $file->getPathname()));
                    }
                } else {
                    $this->io->note(sprintf("Failed to open: %s", $zipFile));
                }
            }
        } else {
            $this->io->note("The specified folder does not exist.");
        }
    }

    /**
     * @throws \JsonException
     */
    private function processXml(string $folderPath, string $xmlFullPath, string $fileName): void
    {
        $handle = fopen($xmlFullPath, 'rwb');
        if (!$handle) {
            throw new \RuntimeException("Unable to open file: $xmlFullPath");
        }

        $xmlStartTag = '<?xml version="1.0" encoding="UTF-8"?>';
        $unwantedStartTag = '<!DOCTYPE sequence-cwu SYSTEM "us-sequence-listing-v1_3-2020-10-08.dtd" [ ]>';
        $xmlEndTag = '</us-patent-grant>';
        $unwantedEndTag = '</sequence-cwu>';
        $xmlCounter = 0;
        $batch = [];
        $skipContent = false;

        foreach ($this->getLines($handle) as $line) {
            if (str_contains($line, $unwantedStartTag)) {
                $skipContent = true;
                $xmlContent = '';
                continue;
            }

            if ($skipContent) {
                if (str_contains($line, $unwantedEndTag)) {
                    $skipContent = false;
                }
                continue;
            }

            if (str_contains($line, $xmlStartTag)) {
                if (!empty($xmlContent)) {
                    $batch[] = $this->processXmlContent($xmlContent, $xmlCounter, $folderPath, $fileName);
                    $xmlCounter++;
                    unset($xmlContent);
                    gc_collect_cycles();
                }
                $xmlContent = $line;
            } else {
                $xmlContent .= $line;
            }

            if (str_contains($line, $xmlEndTag)) {
                $batch[] = $this->processXmlContent($xmlContent, $xmlCounter, $folderPath, $fileName);
                $xmlCounter++;
                unset($xmlContent, $line);
                gc_collect_cycles();
                $xmlContent = '';
            }

            if (count($batch) >= 1000) {
                $this->storeData($batch);
                $batch = [];
            }
        }

        if (!empty($xmlContent)) {
            $batch[] = $this->processXmlContent($xmlContent, $xmlCounter, $folderPath, $fileName);
        }

        if (!empty($batch)) {
            $this->storeData($batch);
        }

        fclose($handle);
        unset($handle, $xmlContent, $xmlCounter, $batch);
        gc_collect_cycles();
    }

    private function getLines($handle): \Generator
    {
        while (($line = fgets($handle)) !== false) {
            yield $line;
        }
    }

    private function storeData(array $batch): void
    {
        foreach ($batch as $data) {
            if($this->checkDuplicates) {
                $existingPatent = $this->documentManager->getRepository(RawPatent::class)
                    ->findOneBy([
                        'year' => $this->yearToProcess,
                        'usPatentGrant' => $data
                    ]);

                if ($existingPatent) {
                    continue;
                }
            }

            $patent = new RawPatent();
            $patent->setYear($this->yearToProcess);
            $patent->setUsPatentGrant($data);

            try {
                $this->documentManager->persist($patent);
            } catch (\Exception $e) {
                dump('Error: ', $e->getMessage());
                continue;
            }
        }

        try {
            $this->documentManager->flush();
            $this->documentManager->clear();
        } catch (\Exception|\Throwable $e) {
            dump('Error: ', $e->getMessage());
        }
    }

    /**
     * @throws \JsonException
     */
    private function processXmlContent(string $xmlContent, int $counter, string $folderPath, string $fileName): array
    {
        $xml = simplexml_load_string($xmlContent);

        if (!$xml) {
            $errorFilesFolder = $folderPath . '/error/' . str_replace('.xml', '', $fileName) . '/';

            $this->createDirectory($errorFilesFolder);

            $errorFile = $errorFilesFolder . $counter . '.json';
            $jsonContent = json_decode(json_encode($xml, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR );
            if (file_put_contents($errorFile, $jsonContent) === false) {
                throw new \RuntimeException("Failed to write error file: $errorFile");
            }
            unset($xml, $jsonContent);
            return [];
        }

        return json_decode(json_encode($xml, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);
    }

    private function createDirectory(string $path): void
    {
        if (!is_dir($path) && !mkdir($path, 0700, true) && !is_dir($path)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $path));
        }
    }
}