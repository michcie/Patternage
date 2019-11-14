<?php

namespace App\Twig;

use App\Entity\Player;
use App\Service\NetworkResolver;
use IntlDateFormatter;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;

class AppExtension extends \Twig_Extension implements \Twig_Extension_GlobalsInterface
{
    /** @var RequestStack */
    private $requestStack;
    /** @var RouterInterface */
    private $router;

    private $breadcrumbs = [];

    public function __construct(RequestStack $requestStack, RouterInterface $router)
    {
        $this->requestStack = $requestStack;
        $this->router = $router;
    }

    public function addBreadcrumb($name, $url = '#')
    {
        $this->breadcrumbs[] = [
            'name' => $name,
            'url' => $url,
        ];
    }

    public function getBreadcrumbs()
    {
        return $this->breadcrumbs;
    }

    public function getGlobals()
    {
        return [
            'app_domain' => getenv('APP_DOMAIN')
        ];
    }

    public function readableTime($ms)
    {
        $ms = abs($ms);
        $ss = floor($ms / 1000);
        $ms = $ms % 1000;
        $mm = floor($ss / 60);
        $ss = $ss % 60;
        $hh = floor($mm / 60);
        $mm = $mm % 60;
        $dd = floor($hh / 24);
        $hh = $hh % 24;
        return sprintf("%dd %02dh %02dm %02ds", $dd, $hh, $mm, $ss, $ms);
    }

    function thousandsFormat($number)
    {
        $abbrevs = [12 => 'T', 9 => 'B', 6 => 'M', 3 => 'K', 0 => ''];

        foreach ($abbrevs as $exponent => $abbrev) {
            if (abs($number) >= pow(10, $exponent)) {
                $display = $number / pow(10, $exponent);
                $decimals = ($exponent >= 3 && round($display) < 100) ? 1 : 0;
                $number = number_format($display, $decimals) . $abbrev;
                break;
            }
        }

        return $number;
    }

    public function sortable(string $text, string $field): string
    {
        $request = $this->requestStack->getCurrentRequest();

        $currentField = strtolower($request->get('sort_field'));
        $currentSort = $currentField == strtolower($field) ? strtolower($request->get('sort_direction')) : false;

        $rewritedParams = [
            'sort_field' => $field,
            'sort_direction' => $currentSort == "asc" ? "desc" : "asc",
        ];

        $route = $request->get('_route');
        $params = $request->get('_route_params') + $request->query->all();
        $a = array_filter($rewritedParams + $params, function ($val) {
            return $val !== null;
        });

        $href = $this->router->generate($route, $a);
        $a = "<a href=\"{$href}\" style=\"color: #000; text-decoration: none;\">";

        if ($currentSort == "asc") {
            $a .= "<i class=\"fa fa-sort-amount-asc\"></i> ";
        } elseif ($currentSort == "desc") {
            $a .= "<i class=\"fa fa-sort-amount-desc\"></i> ";
        } else {
            $a .= "<i class=\"fa fa-sort\" style=\"color: #d2d6de;\"></i> ";
        }

        $a .= $text;

        $a .= "</a>";
        return $a;
    }

    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('truncate', '\app\twig\twig_truncate_filter', array('needs_environment' => true)),
            new \Twig_SimpleFilter('wordwrap', '\app\twig\twig_wordwrap_filter', array('needs_environment' => true)),
        );
    }

    public function getFunctions()
    {
        return array(
            new \Twig_Function('getenv', 'getenv'),
            new \Twig_Function('thousandsFormat', array($this, 'thousandsFormat')),
            new \Twig_Function('readableTime', array($this, 'readableTime')),
            new \Twig_Function('sortable', array($this, 'sortable'), ['is_safe' => ["html"]]),
            new \Twig_Function('breadcrumbs', array($this, 'getBreadcrumbs')),
            new \Twig_Function('breadcrumb', array($this, 'addBreadcrumb')),
        );
    }
}

if (function_exists('mb_get_info')) {
    function twig_truncate_filter(\Twig_Environment $env, $value, $length = 30, $preserve = false, $separator = '...')
    {
        if (mb_strlen($value, $env->getCharset()) > $length) {
            if ($preserve) {
                // If breakpoint is on the last word, return the value without separator.
                if (false === ($breakpoint = mb_strpos($value, ' ', $length, $env->getCharset()))) {
                    return $value;
                }
                $length = $breakpoint;
            }
            return rtrim(mb_substr($value, 0, $length, $env->getCharset())) . $separator;
        }
        return $value;
    }

    function twig_wordwrap_filter(\Twig_Environment $env, $value, $length = 80, $separator = "\n", $preserve = false)
    {
        $sentences = array();
        $previous = mb_regex_encoding();
        mb_regex_encoding($env->getCharset());
        $pieces = mb_split($separator, $value);
        mb_regex_encoding($previous);
        foreach ($pieces as $piece) {
            while (!$preserve && mb_strlen($piece, $env->getCharset()) > $length) {
                $sentences[] = mb_substr($piece, 0, $length, $env->getCharset());
                $piece = mb_substr($piece, $length, 2048, $env->getCharset());
            }
            $sentences[] = $piece;
        }
        return implode($separator, $sentences);
    }
} else {
    function twig_truncate_filter(\Twig_Environment $env, $value, $length = 30, $preserve = false, $separator = '...')
    {
        if (strlen($value) > $length) {
            if ($preserve) {
                if (false !== ($breakpoint = strpos($value, ' ', $length))) {
                    $length = $breakpoint;
                }
            }
            return rtrim(substr($value, 0, $length)) . $separator;
        }
        return $value;
    }

    function twig_wordwrap_filter(\Twig_Environment $env, $value, $length = 80, $separator = "\n", $preserve = false)
    {
        return wordwrap($value, $length, $separator, !$preserve);
    }
}
