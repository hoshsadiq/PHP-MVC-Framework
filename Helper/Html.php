<?php

class Html
{
    public function tag($tag, $attribs, $autoclose = false)
    {
        $tag = array('<' . $tag);
        array_walk(
            $attribs,
            function ($item, $key) use (&$tag) {
                $tag[] = $key . '="' . $item . '"';
            }
        );

        $tag[] = ($autoclose ? '/' : '') . '>';
        return implode(' ', $tag);
    }

    /**
     * Link
     *
     * Generates link to a CSS file
     *
     * @param    mixed    stylesheet hrefs or an array
     * @param    string    rel
     * @param    string    type
     * @param    string    title
     * @param    string    media
     * @param    bool    should index_page be added to the css path
     * @return    string
     */
    public function link($href = '', $rel = 'stylesheet', $type = 'text/css', $title = '', $media = '')
    {
        if (is_array($href)) {
            $rel = isset($href['rel']) ? $href['rel'] : $rel;
            $type = isset($href['type']) ? $href['type'] : $type;
            $title = isset($href['title']) ? $href['title'] : $title;
            $media = isset($href['media']) ? $href['media'] : $media;
            $href = isset($href['href']) ? $href['href'] : '';
        }

        $attribs = array(
            'href' => (strpos($href, '://') !== false ? ABSURL . '/' : '') . $href,
            'rel' => $rel,
            'type' => $type
        );

        if ($media != '') {
            $attribs['media'] = $media;
        }
        if ($title != '') {
            $attribs['title'] = $title;
        }

        return $this->tag('link', $attribs, true) . "\n";
    }
}