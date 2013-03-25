<?php
/**
 * This renderer adds debug information to the bottom of the page.
 * 
 * @package PocketKnife.Plugins
 */
class PocketKnife_RendererFilterDebug implements PocketKnife_RendererFilter
{

  function run($content)
  {
    $content .= <<<EOD
<style type="text/css">
  body { padding-bottom: 120px; }
  #debug { position: fixed; left: 0; bottom: 0; right: 0; height: 80px; overflow-y: scroll; padding: 20px; background: rgba(240,240,240,0.9);}
  #debug, #debug * { font-family: Consolas, monospaced; font-size: 12px; line-height: 16px; }
  .caller { display: inline-block; width: 300px;  }
  .error { color: red; }
  .debug { color: gray; }
</style>
EOD;

    $content .=  '<div id="debug">';
    foreach(PocketKnife::get('logger')->entries as $entry)
      $content .= sprintf('<div class="%s"><span class="caller">%s</span>: %s</div>', $entry[0], $entry[1], $entry[2]);
    $content .=  '</div>';

    return $content;
  }

}

if(php_sapi_name() !== 'cli')
  PocketKnife::get('renderer')->addFilter(new PocketKnife_RendererFilterDebug());
