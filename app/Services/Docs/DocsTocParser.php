<?php

namespace App\Services\Docs;

class DocsTocParser
{
    /**
     * @return list<array{id: string, level: int, text: string}>
     */
    public function parse(string $html): array
    {
        $html = $this->injectHeadingIds($html);
        $items = [];
        $counter = 0;

        if (preg_match_all('/<h([123])(\s[^>]*)?>(.*?)<\/h\1>/is', $html, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $counter++;
                $text = trim(strip_tags($match[3]));
                if ($text === '') {
                    continue;
                }
                $items[] = [
                    'id' => 'heading-'.$counter,
                    'level' => (int) $match[1],
                    'text' => $text,
                ];
            }
        }

        return $items;
    }

    public function injectHeadingIds(string $html): string
    {
        if (trim($html) === '') {
            return $html;
        }

        $counter = 0;

        return preg_replace_callback(
            '/<h([123])(\s[^>]*)?>(.*?)<\/h\1>/is',
            function (array $match) use (&$counter) {
                $counter++;
                $id = 'heading-'.$counter;
                $attrs = $match[2] ?? '';

                if (preg_match('/\bid\s*=/i', $attrs)) {
                    return $match[0];
                }

                return '<h'.$match[1].$attrs.' id="'.$id.'">'.$match[3].'</h'.$match[1].'>';
            },
            $html
        ) ?? $html;
    }
}
