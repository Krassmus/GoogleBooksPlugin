<?php

class GoogleBooksPlugin extends StudIPPlugin implements FilesystemPlugin {

    static public $apikey = "hphxem1vIMLtcT3iVDdmGKQni8kCaQ33AwB9wwmw2PFbX6Fn8xf1478776474690";
    static public $googlebooksapikey = "AIzaSyAB4Q5gOJgeCFT88B-sqppNn6XeeoXxtIQ";

    public function getFileSelectNavigation()
    {
        $nav = new Navigation(_("GoogleBooks"));
        $nav->setImage(Icon::create("literature", "clickable"));
        return $nav;
    }

    public function getFolder($folder_id = null)
    {
        return null;
    }

    public function getPreparedFile($file_id)
    {
        $url = "https://www.googleapis.com/books/v1/volumes/".$file_id."?key=".urlencode(self::$googlebooksapikey);
        $info = file_get_contents($url);
        $info = studip_utf8decode(json_decode($info, true));
        $download = $info['accessInfo']['pdf']['downloadLink'] ?: $info['accessInfo']['epub']['downloadLink'];
        $tmp_path = $GLOBALS['TMP_PATH']."/".md5(uniqid());
        if (!$download) {
            var_dump($info);
        } else {
            file_put_contents($tmp_path, file_get_contents($download));
            $file = array(
                'name' => $info['volumeInfo']['title'] . ($info['accessInfo']['pdf']['downloadLink'] ? ".pdf" : ".epub"),
                'size' => filesize($tmp_path),
                'type' => $info['accessInfo']['pdf']['downloadLink'] ? "application/pdf" : "application/epub+zip",
                'tmp_path' => $tmp_path,
                'description' => $info['volumeInfo']['publishedDate'].", ".implode(", ", (array) $info['volumeInfo']['authors'])
            );
            return $file;
        }
    }

    public function filesystemConfigurationURL()
    {
        return null;
    }

    public function hasSearch() {
        return true;
    }

    public function getSearchParameters()
    {
        // TODO: Implement getSearchParameters() method.
    }

    public function search($text, $parameters = array())
    {
        /*$request = curl_init("http://api.deutsche-digitale-bibliothek.de?query=".urlencode($text));
        curl_setopt($request, CURLOPT_HTTPHEADER, array(
            'Host: api.deutsche-digitale-bibliothek.de',
            'Authorization: OAuth oauth_consumer_key="'.htmlReady(self::$apikey).'"'
        ));
        curl_setopt($request, CURLOPT_RETURNTRANSFER, 1);
        curl_exec($request);
        curl_close($request);*/

        $free = true;

        $folder = new VirtualFolderType();

        $url = "https://www.googleapis.com/books/v1/volumes?q=".urlencode($text)."&key=".urlencode(self::$googlebooksapikey)."&maxResults=40";

        if ($free) {
            $url .= "&filter=free-ebooks";
        }

        $result = file_get_contents($url);
        if ($result) {
            $result = studip_utf8decode(json_decode($result, true));
            foreach ((array) $result['items'] as $item) {
                $folder->createFile(array(
                    'id' => $item['id'],
                    'name' => $item['volumeInfo']['title'],
                    'description' => $item['volumeInfo']['publishedDate'].", ".implode(", ", (array) $item['volumeInfo']['authors']),
                    'url' => $item['accessInfo']['pdf']['downloadLink']
                    //$item['accessInfo']['epub']['downloadLink']
                    //$item['accessInfo']['webReaderLink']
                ));
            }
        }

        return $folder;
    }
}