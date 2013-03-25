<?php

/**
 * 
 * @package PocketKnife.Plugins
 */
class SitemapXmlPlugin
{


  function run()
  {
    $fileSystem   = PocketKnife::get('file_system');
    $contentDir   = $fileSystem->getContentDir();
    $contentFiles = $fileSystem->loadDir($contentDir);
    $contentUrls  = array_map(array($fileSystem,'getUrlFromFile'), $contentFiles);

    // OUTPUT
    header("Cache-Control: no-cache, must-revalidate");
    header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
    header ("Content-Type:text/xml");
    echo '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">';

    foreach($contentUrls as $contentUrl)
      printf('<url><loc>%s</loc></url>', $contentUrl);

    echo '</urlset>';
  }

}


PocketKnife::set('plugin.sitemap_xml',  new SitemapXmlPlugin());
PocketKnife::get('pocket_knife')->addRoute('/sitemap.xml',
                          array(PocketKnife::get('plugin.sitemap_xml'),'run'));