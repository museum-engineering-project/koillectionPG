<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Label;
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

    public function generateQrCode(string $itemId)
    {
        $qrCode = new QrCode($this->urlGenerator->generate(
            'app_item_show',
            array('id' => $itemId),
            UrlGeneratorInterface::ABSOLUTE_URL));
        $writer = new PngWriter();
        $qrCodeImg = $writer->write($qrCode)->getDataUri();

        return $qrCodeImg;
    }

    public function generateLabel(Label $label)
    {
        $labelSize = $label->getLabelSize();
        $orientation = $label->getOrientation();
        $item = $label->getItem();
        $cssOrientation = $orientation == OrientationEnum::ORIENTATION_VERTICAL ? "portrait" : "landscape";

        $qrCodeImg = $this->generateQrCode($item->getId());

        $htmlContent = $this->twig->render('App/Label/label.html.twig', [
            'qrCode' => $qrCodeImg,
            'labelSize' => $labelSize,
            'orientation' => $orientation,
            'css_orientation' => $cssOrientation,
            'item' => $item,
        ]);
        
        $dompdf = new Dompdf();

        $dompdf->loadHtml($htmlContent);
        $dompdf->render();

        $filename = "item_label_{$labelSize}.pdf";

        return array('content' => $dompdf->output(),
                     'filename' => $filename);
    }
}