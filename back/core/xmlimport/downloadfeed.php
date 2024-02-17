<?php

class DownloadFeed
{
    public static function download($url, $path)
    {
        if (!$path) return '';
        $path = dirname(__FILE__, 3) . '/' . $path;
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HEADER, false);
        $content = curl_exec($ch);
        curl_close($ch);

        // надо вынести в плагин
        $content = preg_replace('/(<\/categories>.*?)(<offer>)(.*?<offer>.*?)/s', '\1<offers>\3', $content);
        $content = preg_replace('/<\/offer>(\s|\n|\r){0,}<\/offer>(\s|\n|\r){0,}<\/export>/s', '</offer>\1</offers>\2</export>', $content);

        return ($content && file_put_contents($path, $content)) ? $path : '';
    }
}