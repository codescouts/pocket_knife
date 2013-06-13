<?php

// You rock! Please read the readme.md for more information.


/**
 * This class holds all the messages that are supposed to be displayed
 * to the user.
 * 
 * @since   0.1
 * @package PocketKnife
 */
class PocketKnife_MessagesGerman
{
  public $noIndexFile = 'Hi! Damit deine Seite angezeigt werden kann, musst du eine Datei mit dem Namen <code>index.html</code> in diesem Verzeichnis anlegen: <code>{CONTENT_DIR}</code>';
}


/**
 * This logger just collects all the log messages in a big
 * array. If the PocketKnife_RendererFilterDebug render filter
 * is enabled, it will just read all of the debug messages
 * and display them in a nice format.
 * 
 * @since   0.1
 * @package PocketKnife
 */
class PocketKnife_Logger
{
  public $entries = array();

  public function debug($caller, $string)
  {
    $this->entries[] = array('debug', $caller, $string);
  }

  public function error($caller, $string)
  {
    $this->entries[] = array('error', $caller, $string);
  }
}


/**
 * This class acts as a abstraction for the file system to make
 * sure that we do not have to deal with windows/mac/linux
 * problems anywhere else.
 * 
 * @since   0.1
 * @package PocketKnife
 */
class PocketKnife_FileSystem
{
  function loadDir($dir)
  {
    $baseContentDir  = PocketKnife::get('file_system')->getContentDir();
    $contentFiles    = array();
    $contentFilesRaw = glob($dir.'/*');
    foreach($contentFilesRaw as $contentFile)
    {
      if(is_dir($contentFile))
      {
        $contentFiles = array_merge(self::loadDir($contentFile), $contentFiles);
      }
      else
        $contentFiles[] = str_replace($baseContentDir, '', $contentFile);
    }

    return $contentFiles;
  }

  function getUrlFromFile($file)
  {
    $baseUrl = PocketKnife::get()->getBaseUrl();

    if($file == '/index.html')
      return $baseUrl;
    else
      return $baseUrl.str_replace('.html', '', trim($file));
  }

  function getContentDir()
  {
    return realpath( $_SERVER['DOCUMENT_ROOT'] . '/content' );
  }

  function getTemplateDir()
  {
    return realpath( $_SERVER['DOCUMENT_ROOT'] . '/templates' );
  }

  function findTemplateFile($fileName)
  {
    PocketKnife::get('logger')->debug(__METHOD__, "Trying to find file: {$fileName}");

    $templateDir = $this->getTemplateDir();
    $filePath   = realpath($templateDir . '/' . $fileName);

    return ($filePath) ? $filePath : false;
  }

  function findContentFile($fileName)
  {
    if($fileName == '/')
      $fileName .= 'index.html';

    PocketKnife::get('logger')->debug(__METHOD__, "Trying to find file: {$fileName}");

    $contentDir = $this->getContentDir();
    $filePath   = realpath($contentDir . '/' . $fileName);

    //Try out if the file exists with a .html extension
    if(!$filePath)
      $filePath   = realpath($contentDir . '/' . $fileName . '.html');

    return ($filePath) ? $filePath : false;
  }

  function load($file)
  {
    PocketKnife::get('logger')->debug(__METHOD__, "Loading content of file {$file}");
    return file_get_contents($file);
  }
}


/**
 * @package PocketKnife
 */
class PocketKnife_Renderer
{
  public $filters = array();

  function addFilter(PocketKnife_RendererFilter $filter)
  {
    PocketKnife::get('logger')->debug(__METHOD__, "New filter added: ".get_class($filter));
    $this->filters[] = $filter;
  }

  function runFilters($fileContent)
  {
    foreach($this->filters as $filter)
      $fileContent = $filter->run($fileContent);
    return $fileContent;
  }

  function render($fileName, $directOutput = true, $ignoreFilters = false)
  {
    $fileContent = PocketKnife::get('file_system')->load($fileName);
    
    if(!$ignoreFilters)
      $fileContent = $this->runFilters($fileContent);
    
    if($directOutput)
      echo $fileContent;
    else
      return $fileContent;
  }

  function renderError($message)
  {
    header("Cache-Control: no-cache, must-revalidate");
    header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
    header("HTTP/1.0 500 Internal Server Error");
    $message = $this->runFilters($message);
    die( $message );
  }
}


/**
 * The interface that all the render filters have to implement.
 * 
 * @package PocketKnife
 */
interface PocketKnife_RendererFilter
{
  function run($fileContent);
}


/**
 * This render filter is used to replace all the constants inside the content
 * that is rendered. Currently this filter can replace these patterns:
 *
 * - {CONTENT_DIR}
 *   The full path of the directory, the content is supposed to be in.
 *   
 * - {TEMPLATE_DIR}
 *   The full path of the directory, the template files are supposed to be in.
 * 
 * @package PocketKnife
 */
class PocketKnife_RendererFilter_Constants implements PocketKnife_RendererFilter
{
  function run($fileContent)
  {
    $contentDir   = PocketKnife::get('file_system')->getContentDir();
    $fileContent  = str_replace('{CONTENT_DIR}', $contentDir, $fileContent);

    $templateDir  = PocketKnife::get('file_system')->getTemplateDir();
    $fileContent  = str_replace('{TEMPLATE_DIR}', $templateDir, $fileContent);

    return $fileContent;
  }
}


/**
 * @package PocketKnife
 */
