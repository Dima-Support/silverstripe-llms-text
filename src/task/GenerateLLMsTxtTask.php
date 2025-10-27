<?php

namespace Task;

use DateTimeImmutable;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\Director;
use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\FieldType\DBDatetime;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\Versioned\Versioned;

class GenerateLLMsTxtTask extends BuildTask
{
    private static $segment = 'generate-llms-txt';

    protected $title = 'Generate llms.txt';
    protected $description = 'Genereert /public/llms.txt met alle gepubliceerde pagina-URLs.';

    public function run($request)
    {
        // Live (gepubliceerde) pagina’s ophalen
        $pages = Versioned::get_by_stage(SiteTree::class, Versioned::LIVE)
            ->filter('ShowInSearch', 1)
            ->exclude('ClassName', 'SilverStripe\\ErrorPage\\ErrorPage')
            ->sort('Title ASC');

        $cfg = SiteConfig::current_site_config();
        $siteTitle = trim($cfg->Title ?: 'Website');

        // NL-achtige timestamp
        $now = new DateTimeImmutable(DBDatetime::now()->Rfc2822());
        $stamp = $now->format('Y-m-d H:i:s');

        $lines = [];
        foreach ($pages as $page) {
            $url = $page->AbsoluteLink();
            $title = trim(preg_replace('/[\[\]]/', '', (string)($page->MenuTitle ?: $page->Title)));
            $lines[] = sprintf('- [%s](%s)', $title, $url);
        }

        $header = "# {$siteTitle}\n> Automatisch gegenereerd op {$stamp}\n\n## Pagina's\n";
        $body   = implode("\n", $lines) . "\n";
        $contents = $header . $body;

        // Cross-env: bepaal public-pad robuust
        if (method_exists(Director::class, 'publicFolder')) {
            $publicPath = Director::publicFolder();           // SS4/SS5 met /public
            if (!is_dir($publicPath) && is_dir(BASE_PATH . '/public')) {
                $publicPath = BASE_PATH . '/public';
            }
        } else {
            // Oudere setups zonder separate /public
            $publicPath = BASE_PATH;
        }

        $filePath = rtrim($publicPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'llms.txt';
        $ok = @file_put_contents($filePath, $contents);

        if ($ok === false) {
            echo "Kon llms.txt niet schrijven naar: {$filePath}. Controleer bestandsrechten.\n";
            return;
        }

        echo "llms.txt aangemaakt/opnieuw gegenereerd: {$filePath}\n";
        echo "Aantal pagina's: " . count($lines) . "\n";
    }
}
