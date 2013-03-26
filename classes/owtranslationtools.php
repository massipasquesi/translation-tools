<?php

include_once( 'extension/translation-tools/scripts/genericfunctions.php' );

class owTranslationTools
{
	/*
	 * CONSTANTES
	 */
	
	const LOGFILE = "var/log/owTranslationTools";
	const INIPATH = "extension/translation-tools/settings/";
	const INIFILE = "owtranslationtools.ini.append.php";
    const MINSCRIPTPAGINATION = 10;
	
	/*
	 * PROPRIÉTÉS
	 */
	
	// STATIQUES
	private static $definition = array();
	private static $properties_list;
	private static $parameters_per_function;
	private static $inidata_list;
	
	protected static $inidata = array();
	protected static $fullinidata = array();
	protected static $ini = null;
	protected static $logger;

    protected static $true_array = array("true", "yes", "1", "enabled", "on", true, 1);
    protected static $false_array = array("false", "no", "0", "disabled", "off", false, 0);

    protected static $version_status_array = array( 
                                                'DRAFT'         => eZContentObjectVersion::STATUS_DRAFT,
                                                'PENDING'       => eZContentObjectVersion::STATUS_PENDING,
                                                'REJECTED'      => eZContentObjectVersion::STATUS_REJECTED,
                                                'ARCHIVED'      => eZContentObjectVersion::STATUS_ARCHIVED,
                                                'INTERNAL_DRAFT'=> eZContentObjectVersion::STATUS_INTERNAL_DRAFT );
	
	// D'INSTANCE
	protected $properties = array();
	
	/*
	 * MÉTHODES STATIQUES
	 */
	
	/*
	 * instancie un nouveau objet de cette class
	 * @return object[owTranslationTools] OR false
	 */
	public static function instance($properties = array())
	{
		self::initLogger();
		self::definition();
		
        $siteaccess = false;
			
		if(count($properties) > 0)
		{
			$object = new owTranslationTools();
			$object->init($properties);
            if( isset($object->properties['siteaccess']) )
                $siteaccess = $object->properties['siteaccess'];
		}

		if( !self::getFullIniData($siteaccess) )
			return false;

        if( isset($object) )
            return $object;

		return new owTranslationTools();
	}
	
	public static function definition()
	{
		// inidata_list ***
        // exemple :  (	'AdminID'	=> array( 'block' => "Users", 'var' => "AdminID" ),
		$inidata_list = array(	'AdminID'	=> array( 'block' => "Users", 'var' => "AdminID" ),
                                'do_update_initial_language' => array( 'block' => "Update", 'var' => "do_update_initial_language" ),
                                'pagination' => array( 'block' => "Scripts", 'var' => "pagination" ),
                            ); 
		self::$inidata_list = $inidata_list;
		
		
		// properties_list ***
        // exemple : 'property-name',
		$properties_list = array();
		self::$properties_list = $properties_list;
		
		
		// parameters_per_function ***
        // exemple : 'function-name' => array( 'property-name' => true/false(required?)  ),
		$parameters_per_function = array();
		self::$parameters_per_function = $parameters_per_function;
										
										
		// tableau complet *******
		$definition = array('properties_list' => $properties_list,
							'parameters_per_function' => $parameters_per_function,
							'inidata_list' => $inidata_list
							);
		self::$definition = $definition;
										
		return $definition;
	}
	
	
	
	public static function initLogger()
	{
		if( !is_object(self::$logger) )
			self::$logger = owLogger::CreateForAdd(self::LOGFILE . date("d-m-Y") . ".log");
	}
	
	
	/*
	 * retourne la valeur d'une propriété statique si elle existe
	 * @param $name string
	 * @return self::$name mixed or null
	 */
	public static function getStaticProperty($name)
	{
		if(isset(self::$$name))
			return self::$$name;
	}
	
	
	public static function lastLogContent()
	{
		self::initLogger();
		return self::$logger->getLogContentFromCurrentStartTime();
	}
	
	
	public static function initIni()
	{
		// init du fichier de log
		self::initLogger();
		
		// load definition si ce n'est pas dèjà fait
		if(count(self::$definition) == 0)
			self::definition();
		
		if( is_null(self::$ini) )
		{
			// verifie si self::INIFILE existe
		   	$initest = eZINI::exists(self::INIFILE, self::INIPATH);
			if($initest)
			{
				self::$ini = eZINI::instance(self::INIFILE);
				return self::$ini;
			}
			else
			{
				$error = self::INIFILE . " NON TROUVÉ in " . self::INIPATH;
				self::$logger->writeTimedString("Erreur initIni() : " . $error);
				return false;
			}
		}
		
		return self::$ini;
		
	}
	