class PocketKnife_RendererFilter_Variables implements PocketKnife_RendererFilter
{
  public static $variables = array();

  function run($fileContent)
  {
    $fileContent = preg_replace_callback(
      '/({{\s*(set|SET)\s*(\w+)\s(.+)\s*}})/',
      create_function(
        '$match',
        'PocketKnife_RendererFilter_Variables::$variables[$match[3]] = $match[4];
         return "";'
      ),
      $fileContent
    );

    $fileContent = preg_replace_callback(
      '/({{\s*(get|GET)\s*(\w+)\s*}})/s',
      create_function(
        '$match',
        '$vars = PocketKnife_RendererFilter_Variables::$variables;
         $key  = $match[3];
         $var  = array_key_exists($key, $vars) ? $vars[$key] : "<!-- Variable \"" . $key . "\" could not be found.-->";
         return $var;'
      ),
      $fileContent
    );

    return $fileContent;
  }
}


/**
 * This render filter handles the actual load commands inside templates.
 * In case you need to load one template into another one, you can use these
 * commands:
 *
 * - {{load filename.html}}
 *   Loads a file from the templates directory and replaces this whole thing
 *   (including the curly braces). The loaded file will run through the
 *   remplate renderer.
 * 
 * @package PocketKnife
 */
class PocketKnife_RendererFilter_Template implements PocketKnife_RendererFilter
{
  function run($fileContent)
  {
    return preg_replace_callback(
      '/({{\s*(LOAD|load)\s*([\w\.]+)\s*}})/',
      create_function(
        '$match',
        '$fileName = PocketKnife::get("file_system")->findTemplateFile($match[3]);
         return PocketKnife::get("renderer")->render($fileName, false, true);'
      ),
      $fileContent
    );
  }
}


/**
 * @package PocketKnife
 */
class PocketKnife_Container
{
  static $things = array();

  static function get($name = 'pocket_knife')
  {
    if(array_key_exists($name, self::$things))
      return self::$things[$name];
    else
      PocketKnife::get('renderer')->renderError('Oh boy, I cannot find <em>'.$name.'</em>. I am very sorry.');
  }

  static function set($name, $thing)
  {
    self::$things[$name] = $thing;
  }
}


/**
 * @package PocketKnife
 */
class PocketKnife extends PocketKnife_Container
{
  protected $routes = array();

  function addRoute($route, $callback)
  {
    $this->routes[$route] = $callback;
  }
  
  function getBaseUrl()
  {
    return "http://" . $_SERVER['HTTP_HOST'];
  }

  function getUrlWithoutParameters()
  {
    PocketKnife::get('logger')->debug(__METHOD__, "Looking for the url.");
    $url                  = $_SERVER['REQUEST_URI'];
    $urlWithoutParameters = preg_replace('/\?.*$/', '', $url);
    return $urlWithoutParameters;
  }

  function registerRendererFilters()
  {
    PocketKnife::get('renderer')->addFilter(new PocketKnife_RendererFilter_Template());
    PocketKnife::get('renderer')->addFilter(new PocketKnife_RendererFilter_Constants());
    PocketKnife::get('renderer')->addFilter(new PocketKnife_RendererFilter_Variables());
  }

  function registerPlugins()
  {
    $pluginFiles = glob(dirname(__FILE__).'/*.plugin.php');
    $pluginFiles = array_merge(glob(dirname(__FILE__).'/plugins/*.php'), $pluginFiles);
    foreach($pluginFiles as $pluginFile)
    {
      PocketKnife::get('logger')->debug(__METHOD__, "Trying to load the plugin in " . $pluginFile);
      include_once($pluginFile);
    }
  }

  static function run()
  { 
    $url              = PocketKnife::get()->getUrlWithoutParameters();
    $file             = PocketKnife::get('file_system')->findContentFile($url);
    $is_static_route  = (in_array($url, array_keys(PocketKnife::get()->routes)));

    if($is_static_route)
    {
      PocketKnife::get('logger')->debug(__METHOD__, "Found static route.");
      return PocketKnife::get()->routes[$url]();
    }
    elseif($file)
    {
      PocketKnife::get('logger')->debug(__METHOD__, "Found: {$file}");
      PocketKnife::get('renderer')->render($file);
    }
    elseif(!$file)
    {
      if($url != '/')
      {
        PocketKnife::get('logger')->error(__METHOD__, "Could not find a file, redirecting.");
        header("Location: /", true, 307);
        die('Redirecting.');
      }
      else
      {
        PocketKnife::get('logger')->error(__METHOD__, "Could not find index file. Display an error.");
        $message =  PocketKnife::get('messages')->noIndexFile;
        PocketKnife::get('renderer')->renderError($message);
      }
    }
  }
}


// MAIN ------------------------------------------------------------------------
PocketKnife::set('logger',        new PocketKnife_Logger());
PocketKnife::set('file_system',   new PocketKnife_FileSystem());
PocketKnife::set('messages',      new PocketKnife_MessagesGerman());
PocketKnife::set('renderer',      new PocketKnife_Renderer());
PocketKnife::set('pocket_knife',  new PocketKnife());


#Register filters and plugins
PocketKnife::get()->registerRendererFilters();
PocketKnife::get()->registerPlugins();

#Configure the plugins
PocketKnife::get('plugin.google_code_prettify_filter')->setTheme('sons-of-obsidian');


// Run
PocketKnife::get()->run();