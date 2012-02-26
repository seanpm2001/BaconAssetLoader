<?php
namespace BaconAssetLoader\Asset;

use Zend\Module\Manager as ModuleManager,
    Zend\EventManager\EventManager,
    Zend\EventManager\StaticEventManager,
    Zend\EventManager\Event,
    Zend\Mvc\MvcEvent;

/**
 * Asset manager.
 */
class Manager
{
    /**
     * Events other modules can subscribe to.
     *
     * @var EventManager
     */
    protected $events;

    /**
     * Registered asset collections.
     *
     * @var array
     */
    protected $assets = array();

    /**
     * Create a new asset manager.
     * 
     * @return void
     */
    public function __construct()
    {
        $events = StaticEventManager::getInstance();
        $events->attach('Zend\Mvc\Application', 'route', array($this, 'testRequestForAsset'), PHP_INT_MAX);                
    }
    
    /**
     * Trigger event to collect asset information.
     *
     * @return void
     */
    public function collectAssetInformation()
    {
        $this->events()->trigger(__FUNCTION__, $this);
    }
    
    /**
     * Add assets to the manager.
     * 
     * @param  AssetCollection $assets
     * @return void
     */
    public function addAssets(Collection $assets)
    {
        $this->assets[] = $assets;
    }
    
    /**
     * Test the request for an existing asset.
     * 
     * If an asset matches the request, it is passed to the client and
     * the application quits.
     * 
     * @param  MvcEvent $event
     * @return void 
     */
    public function testRequestForAsset(MvcEvent $event)
    {
        $request = $event->getRequest();

        if (!method_exists($request, 'uri')) {
            return;
        }

        if (method_exists($request, 'getBaseUrl')) {
            $baseUrlLength = strlen($request->getBaseUrl() ?: '');
        } else {
            $baseUrlLength = 0;
        }

        $path = substr($request->uri()->getPath(), $baseUrlLength);
        $file = null;
        
        foreach ($this->assets as $collection) {
            if (null !== ($file = $collection->getAsset($path))) {
                break;
            }
        }

        if ($file !== null) {
            $mimeType = MimeDetector::getMimeType($file->getPath());
            
            header('Content-Type: ' . $mimeType);
            $file->streamToClient();
            exit;
        }
    }
    
    /**
     * Compile all collected assets into a path.
     *  
     * @param  string $path
     * @return void
     */
    public function compile($path)
    {
        
    }

    /**
     * Get event manager instance.
     *
     * @return EventCollection
     */
    public function events()
    {
        if ($this->events === null) {
            $this->events = new EventManager(__CLASS__);
        }

        return $this->events;
    }
}