	public static function getFullIniData( $siteaccess = false )
	{
		// initialise l'instance eZINI
		if( self::initIni() === false )
			return false;
			
		// recupere toutes les variables du fichier ini
		self::$fullinidata = self::$ini->BlockValues; 

		return self::$fullinidata;
		
	}
	
	

    public static function getFiltersForFunction($function_name)
    {
        self::getFullIniData();
        if( !isset(self::$fullinidata['Filters'][$function_name]) )
            return array();
        else
            $filters = self::$fullinidata['Filters'][$function_name];
    
        // les filtres doivent être en groupe de trois, sinon on n'applique aucun filtres
        if( (count($filters) % 3) != 0 )
            return array();
            
        return array_chunk( $filters, 3 );

    }


    public static function fetchLastVersionListByVersion( $version, $limit = 50, $asObject = true )
    {
        $conds = array( "version" => $version );
        $sorts = array( 'contentobject_id' => 'asc' );
        $limit = array( 'offset' => 0, 'length' => $limit );

        $ret = eZPersistentObject::fetchObjectList( eZContentObjectVersion::definition(),
                                                        null, $conds,
                                                        $sorts, $limit,
                                                        $asObject );
        
        return isset( $ret[0] ) ? $ret : false;
    }

    public static function countLastVersionsByVersion( $version )
    {
        $conds = array( "version" => $version );
        $custom = array( array( 'operation' => 'count( id )',
                                     'name' => 'count' ) );

        $ret = eZPersistentObject::fetchObjectList( eZContentObjectVersion::definition(),
                                                        array(), $conds,
                                                        null, null,
                                                        false, false, 
                                                        $custom );

        return isset( $ret[0] ) ? (int)$ret[0]['count'] : false;
    }

    // retrieve object with translation of lang_ID
	public static function objectIDListByLangID($lang_id)
	{
        $filters = self::getFiltersForFunction("objectIDListByLangID");

        $db = eZDB::instance();

        $whereSQL = "language_mask & $lang_id > 0";

        if( count($filters) > 0 )
        {
            foreach( $filters as $filter )
            {
                if( is_array($filter) && count($filter) === 3 )
                    $whereSQL.= " AND " . implode(" ", $filter);
            }
        }
        $result = $db->arrayQuery( "SELECT id FROM ezcontentobject WHERE $whereSQL ORDER BY id" );

        if ( count($result) <= 0 )
            return array();

        foreach( $result as $element )
        {
            $lang_objectID_list[] = $element['id'];
        }

        return $lang_objectID_list;
	}


