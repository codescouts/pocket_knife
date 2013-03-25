<?php
/**
 * 
 * @package PocketKnife.Plugins
 */
class PocketKnife_RendererFilterNavigation implements PocketKnife_RendererFilter
{

  const placeholder = '{{navigation}}';

  function getAllUrls()
  {
    $fileSystem   = PocketKnife::get('file_system');
    $contentDir   = $fileSystem->getContentDir();
    $contentFiles = $fileSystem->loadDir($contentDir);
    $contentUrls  = array_map(array($fileSystem,'getUrlFromFile'), $contentFiles);
    sort($contentUrls);

    return $contentUrls;
  }

  function run($fileContent)
  {
    $hasNavigation = (strpos($fileContent, self::placeholder) !== false);

    if($hasNavigation)
    {
      $allUrls = $this->getAllUrls();

      $navigationHtml = '<ul id="navigation">';

      foreach ($allUrls as $url)
      {
        if($url == PocketKnife::get()->getBaseUrl())
        {
          $name = 'Home';
        }
        else
        {
          $urlParts = explode('/', $url);
          $name     = array_pop($urlParts);
          $name     = str_replace(array('_'), array(' '), $name);
          $name     = ucwords($name);
          $name     = trim($name);
        }

        $navigationHtml .= sprintf('<li><a href="%s">%s</a></li>',$url, $name);
      }
      $navigationHtml .= '</ul>';
      $fileContent    = str_replace(self::placeholder, $navigationHtml, $fileContent);
    }

    return $fileContent;
  }
}


PocketKnife::get('renderer')->addFilter(new PocketKnife_RendererFilterNavigation());