<?php

include_once( 'extension/translation-tools/scripts/genericfunctions.php' );

class SmartCLI extends eZCLI
{
    protected static $label_color = array("notice" => "green", "warning" => "yellow", "error" => "red");

    protected $scriptname;
    protected $scriptpath;
    protected $dictionary;
    protected $logname;
    protected $logger;
    protected $logmemory = false;
        
	/**
     * Returns a shared instance of the SmartCLI class.
     *
     * return SmartCLI
     */
    static public function instance()
    {
        if ( !isset( $GLOBALS['eZCLIInstance'] ) ||
             !( $GLOBALS['eZCLIInstance'] instanceof SmartCLI ) )
        {
            $GLOBALS['eZCLIInstance'] = new SmartCLI();
        }

        $GLOBALS['eZCLIInstance']->setUseStyles( true ); // enable colors
        
        return $GLOBALS['eZCLIInstance'];
    }

    public function init($params)
    {
        foreach( $params as $name => $value )
        {
            if( array_key_exists( $name, get_class_vars( get_class($this) ) ) )
                $this->$name = $value;
            else
                $this->warning( "\n[WARNING] SmartCLI::init() : property $name does not exists !\n" );
        }

        $this->initLogger();
    }

    public function initLogger($logname = false)
    {
        if( empty($this->logname) )
        {
            if( !empty($logname) )
                $this->logname = $logname;
            elseif( !empty($this->scriptname) )
                $logname = ( substr($this->scriptname,-4) == ".php" )? substr($this->scriptname,0,-4) : $this->scriptname;
            else
                $logname = "cli";
        }

        $this->logger = owLogger::CreateForAdd("var/log/" . $this->logname . "_" . date("d-m-Y") . ".log");
        if( $this->logmemory === true )
            $this->logmemory = owLogger::CreateForAdd("var/log/memory_usage-" . date("d-m-Y") . ".log");
    }

    public function __get($property)
    {
        return $this->$property;
    }
	
    public function setScriptName($scriptname)
    {
        $this->scriptname = $scriptname;
    }

    public function setDictionary($dictionary)
    {
        $this->dictionary = $dictionary;
    }

    /*
     * output message and log it
    */
    public function outputAndLog($label, $msg)
    {
        $this->logger->writeTimedString($msg, $label);

        $this->colorout(self::$label_color[$label], $msg);
    }

	/*
     \return the text \a $text wrapped in the style \a $styleName.
    */
    public function instylize( $instyle, $text, $outstyle )
    {
    	
        $preStyle = $this->style( $outstyle . '-end' ) . $this->style( $instyle );
        $postStyle = $this->style( $instyle . '-end' ) . $this->style( $outstyle );
        return $preStyle . $text . $postStyle;
    }
    
	/*
     * fait un output du message $message avec la coleur $color
     * @param $message string
     * @param $params array('color' => string, 'addEOL' => boolean(true), 'indent' => integer, 'emptyline' => boolean(false) )
    */
	public function styleout( $message, $params = array() )
	{
		if( count($params) > 0 )
		{
			// si definie applique le style $params['color'] à $message 
			if( isset($params['color']) and is_string($params['color']) )
				$message = $this->stylize($params['color'],$message);
				
			// si definie rajoute $params['indent'] tabs devant $message 
			if( isset($params['indent']) and is_integer($params['indent']) and $params['indent'] < 10 )
				$message = $this->indent($message, $params['indent']);
				
			// print du message
			$this->output($message);
				
			// si definie rajoute ou pas EOL à la fin du $message
			// par default la rajoute
			if( !isset($params['addEOL']) or  $params['addEOL'] !== false )
				$addEOL = true;
			else
				$addEOL = false;
				
			// si definie rajoute une ligne vide après la fin du $message
			if( isset($params['emptyline']) and $params['emptyline'] === true )
				$this->emptyline();
		}
		else
			$this->output($message);
	}
    
    /*
     * fait un output du message $message avec la coleur $color
     * @param $color string
     * @param $message string
    */
	public function colorout($color, $message, $indent=0, $emptyline=false)
	{
		// applique le style $color à $message
		$message = $this->stylize($color,$message);
		// rajoute $indent indentations 
		$this->output($this->indent($message, $indent));
		// si $emptyline est à TRUE rajoute une ligne vide
		if($emptyline)
			$this->emptyline();
	}
	
	/*
	 * fais reference à eZCLI::notice
	 * pour SmartCLI les message de type gnotice seront en green
	 */
	public function gnotice( $message = false, $params = array() )
    {
		if ( $this->isQuiet() )
            return;
            
    	$params['color'] = "green";
        $this->styleout($message, $params);
    }
    
	/*
	 * fais reference à eZCLI::notice
	 * pour SmartCLI les message de type dgnotice seront en dark-green
	 */
	public function dgnotice( $message = false, $params = array() )
    {
        if ( $this->isQuiet() )
            return;
		
    	$params['color'] = "dark-green";
        $this->styleout($message, $params);
    }
    
	/*
	 * fais reference à eZCLI::notice
	 * pour SmartCLI les message de type dynotice seront en dark-yellow
	 */
	public function dynotice( $message = false, $params = array() )
    {
        if ( $this->isQuiet() )
            return;
		
    	$params['color'] = "dark-yellow";
        $this->styleout($message, $params);
    }
    
	
    public function beginout($scriptname = false)
    {
        if( $scriptname === false )
            $scriptname = $this->scriptname;
    	$params = array('color' => "green-bg", 'emptyline' => true);
    	$this->styleout("Demarrage du script " . $scriptname, $params);
    }
    
	public function endout($scriptname = false, $color = "green-bg")
    {
        if( $scriptname === false )
            $scriptname = $this->scriptname;
    	$this->emptyline();
    	$params = array('color' => $color);
    	$this->styleout("Fin du script " . $scriptname, $params);
    }
    
	// fonction pour afficher les variable en console
	public function show($var)
	{
		$show = print_r($var,true);
		return $show; 
	}
	
	// fonction pour indenter du text
	public function indent($text, $tabs=1)
	{
		for($i=0;$i<$tabs;$i++)
		{
			$text = "	" . $text;
		}
		
		return $text; 
	}
	
	public function color($color,$string)
	{
		$colored = $this->stylize($color,$string);
		
		return $colored;
	}
	
	public function incolor($incolor, $string, $outcolor)
	{
		$colored = $this->instylize($incolor, $string, $outcolor);
		
		return $colored;
	}
	
	public function emptyline()
	{
		$this->output("\n");
	}

    public function showmem($msg = false, $color = false)
    {
        if( $color === false )
            $color = "magenta";

        if( !empty($msg) )
        {
            $this->colorout($color, $msg);
            $this->doLogMemoryIf($msg);
        }

        $memory_usage = "memory usage : " . show(memory_get_usage_hr());

        $this->colorout( $color, $memory_usage );        
        $this->doLogMemoryIf($memory_usage);
    }
	
    protected function doLogMemoryIf($msg)
    {
        if( $this->logmemory instanceof owLogger )
            $this->logmemory->writeTimedString($msg, "notice");
    }


} // END of CLASS

?>
