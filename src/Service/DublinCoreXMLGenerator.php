<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Item;
use Twig\Environment;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class DublinCoreXMLGenerator
{
    public function __construct(
        private Environment $twig,
        private UrlGeneratorInterface $urlGenerator
    )
    {}

    public function printWithTab(string $printString, int $tabCount)
    {
        $str = "";

        for ($i = 0; $i < $tabCount*4; $i++)
        {
            $str .= " ";
        }
        
        return $str.$printString;
    }

    public function generateDublinCoreXML(Item $item, string $reqDomain)
    {
        $filename_postfix = "_xml.xml";
        $itemData = $item->getPublicDataTexts();
        $xmlElement = "";
        $url = $this->urlGenerator->generate("app_item_show", array('id' => $item->getId()), UrlGeneratorInterface::ABSOLUTE_URL);

        // XML Header
        $xml = $this->printWithTab("<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n", 0);

        // namespace
        $xmlElement = htmlspecialchars($reqDomain, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $xml .= $this->printWithTab("<metadata xmlns:dc=\"http://purl.org/dc/elements/1.1/\" xmlns:koillectionPG=\"{$xmlElement}/koillectionPG\">\n", 0);

        // dc.title (__toString())
        $xmlElement = htmlspecialchars((string)$item, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $filename = $xmlElement.$filename_postfix;
        $xml .= $this->printWithTab("<dc:title>{$xmlElement}</dc:title>\n", 1);

        // dc.type (collection name)
        $xmlElement = htmlspecialchars((string)$item->getCollection(), ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $xml .= $this->printWithTab("<dc:type>{$xmlElement}</dc:type>\n", 1);

        // dc.publisher (username)
        $xmlElement = htmlspecialchars((string)$item->getOwner(), ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $xml .= $this->printWithTab("<dc:publisher>{$xmlElement}</dc:publisher>\n", 1);

        // dc.source (link to item)
        $xml .= $this->printWithTab("<dc:source>{$url}</dc:source>\n", 1);

        // dc.identifier (item ID)
        $xml .= $this->printWithTab("<dc:identifier>{$item->getID()}</dc:identifier>\n", 1);

        // dc.relation (related items)
        foreach ($item->getRelatedItems() as $relItem)
        {
            $xmlElement = htmlspecialchars((string)$relItem, ENT_XML1, 'UTF-8');
            $xml .= $this->printWithTab("<dc:relation>{$xmlElement}<dc:relation>\n", 1);
        }

        // dc.language (default description language)
        $xml .= $this->printWithTab("<dc:language>Polish</dc:language>\n", 1);

        // koilectionPG: (other item properties) or description
        $koillectionPGXML = "";
        foreach ($itemData as $data)
        {
            // XML doesn't accept spaces in markup name and parenthesis
            $label = str_replace(")", "", str_replace("(", "", str_replace(" ", "-", strToLower($data->getLabel()))));
            $value = $data->getValue();
            $free_value = htmlspecialchars((string)$value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
            $free_label = htmlspecialchars((string)$label, ENT_XML1 | ENT_QUOTES, 'UTF-8');
            if ($label == "description" || $label == "year") {
                $xml .= $this->printWithTab("<dc:{$free_label}>{$free_value}</dc:{$free_label}>", 1);
            }
            else {
                $koillectionPGXML .= $this->printWithTab("<koillectionPG:custom-field name='{$free_label}'>{$free_value}</koillectionPG:custom-field>\n", 1);
            }
        }

        $xml .= $koillectionPGXML;

        $xml .= $this->printWithTab("</metadata>", 0);

        return array('content' => $xml, 'filename' => $filename);
    }
}