    // retrieve subtree list of objects with translation of lang_ID
	public static function subtreeObjectsIDListByLocale($language, $nodeID)
	{
        $params = array('language' => $language);
        evd( eZContentObjectTreeNode::fetch($nodeID, "eng-GB") );
        evd( eZContentObjectTreeNode::fetchByContentObjectID($nodeID) );
        eZContentObjectTreeNode::subtreeByNodeID( $params, $nodeID );

        if ( !is_numeric( $nodeID ) and !is_array( $nodeID ) )
        {
            return null;
        }
        
        if ( $language )
        {
            if ( !is_array( $language ) )
            {
             $language = array( $language );
            }
             // This call must occur after eZContentObjectTreeNode::createPathConditionAndNotEqParentSQLStrings,
             // because the parent node may not exist in Language
             eZContentLanguage::setPrioritizedLanguages( $language );
         }
        
         $languageFilter = ' AND ' . eZContentLanguage::languagesSQLFilter( 'ezcontentobject' );
         
         if ( $language )
         {
             eZContentLanguage::clearPrioritizedLanguages();
         }
         
        evd($languageFilter);

         // Determine whether we should show invisible nodes.
         $showInvisibleNodesCond = eZContentObjectTreeNode::createShowInvisibleSQLString( true );
         
         $query = "SELECT DISTINCT
                       id,
                    FROM
                       ezcontentobject,
                    WHERE
                       $languageFilter
                   ";
         
         $query .= " ORDER BY id";
         
         $db = eZDB::instance();
         
         $server = count( $sqlPermissionChecking['temp_tables'] ) > 0 ? eZDBInterface::SERVER_SLAVE : false;
         
         $nodeListArray = $db->arrayQuery( $query, array( 'offset' => $offset,
                                                          'limit'  => $limit ),
                                                   $server );
         
         if ( $asObject )
         {
             $retNodeList = eZContentObjectTreeNode::makeObjectsArray( $nodeListArray );
             if ( $loadDataMap === true )
                 eZContentObject::fillNodeListAttributes( $retNodeList );
             else if ( $loadDataMap && is_numeric( $loadDataMap ) && $loadDataMap >= count( $retNodeList ) )
                 eZContentObject::fillNodeListAttributes( $retNodeList );
         }
         else
         {
             $retNodeList = $nodeListArray;
         }
         
         // cleanup temp tables
         $db->dropTempTableList( $sqlPermissionChecking['temp_tables'] );
         
         return $retNodeList;

    }


    //update initial language
    // @newInitalLang string like 'eng-GB'
    // @object object eZContentObject
    public static function updateObjectInitialLanguage($newInitialLang, $object)
    {
        if( !self::checkDoUpdateInitialLanguage() )
            return false;

        $language = eZContentLanguage::fetchByLocale($newInitialLang);
        if ( $language )
        {
            $object->setAttribute( 'initial_language_id', $language->ID );
            $object->setAlwaysAvailableLanguageID( $language->ID );
        }
        else
            return false;

        return $object->initialLanguageCode();
    }
	
    public static function checkDoUpdateInitialLanguage()
    {
        self::getFullIniData();
        $do_update_initial_language = self::$fullinidata['Update']['do_update_initial_language'];

        if( !isset($do_update_initial_language) ||
          !in_array( $do_update_initial_language, self::$true_array ) )
            return false;

        return true;
    }

    public static function getScriptPagination()
    {
        self::getFullIniData();
        $pagination = self::$fullinidata['Scripts']['pagination'];

        if( !isset($pagination) || in_array( $pagination, self::$false_array ) ||
          !is_numeric($pagination) || $pagination < 0 )
            return 0;

       if( $pagination < self::MINSCRIPTPAGINATION )
           return self::MINSCRIPTPAGINATION;

        return $pagination;
    }

    public static function getVersionStatusFromString( $version_status_array )
    {
        if( !is_array( $version_status_array ) )
            $version_status_array = array($version_status_array);

        foreach( $version_status_array as $key => $version_status )
        {
            if( is_string( $version_status ) )
            {
                if( stripos($version_status, "STATUS_") === 0 )
                {
                    $start = ( strlen($version_status) - strlen("STATUS_") ) * -1;
                    $version_status = strtoupper( $version_status, $start );
                }

                $version_status= strtoupper($version_status);
                $version_status_array[$key] = self::$version_status_array[$version_status];
            }
        }

        return $version_status_array;
    }

    public static function updateAlwaysAvailable(  $objectID, $newAlwaysAvailable = true )
    {
        if ( eZOperationHandler::operationIsAvailable( 'content_updatealwaysavailable' ) )
        {
            $operationResult = eZOperationHandler::execute( 'content', 'updatealwaysavailable',
                                                            array( 'object_id'            => $objectID,
                                                                   'new_always_available' => $newAlwaysAvailable,
                                                                   ) );
        }
        else
        {
            eZContentOperationCollection::updateAlwaysAvailable( $objectID, $newAlwaysAvailable );
        }

        eZContentCacheManager::clearContentCache($objectID);

    }

