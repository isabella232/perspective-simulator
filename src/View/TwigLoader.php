<?php
/**
 * Twig Loader class for simulator.
 *
 * @package    Perspective
 * @subpackage Simulator
 * @author     Squiz Pty Ltd <products@squiz.net>
 * @copyright  2018 Squiz Pty Ltd (ABN 77 084 670 600)
 */

namespace PerspectiveSimulator\View;

/**
 * TwigLoader Class.
 */
class TwigLoader implements \Twig_LoaderInterface
{

    /**
     * Local cache of template logical name cache key.
     *
     * @var array
     */
    protected $cacheKey = [];

    /**
     * The base folder path to find the twig templates.
     *
     * @var string|null
     */
    protected $basePath = null;


    /**
     * Constructor
     *
     * @param string $basePath The base folder path to find the twig templates.
     *
     * @return void
     */
    public function __construct(string $basePath)
    {
        $this->basePath = $basePath;

    }//end __construct()


    /**
     * Gets the cache key to use for the cache for a given template name.
     *
     * @param string $name The name of the template to load.
     *
     * @return string
     */
    public function getCacheKey($name)
    {
        self::validateNameExists($name);
        if (isset($this->cacheKey[$name]) === true) {
            return $this->cacheKey[$name];
        } else {
            $this->cacheKey[$name] = $name.'-'.uniqid();
        }

        return $this->cacheKey[$name];

    }//end getCacheKey()


    /**
     * Validates the logical name exists in the base path.
     *
     * @param string $name The template logical name.
     *
     * @return void
     * @throws \Twig_Error_Loader Thrown when $name is not found.
     */
    private function validateNameExists(string $name)
    {
        if ($this->exists($name) === false) {
            throw new \Twig_Error_Loader(sprintf(_('Source file path can not be found: %s.twig'), $name));
        }

    }//end validateNameExists()


    /**
     * Returns the file path for the template logical name.
     *
     * @param string $name The template logical name.
     *
     * @return string
     */
    private function getSourceFilePath(string $name)
    {
        return $this->basePath.'/'.$name.'.twig';

    }//end getSourceFilePath()


    /**
     * Returns the source context for a given template logical name.
     *
     * @param string $name The template logical name.
     *
     * @return Twig_Source
     * @throws \Twig_Error_Loader When $name is not found.
     */
    public function getSourceContext($name)
    {
        self::validateNameExists($name);

        $sourceFilePath = $this->getSourceFilePath($name);
        return new \Twig_Source(file_get_contents($sourceFilePath), $name);

    }//end getSourceContext()


    /**
     * Returns true if the template is still fresh.
     *
     * @param string  $name The template name.
     * @param integer $time The last modification time of the cached template.
     *
     * @return boolean
     */
    public function isFresh($name, $time)
    {
        self::validateNameExists($name);
        return true;

    }//end isFresh()


    /**
     * Check if we have the source code of a template, given its name.
     *
     * @param string $name The name of the template to check if we can load.
     *
     * @return boolean
     */
    public function exists($name)
    {
        return file_exists($this->getSourceFilePath($name));

    }//end exists()


}//end class
