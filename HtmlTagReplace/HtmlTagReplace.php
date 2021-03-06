<?php

namespace HtmlTagReplace;

/**
 * Class HtmlTagReplace
 * @package HtmlTagReplace
 */
class HtmlTagReplace
{
    /**
     * @var string
     */
    private $markup = '';

    /**
     * HtmlTagReplace constructor.
     * @param $markup
     */
    public function __construct($markup)
    {
        $this->markup = $markup;
    }

    /**
     * @return string
     */
    public function getMarkup()
    {
        return $this->markup;
    }

    /**
     * @param string $markup
     */
    public function setMarkup($markup)
    {
        $this->markup = $markup;
    }

    /**
     * @param $search
     * @param $replace
     * @param bool $closingTag
     * @param array $argumentsReplace
     * @param string $arguments
     * @param string $append
     * @param string $prepend
     * @return $this
     */
    public function replaceTag(
        $search,
        $replace,
        $closingTag = false,
        $argumentsReplace = [],
        $arguments = '',
        $append = '',
        $prepend = ''
    )
    {
        $arguments      = !empty($arguments) ? ' ' . $arguments : '';
        $pattern        = '/<' . $search . '(.*?)>';
        $replacement    = '<' . $replace . $arguments . '$1>';

        if ($closingTag) {
            $pattern        .= '(.*?)<\/' . $search . '>';
            $replacement    .= '$2</' . $replace . '>';
        }

        $pattern .= '/is';
        $replacement = $prepend . $replacement . $append;

        if (empty($argumentsReplace)) {
            $this->setMarkup(
                preg_replace(
                    $pattern,
                    $replacement,
                    $this->getMarkup()
                )
            );
        } else {
            $this->setMarkup(
                preg_replace_callback(
                    $pattern,
                    function($matches) use ($replacement, $argumentsReplace) {
                        return $this->replaceArguments(
                            $matches,
                            $replacement,
                            $argumentsReplace
                        );
                    },
                    $this->getMarkup()
                )
            );
        }

        return $this;
    }

    /**
     * @param array $matches
     * @param string $replacement
     * @param array $argumentsReplace
     * @return string
     */
    private function replaceArguments(
        $matches,
        $replacement,
        $argumentsReplace
    )
    {
        $replacement = str_replace(
            ['$1', '$2'],
            '%s',
            $replacement
        );

        if (isset($matches[0])) {
            unset($matches[0]);
        }

        if (isset($matches[1])) {
            $arguments = preg_split('/ (?=\w+=)/', $matches[1]);

            if (is_array($arguments)) {
                $arguments = $this->resetArguments($arguments, $argumentsReplace);

                $matches[1] = join(' ', $arguments);
            }
        }

        return vsprintf($replacement, $matches);
    }

    /**
     * @param array $arguments
     * @param array $argumentsReplace
     * @return array
     */
    private function resetArguments($arguments, $argumentsReplace)
    {
        foreach ($arguments as $key => $argument) {
            $pair = explode('=', $argument);

            if (!isset($argumentsReplace[trim($pair[0])])) {
                continue;
            }

            $newTag = $argumentsReplace[trim($pair[0])];
            if (!is_array($newTag)) {
                if ($newTag === false) {
                    unset($arguments[$key]);
                    continue;
                }
                $pair[0] = $newTag;
                $arguments[$key] = join('=', $pair);
            } else {
                $clones = [];
                foreach ($newTag as $clone) {
                    $pair[0] = $clone;
                    $clones[] = join('=', $pair);
                }
                $arguments[$key] = join(' ', $clones);
            }
        }

        return $arguments;
    }

    /**
     * @return $this
     */
    public function compress()
    {
        $this->setMarkup(
            preg_replace(
                ['/\n/', '/\>[^\S ]+/s', '/[^\S ]+\</s', '/(\s)+/s'],
                [' ', '>', '<', '\\1'],
                $this->getMarkup()
            )
        );

        return $this;
    }
}