    public static function getObjectIDFromNodeID($node_id)
    {
        $query = "SELECT contentobject_id FROM ezcontentobject_tree WHERE node_id=$node_id";
         
        $db = eZDB::instance();

        $response = $db->arrayQuery( $query );

        if( count($response) <= 0 )
            return false;

        return $response[0]['contentobject_id'];        
    }

    public static function getLocaleByObjectID($object_id, $priorized_locale = false)
    {
        $query = "SELECT content_translation from ezcontentobject_name where contentobject_id=$object_id";

        $db = eZDB::instance();

        $response = $db->arrayQuery( $query );

        if( count($response) <= 0 )
            return false;

        if( count($response) == 1 )
            return $response[0]['content_translation'];        

        if( count($response) > 1 )
        {
            foreach( $response as $row )
            {
                if( $row['content_translation'] == $priorized_locale )
                    return $priorized_locale;
            }

            $last_index = count($response) - 1;
            return $response[$last_index]['content_translation'];
        }


    }

    public static function copyObjectAttributes($object)
    {
        // get datamap
        $datamap = $object->dataMap();
        //evd($object->contentObjectAttributes());
        $current_attributes = array();

        foreach( $datamap as $attr )
        {
            $insertAttribute = true;
            $dataString = $attr->toString();

            switch ( $datatypeString = $attr->attribute( 'data_type_string' ) )
            {
                case 'ezimage':
                case 'ezbinaryfile':
                case 'ezmedia':
                {
                    if( empty($dataString) or $dataString == "|" )
                        $insertAttribute = false;
                    elseif( $datatypeString != 'ezimage' )
                    {
                        $dataString = $attr->content();
                    }

                    break;
                }
                default:
            }

            if( $insertAttribute )
                $current_attributes[$attr->ContentClassAttributeIdentifier] = $dataString;
        }

        return $current_attributes;
    }

    /**
        [ MASSI 22/03/2013 ]
        This method is a copy of eZContentFunction::updateAndPublishObject
        with some commented change :
         - argument $from_lang added  
    **/
    public static function publishNewTraductionFromLanguage( eZContentObject $object, array $params, $from_lang )
    {

        if ( !array_key_exists( 'attributes', $params ) and !is_array( $params['attributes'] ) and count( $params['attributes'] ) > 0 )
        {
            eZDebug::writeError( 'No attributes specified for object' . $object->attribute( 'id' ), __METHOD__ );
            return false;
        }

        $storageDir   = '';
        $languageCode = false;
        $mustStore    = false;

        if ( array_key_exists( 'remote_id', $params ) )
        {
            $object->setAttribute( 'remote_id', $params['remote_id'] );
            $mustStore = true;
        }

        if ( array_key_exists( 'section_id', $params ) )
        {
            $object->setAttribute( 'section_id', $params['section_id'] );
            $mustStore = true;
        }

        if ( $mustStore )
            $object->store();

        if ( array_key_exists( 'storage_dir', $params ) )
            $storageDir = $params['storage_dir'];

        if ( array_key_exists( 'language', $params ) and $params['language'] != false )
        {
            $languageCode = $params['language'];
        }
        else
        {
            $initialLanguageID = $object->attribute( 'initial_language_id' );
            $language = eZContentLanguage::fetch( $initialLanguageID );
            $languageCode = $language->attribute( 'locale' );
        }

        $db = eZDB::instance();
        $db->begin();

        /**
            [ MASSI 22/03/2013 ]
        **/

        //$newVersion = $object->createNewVersion( false, true, $languageCode );
        $newVersion = $object->createNewVersionIn( $languageCode, $from_lang, false, true, eZContentObjectVersion::STATUS_INTERNAL_DRAFT );


        if ( !$newVersion instanceof eZContentObjectVersion )
        {
            eZDebug::writeError( 'Unable to create a new version for object ' . $object->attribute( 'id' ), __METHOD__ );

            $db->rollback();

            return false;
        }

        $newVersion->setAttribute( 'modified', time() );
        $newVersion->store();

        $attributeList = $newVersion->attribute( 'contentobject_attributes' );

        $attributesData = $params['attributes'];

        foreach( $attributeList as $attribute )
        {
            $fromString = true;
            $attributeIdentifier = $attribute->attribute( 'contentclass_attribute_identifier' );

            if ( array_key_exists( $attributeIdentifier, $attributesData ) )
            {
                $dataString = $attributesData[$attributeIdentifier];
                switch ( $datatypeString = $attribute->attribute( 'data_type_string' ) )
                {
                    /**
                        [ MASSI 22/03/2013 ]
                    **/
                    /*
                    case 'ezimage':
                    case 'ezbinaryfile':
                    case 'ezmedia':
                    {
                        $dataString = $storageDir . $dataString;
                        break;
                    }
                    */
                    case 'ezimage':
                    {
                        $dataString = $storageDir . $dataString;
                        break;
                    }
                    case 'ezbinaryfile':
                    case 'ezmedia':
                    {
                        if ( is_object( $dataString ) )
                        {
                            $dataString->setAttribute( "contentobject_attribute_id", $attribute->attribute('id') );
                            $dataString->setAttribute( "version", $newVersion->attribute('version') );
                            $dataString->store();
                            $attribute->setContent($dataString);

                            $fromString = false;
                        }
                        else
                            $dataString = $storageDir . $dataString;

                        break;
                    }
                    default:
                }

                //$attribute->fromString( $dataString );
                if( $fromString )
                    $attribute->fromString( $dataString );

                $attribute->store();
            }
        }

        $db->commit();


        $operationResult = eZOperationHandler::execute( 'content', 'publish', array( 'object_id' => $object->attribute( 'id' ),
                                                                                         'version' => $newVersion->attribute( 'version' ) ) );

        if( $operationResult['status'] == eZModuleOperationInfo::STATUS_CONTINUE )
            return true;

        return false;
    }
	
