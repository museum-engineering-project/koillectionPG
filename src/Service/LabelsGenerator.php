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
        $object = $label->getObject();
        $cssOrientation = $orientation == OrientationEnum::ORIENTATION_VERTICAL ? "portrait" : "landscape";

        $qrCodeImg = $this->generateQrCode($object);

        $htmlContent = $this->twig->render('App/Label/label.html.twig', [
            'qrCode' => $qrCodeImg,
            'labelSize' => $labelSize,
            'orientation' => $orientation,
            'css_orientation' => $cssOrientation,
            'object' => $object
        ]);
        
        $dompdf = new Dompdf();

        $dompdf->loadHtml($htmlContent);
        $dompdf->render();

        $type = $object instanceof Item ? 'item' : 'collection';
        $objectName = $type == "item" ? $object->getName() : $object->getTitle();
        $filename = "{$type}_label_{$labelSize}.pdf";

        return array('content' => $dompdf->output(),
                     'filename' => $filename);
    }
}
