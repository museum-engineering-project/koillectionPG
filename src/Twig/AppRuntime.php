<?php

declare(strict_types=1);

namespace App\Twig;

use App\Entity\Tag;
use App\Model\BreadcrumbElement;
use App\Repository\TagRepository;
use App\Service\ConfigurationHelper;
use App\Service\ContextHandler;
use App\Service\FeatureChecker;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\RuntimeExtensionInterface;

readonly class AppRuntime implements RuntimeExtensionInterface
{
    public function __construct(
        private TranslatorInterface $translator,
        private RouterInterface $router,
        private TagRepository $tagRepository,
        private ContextHandler $contextHandler,
        private FeatureChecker $featureChecker,
        private ConfigurationHelper $configurationHelper,
        private string $publicPath,
        private string $kernelProjectDir,
    ) {
    }

    public function safeContent(string $string): string
    {
        return $string;
    }

    public function bytes(float $bytes, int $precision = 2): string
    {
        $base = $bytes > 0 ? log($bytes, 1024) : $bytes;

        $suffixes = ['', 'Ki', 'Mi', 'Gi', 'Ti', 'Pi', 'Ei', 'Zi', 'Yi'];

        return round(pow(1024, $base - floor($base)), $precision).' '.$suffixes[floor($base)].$this->translator->trans('global.byte_abbreviation');
    }

    public function transMlang(?string $text, ?string $locale=null): string
    {
        if ($text === null) {
            return "";
        }

        if ($locale === null) {
            $locale = $this->translator->getLocale();
        }
        
        $locale = preg_quote($locale);

        $pattern = "/{mlang " . $locale . "}.*?{mlang}/";
        $matches = [];
        preg_match_all($pattern, $text, $matches);

        // remove mlang tags of matched locale while keeping their content
        foreach ($matches[0] as &$match) {
            $originalMatch = $match;
            
            $match = str_replace("{mlang " . $locale . "}", "", $match);
            $match = str_replace("{mlang}", "", $match);
            
            $text = str_replace($originalMatch, $match, $text);
        }

        // remove all remaining (unmatched) mlang tags and their content
        $text = preg_replace("/{mlang .*?}.*?{mlang}/", "", $text);

        return $text;
    }

    public function renderTitle(array $breadcrumb): string
    {
        $element = array_shift($breadcrumb);

        if ($element instanceof BreadcrumbElement && isset($element->getParams()['username'])) {
            return $this->translator->trans($element->getLabel(), ['username' => $element->getParams()['username']]);
        }

        $element = [] === $breadcrumb ? $element : array_pop($breadcrumb);

        if ($element instanceof BreadcrumbElement) {
            if ('action' === $element->getType()) {
                $entityElement = array_pop($breadcrumb);

                if ($entityElement instanceof BreadcrumbElement && null !== $entityElement->getEntity()) {
                    $class = (new \ReflectionClass($entityElement->getEntity()))->getShortName();

                    return $this->translator->trans('global.entities.'.strtolower($class)).' · '.$this->transMlang($entityElement->getLabel()).' · '.$this->translator->trans($element->getLabel());
                } elseif (str_contains($element->getLabel(), 'breadcrumb.')) {
                    return $this->translator->trans($element->getLabel());
                }

                return $this->translator->trans('label.search').' · '.$this->transMlang($element->getLabel());
            }

            if ('entity' === $element->getType()) {
                $class = (new \ReflectionClass($element->getEntity()))->getShortName();
                $pieces = preg_split('/(?=[A-Z])/', lcfirst($class));
                $class = implode('_', $pieces);
                $class = strtolower($class);

                return $this->translator->trans('global.entities.'.strtolower($class)).' · '.$this->transMlang($element->getLabel());
            }

            if ('root' === $element->getType()) {
                if ('shared' === $this->contextHandler->getContext()) {
                    return $this->translator->trans($element->getLabel().'_shared', ['username' => $this->contextHandler->getUsername()]);
                }

                return $this->translator->trans($element->getLabel());
            }
        }

        return $this->translator->trans('global.koillection');
    }

    public function highlightTags(?string $text): array|string|null
    {
        if (null === $text) {
            return null;
        }

        $tags = $this->tagRepository->findAllForHighlight();

        $words = [];
        foreach ($tags as $tag) {
            $id = \is_string($tag['id']) ? $tag['id'] : $tag['id']->toString();
            $words[$id] = preg_quote($tag['label'], '/');
        }

        return preg_replace_callback(
            "/\b(".implode('|', $words).")\b/ui",
            function ($matches) use ($words) {
                $id = array_search(preg_quote(strtolower($matches[1]), '/'), array_map('strtolower', $words), true);

                $route = $this->contextHandler->getRouteContext('app_tag_show');
                $route = $this->router->generate($route, ['id' => $id]);

                return "<a href='{$route}'>$matches[1]</a>";
            },
            $text
        );
    }

    public function jsonDecode(?string $string): array
    {
        if ($string === null || $string === '') {
            return [];
        }

        return json_decode($string, true);
    }

    public function getUnderlinedTags(?iterable $data): array
    {
        if (!$this->isFeatureEnabled('tags') || empty($data)) {
            return [];
        }

        $texts = [];
        foreach ($data as $datum) {
            if (null !== $datum->getValue()) {
                $texts = array_merge($texts, explode(',', $datum->getValue()));
            }
        }

        $texts = array_map(static function ($text): string {
            return trim($text);
        }, $texts);
        $tags = $this->tagRepository->findBy(['label' => $texts]);

        $results = [];
        foreach ($texts as $text) {
            $matchingTag = null;
            foreach ($tags as $tag) {
                if ($text === $tag->getLabel()) {
                    $matchingTag = $tag;
                    break;
                }
            }

            if ($matchingTag instanceof Tag) {
                $route = $this->contextHandler->getRouteContext('app_tag_show');
                $url = $this->router->generate($route, ['id' => $matchingTag->getId()]);
                $results[$text] = '<a href="'.$url.'">'.$text.'</a>';
            } else {
                $results[$text] = $text;
            }
        }

        return $results;
    }

    public function isFeatureEnabled(string $feature): bool
    {
        return $this->featureChecker->isFeatureEnabled($feature);
    }

    public function fileSize(string $path): int
    {
        return is_file($path) ? filesize($path) : 0;
    }

    public function base64Encode(string $path): string
    {
        return base64_encode(file_get_contents($this->publicPath . '/' . $path));
    }

    public function getDefaultLightThemeColors(): ?string
    {
        $path = $this->kernelProjectDir . '/assets/styles/themes/light.css';
        $content = file_get_contents($path);
        preg_match('/:root {(.*?)}/ms', $content, $matches);

        return $matches[0];
    }

    public function getDefaultDarkThemeColors(): ?string
    {
        $path = $this->kernelProjectDir . '/assets/styles/themes/dark.css';
        $content = file_get_contents($path);
        preg_match('/:root {(.*?)}/ms', $content, $matches);

        return $matches[0];
    }

    public function getConfigurationValue(string $label): ?string
    {
        return $this->configurationHelper->getValue($label);
    }
}