	/*
	 * CONSTRUCTEUR
	 * on ne peut pas faire un new owTranslationTools() en dehors de la class ou des classes qui l'etende
	 * on est obligé de passer par la mèthode statique owTranslationTools::instance()
	*/
	protected function owTranslationTools()
	{
		
	}
	
	
	/*
	 * MÉTHODES D'INSTANCE
	 */
	
	protected function init($properties)
	{		
		foreach( $properties as $name => $value )
		{
			if(in_array($name,self::$properties_list))
			{
				$this->properties[$name] = $value;
			}
			else
			{	
				$error = "Propriété " . $name . " non trouvé parmi les propriétés d'un objet " . get_class();
				self::$logger->writeTimedString($error);
			}
		}
	}
	
	public function getProperties()
	{
		return $this->properties;
	}
	
	public function setProperties($properties)
	{
		$this->init($properties);
	}
	
	public function getProperty($name)
	{
		if( isset($this->properties[$name]) )
			return $this->properties[$name];
		else
			return null;
	}
	
	public function setProperty($name,$value)
	{
		if(in_array($name,self::$properties_list))
		{
			$this->properties[$name] = $value;
		}
		else
		{	
			$error = "Propriété " . $name . " non trouvé parmi les propriétés d'un objet " . get_class();
			self::$logger->writeTimedString($error);
		}
	}
	
	
	protected function verifyArgsForFunction($function_name, $args)
	{
		// load definition si ce n'est pas dèjà fait
		if(count(self::$definition) == 0)
			self::definition();

		// parametres necessaires à la function
		$parameters = self::$parameters_per_function[$function_name];

		// verifie si on a passé le parametre $args à la function $function_name
		if(is_null($args)) // si non
		{
			// verifie properties
			foreach($parameters as $name => $required)
			{
				if( !isset($this->properties[$name]) and $required )
				{
					$error = $function_name . " : " . $name . " n'est pas reinsegné !";
					self::$logger->writeTimedString($error);
					return false;
				}
			}
		}
		else // si oui
		{
			// verifie args
			foreach($parameters as $name => $required)
			{
				if( !isset($args[$name]) and $required )
				{
					if(!isset($this->properties[$name]))
					{
						$error = $function_name . " : " . $name . " n'est pas reinsegné !";
						self::$logger->writeTimedString($error);
						return false;
					}
				}
				elseif( isset($args[$name]) )
				{
					// set property
					$this->properties[$name] = $args[$name];
				}
			}
		}
		
		return true;
		
	}
	

	
	
} // fin de class

?>
