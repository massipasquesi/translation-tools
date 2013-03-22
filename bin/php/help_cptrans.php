<?php
/*
AIDE pour copy_translation.php :
script pour le transfert de contenus d'un langue à un autre 

@author Pasquesi Massimiliano <massimiliano.pasquesi@openwide.fr>
*/

require 'autoload.php';
include_once( 'extension/translation-tools/scripts/genericfunctions.php' );

// init CLI
$cli = SmartCLI::instance();
$cli->setIsQuiet(false);

$cli->init( array(  'scriptname' => "help_cptrans.php",
                    'scriptpath' => "extension/translation-tools/bin/php/",
                    'dictionary' => array(  "list-locales", 
                                            "list-colors", 
                                            "count-objects",
                                            "list-globals",
                                            "list-version-status",
                                            "get-fullinidata",
                                            "fetch" )
                ) );

// init script
$script = eZScript::instance( array( 'description' => ('HELP for cptrans'),
                                     'use-session' => false,
                                     'use-modules' => true,
                                     'use-extensions' => true,
                                     'debug-output' => true,
                                     'debug-message' =>true) );

$script->startup();
$script->initialize();

// get options 
$options = $script->getOptions();
$arguments = $options['arguments'];

// verify arguments
if ( count($arguments) < 1 )
{
    script_usage($cli, $script);
}


// debut du script
$action= $arguments[0];
switch($action)
{
    case "list-locales" :
        $localeList = eZLocale::localeList(true, false);
        $cust_localeList = array();
        foreach($localeList as $locale)
        {
            $cust_localeList[$locale->localeCode()] = $locale->internationalLanguageName();
        }
        
        $cli->colorout( "green-bg", "list of availables languages locales" );
        $cli->gnotice( show($cust_localeList) );
        break;

    case "list-colors" :
        $cli->output( show( $cli->terminalStyles() ) );
        break;

    case "count-objects" :
        $language_list = eZContentLanguage::fetchLocaleList();
        foreach( $language_list as $lang_code )
        {
            $langObj = eZContentLanguage::fetchByLocale($lang_code);
            if( $langObj instanceof eZContentLanguage )
               $cli->gnotice( "$lang_code : " .  $langObj->objectCount() . " objects");
            else
                $cli->error( "$lang_code : eZContentLanguage::fetchByLocale return false" );
        }
        break;

    case "list-globals" :
        if( isset($arguments[1]) )
            $cli->notice( show($GLOBALS[$arguments[1]]) );
        else
            $cli->notice( show($GLOBALS) );
        break;

    case "list-version-status" :
        $cli->notice( show( owTranslationTools::getStaticProperty("version_status_array") ) );
        break;

    case "get-fullinidata" :
        $cli->gnotice( show(owTranslationTools::getFullIniData()) );
        break;

    case "fetch" :
        //$cli->notice( show($arguments) );
        if( count($arguments) < 3 )
        {
            $cli->warning("You have to specifie à type of fetch (node, object)");
            $cli->warning("and an ID (node_id, object_id)");
            break;
        }
        $fetch_type = $arguments[1];
        $fetch_id = $arguments[2];
        switch($fetch_type)
        {
            case "node" :
                $cli->gnotice( show( eZContentObjectTreeNode::fetch($fetch_id) ) );
                break;
            case "object" :
                $cli->gnotice( show( eZContentObject::fetch($fetch_id) ) );
                break;
            default :
                $cli->warning("Argument 1 must be a valid fetch_type (node, object)" );
                break;
        }
        break;

    default :
        $cli->error("action '$action' does not exist ! ");
        script_usage($cli, $script);
        break;
}


// fin du script
$script->shutdown();

?>
