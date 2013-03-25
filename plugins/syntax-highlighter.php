<?php


/**
 * https://code.google.com/p/google-code-prettify/
 * @package PocketKnife.Plugins
 */
class GoogleCodePrettifyFilter implements PocketKnife_RendererFilter
{

  protected $theme = 'default';

  function setTheme($theme)
  {
    $this->theme = $theme;
  }

  function replaceCodePart($fileContent)
  {
    $filgeContent = preg_replace_callback(
      '/\{\{code\}\}(.*)\{\{\/code\}\}/s',
      create_function(
        '$match',
        'return "{{code}}".htmlentities(trim($match[1]))."{{/code}}";'
      ),
      $fileContent
    );

    $fileContent = str_replace('{{code}}', '<pre class="prettyprint">', $fileContent);
    $fileContent = str_replace('{{/code}}', '</pre>', $fileContent);

    $fileContent = str_replace('<pre><code>', '<pre class="prettyprint">', $fileContent);
    $fileContent = str_replace('</code></pre>', '</pre>', $fileContent);
    
    return $fileContent;
  }

  function addGoogleScript($fileContent)
  {
    $googleScriptCode = sprintf('<script type="text/javascript" src="https://google-code-prettify.googlecode.com/svn/loader/run_prettify.js?autoload=true&amp;skin=%s"></script>', $this->theme);
    if(strpos($fileContent, '</body>') !== false)
      $fileContent = str_replace('</body>', $googleScriptCode.PHP_EOL.'</body>', $fileContent);
    else
      $fileContent .= $googleScriptCode;
    return $fileContent;
  }

  function run($fileContent)
  {
    $fileContent = $this->replaceCodePart($fileContent);
    $fileContent = $this->addGoogleScript($fileContent);
    return $fileContent;
  }
}

PocketKnife::set('plugin.google_code_prettify_filter', new GoogleCodePrettifyFilter());
PocketKnife::get('renderer')->addFilter(PocketKnife::get('plugin.google_code_prettify_filter'));