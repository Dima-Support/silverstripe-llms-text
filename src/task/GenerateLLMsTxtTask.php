<?php

namespace Task;

use DateTimeImmutable;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\Director;
use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\FieldType\DBDatetime;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\Versioned\Versioned;
use SilverStripe\PolyExecution\PolyOutput;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;

class GenerateLLMsTxtTask extends BuildTask
{
    
    protected static string $commandName = 'generate-llms-txt';

    
    protected string $title = 'Generate llms.txt';
    protected static string $description = 'Genereert /public/llms.txt met alle gepubliceerde pagina-URLs.';

    
    protected function execute(InputInterface $input, PolyOutput $output): int
    {
        $pages = Versioned::get_by_stage(SiteTree::class, Versioned::LIVE)
            ->filter('ShowInSearch', 1)
            ->exclude('ClassName', 'SilverStripe\\ErrorPage\\ErrorPage')
            ->sort('Title ASC');

        $cfg = SiteConfig::current_site_config();
        $siteTitle = trim($cfg->Title ?: 'Website');

        $now = new DateTimeImmutable(DBDatetime::now()->Rfc2822());
        $stamp = $now->format('Y-m-d H:i:s');

        $lines = [];
        foreach ($pages as $page) {
            $url = $page->AbsoluteLink();
            $title = trim(preg_replace('/[\[\]]/', '', (string)($page->MenuTitle ?: $page->Title)));
            $lines[] = sprintf('- [%s](%s)', $title, $url);
        }

        $header   = "# {$siteTitle}\n> Automatisch gegenereerd op {$stamp}\n\n## Pagina's\n";
        $body     = implode("\n", $lines) . "\n";
        $contents = $header . $body;

        
        if (method_exists(Director::class, 'publicFolder')) {
            $publicPath = Director::publicFolder();
            if (!is_dir($publicPath) && is_dir(BASE_PATH . '/public')) {
                $publicPath = BASE_PATH . '/public';
            }
        } else {
            $publicPath = BASE_PATH;
        }

        $filePath = rtrim($publicPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'llms.txt';

        if (@file_put_contents($filePath, $contents) === false) {
            $output->writeln("Kon llms.txt niet schrijven naar: {$filePath}. Controleer bestandsrechten.");
            return Command::FAILURE;
        }

        $output->writeln("llms.txt aangemaakt/opnieuw gegenereerd: {$filePath}");
        $output->writeln("Aantal pagina's: " . count($lines));

        return Command::SUCCESS;
    }
}
