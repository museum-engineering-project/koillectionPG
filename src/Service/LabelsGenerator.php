<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Label;
use App\Entity\Item;
use App\Entity\Collection;
use App\Enum\OrientationEnum;
use Twig\Environment;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Dompdf\Dompdf;

class LabelsGenerator
{
    public function __construct(
        private Environment $twig,
        private UrlGeneratorInterface $urlGenerator
    ) 
    {}

    public function generateQrCode(Item|Collection $object)
    {
        $route = $object instanceof Item ? "app_item_show" : "app_collection_show";

        $qrCode = new QrCode($this->urlGenerator->generate(
            $route,
            array('id' => $object->getId()),
            UrlGeneratorInterface::ABSOLUTE_URL));
        $writer = new PngWriter();
        $qrCodeImg = $writer->write($qrCode)->getDataUri();

        return $qrCodeImg;
    }

    public function generateLabel(Label $label)
    {
        $labelSize = $label->getLabelSize();
        $orientation = $label->getOrientation();
        $cssOrientation = $orientation == OrientationEnum::ORIENTATION_VERTICAL ? "portrait" : "landscape";
        $fontSize = $label->getFontSize();
        $fields = $label->getFields();
        $qrSize = $label->getQrSize();
        $textAlignment = $label->getTextAlignment();
        $object = $label->getObject();
        $labelType = $label->getLabelType();
        $htmlContent = "";

        if (!is_array($object))
        {
            $object = [$object];
        }

        if ($labelType == "table")
        {
            $htmlContent = $this->twig->render('App/Label/table_label.html.twig', [
                'fields' => $fields,
                'items' => $object,
                'labelSize' => $labelSize,
                'fontSize' => $fontSize,
                'textAlignment' => $textAlignment,
                'orientation' => $orientation,
                'css_orientation' => $cssOrientation
            ]);
        }
        
        else
        {
            foreach ($object as $objectElement)
            {
                $objectData = [];
                foreach ($fields as $field)
                {
                    $datum = $objectElement->getDatumByLabelCaseInsensitive($field);
                    if ($datum != null)
                    {
                        $objectData[$field] = $datum;
                    }
                }

                $objectName = $objectElement instanceof Item ? $objectElement->getName() : $objectElement->getTitle();
        
                $qrCodeImg = $this->generateQrCode($objectElement);
        
                $htmlContent .= $this->twig->render('App/Label/label.html.twig', [
                    'qrCode' => $qrCodeImg,
                    'labelSize' => $labelSize,
                    'fontSize' => $fontSize,
                    'qrSize' => $qrSize,
                    'textAlignment' => $textAlignment,
                    'orientation' => $orientation,
                    'css_orientation' => $cssOrientation,
                    'objectName' => $objectName,
                    'objectData' => $objectData
                ]);
            }
        }

        $dompdf = new Dompdf();

        $dompdf->loadHtml($htmlContent);
        $dompdf->render();

        if (count($object) > 1)
        {
            $filename = "multiple_items_labels_{$labelSize}.pdf";
        }
        else
        {
            $type = current($object) instanceof Item ? 'item' : 'collection';
            $filename = "{$type}_label_{$labelSize}.pdf";
        }

        return array('content' => $dompdf->output(),
                     'filename' => $filename);
    }
}
